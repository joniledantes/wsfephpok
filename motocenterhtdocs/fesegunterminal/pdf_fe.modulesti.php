<?php
/* Copyright (C) 2004-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012      	Christophe Battarel <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2016       Catriel Rios       <catriel_r@hotmail.com>

 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/facture/doc/pdf_fe.modules.php
 *	\ingroup    facture
 *	\brief      File of class to generate customers Argentina Electronic invoices
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/wsfephp/class/wsfe_db.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/doc/qrcode.php';

//use QrCode\QrCode;
/**
 *	Class to manage PDF invoice template Crabe
 */
class pdf_fe extends ModelePDFFactures
{
    var $db;
    var $name;
    var $description;
    var $type;
    var $phpmin = array(4,3,0); // Minimum version of PHP required by module
    var $version = 'dolibarr';
    var $page_largeur;
    var $page_hauteur;
    var $format;
  	var $marge_gauche;
  	var	$marge_droite;
  	var	$marge_haute;
  	var	$marge_basse;
  	var $emetteur;	// Objet societe qui emet

	/**
	 * @var bool Situation invoice type
	 */
	public $situationinvoice;

	/**
	 * @var float X position for the situation progress column
	 */
	public $posxprogress;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");

		$this->db = $db;
		$this->name = "Factura Electronica";
		$this->description = "Modelo para Factura Electronica";

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;                 // Affiche mode reglement
		$this->option_condreg = 1;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_escompte = 1;                // Affiche si il y a eu escompte
		$this->option_credit_note = 1;             // Support credit notes
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 1;		   // Support add of a watermark on drafts

		$this->franchise=!$mysoc->tva_assuj;
         
        // Get source company
		$this->emetteur=$mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default, if was not defined

		// Define position of columns
		$this->posxdesc=$this->marge_gauche+1;
		if($conf->global->PRODUCT_USE_UNITS)
		{
			$this->posxtva=99;
			$this->posxup=114;
			$this->posxqty=133;
			$this->posxunit=150;
		}
		else
		{
			$this->posxtva=112;
			$this->posxup=126;
			$this->posxqty=145;
		}
		$this->posxdiscount=162;
		$this->posxprogress=174; // Only displayed for situation invoices
		$this->postotalht=174;
		if (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT)) $this->posxtva=$this->posxup;
		$this->posxpicture=$this->posxtva - (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH)?20:$conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH);	// width of images
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->posxpicture-=20;
			$this->posxtva-=20;
			$this->posxup-=20;
			$this->posxqty-=20;
			$this->posxdiscount-=20;
			$this->postotalht-=20;
		}

		$this->tva=array();
		$this->localtax1=array();
		$this->localtax2=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
		$this->situationinvoice=False;

		//WSFE
		$this->copias=array(
			1=>'Original',
			2=>'Duplicado',
			3=>'Triplicado',
			4=>'Cutriplicado');

		$this->posxfatipo=95;
		$this->tipos_fact=array(
			1=>'A',
			2=>'A',
			3=>'A',
			4=>'A',
			5=>'A',
			39=>'A',
			60=>'A',
			63=>'A',
		    6=>'B',
			7=>'B',
			8=>'B',
			9=>'B',
			10=>'B',
			40=>'B',
			61=>'B',
			64=>'B',
            11=>'C',
			12=>'C',
			13=>'C',
			15=>'C',
	        51=>'M',
			52=>'M',
			53=>'M',
			54=>'M',
	        19=>'E',
			20=>'E',
			21=>'E',
	        91=>'R');

		$rows = array_map('str_getcsv', file(DOL_DOCUMENT_ROOT.'/wsfephp/plantillas/factura.csv'));
		$header =array_shift($rows);
		unset($header[0]);
    	$csv = array();
		foreach ($rows as $row) {
          $nom=$row[0];
			unset($row[0]);
			$this->posfac[$nom] =array_combine($header, $row);
		}

	}



	/**
     *  Function to build pdf onto disk
     *
     *  @param		Object		$object				Object to generate
     *  @param		Translate	$outputlangs		Lang output object
     *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int			$hidedetails		Do not show line details
     *  @param		int			$hidedesc			Do not show desc
     *  @param		int			$hideref			Do not show ref
     *  @return     int         	    			1=OK, 0=KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf,$mysoc,$db,$hookmanager;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");

		//wsfe
		if ($object->statut!=0 and  $object->array_options['options_fe'] = 1) {

			$wsfedb = new wsfe_db($db);
			$wsfedb->fk_facture = $object->id;
			$wsfedb->fetch();
			$this->wsfe = $wsfedb;
		}
		//----------------------

		$nblignes = count($object->lines);


		// Loop on each lines to detect if there is at least one image to show
		/*$realpatharray=array();
		if (! empty($conf->global->MAIN_GENERATE_INVOICES_WITH_PICTURE))
		{
			for ($i = 0 ; $i < $nblignes ; $i++)
			{
				if (empty($object->lines[$i]->fk_product)) continue;

				$objphoto = new Product($this->db);
				$objphoto->fetch($object->lines[$i]->fk_product);

				$pdir = get_exdir($object->lines[$i]->fk_product,2,0,0,'product') . $object->lines[$i]->fk_product ."/photos/";
				$dir = $conf->product->dir_output.'/'.$pdir;

				$realpath='';
				foreach ($objphoto->liste_photos($dir,1) as $key => $obj)
				{
					$filename=$obj['photo'];
					//if ($obj['photo_vignette']) $filename='thumbs/'.$obj['photo_vignette'];
					$realpath = $dir.$filename;
					break;
				}

				if ($realpath) $realpatharray[$i]=$realpath;
			}
		}
		if (count($realpatharray) == 0) $this->posxpicture=$this->posxtva;
		*/

		if ($conf->facture->dir_output)
		{
			$object->fetch_thirdparty();

			$deja_regle = $object->getSommePaiement();
			$amount_credit_notes_included = $object->getSumCreditNotesUsed();
			$amount_deposits_included = $object->getSumDepositsUsed();

			// Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->facture->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->facture->dir_output . "/" . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
			}
			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('beforePDFCreation',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

				// Create pdf instance
				$pdf=pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
				$heightforinfotot = 50;	// Height reserved to output the info and total part
		        $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
	            //$heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)
				$heightforfooter = 61; //margen inferior para corte de pagina
                $pdf->SetAutoPageBreak(1,0);

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));

               //Cargo Plantilla
               
				// arreglo joni font pdf (REVERTIDO MANU)
				$pagecount = $pdf->setSourceFile(DOL_DOCUMENT_ROOT.'/wsfephp/plantillas/pos.pdf');
				$tplidx = $pdf->importPage(1);
				// arreglo joni font pdf (REVERTIDO MANU)

                // Set path to the background PDF File
                if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
                {
				    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $pdf->importPage(1);
                }

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Invoice"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION." wsfevphp-2.0");
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Invoice"));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				/*
				// Positionne $this->atleastonediscount si on a au moins une remise

				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					if ($object->lines[$i]->remise_percent)
					{
						$this->atleastonediscount++;
					}
				}
				if (empty($this->atleastonediscount) && empty($conf->global->PRODUCT_USE_UNITS))
				{
					$this->posxpicture+=($this->postotalht - $this->posxdiscount);
					$this->posxtva+=($this->postotalht - $this->posxdiscount);
					$this->posxup+=($this->postotalht - $this->posxdiscount);
					$this->posxqty+=($this->postotalht - $this->posxdiscount);
					$this->posxdiscount+=($this->postotalht - $this->posxdiscount);
					//$this->postotalht;
				}


				// Situation invoice handling

				if ($object->situation_cycle_ref)
				{
					$this->situationinvoice = True;
					$progress_width = 14;
					$this->posxtva -= $progress_width;
					$this->posxup -= $progress_width;
					$this->posxqty -= $progress_width;
					$this->posxdiscount -= $progress_width;
					$this->posxprogress -= $progress_width;
				}
				*/
				
				// desde aca movi jonathan parar arreglo de fonts				
				//COPIAS ORIGINAL DUPLICADO
				$intCopias=1;
				if ($conf->global->WSFE_FAC_PDF_PRINT_COPIES >4)  $conf->global->WSFE_FAC_PDF_PRINT_COPIES = 4;
				if (empty($conf->global->WSFE_FAC_PDF_PRINT_COPIES)) $conf->global->WSFE_FAC_PDF_PRINT_COPIES=1;
				while ($intCopias <= $conf->global->WSFE_FAC_PDF_PRINT_COPIES){
				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs,$intCopias);
				
				// $pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				// $pdf->SetTextColor(60,60,60);

				$tab_top = 90;
				//$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);
				//$tab_top_newpage = $tab_top-10;
					$tab_top_newpage = $this->posfac['pos_item_desc']['y'];
					$tab_height = 130;
				$tab_height_newpage = 150;



					/*
                                    // Incoterm
                                    $height_incoterms = 0;

                                    if ($conf->incoterm->enabled)
                                    {
                                        $desc_incoterms = $object->getIncotermsForPDF();
                                        if ($desc_incoterms)
                                        {
                                            $tab_top = 88;

                                            $pdf->SetFont('','', $default_font_size - 1);
                                            $pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top-1, dol_htmlentitiesbr($desc_incoterms), 0, 1);
                                            $nexY = $pdf->GetY();
                                            $height_incoterms=$nexY-$tab_top;

                                            // Rect prend une longueur en 3eme param
                                            $pdf->SetDrawColor(192,192,192);
                                            $pdf->Rect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_incoterms+1);

                                            $tab_top = $nexY+6;
                                            $height_incoterms += 4;
                                        }
                                    }
                    */

				// Affiche notes
				$notetoshow=empty($object->note_public)?'':$object->note_public;
				if (! empty($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_NOTE))
				{
					// Get first sale rep
					if (is_object($object->thirdparty))
					{
						$salereparray=$object->thirdparty->getSalesRepresentatives($user);
						$salerepobj=new User($this->db);
						$salerepobj->fetch($salereparray[0]['id']);
						if (! empty($salerepobj->signature)) $notetoshow=dol_concatdesc($notetoshow, $salerepobj->signature);
					}
				}
				if ($notetoshow) {
					//$tab_top = 88 + $height_incoterms;
					//$tab_top = 88;

					$pdf->SetFont($this->posfac['pos_factu_note']['font'],$this->posfac['pos_factu_note']['style'],$this->posfac['pos_factu_note']['size']);
					$pdf->SetXY($this->posfac['pos_factu_note']['x'], $this->posfac['pos_factu_note']['y']);
					$pdf->MultiCell($this->posfac['pos_factu_note']['w'], $this->posfac['pos_factu_note']['h'], $notetoshow, 0, $this->posfac['pos_factu_note']['alig']);

					//$pdf->SetFont('', '', $default_font_size - 1);
					//$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
					//$nexY = $pdf->GetY();
					//$height_note = $nexY - $tab_top;

					// Rect prend une longueur en 3eme param
					//$pdf->SetDrawColor(192,192,192);
					//$pdf->Rect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+1);

					//$tab_height = $tab_height - $height_note;
					//$tab_top = $nexY+6;
				}
				else
				{
					//$height_note=0;
				}

				//$iniY = $tab_top + 7;
				//$curY = $tab_top + 7;
				//$nexY = $tab_top + 7;

				//LINEAS----------------------------------------------------------
         		// Loop on each lines
				$nexY=$this->posfac['pos_item_desc']['y'];
				for ($i = 0; $i < $nblignes; $i++)
				{
					$curY = $nexY;

					$pdf->SetFont($this->posfac['pos_item_desc']['font'],$this->posfac['pos_item_desc']['style'], $this->posfac['pos_item_desc']['size']);   // Into loop to work with multipage
					// $pdf->SetTextColor(60,60,60);

					// Define size of image if we need it
					$imglinesize=array();
					if (! empty($realpatharray[$i])) $imglinesize=pdf_getSizeForImage($realpatharray[$i]);

					$pdf->setTopMargin($tab_top_newpage);
					//$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
					$pdf->setPageOrientation('', 1, $heightforfooter);
					$pageposbefore=$pdf->getPage();

					$showpricebeforepagebreak=1;
					$posYAfterImage=0;
					$posYAfterDescription=0;

					/*
                    // We start with Photo of product line
					if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur-($heightforfooter+$heightforfreetext+$heightforinfotot)))	// If photo too high, we moved completely on new page
					{
						$pdf->AddPage('','',true);
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
						$pdf->setPage($pageposbefore+1);

						$curY = $tab_top_newpage;
						$showpricebeforepagebreak=0;
					}

					if (isset($imglinesize['width']) && isset($imglinesize['height']))
					{
						$curX = $this->posxpicture-1;
						$pdf->Image($realpatharray[$i], $curX + (($this->posxtva-$this->posxpicture-$imglinesize['width'])/2), $curY, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300);	// Use 300 dpi
						// $pdf->Image does not increase value return by getY, so we save it manually
						$posYAfterImage=$curY+$imglinesize['height'];
					}*/


					// Description of product line
					$curX =$this->posfac['pos_item_desc']['x'];
					
					$pdf->startTransaction();
					//pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posfac['pos_item_desc']['w'],$this->posfac['pos_item_desc']['h'],$curX,$curY,$hideref,$hidedesc);
					pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posfac['pos_item_desc']['w'],$this->posfac['pos_item_desc']['h'],$curX,$curY,$hideref,true);
					$pageposafter=$pdf->getPage();

					if ($pageposafter > $pageposbefore)	// There is a pagebreak
					{
						$pdf->rollbackTransaction(true);
						$pageposafter=$pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
						pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posfac['pos_item_desc']['w'],$this->posfac['pos_item_desc']['h'],$curX,$curY,$hideref,true);

						$pageposafter=$pdf->getPage();
						$posyafter=$pdf->GetY();
						//var_dump($posyafter); var_dump(($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot))); exit;
						if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot)))	// There is no space left for total+free text
						{
							if ($i == ($nblignes-1))	// No more lines, and no space left to show total, so we create a new page
							{
								$pdf->AddPage('','',true);
								if (! empty($tplidx)) $pdf->useTemplate($tplidx);
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs,$intCopias);
								$pdf->setPage($pageposafter+1);
							}
						}
						else
						{
							// We found a page break
							$showpricebeforepagebreak=0;
							if (! empty($tplidx)) $pdf->useTemplate($tplidx); //agrega template
						}
					}
					else	// No pagebreak
					{
						$pdf->commitTransaction();
					}
					
					$posYAfterDescription=$pdf->GetY();

					$nexY = $pdf->GetY();
					$pageposafter=$pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
					}


				  // Se agregaron estas lineas por que el la funcion pdf_getlinetotalwithtax y pdf_getlineupwithtax mo chequea signo
					$sign=1;
					if (isset($object->type) && $object->type == 2 && !empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;
                   ///-------------------
					if ($this->wsfe->cbttipo == '1' or $object->thirdparty->typent_code =='A') {
						//$tvat = price($sign*pdf_getlinetotalwithtax($object, $i, $outputlangs, $hidedetails) - pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails)); //valor iva
						$tvat = price($sign*$object->lines[$i]->total_tva);
						$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
						$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);

						// Manuel
						$total_excl_tax_aimprimir = number_format($object->lines[$i]->total_ttc, 2, ',', '.');
						/*
						if (isset($this->wsfe->cbttipo) && $this->tipos_fact[$this->wsfe->cbttipo] == 'A') {
							$total_excl_tax_aimprimir = number_format($object->lines[$i]->total_ttc, 2, ',', '.');
							//$total_excl_tax_aimprimir = number_format(floatval(str_replace(',', '.', strval($total_excl_tax))) + floatval(str_replace(',', '.', strval($tvat))), 2, ',', '.');
						} else {
							$total_excl_tax_aimprimir = $total_excl_tax;
						}
						*/
						// Fin Manuel

					}else{
						$tvat='';
						//$up_excl_tax = price($sign * pdf_getlineupwithtax($object, $i, $outputlangs, $hidedetails));
						//$total_excl_tax = price($sign * pdf_getlinetotalwithtax($object, $i, $outputlangs, $hidedetails));
						$up_excl_tax = price($sign * ($object->lines[$i]->total_ttc/$object->lines[$i]->qty));
						$total_excl_tax = price($sign*$object->lines[$i]->total_ttc);
						
						// Manuel
						$total_excl_tax_aimprimir = number_format($object->lines[$i]->total_ttc, 2, ',', '.');
						//$total_excl_tax_aimprimir = $total_excl_tax;
						// Fin Manuel

					}
					// VAT Rate y VAT
					if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
					{
						$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
						// Manuel
						$vat_rateaimprimir = (isset($tvat) && $tvat != 0) ? $vat_rate : '';
						// Fin Manuel
						$pdf->SetFont($this->posfac['pos_item_ivarate']['font'],$this->posfac['pos_item_ivarate']['style'],$this->posfac['pos_item_ivarate']['size']);
						$pdf->SetXY($this->posfac['pos_item_ivarate']['x'], $curY);
						$pdf->MultiCell($this->posfac['pos_item_ivarate']['w'], $this->posfac['pos_item_ivarate']['h'], $vat_rateaimprimir, 0, $this->posfac['pos_item_ivarate']['alig']);


						//$tvat = pdf_getlinetotalwithtax($object, $i, $outputlangs, $hidedetails) - pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
						$pdf->SetFont($this->posfac['pos_item_iva']['font'], $this->posfac['pos_item_iva']['style'], $this->posfac['pos_item_iva']['size']);
						$pdf->SetXY($this->posfac['pos_item_iva']['x'], $curY);

						// Manuel
						$tvataimprimir = (isset($tvat) && $tvat != 0) ? '$' . $tvat : '';
						// Fin Manuel
						//comentado por Manuel linea de abajo
						//$pdf->MultiCell($this->posfac['pos_item_iva']['w'], $this->posfac['pos_item_iva']['h'], $tvataimprimir, 0, $this->posfac['pos_item_iva']['alig']);

					}



					// Unit price before discount
					//$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
					
					$pdf->SetFont($this->posfac['pos_item_prec']['font'],$this->posfac['pos_item_prec']['style'],$this->posfac['pos_item_prec']['size']);
					$pdf->SetXY($this->posfac['pos_item_prec']['x'], $curY);
					
					if ($this->wsfe->cbttipo == '1' or $object->thirdparty->typent_code =='A') {
						$pdf->MultiCell($this->posfac['pos_item_prec']['w'], $this->posfac['pos_item_prec']['h'], '$' . $up_excl_tax, 0, $this->posfac['pos_item_prec']['alig'], 0);
					} else {
						$pdf->MultiCell($this->posfac['pos_item_prec']['w'], $this->posfac['pos_item_prec']['h'], '$' . number_format(($object->lines[$i]->total_ttc/$object->lines[$i]->qty)/((100-$object->lines[$i]->remise_percent)/100), 2, ',', '.'), 0, $this->posfac['pos_item_prec']['alig'], 0);
					}
					

					// Quantity
					$pdf->SetFont($this->posfac['pos_item_qty']['font'],$this->posfac['pos_item_qty']['style'],$this->posfac['pos_item_qty']['size']);
					$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY($this->posfac['pos_item_qty']['x'], $curY);
					$title=$this->copias[$intCopias].' Pagina: '.$intPag;
					$pdf->MultiCell($this->posfac['pos_item_qty']['w'], $this->posfac['pos_item_qty']['h'], $qty . ' x ', 0, $this->posfac['pos_item_qty']['alig'], 0);

					// Enough for 6 chars
					if($conf->global->PRODUCT_USE_UNITS)
					{
						$unit = pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager);
						$pdf->SetFont($this->posfac['pos_item_uni']['font'],$this->posfac['pos_item_uni']['style'],$this->posfac['pos_item_uni']['size']);
						$pdf->SetXY($this->posfac['pos_item_uni']['x'], $curY);
						$pdf->MultiCell($this->posfac['pos_item_uni']['w'], $this->posfac['pos_item_uni']['h'], $unit, 0, $this->posfac['pos_item_uni']['alig'], 0);
					}

					// Discount on line
					if ($object->lines[$i]->remise_percent)
					{
						$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
						$pdf->SetFont($this->posfac['pos_item_disc']['font'],$this->posfac['pos_item_disc']['style'],$this->posfac['pos_item_disc']['size']);
						$pdf->SetXY($this->posfac['pos_item_disc']['x'], $curY);
						$pdf->MultiCell($this->posfac['pos_item_disc']['w'], $this->posfac['pos_item_disc']['h'], $remise_percent, 0, $this->posfac['pos_item_disc']['alig'], 0);

					}

					if ($this->situationinvoice)
					{
						// Situation progress
						$progress = pdf_getlineprogress($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->posxprogress, $curY);
						$pdf->MultiCell($this->postotalht-$this->posxprogress, 3, $progress, 0, 'R');
					}
		
					// Total HT line
					//$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
					$pdf->SetFont($this->posfac['pos_item_imp']['font'],$this->posfac['pos_item_imp']['style'],$this->posfac['pos_item_imp']['size']);
					$pdf->SetXY($this->posfac['pos_item_imp']['x'], $curY);
					$pdf->MultiCell($this->posfac['pos_item_imp']['w'], $this->posfac['pos_item_imp']['h'], '$' . $total_excl_tax_aimprimir, 0, $this->posfac['pos_item_imp']['alig'], 0);

					// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
					$prev_progress = $object->lines[$i]->get_prev_progress($object->id);
					if ($prev_progress > 0) // Compute progress from previous situation
					{
						$tvaligne = $sign* $object->lines[$i]->total_tva * ($object->lines[$i]->situation_percent - $prev_progress) / $object->lines[$i]->situation_percent;
					} else {
						$tvaligne = $sign* $object->lines[$i]->total_tva;
					}
					$localtax1ligne=$object->lines[$i]->total_localtax1;
					$localtax2ligne=$object->lines[$i]->total_localtax2;
					$localtax1_rate=$object->lines[$i]->localtax1_tx;
					$localtax2_rate=$object->lines[$i]->localtax2_tx;
					$localtax1_type=$object->lines[$i]->localtax1_type;
					$localtax2_type=$object->lines[$i]->localtax2_type;

					if ($object->remise_percent) $tvaligne-=($tvaligne*$object->remise_percent)/100;
					if ($object->remise_percent) $localtax1ligne-=($localtax1ligne*$object->remise_percent)/100;
					if ($object->remise_percent) $localtax2ligne-=($localtax2ligne*$object->remise_percent)/100;

					$vatrate=(string) $object->lines[$i]->tva_tx;

					// Retrieve type from database for backward compatibility with old records
					if ((! isset($localtax1_type) || $localtax1_type=='' || ! isset($localtax2_type) || $localtax2_type=='') // if tax type not defined
					&& (! empty($localtax1_rate) || ! empty($localtax2_rate))) // and there is local tax
					{
						$localtaxtmp_array=getLocalTaxesFromRate($vatrate,0, $object->thirdparty, $mysoc);
						$localtax1_type = $localtaxtmp_array[0];
						$localtax2_type = $localtaxtmp_array[2];
					}

				    // retrieve global local tax
					if ($localtax1_type && $localtax1ligne != 0)
						$this->localtax1[$localtax1_type][$localtax1_rate]+=$localtax1ligne;
					if ($localtax2_type && $localtax2ligne != 0)
						$this->localtax2[$localtax2_type][$localtax2_rate]+=$localtax2ligne;

					if (($object->lines[$i]->info_bits & 0x01) == 0x01) $vatrate.='*';
					if (! isset($this->tva[$vatrate])) 				$this->tva[$vatrate ] = 0; //$this->tva[$vatrate]='';
					$this->tva[$vatrate] += $tvaligne;

					if ($posYAfterImage > $posYAfterDescription) $nexY=$posYAfterImage;

					// Add line
					if (! empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblignes - 1))
					{
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash'=>'0,0','color'=>array(256,256,256)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY+1, $this->page_largeur - $this->marge_droite, $nexY+1);
						//$pdf->SetLineStyle(array('dash'=>0)); //comentado por Manuel
					}

					$nexY+=2;    // Passe espace entre les lignes

					// Detect if some page were added automatically and output _tableau for past pages
					// Solo El if agregado por Manuel (no modificado el contenido del if)
					if(is_numeric($pagenb) && is_numeric($pageposafter)) {
						while ($pagenb < $pageposafter)
						{
							$pdf->setPage($pagenb);
							if ($pagenb == 1)
							{
								$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
							}
							else
							{
								$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
							}
							$this->_pagefoot($pdf,$object,$outputlangs,1);

							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
							if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs,$intCopias);
						}
					}
					// Fin if agregado por Manuel

					if (isset($object->lines[$i+1]->pagebreak) && $object->lines[$i+1]->pagebreak)
					{
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf,$object,$outputlangs,1);


						// New page
						$pdf->AddPage();
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						$pagenb++;
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs,$intCopias);
					}
				}
				
				//Fin LINEAS-------------------------------------------------------------------

				//Linea separadora de items
				$pdf->SetFont($this->posfac['pos_factu_mpago']['font'],'B',$this->posfac['pos_factu_mpago']['size']);
				$pdf->SetXY($this->posfac['pos_factu_mpago']['x'], $nexY-3);
				$pdf->MultiCell($this->posfac['pos_factu_mpago']['w'], $this->posfac['pos_factu_mpago']['h'], '  ____________________________', 0, $this->posfac['pos_factu_mpago']['alig']);

				//Condicion de pago
				// Show payments conditions
				if ($object->type != 2 && ($object->cond_reglement_code || $object->cond_reglement)) {
					$titre = $outputlangs->transnoentities("PaymentConditions") . ':';
					$lib_condition_paiement = $outputlangs->transnoentities("PaymentCondition" . $object->cond_reglement_code) != ('PaymentCondition' . $object->cond_reglement_code) ? $outputlangs->transnoentities("PaymentCondition" . $object->cond_reglement_code) : $outputlangs->convToOutputCharset($object->cond_reglement_doc);
					$lib_condition_paiement = str_replace('\n', "\n", $lib_condition_paiement);

					//Comentado por Manuel
					//$pdf->SetFont($this->posfac['pos_factu_cpago']['font'], $this->posfac['pos_factu_cpago']['style'], $this->posfac['pos_factu_cpago']['size']);
					//$pdf->SetXY($this->posfac['pos_factu_cpago']['x'], $this->posfac['pos_factu_cpago']['y']);
					/*if ($conf->global->WSFE_FAC_PDF_PRINT_LABEL)*/ //$label= $titre . ' ';
					//$pdf->MultiCell($this->posfac['pos_factu_cpago']['w'], $this->posfac['pos_factu_cpago']['h'], $label. $lib_condition_paiement, 0, $this->posfac['pos_factu_cpago']['alig']);

				
					//Modo de pago
					// Show payment mode
					if ($object->mode_reglement_code){

					$titre = $outputlangs->transnoentities("PaymentMode").':';
					$lib_mode_reg=$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code)!=('PaymentType'.$object->mode_reglement_code)?$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code):$outputlangs->convToOutputCharset($object->mode_reglement);

					$pdf->SetFont($this->posfac['pos_factu_mpago']['font'], $this->posfac['pos_factu_mpago']['style'], $this->posfac['pos_factu_mpago']['size']);
					$pdf->SetXY($this->posfac['pos_factu_mpago']['x'], $nexY);
					/*if ($conf->global->WSFE_FAC_PDF_PRINT_LABEL)*/ //$label= $titre . ' ';
					$label= '';
					$pdf->MultiCell($this->posfac['pos_factu_mpago']['w'], $this->posfac['pos_factu_mpago']['h'], $label . $lib_mode_reg, 0, $this->posfac['pos_factu_mpago']['alig']);
					}
				}


				//MANU QR
				
				/*$cantDigDNICUITCUIL = strlen(str_replace('-','',$object->thirdparty->idprof1));
				if($cantDigDNICUITCUIL == 11) {
					$tipoDocRec = 80;
				} else {
					$tipoDocRec = 96;
				}*/

				$tipo_cbte = $this->wsfe->cbttipo;			
				if($tipo_cbte == 6 || $tipo_cbte == 7 || $tipo_cbte == 8 ||  $tipo_cbte == 11 ||  $tipo_cbte == 13 ||  $tipo_cbte == 12) { 
					$nro_doc = (double)preg_replace('/[^0-9]/', '', $object->thirdparty->idprof1); //"20241952569"

					if (strlen($nro_doc) == 8) {  //es DNI
						$tipo_doc = 96; 
					}elseif (strlen($nro_doc) == 11) { //es cuit
						$tipo_doc = 80;
					}else{ //consumidor final
						$tipo_doc = 99; 
						$nro_doc = 0; //"20241952569"
					}
				} else {
					$nro_doc = (int) str_replace('-','',$object->thirdparty->idprof1);
				}

				include "barcode/vendor/autoload.php"; // Incluimos la libreria
						
				$datos_cmp_base_64 = json_encode([
					"ver" => 1,	// Numérico 1 digito -  OBLIGATORIO – versión del formato de los datos del comprobante	1
					"fecha" => '"' . date_format(date_create_from_format("d/m/Y", dol_print_date($object->date,"day",false,$outputlangs)), 'Y-m-d') . '"',	// full-date (RFC3339) - OBLIGATORIO – Fecha de emisión del comprobante
					"cuit" => (int) str_replace('-','',$this->emetteur->idprof1),	// Numérico 11 dígitos -  OBLIGATORIO – Cuit del Emisor del comprobante  
					//"ptoVta" => (int) $conf->global->MAIN_INFO_SOCIETE_PTOVTA,	// Numérico hasta 5 digitos - OBLIGATORIO – Punto de venta utilizado para emitir el comprobante
					"ptoVta" => (int) $this->wsfe->puntodeventa,	// Numérico hasta 5 digitos - OBLIGATORIO – Punto de venta utilizado para emitir el comprobante
					"tipoCmp" => (int) $this->wsfe->cbttipo,	// Numérico hasta 3 dígitos - OBLIGATORIO – tipo de comprobante (según Tablas del sistema. Ver abajo )
					"nroCmp" => (int) $this->wsfe->cbtnro,	// Numérico hasta 8 dígitos - OBLIGATORIO – Número del comprobante
					"importe" => number_format((float) str_replace(',','.',price($sign * ($object->total_ttc + (!empty($object->remise) ? $object->remise : 0)))),2),	// Decimal hasta 13 enteros y 2 decimales - OBLIGATORIO – Importe Total del comprobante (en la moneda en la que fue emitido)
					"moneda" => '"' . "PES" . '"',	// 3 caracteres - OBLIGATORIO – Moneda del comprobante (según Tablas del sistema. Ver Abajo )
					"ctz" => (float) 1,	// Decimal hasta 13 enteros y 6 decimales - OBLIGATORIO – Cotización en pesos argentinos de la moneda utilizada (1 cuando la moneda sea pesos)
					"tipoDocRec" =>  $tipo_doc,	// Numérico hasta 2 dígitos - DE CORRESPONDER – Código del Tipo de documento del receptor (según Tablas del sistema )
					"nroDocRec" => $nro_doc,	// Numérico hasta 20 dígitos - DE CORRESPONDER – Número de documento del receptor correspondiente al tipo de documento indicado
					"tipoCodAut" => '"' . "E" . '"',	// string - OBLIGATORIO – “A” para comprobante autorizado por CAEA, “E” para comprobante autorizado por CAE
					"codAut" => (int) $this->wsfe->cae	// Numérico 14 dígitos -  OBLIGATORIO – Código de autorización otorgado por AFIP para el comprobante
				]);

				/*
				$a = [
					"ver" => 1,	// Numérico 1 digito -  OBLIGATORIO – versión del formato de los datos del comprobante	1
					"fecha" => '"' . date_format(date_create_from_format("d/m/Y", dol_print_date($object->date,"day",false,$outputlangs)), 'Y-m-d') . '"',	// full-date (RFC3339) - OBLIGATORIO – Fecha de emisión del comprobante
					"cuit" => (int) str_replace('-','',$this->emetteur->idprof1),	// Numérico 11 dígitos -  OBLIGATORIO – Cuit del Emisor del comprobante  
					//"ptoVta" => (int) $conf->global->MAIN_INFO_SOCIETE_PTOVTA,	// Numérico hasta 5 digitos - OBLIGATORIO – Punto de venta utilizado para emitir el comprobante
					"ptoVta" => (int) $this->wsfe->puntodeventa,	// Numérico hasta 5 digitos - OBLIGATORIO – Punto de venta utilizado para emitir el comprobante
					"tipoCmp" => (int) $this->wsfe->cbttipo,	// Numérico hasta 3 dígitos - OBLIGATORIO – tipo de comprobante (según Tablas del sistema. Ver abajo )
					"nroCmp" => (int) $this->wsfe->cbtnro,	// Numérico hasta 8 dígitos - OBLIGATORIO – Número del comprobante
					"importe" => number_format((float) str_replace(',','.',price($sign * ($object->total_ttc + (!empty($object->remise) ? $object->remise : 0)))),2),	// Decimal hasta 13 enteros y 2 decimales - OBLIGATORIO – Importe Total del comprobante (en la moneda en la que fue emitido)
					"moneda" => '"' . "PES" . '"',	// 3 caracteres - OBLIGATORIO – Moneda del comprobante (según Tablas del sistema. Ver Abajo )
					"ctz" => (float) 1,	// Decimal hasta 13 enteros y 6 decimales - OBLIGATORIO – Cotización en pesos argentinos de la moneda utilizada (1 cuando la moneda sea pesos)
					"tipoDocRec" =>  $tipo_doc,	// Numérico hasta 2 dígitos - DE CORRESPONDER – Código del Tipo de documento del receptor (según Tablas del sistema )
					"nroDocRec" => $nro_doc,	// Numérico hasta 20 dígitos - DE CORRESPONDER – Número de documento del receptor correspondiente al tipo de documento indicado
					"tipoCodAut" => '"' . "E" . '"',	// string - OBLIGATORIO – “A” para comprobante autorizado por CAEA, “E” para comprobante autorizado por CAE
					"codAut" => (int) $this->wsfe->cae	// Numérico 14 dígitos -  OBLIGATORIO – Código de autorización otorgado por AFIP para el comprobante
				];
		
				$pdf->SetFont($this->posfac['pos_empresa_nom']['font'],$this->posfac['pos_empresa_nom']['style'],$this->posfac['pos_empresa_nom']['size']);
				$pdf->SetXY($this->posfac['pos_empresa_nom']['x'],$this->posfac['pos_empresa_nom']['y']);
				$pdf->MultiCell($this->posfac['pos_empresa_nom']['w'],$this->posfac['pos_empresa_nom']['h'],implode(' , ', $a ),0,$this->posfac['pos_empresa_nom']['alig']);
				*/



				$datos_cmp_base_64 = base64_encode($datos_cmp_base_64);

				$url = 'https://www.afip.gob.ar/fe/qr/';
				$to_qr = $url.'?p='.$datos_cmp_base_64;

				$barcode = new \Com\Tecnick\Barcode\Barcode();
				$bobj = $barcode->getBarcodeObj(
					'QRCODE,M',
					$to_qr,
					-4,
					-4,
					'black',
					array(-2, -2, -2, -2)
					)->setBackgroundColor('white');
				//$qr_div = base64_encode($bobj->getPngData());
				
				$imageData = $bobj->getPngData(); // Obtenemos el resultado en formato PNG
			
				file_put_contents(DOL_DOCUMENT_ROOT.'/core/modules/facture/doc/barcode/qrcode.png', $imageData); // Guardamos el resultado

				$qrpath = DOL_DOCUMENT_ROOT.'/core/modules/facture/doc/barcode/qrcode.png';
				$nexY += 6; 
				$pdf->Image($qrpath, 15, $nexY, 0, 40);	// width=0 (auto)

				// Fin QR Manu

				// Show square

				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}


				// Affiche zone infos
				//linea de abajo cambiada por Manuel

				$posy=$this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);
				//$posy=$this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

				// Affiche zone totaux
				//linea de abajo cambiada por Manuel
				$posy=$this->_tableau_tot($pdf, $object, $deja_regle, $nexY+40, $outputlangs);
				//$posy=$this->_tableau_tot($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

				// Affiche zone versements
				//if ($deja_regle || $amount_credit_notes_included || $amount_deposits_included)
				//{
				//	$posy=$this->_tableau_versements($pdf, $object, $posy, $outputlangs);
				//}



				// Pied de page
				$this->_pagefoot($pdf,$object,$outputlangs);
				if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

					$intCopias++;

				} // COPIAS

				$pdf->Close();

				$pdf->Output($file,'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","FAC_OUTPUTDIR");
			return 0;
		}
		$this->error=$langs->trans("ErrorUnknown");
		return 0;   // Erreur par defaut
	}


	/**
	 *  Show payments table
	 *
     *  @param	PDF			$pdf           Object PDF
     *  @param  Object		$object         Object invoice
     *  @param  int			$posy           Position y in PDF
     *  @param  Translate	$outputlangs    Object langs for output
     *  @return int             			<0 if KO, >0 if OK
	 */
//No muestro pagos
/*
	function _tableau_versements(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

        $sign=1;
        if ($object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

        $tab3_posx = 120;
		$tab3_top = $posy + 8;
		$tab3_width = 80;
		$tab3_height = 4;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$tab3_posx -= 20;
		}

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$title=$outputlangs->transnoentities("PaymentsAlreadyDone");
		if ($object->type == 2) $title=$outputlangs->transnoentities("PaymentsBackAlreadyDone");

		$pdf->SetFont('','', $default_font_size - 3);
		$pdf->SetXY($tab3_posx, $tab3_top - 4);
		$pdf->MultiCell(60, 3, $title, 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top, $tab3_posx+$tab3_width, $tab3_top);

		$pdf->SetFont('','', $default_font_size - 4);
		$pdf->SetXY($tab3_posx, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Payment"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx+21, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Amount"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx+40, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Type"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx+58, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Num"), 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top-1+$tab3_height, $tab3_posx+$tab3_width, $tab3_top-1+$tab3_height);

		$y=0;

		$pdf->SetFont('','', $default_font_size - 4);

		// Loop on each deposits and credit notes included
		$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
		$sql.= " re.description, re.fk_facture_source,";
		$sql.= " f.type, f.datef";
		$sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re, ".MAIN_DB_PREFIX ."facture as f";
		$sql.= " WHERE re.fk_facture_source = f.rowid AND re.fk_facture = ".$object->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			$invoice=new Facture($this->db);
			while ($i < $num)
			{
				$y+=3;
				$obj = $this->db->fetch_object($resql);

				if ($obj->type == 2) $text=$outputlangs->trans("CreditNote");
				elseif ($obj->type == 3) $text=$outputlangs->trans("Deposit");
				else $text=$outputlangs->trans("UnknownType");

				$invoice->fetch($obj->fk_facture_source);

				$pdf->SetXY($tab3_posx, $tab3_top+$y);
				$pdf->MultiCell(20, 3, dol_print_date($obj->datef,'day',false,$outputlangs,true), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+21, $tab3_top+$y);
				$pdf->MultiCell(20, 3, price($obj->amount_ttc, 0, $outputlangs), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+40, $tab3_top+$y);
				$pdf->MultiCell(20, 3, $text, 0, 'L', 0);
				$pdf->SetXY($tab3_posx+58, $tab3_top+$y);
				$pdf->MultiCell(20, 3, $invoice->ref, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3);

				$i++;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}

		// Loop on each payment
		$sql = "SELECT p.datep as date, p.fk_paiement as type, p.num_paiement as num, pf.amount as amount,";
		$sql.= " cp.code";
		$sql.= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."paiement as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp ON p.fk_paiement = cp.id";
		$sql.= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = ".$object->id;
		$sql.= " ORDER BY p.datep";
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num) {
				$y+=3;
				$row = $this->db->fetch_object($resql);

				$pdf->SetXY($tab3_posx, $tab3_top+$y);
				$pdf->MultiCell(20, 3, dol_print_date($this->db->jdate($row->date),'day',false,$outputlangs,true), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+21, $tab3_top+$y);
				$pdf->MultiCell(20, 3, price($sign * $row->amount, 0, $outputlangs), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+40, $tab3_top+$y);
				$oper = $outputlangs->transnoentitiesnoconv("PaymentTypeShort" . $row->code);

				$pdf->MultiCell(20, 3, $oper, 0, 'L', 0);
				$pdf->SetXY($tab3_posx+58, $tab3_top+$y);
				$pdf->MultiCell(30, 3, $row->num, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3);

				$i++;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}

	}
*/

	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		PDF			$pdf     		Object PDF
	 *   @param		Object		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	void
	 */
	function _tableau_info(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// $pdf->SetFont('','', $default_font_size - 1);

		// If France, show VAT mention if not applicable
		if ($this->emetteur->country_code == 'FR' && $this->franchise == 1)
		{
			// $pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy=$pdf->GetY()+4;
		}

		$posxval=52;

				// Show payments conditions
		//		if ($object->type != 2 && ($object->cond_reglement_code || $object->cond_reglement))
		//		{
		//			$titre = $outputlangs->transnoentities("PaymentConditions").':';
		//			$lib_condition_paiement=$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code)!=('PaymentCondition'.$object->cond_reglement_code)?$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code):$outputlangs->convToOutputCharset($object->cond_reglement_doc);
		//			$lib_condition_paiement=str_replace('\n',"\n",$lib_condition_paiement);
		//
		//			//Forma de pago
		//			$pdf->SetFont($this->posfac['pos_factu_cpago']['font'],$this->posfac['pos_factu_cpago']['style'],$this->posfac['pos_factu_cpago']['size']);
		//			$pdf->SetXY($this->posfac['pos_factu_cpago']['x'],$this->posfac['pos_factu_cpago']['y']);
		//			$pdf->MultiCell($this->posfac['pos_factu_cpago']['w'],$this->posfac['pos_factu_cpago']['h'],$titre.' '.$lib_condition_paiement,0,$this->posfac['pos_factu_cpago']['alig']);

					//$posy=$pdf->GetY()+3;
		//		}

		//		if ($object->type != 2)
		//		{
		//			// Check a payment mode is defined
		//			if (empty($object->mode_reglement_code)
		//			&& empty($conf->global->FACTURE_CHQ_NUMBER)
		//			&& empty($conf->global->FACTURE_RIB_NUMBER))
		//			{
		//				$this->error = $outputlangs->transnoentities("ErrorNoPaiementModeConfigured");
		//			}
		//			// Avoid having any valid PDF with setup that is not complete
		//			elseif (($object->mode_reglement_code == 'CHQ' && empty($conf->global->FACTURE_CHQ_NUMBER))
		//				|| ($object->mode_reglement_code == 'VIR' && empty($conf->global->FACTURE_RIB_NUMBER)))
		//			{
		//				$outputlangs->load("errors");
		//
		//				$pdf->SetXY($this->marge_gauche, $posy);
		//				$pdf->SetTextColor(200,0,0);
		//				$pdf->SetFont('','B', $default_font_size - 2);
		//				$this->error = $outputlangs->transnoentities("ErrorPaymentModeDefinedToWithoutSetup",$object->mode_reglement_code);
		//				$pdf->MultiCell(80, 3, $this->error,0,'L',0);
		//				$pdf->SetTextColor(0,0,0);
		//
		//				$posy=$pdf->GetY()+1;
		//			}
		//
		//			// Show payment mode
		//			if ($object->mode_reglement_code
		//			&& $object->mode_reglement_code != 'CHQ'
		//			&& $object->mode_reglement_code != 'VIR')
		//			{
		//				$pdf->SetFont('','B', $default_font_size - 2);
		//				$pdf->SetXY($this->marge_gauche, $posy);
		//				$titre = $outputlangs->transnoentities("PaymentMode").':';
		//				$pdf->MultiCell(80, 5, $titre, 0, 'L');
		//
		//				$pdf->SetFont('','', $default_font_size - 2);
		//				$pdf->SetXY($posxval, $posy);
		//				$lib_mode_reg=$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code)!=('PaymentType'.$object->mode_reglement_code)?$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code):$outputlangs->convToOutputCharset($object->mode_reglement);
		//				$pdf->MultiCell(80, 5, $lib_mode_reg,0,'L');
		//
		//				$posy=$pdf->GetY()+2;
		//			}
		//
		//			// Show payment mode CHQ
		//			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ')
		//			{
		//				// Si mode reglement non force ou si force a CHQ
		//				if (! empty($conf->global->FACTURE_CHQ_NUMBER))
		//				{
		//					$diffsizetitle=(empty($conf->global->PDF_DIFFSIZE_TITLE)?3:$conf->global->PDF_DIFFSIZE_TITLE);
		//
		//					if ($conf->global->FACTURE_CHQ_NUMBER > 0)
		//					{
		//						$account = new Account($this->db);
		//						$account->fetch($conf->global->FACTURE_CHQ_NUMBER);
		//
		//						$pdf->SetXY($this->marge_gauche, $posy);
		//						$pdf->SetFont('','B', $default_font_size - $diffsizetitle);
		//						$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$account->proprio),0,'L',0);
		//						$posy=$pdf->GetY()+1;
		//
		//			            if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS))
		//			            {
		//							$pdf->SetXY($this->marge_gauche, $posy);
		//							$pdf->SetFont('','', $default_font_size - $diffsizetitle);
		//							$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($account->owner_address), 0, 'L', 0);
		//							$posy=$pdf->GetY()+2;
		//			            }
		//					}
		//					if ($conf->global->FACTURE_CHQ_NUMBER == -1)
		//					{
		//						$pdf->SetXY($this->marge_gauche, $posy);
		//						$pdf->SetFont('','B', $default_font_size - $diffsizetitle);
		//						$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$this->emetteur->name),0,'L',0);
		//						$posy=$pdf->GetY()+1;
		//
		//			            if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS))
		//			            {
		//							$pdf->SetXY($this->marge_gauche, $posy);
		//							$pdf->SetFont('','', $default_font_size - $diffsizetitle);
		//							$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($this->emetteur->getFullAddress()), 0, 'L', 0);
		//							$posy=$pdf->GetY()+2;
		//			            }
		//					}
		//				}
		//			}
		//
		//			// If payment mode not forced or forced to VIR, show payment with BAN
		//			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR')
		//			{
		//				if (! empty($object->fk_account) || ! empty($object->fk_bank) || ! empty($conf->global->FACTURE_RIB_NUMBER))
		//				{
		//					$bankid=(empty($object->fk_account)?$conf->global->FACTURE_RIB_NUMBER:$object->fk_account);
		//					if (! empty($object->fk_bank)) $bankid=$object->fk_bank;   // For backward compatibility when object->fk_account is forced with object->fk_bank
		//					$account = new Account($this->db);
		//					$account->fetch($bankid);
		//
		//					$curx=$this->marge_gauche;
		//					$cury=$posy;
		//
		//					$posy=pdf_bank($pdf,$outputlangs,$curx,$cury,$account,0,$default_font_size);
		//
		//					$posy+=2;
		//				}
		//			}
		//		}

		//		return $posy;
	}

	//TOTALES
	/**
	 *	Show total to pay
	 *
	 *	@param	PDF			$pdf           Object PDF
	 *	@param  Facture		$object         Object invoice
	 *	@param  int			$deja_regle     Montant deja regle
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		global $conf,$mysoc;

        $sign=1;
        if ($object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

		//Cambiada linea por Manuel
		$tab2_top = $posy;
		//$tab2_top = $this->posfac['pos_tot_col1']['y'];
		
		
		//$tab2_hl = 4;
		$tab2_hl = $this->posfac['pos_tot_col1']['h'];
		// $pdf->SetFont('','', $default_font_size - 1);

		// Tableau total
		//$pdf->SetXY($this->posfac['pos_factu_subt']['x'],$this->posfac['pos_factu_subt']['y']);

		$col1x = $this->posfac['pos_tot_col1']['x'];
		$col2x = $this->posfac['pos_tot_col2']['x'];
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$col2x-=20;
		}
		$largcol2 = $this->posfac['pos_tot_col2']['w'];

		$useborder=0;
		$index = 0;

		$pdf->SetFillColor(255, 255, 255);
		// Total HT //subtotal
		if ($this->wsfe->cbttipo =='1' or $object->thirdparty->typent_code=='A') {
		
		  $pdf->SetXY($col1x, $tab2_top + 0);
		  
			//$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);
		  $pdf->MultiCell($this->posfac['pos_tot_col1']['w'],$this->posfac['pos_tot_col1']['h'], $outputlangs->transnoentities("TotalHT"), 0, $this->posfac['pos_tot_col1']['alig'], 1);
			//$pdf->MultiCell($largcol2, $tab2_hl, price($sign * ($object->total_ht + (!empty($object->remise) ? $object->remise : 0)), 0, $outputlangs), 0, 'R', 1);
		  $pdf->SetXY($col2x, $tab2_top + 0);
		  $pdf->MultiCell($this->posfac['pos_tot_col2']['w'],$this->posfac['pos_tot_col2']['h'], '$' . price($sign * ($object->total_ht + (!empty($object->remise) ? $object->remise : 0)), 0, $outputlangs), 0, $this->posfac['pos_tot_col2']['alig'], 1);
    	}
		
    	// Show VAT by rates and total
		//$pdf->SetFillColor(248,248,248);

		$this->atleastoneratenotnull=0;
		if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
		{
			$tvaisnull=((! empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000'])) ? true : false);
			if (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_IFNULL) && $tvaisnull)
			{
				// Nothing to do
			}
			else
			{
				//Local tax 1 before VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')
				//{
				foreach( $this->localtax1 as $localtax_type => $localtax_rate )
				{
					if (in_array((string) $localtax_type, array('1','3','5'))) continue;

					foreach( $localtax_rate as $tvakey => $tvaval )
					{
						if ($tvakey!=0)    // On affiche pas taux 0
						{
							//$this->atleastoneratenotnull++;

							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl='';
							if (preg_match('/\*/',$tvakey))
							{
								$tvakey=str_replace('*','',$tvakey);
								$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}

							$totalvat = $outputlangs->transcountrynoentities("TotalLT1",$mysoc->country_code).' ';
							$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
							$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

							$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, '$' . price($tvaval, 0, $outputlangs), 0, 'R', 1);
						}
					}
				}
	      		//}
				//Local tax 2 before VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')
				//{
				foreach( $this->localtax2 as $localtax_type => $localtax_rate )
				{
					if (in_array((string) $localtax_type, array('1','3','5'))) continue;

					foreach( $localtax_rate as $tvakey => $tvaval )
					{
						if ($tvakey!=0)    // On affiche pas taux 0
						{
							//$this->atleastoneratenotnull++;



							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl='';
							if (preg_match('/\*/',$tvakey))
							{
								$tvakey=str_replace('*','',$tvakey);
								$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}
							$totalvat = $outputlangs->transcountrynoentities("TotalLT2",$mysoc->country_code).' ';
							$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
							$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

							$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, '$' . price($tvaval, 0, $outputlangs), 0, 'R', 1);

						}
					}
				}
				//}
				// VAT
				if ($this->wsfe->cbttipo =='1' or $object->thirdparty->typent_code=='A'){
					foreach($this->tva as $tvakey => $tvaval)
					{
						if ($tvakey != 0)    // On affiche pas taux 0
						{
							$this->atleastoneratenotnull++;

							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl = '';
							if (preg_match('/\*/', $tvakey)) {
								$tvakey = str_replace('*', '', $tvakey);
								$tvacompl = " (" . $outputlangs->transnoentities("NonPercuRecuperable") . ")";
							}
							$totalvat = $outputlangs->transnoentities("TotalVAT") . ' ';
							$totalvat .= vatrate($tvakey, 1) . $tvacompl;
							//	$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);
						
							$pdf->MultiCell($this->posfac['pos_tot_col1']['w'], $tab2_hl, $totalvat, 0, 'L', 1);
							$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, '$' . price($tvaval, 0, $outputlangs), 0, 'R', 1);
						}
					}
				}//fin typeent

				//Local tax 1 after VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')
				//{
				foreach( $this->localtax1 as $localtax_type => $localtax_rate )
				{
					if (in_array((string) $localtax_type, array('2','4','6'))) continue;

					foreach( $localtax_rate as $tvakey => $tvaval )
					{
						if ($tvakey != 0)    // On affiche pas taux 0
						{
							//$this->atleastoneratenotnull++;

							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl='';
							if (preg_match('/\*/',$tvakey))
							{
								$tvakey=str_replace('*','',$tvakey);
								$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}
							$totalvat = $outputlangs->transcountrynoentities("TotalLT1",$mysoc->country_code).' ';
							$totalvat.=vatrate(abs($tvakey),1).$tvacompl;

							$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);
							$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, '$' . price($tvaval, 0, $outputlangs), 0, 'R', 1);
						}
					}
				}
	      		//}
				//Local tax 2 after VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')
				//{
				foreach( $this->localtax2 as $localtax_type => $localtax_rate )
				{
					if (in_array((string) $localtax_type, array('2','4','6'))) continue;

					foreach( $localtax_rate as $tvakey => $tvaval )
					{
						// retrieve global local tax
						if ($tvakey != 0)    // On affiche pas taux 0
						{
							//$this->atleastoneratenotnull++;

							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl='';
							if (preg_match('/\*/',$tvakey))
							{
								$tvakey=str_replace('*','',$tvakey);
								$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}
							$totalvat = $outputlangs->transcountrynoentities("TotalLT2",$mysoc->country_code).' ';

							$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
							$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

							$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, '$' . price($tvaval, 0, $outputlangs), 0, 'R', 1);
						}
					}
				//}
				}

				// Revenue stamp
				if (price2num($object->revenuestamp) != 0)
				{
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RevenueStamp"), $useborder, 'L', 1);

					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, '$' . price($sign * $object->revenuestamp), $useborder, 'R', 1);
				}

				// Total TTC
				$index++;
				
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				//$pdf->SetTextColor(60,60,60);
				//$pdf->SetFillColor(245,245,245);
				//$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);
				$pdf->MultiCell($this->posfac['pos_tot_col1']['w'], $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);
				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				//$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->total_ttc, 0, $outputlangs), $useborder, 'R', 1);
				$pdf->MultiCell($this->posfac['pos_tot_col2']['w'],$tab2_hl, '$' . price($sign * $object->total_ttc, 0, $outputlangs), $useborder, 'R', 1);
			}
		}

		$pdf->SetTextColor(0,0,0);

		$creditnoteamount=$object->getSumCreditNotesUsed();
		$depositsamount=$object->getSumDepositsUsed();
		//print "x".$creditnoteamount."-".$depositsamount;exit;
		$resteapayer = price2num($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
		if ($object->paye) $resteapayer=0;

		//Impromi pagos
		//		if ($deja_regle > 0 || $creditnoteamount > 0 || $depositsamount > 0)
		//		{
		//			// Already paid + Deposits
		//			$index++;
		//			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
		//			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("Paid"), 0, 'L', 0);
		//			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
		//			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle + $depositsamount, 0, $outputlangs), 0, 'R', 0);
		//
		//			// Credit note
		//			if ($creditnoteamount)
		//			{
		//				$index++;
		//				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
		//				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("CreditNotes"), 0, 'L', 0);
		//				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
		//				$pdf->MultiCell($largcol2, $tab2_hl, price($creditnoteamount, 0, $outputlangs), 0, 'R', 0);
		//			}
		//
		//			// Escompte
		//			if ($object->close_code == Facture::CLOSECODE_DISCOUNTVAT)
		//			{
		//				$index++;
		//				$pdf->SetFillColor(255,255,255);
		//
		//				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
		//				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOffered"), $useborder, 'L', 1);
		//				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
		//				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 0, $outputlangs), $useborder, 'R', 1);
		//
		//				$resteapayer=0;
		//			}
		//
		//			$index++;
		//			$pdf->SetTextColor(0,0,60);
		//			$pdf->SetFillColor(224,224,224);
		//			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
		//			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);
		//			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
		//			$pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs), $useborder, 'R', 1);
		//
		//			$pdf->SetFont('','', $default_font_size - 1);
		//			$pdf->SetTextColor(0,0,0);
		//		}

		$index++;

		//AFIP Barras y obs
		if ($this->wsfe->cae != null) {
			$barras=$this->wsfe->cuitemisor.str_pad($this->wsfe->cbttipo,2,'0',STR_PAD_LEFT).str_pad($this->wsfe->puntodeventa,4,'0',STR_PAD_LEFT).$this->wsfe->cae.$this->wsfe->caevto;

			$pares=0;
			$impares=0;
			for ($i=1;$i<strlen($barras);$i++){
				if ($i%2==0) {
					$pares+=substr($barras,$i-1,1);

				}else{
					$impares+=substr($barras,$i-1,1);

				}
			}
			$digito=$pares+($impares*3);
			$digito = 10 - ($digito - (intval($digito / 10) * 10));
			if ($digito == 10) $digito = 0;

			$barras=$barras.$digito;
			$estado='Comprobante Autorizado';
			$mensajecae='C.A.E.: '.$this->wsfe->cae.' Fecha Vto. CAE: '.date_format(date_create_from_format('Ymd', $this->wsfe->caevto), 'd/m/Y');
		}else{
			$barras= "";
			$estado='Comprobante No Autorizado';
			$mensajecae='';
		}
		if (str_replace(' ','',$this->wsfe->obs) !=null ){
			$pdf->SetFont($this->posfac['pos_factu_obs']['font'],$this->posfac['pos_factu_obs']['style'],$this->posfac['pos_factu_obs']['size']);
			$pdf->SetXY($this->posfac['pos_factu_obs']['x'],$this->posfac['pos_factu_obs']['y']);
			$pdf->MultiCell($this->posfac['pos_factu_obs']['w'],$this->posfac['pos_factu_obs']['h'],'Observaciones AFIP',0,$this->posfac['pos_factu_obs']['alig']);

			$pdf->SetFont($this->posfac['pos_factu_obstx']['font'],$this->posfac['pos_factu_obstx']['style'],$this->posfac['pos_factu_obstx']['size']);
			$pdf->SetXY($this->posfac['pos_factu_obstx']['x'],$this->posfac['pos_factu_obstx']['y']);
			$pdf->MultiCell($this->posfac['pos_factu_obstx']['w'],$this->posfac['pos_factu_obstx']['h'],$this->wsfe->obs,0,$this->posfac['pos_factu_obstx']['alig']);

		}

		// define barcode style

		//Referencia Factura para Nota de Credito
    	if ($object->type == 2)
		{
			$objectreplaced=new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);

            $pdf->SetFont($this->posfac['pos_factu_debit']['font'],$this->posfac['pos_factu_debit']['style'],$this->posfac['pos_factu_debit']['size']);
			$pdf->SetX(1);
			$pdf->SetY($posy+35.5);
			$pdf->MultiCell($this->posfac['pos_factu_debit']['w'],$this->posfac['pos_factu_debit']['h'], $outputlangs->transnoentities("CorrectionInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref),0,$this->posfac['pos_factu_debit']['alig']);
     	} 
		
		// hasta aca movi font pdf jonathan

		$posx=$this->posfac['pos_factu_afip']['x'];
		$pdf->SetX($posx);
		$pdf->SetY($posy);

		$posy += 12;

		$pdf->Image(DOL_DOCUMENT_ROOT.'/wsfephp/plantillas/afip.png',$posx,	$posy, 27, 7.5);

		$pdf->SetX(1);
		$pdf->SetY($posy+8.5);

		$pdf->SetFont('helvetica','I', 10);
		$pdf->Multicell(60,3,$estado,0,'L');

		$pdf->SetX(1);
		$pdf->SetY($posy+12.5);
		$pdf->SetFont('helvetica','I', 6);
		$pdf->Multicell(60,3,'La Administración Federal no se responsabiliza por los','','L');

		$pdf->SetX(1);
		$pdf->SetY($posy+14.5);
		$pdf->SetFont('helvetica','N', 6);
		$pdf->Multicell(60,3,'datos ingresados en el detalle de la operación','','L');

		$pdf->SetX(1);
		$pdf->SetY($posy+18.5);
		$pdf->SetFont('helvetica','N', 6);
		$pdf->Multicell(60,3,$mensajecae,'','L');

		if ($object->ref_client)
		{

			$pdf->SetFont($this->posfac['pos_factu_refcli']['font'],$this->posfac['pos_factu_refcli']['style'],$this->posfac['pos_factu_refcli']['size']);
			$pdf->SetX(1);
			$pdf->SetY($posy+20.5);
			/*if ($conf->global->WSFE_FAC_PDF_PRINT_LABEL)*/ $label=  $outputlangs->transnoentities("RefCustomer")." : " ;

            $pdf->MultiCell($this->posfac['pos_factu_refcli']['w'],$this->posfac['pos_factu_refcli']['h'],$label. $outputlangs->convToOutputCharset($object->ref_client),0,$this->posfac['pos_factu_refcli']['alig']);
		}
		
		//$pdf->write1DBarcode($barras, 'I25', $posx,$posy+12,100,13, 0.4,$style, 'N');
		
		//FIN AFIP Barras y obs		


		// arreglo joni font pdf  (movi de lugar las cosas ahora imprime bien algo de los fonts es)
		// Nombre Empres

		$pdf->SetFont($this->posfac['pos_empresa_nom']['font'],$this->posfac['pos_empresa_nom']['style'],$this->posfac['pos_empresa_nom']['size']);
		$pdf->SetXY($this->posfac['pos_empresa_nom']['x'],$this->posfac['pos_empresa_nom']['y']);
		$pdf->MultiCell($this->posfac['pos_empresa_nom']['w'],$this->posfac['pos_empresa_nom']['h'],$outputlangs->convToOutputCharset($this->emetteur->name),0,$this->posfac['pos_empresa_nom']['alig']);


		//Cliente Nombre
		// On peut utiliser le nom de la societe du contact
			if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
				$thirdparty = $object->contact;
			} else {
				//$thirdparty = $object->thirdparty;  //version 3.8xx
				$thirdparty = $object->thirdparty;
			}



		$carac_client_name= pdfBuildThirdpartyName($thirdparty, $outputlangs);
		$pdf->SetFont($this->posfac['pos_cliente_nom']['font'],$this->posfac['pos_cliente_nom']['style'],$this->posfac['pos_cliente_nom']['size']);
		$pdf->SetXY($this->posfac['pos_cliente_nom']['x'],$this->posfac['pos_cliente_nom']['y']);
		$pdf->MultiCell($this->posfac['pos_cliente_nom']['w'],$this->posfac['pos_cliente_nom']['h'],'CLIENTE: ' . $carac_client_name,0,$this->posfac['pos_cliente_nom']['alig']);

        //Direccion Cliente
		
		$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->thirdparty,($usecontact?$object->contact:''),$usecontact,'target');
		$pdf->SetFont($this->posfac['pos_cliente_dom']['font'],$this->posfac['pos_cliente_dom']['style'],$this->posfac['pos_cliente_dom']['size']);
		$pdf->SetXY($this->posfac['pos_cliente_dom']['x'],$this->posfac['pos_cliente_dom']['y']);
		$pdf->MultiCell($this->posfac['pos_cliente_dom']['w'],$this->posfac['pos_cliente_dom']['h'],'DIRECCION: ' . $carac_client . ' - ' . $object->thirdparty->state,0,$this->posfac['pos_cliente_dom']['alig']);

		//Títulos de lista de items
		$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->thirdparty,($usecontact?$object->contact:''),$usecontact,'target');
		$pdf->SetFont($this->posfac['pos_cliente_dom']['font'],$this->posfac['pos_cliente_dom']['style'],8);
		$pdf->SetXY($this->posfac['pos_cliente_dom']['x'],$this->posfac['pos_cliente_dom']['y']+8);
		$pdf->MultiCell(70,1,'Cant. | Descripción           | Precio | Bonif. | Importe',0,$this->posfac['pos_cliente_dom']['alig']);
		
		//Cliente Provincia
		/*
		$pdf->SetFont($this->posfac['pos_cliente_prov']['font'],$this->posfac['pos_cliente_prov']['style'],$this->posfac['pos_cliente_prov']['size']);
		$pdf->SetXY($this->posfac['pos_cliente_prov']['x'],$this->posfac['pos_cliente_prov']['y']);
		$pdf->MultiCell($this->posfac['pos_cliente_prov']['w'],$this->posfac['pos_cliente_prov']['h'],'PROVINCIA: ' . $object->thirdparty->state,0,$this->posfac['pos_cliente_prov']['alig']);
		*/



    	//Referencia Factura para Nota de Credito
    	/*if ($object->type == 2)
		{
			$objectreplaced=new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);

            $pdf->SetFont($this->posfac['pos_factu_debit']['font'],$this->posfac['pos_factu_debit']['style'],$this->posfac['pos_factu_debit']['size']);
			$pdf->SetXY($this->posfac['pos_factu_debit']['x'],$this->posfac['pos_factu_debit']['y']);
			$pdf->MultiCell($this->posfac['pos_factu_debit']['w'],$this->posfac['pos_factu_debit']['h'], $outputlangs->transnoentities("CorrectionInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref),0,$this->posfac['pos_factu_debit']['alig']);
     	} */
			

		

		//Email Cliente
		//$pdf->SetFont($this->posfac['pos_cliente_email']['font'],$this->posfac['pos_cliente_email']['style'],$this->posfac['pos_cliente_email']['size']);
		//$pdf->SetXY($this->posfac['pos_cliente_email']['x'],$this->posfac['pos_cliente_email']['y']);
		/*if ($conf->global->WSFE_FAC_PDF_PRINT_LABEL)*/ $label= 'Email: ';
        //$pdf->MultiCell($this->posfac['pos_cliente_email']['w'],$this->posfac['pos_cliente_email']['h'],$label . $object->thirdparty->email,0,$this->posfac['pos_cliente_email']['alig']);

		//CUIT Cliente
		$pdf->SetFont($this->posfac['pos_cliente_cuit']['font'],$this->posfac['pos_cliente_cuit']['style'],$this->posfac['pos_cliente_cuit']['size']);
		$pdf->SetXY($this->posfac['pos_cliente_cuit']['x'],$this->posfac['pos_cliente_cuit']['y']);
		/*if ($conf->global->WSFE_FAC_PDF_PRINT_LABEL)*/ $label= 'CUIT/DNI: ';
		$pdf->MultiCell($this->posfac['pos_cliente_cuit']['w'],$this->posfac['pos_cliente_cuit']['h'],$label.$object->thirdparty->idprof1,0,$this->posfac['pos_cliente_cuit']['alig']);


		// Show list of linked objects
		//$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $this->posfac['pos_factu_remito']['x'], $this->posfac['pos_factu_remito']['y'], $this->posfac['pos_factu_remito']['w'], 3, $this->posfac['pos_factu_fec']['alig'], $this->posfac['pos_factu_remito']['size']);

		
		// arreglo joni font pdf

		// arreglo joni font pdf

		//Fecha de Inicio
		$pdf->SetFont($this->posfac['pos_empresa_incio']['font'],$this->posfac['pos_empresa_incio']['style'],$this->posfac['pos_empresa_incio']['size']);
		$pdf->SetXY(1,23);
		$pdf->MultiCell($this->posfac['pos_empresa_incio']['w'],$this->posfac['pos_empresa_incio']['h'],'Inicio Actividad: ' . $conf->global->MAIN_INFO_SOCIETE_FECHA_INICIO,0,$this->posfac['pos_empresa_incio']['alig']);


		// Direccion Empresa
		$carac_emetteur = explode("Correo:", pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty))[0];
		$carac_emetteur = explode("Teléfono:", $carac_emetteur)[0];
		$pdf->SetFont($this->posfac['pos_empresa_dom']['font'],$this->posfac['pos_empresa_dom']['style'],$this->posfac['pos_empresa_dom']['size']);
		$pdf->SetXY($this->posfac['pos_empresa_dom']['x'],$this->posfac['pos_empresa_dom']['y']);
		$pdf->MultiCell($this->posfac['pos_empresa_dom']['w'],$this->posfac['pos_empresa_dom']['h'], 'DIRECCION: ' . substr($carac_emetteur,0,-2) . ' - ' . $this->emetteur->state,0,$this->posfac['pos_empresa_dom']['alig']);
		
		
		
		// Provincia Empresa
		//$pdf->SetFont($this->posfac['pos_empresa_prov']['font'],$this->posfac['pos_empresa_prov']['style'],$this->posfac['pos_empresa_prov']['size']);
		//$pdf->SetXY($this->posfac['pos_empresa_prov']['x'],$this->posfac['pos_empresa_prov']['y']);
		//$pdf->MultiCell($this->posfac['pos_empresa_prov']['w'],$this->posfac['pos_empresa_prov']['h'], $this->emetteur->state,0,$this->posfac['pos_empresa_prov']['alig']);
		
		// Forma Juridica
		$formaJuridica = "";
		if($object->thirdparty->forme_juridique_code == 2301) {
			$formaJuridica = "MONOTRIBUTISTA";
		} else if($object->thirdparty->forme_juridique_code == 2302) {
			$formaJuridica = "SOCIEDAD CIVIL";
		} else if($object->thirdparty->forme_juridique_code == 2303) {
			$formaJuridica = "SOCIEDADES COMERCIALES";
		} else if($object->thirdparty->forme_juridique_code == 2304) {
			$formaJuridica = "SOCIEDADES DE HECHO";
		} else if($object->thirdparty->forme_juridique_code == 2305) {
			$formaJuridica = "SOCIEDADES IRREGULARES";
		} else if($object->thirdparty->forme_juridique_code == 2306) {
			$formaJuridica = "SOCIEDAD COLECTIVA";
		} else if($object->thirdparty->forme_juridique_code == 2307) {
			$formaJuridica = "SOCIEDAD EN COMANDITA SIMPLE";
		} else if($object->thirdparty->forme_juridique_code == 2308) {
			$formaJuridica = "SOCIEDAD DE CAPITAL E INDUSTRIA";
		} else if($object->thirdparty->forme_juridique_code == 2309) {
			$formaJuridica = "SOCIEDAD ACCIDENTAL O EN PARTICIPACION";
		} else if($object->thirdparty->forme_juridique_code == 2310) {
			$formaJuridica = "SOCIEDAD DE RESPONSABILIDAD LIMITADA";
		} else if($object->thirdparty->forme_juridique_code == 2311) {
			$formaJuridica = "SOCIEDAD ANONIMA";
		} else if($object->thirdparty->forme_juridique_code == 2312) {
			$formaJuridica = "SOCIEDAD ANONIMA CON PARTICIPACION ESTATAL";
		} else if($object->thirdparty->forme_juridique_code == 2313) {
			$formaJuridica = "SOCIEDAD EN COMANDITA POR ACCIONES";
		} else if($object->thirdparty->forme_juridique_code == 2314) {
			$formaJuridica = "RESPONSABLE INSCRIPTO";
		}


		//Forma Juridica Empresa
		$pdf->SetFont($this->posfac['pos_empresa_forme_juridique_code']['font'],$this->posfac['pos_empresa_forme_juridique_code']['style'],$this->posfac['pos_empresa_forme_juridique_code']['size']);
		$pdf->SetXY($this->posfac['pos_empresa_forme_juridique_code']['x'],$this->posfac['pos_empresa_forme_juridique_code']['y']);
		$pdf->MultiCell($this->posfac['pos_empresa_forme_juridique_code']['w'],$this->posfac['pos_empresa_forme_juridique_code']['h'], $formaJuridica,0,$this->posfac['pos_empresa_forme_juridique_code']['alig']);

		//IIBB Empresa
		$pdf->SetFont($this->posfac['pos_empresa_iibb']['font'],$this->posfac['pos_empresa_iibb']['style'],$this->posfac['pos_empresa_iibb']['size']);
		$pdf->SetXY($this->posfac['pos_empresa_iibb']['x'],$this->posfac['pos_empresa_iibb']['y']);
		$pdf->MultiCell($this->posfac['pos_empresa_iibb']['w'],$this->posfac['pos_empresa_iibb']['h'],'CUIT: ' . $this->emetteur->idprof1 . ' | IIBB: ' . $this->emetteur->idprof2,0,$this->posfac['pos_empresa_iibb']['alig']);

		// If BILLING contact defined on invoice, we use it
			$usecontact=false;
			$arrayidcontact=$object->getIdContact('external','BILLING');
			if (count($arrayidcontact) > 0)
			{
				$usecontact=true;
				$result=$object->fetch_contact($arrayidcontact[0]);
			}

		//IVA Cliente
		if ($object->thirdparty->typent_code == "A") {
			$id_impositivo = "Responsable Inscripto";
		}elseif ($object->thirdparty->typent_code == "B") {
			$id_impositivo = "Responsable no Inscripto";
		}elseif ($object->thirdparty->typent_code == "CF") {
			$id_impositivo = "Consumidor Final";
		}elseif ($object->thirdparty->typent_code == "EX") {
			$id_impositivo = "Exento";
		}

		//IVA Cliente
		/*If ($object->thirdparty->typent_code == "TE_A_RI") {
			$id_impositivo = "IVA RESPONSABLE INSCRIPTO";
		}elseif ($object->thirdparty->typent_code == "TE_B_RNI") {
			$id_impositivo = "IVA RESPONSABLE NO INSCRIPTO";
		}elseif ($object->thirdparty->typent_code == "TE_C_FE") {
			$id_impositivo = "IVA CONSUMIDOR FINAL";
		}elseif ($object->thirdparty->typent_code == "EX") {
			$id_impositivo = "IVA EXENTO";
		}*/

		$pdf->SetFont($this->posfac['pos_cliente_iva']['font'],$this->posfac['pos_cliente_iva']['style'],$this->posfac['pos_cliente_iva']['size']);
		$pdf->SetXY($this->posfac['pos_cliente_iva']['x'],$this->posfac['pos_cliente_iva']['y']);
		/*if ($conf->global->WSFE_FAC_PDF_PRINT_LABEL)*/ $label= 'Cond. Ante IVA: ';
        $pdf->MultiCell($this->posfac['pos_cliente_iva']['w'],$this->posfac['pos_cliente_iva']['h'],$label . $id_impositivo,0,$this->posfac['pos_cliente_iva']['alig']);


		

// hasta aca movi font pdf jonathan		

		return ($tab2_top + ($tab2_hl * $index));
	}

	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height=0, $nexY, $outputlangs, $hidetop=1, $hidebottom=0)
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;
		$hidetop=1;
		$default_font_size = pdf_getPDFFontSize($outputlangs);



		// Amount in (at tab_top - 1)
		// $pdf->SetTextColor(60,60,60);
		// $pdf->SetFont('','', $default_font_size - 2);

		if (empty($hidetop))
		{
			$titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentitiesnoconv("Currency".$conf->currency));
			$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top-4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			//$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
			if (! empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) $pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_droite-$this->marge_gauche, 5, 'F', null, explode(',',$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR));
		}

		// $pdf->SetDrawColor(128,128,128);
		// $pdf->SetFont('','', $default_font_size - 1);

		// Output Rect
		//$this->printRect($pdf,$this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, $hidetop, $hidebottom);	// Rect prend une longueur en 3eme param et 4eme param

		if (empty($hidetop))
		{
			$pdf->line($this->marge_gauche, $tab_top+5, $this->page_largeur-$this->marge_droite, $tab_top+5);	// line prend une position y en 2eme param et 4eme param

			$pdf->SetXY($this->posxdesc-1, $tab_top+1);
			$pdf->MultiCell(108,2, $outputlangs->transnoentities("Designation"),'','L');
		}

		if (! empty($conf->global->MAIN_GENERATE_INVOICES_WITH_PICTURE))
		{
		//	$pdf->line($this->posxpicture-1, $tab_top, $this->posxpicture-1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				//$pdf->SetXY($this->posxpicture-1, $tab_top+1);
				//$pdf->MultiCell($this->posxtva-$this->posxpicture-1,2, $outputlangs->transnoentities("Photo"),'','C');
			}
		}

		if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
		{
		//	$pdf->line($this->posxtva-1, $tab_top, $this->posxtva-1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxtva-3, $tab_top+1);
				$pdf->MultiCell($this->posxup-$this->posxtva+3,2, $outputlangs->transnoentities("VAT"),'','C');
			}
		}

		//$pdf->line($this->posxup-1, $tab_top, $this->posxup-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxup-1, $tab_top+1);
			$pdf->MultiCell($this->posxqty-$this->posxup-1,2, $outputlangs->transnoentities("PriceUHT"),'','C');
		}
		//$pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxqty-1, $tab_top+1);
			if($conf->global->PRODUCT_USE_UNITS)
			{
				$pdf->MultiCell($this->posxunit-$this->posxqty-1,2, $outputlangs->transnoentities("Qty"),'','C');
			}
			else
			{
				$pdf->MultiCell($this->posxdiscount-$this->posxqty-1,2, $outputlangs->transnoentities("Qty"),'','C');
			}
		}

		if($conf->global->PRODUCT_USE_UNITS) {
		//	$pdf->line($this->posxunit - 1, $tab_top, $this->posxunit - 1, $tab_top + $tab_height);
			if (empty($hidetop)) {
				$pdf->SetXY($this->posxunit - 1, $tab_top + 1);
				$pdf->MultiCell($this->posxdiscount - $this->posxunit - 1, 2, $outputlangs->transnoentities("Unit"), '',
					'C');
			}
		}

		//$pdf->line($this->posxdiscount-1, $tab_top, $this->posxdiscount-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			if ($this->atleastonediscount)
			{
				$pdf->SetXY($this->posxdiscount-1, $tab_top+1);
				$pdf->MultiCell($this->postotalht-$this->posxdiscount+1,2, $outputlangs->transnoentities("ReductionShort"),'','C');
			}
		}
		if ($this->atleastonediscount)
		{
		//	$pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
		}
		if (empty($hidetop))
		{
			$pdf->SetXY($this->postotalht-1, $tab_top+1);
			$pdf->MultiCell(30,2, $outputlangs->transnoentities("TotalHT"),'','C');
		}
	}
	
	//ENCABEZADO
	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  int         $intCopias      Variable que define copias //Catriel
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs,$intCopias=1)
	{
		global $conf, $langs;

		$outputlangs->load("main");
		$outputlangs->load("bills");
		$outputlangs->load("propal");
		$outputlangs->load("companies");

		$default_font_size = pdf_getPDFFontSize($outputlangs);
    	$label=''; //Print label check WSFE_FAC_PDF_PRINT_LABEL
		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		// Show Draft Watermark
		if ($object->statut == 0 && (!empty($conf->global->FACTURE_DRAFT_WATERMARK))) {
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->FACTURE_DRAFT_WATERMARK);
		}

		$posy = $this->marge_haute;
		$posx = $this->marge_gauche;

		// $pdf->SetTextColor(60, 60, 60);
		// $pdf->SetFont('', 'B', $default_font_size + 3);

		$w = 110;

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - $w;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo // Comentado por Manuel
		/*
		$logo = DOL_DOCUMENT_ROOT.'/wsfephp/plantillas/logo.png';
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
				// $height=pdf_getHeightForLogo($logo);
				$height=15;
				$pdf->Image($logo, $this->posfac['pos_empresa_logo']['x'], $this->posfac['pos_empresa_logo']['y'], 0, $this->posfac['pos_empresa_logo']['h']);	// width=0 (auto)
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B',$default_font_size - 2);
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}   
		}*/
		/*else
		{
			$text=$this->emetteur->name;
			$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}*/
		// Fin Comentado por Manuel

    
		
		//Tipo de Documento

		//$pdf->SetTextColor(60,60,60);
		if (!empty($this->wsfe->cbttipo)){
		    $codfact='COD.'.str_pad($this->wsfe->cbttipo,2,'0', STR_PAD_LEFT);
			$letfact=$this->tipos_fact[$this->wsfe->cbttipo];
		}else{
			$codfact='COD.'.str_pad('0',2,'0', STR_PAD_LEFT);
			$letfact='X';

		}

		if($codfact == 'COD.03') { //factura de abono A
			$prefijoTextoFactura = 'NOTA DE CRÉDITO ';
			$prefijoTextoFacturaAbr = 'NC';
		} else if ($codfact == 'COD.02') { //factura de rectificativa A
			$prefijoTextoFactura = 'NOTA DE DÉBITO ';
			$prefijoTextoFacturaAbr = 'ND';
		} else if($codfact == 'COD.07') { //factura abono B
			$prefijoTextoFactura = 'NOTA DE CRÉDITO ';
			$prefijoTextoFacturaAbr = 'NC';
		} else if ($codfact == 'COD.08') { //factura rectificativa B
			$prefijoTextoFactura = 'NOTA DE DÉBITO ';
			$prefijoTextoFacturaAbr = 'ND';
		}  else if($codfact == 'COD.12') { //factura abono C
			$prefijoTextoFactura = 'NOTA DE CRÉDITO ';
			$prefijoTextoFacturaAbr = 'NC';
		} else if ($codfact == 'COD.13') { //factura rectificativa C
			$prefijoTextoFactura = 'NOTA DE DÉBITO ';
			$prefijoTextoFacturaAbr = 'ND';
		} else if ($object->mode_reglement_code == "ECO") { //factura Proforma
			$prefijoTextoFactura = 'FACT. PROFORMA ';
			$prefijoTextoFacturaAbr = 'PROF';
		} else {
			$prefijoTextoFactura = 'FACTURA ';
			$prefijoTextoFacturaAbr = 'F';
		}

        if ($object->mode_reglement_code == "ECO") { //factura Proforma
		    $title= $prefijoTextoFactura;
        } else {
            $title= $prefijoTextoFactura . $letfact;
        }
		$pdf->SetFont($this->posfac['pos_factu_letra']['font'],$this->posfac['pos_factu_letra']['style'],$this->posfac['pos_factu_letra']['size']-2);
		$pdf->SetXY(1,$this->posfac['pos_factu_letra']['y']);
		$pdf->MultiCell($this->posfac['pos_factu_letra']['w'],$this->posfac['pos_factu_letra']['h'],$title,0,$this->posfac['pos_factu_letra']['alig']);

		$pdf->SetFont($this->posfac['pos_factu_cod']['font'],$this->posfac['pos_factu_cod']['style'],$this->posfac['pos_factu_cod']['size']);
		$pdf->SetXY(30,$this->posfac['pos_factu_cod']['y']);
		$pdf->MultiCell($this->posfac['pos_factu_cod']['w'],$this->posfac['pos_factu_cod']['h'],$codfact,0,$this->posfac['pos_factu_cod']['alig']);




		$title=$outputlangs->transnoentities("Invoice");
		if ($object->type == 1) $title=$outputlangs->transnoentities("InvoiceReplacement");
		if ($object->type == 2) $title=$outputlangs->transnoentities("InvoiceAvoir");
		if ($object->type == 3) $title=$outputlangs->transnoentities("InvoiceDeposit");
		if ($object->type == 4) $title=$outputlangs->transnoentities("InvoiceProFormat");

		/*
		$pdf->SetFont($this->posfac['pos_factu_cbte']['font'],$this->posfac['pos_factu_cbte']['style'],$this->posfac['pos_factu_cbte']['size']);
		$pdf->SetXY($this->posfac['pos_factu_cbte']['x'],$this->posfac['pos_factu_cbte']['y']);
		$pdf->MultiCell($this->posfac['pos_factu_cbte']['w'],$this->posfac['pos_factu_cbte']['h'],$title,0,$this->posfac['pos_factu_cbte']['alig']);
		*/
		
		// TODOMANU ver el harcode de abajo en caso que no sea factura (nc/nd)
		//$title= 'Factura ' . $letfact . ' N°'.str_pad($this->wsfe->puntodeventa, 4,"0",STR_PAD_LEFT) ."-".str_pad($this->wsfe->cbtnro, 8,"0",STR_PAD_LEFT);

        if ($object->mode_reglement_code == "ECO") { //factura Proforma
			$title= ' Nro: ' .str_pad($object->ref, 8,"0",STR_PAD_LEFT);
		} else {
		    $title= ' Nro: ' . $prefijoTextoFacturaAbr .  $letfact . '-' . str_pad($this->wsfe->puntodeventa, 4,"0",STR_PAD_LEFT) ."-".str_pad($this->wsfe->cbtnro, 8,"0",STR_PAD_LEFT);
		}
		
		
		
		$pdf->SetFont($this->posfac['pos_factu_nro']['font'],$this->posfac['pos_factu_nro']['style'],$this->posfac['pos_factu_nro']['size']);
		$pdf->SetXY($this->posfac['pos_factu_nro']['x'],$this->posfac['pos_factu_nro']['y']);
		$pdf->MultiCell($this->posfac['pos_factu_nro']['w'],$this->posfac['pos_factu_nro']['h'],$title,0,$this->posfac['pos_factu_nro']['alig']);
		
		$intPag=floor($pdf->PageNo()/$intCopias);
		//$intTotalPag=floor($pdf->PageNo());
		//$title=$this->copias[$intCopias].' Pagina: '.$intPag; esta linea es la orig, la de arriba estaba comentada
		$title=$this->copias[$intCopias];
		$pdf->SetFont($this->posfac['pos_factu_pag']['font'],$this->posfac['pos_factu_pag']['style'],$this->posfac['pos_factu_pag']['size']);
		$pdf->SetXY(1,$this->posfac['pos_factu_pag']['y']);
		$pdf->MultiCell($this->posfac['pos_factu_pag']['w'],$this->posfac['pos_factu_pag']['h'],$title,0,$this->posfac['pos_factu_pag']['alig']);
		

		/*$objectidnext=$object->getIdReplacingInvoice('validated');
		if ($object->type == 0 && $objectidnext)
		{
			$objectreplacing=new Facture($this->db);
			$objectreplacing->fetch($objectidnext);

			$posy+=3;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
		//	$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ReplacementByInvoice").' : '.$outputlangs->convToOutputCharset($objectreplacing->ref), '', 'R');
		}
		if ($object->type == 1)
		{
			$objectreplaced=new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);

			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
		//	$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ReplacementInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
		}
		if ($object->type == 2)
		{
			$objectreplaced=new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);

			$posy+=3;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
		//	$pdf->MultiCell($w, 3, $outputlangs->transnoentities("CorrectionInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
		}*/

		$pdf->SetFont($this->posfac['pos_factu_fec']['helvetica'],$this->posfac['pos_factu_fec']['style'],$this->posfac['pos_factu_fec']['size']);
		$pdf->SetXY($this->posfac['pos_factu_fec']['x'],$this->posfac['pos_factu_fec']['y']);
        /*if ($conf->global->WSFE_FAC_PDF_PRINT_LABEL)*/ $label= $outputlangs->transnoentities("DateInvoice")." : ";
		$pdf->MultiCell($this->posfac['pos_factu_fec']['w'],$this->posfac['pos_factu_fec']['h'], 'FECHA: ' . dol_print_date($object->date,"day",false,$outputlangs),0,$this->posfac['pos_factu_fec']['alig']);



		$pdf->SetFont($this->posfac['pos_empresa_nom']['font'],$this->posfac['pos_empresa_nom']['style'],$this->posfac['pos_empresa_nom']['size']);
		$pdf->SetXY($this->posfac['pos_empresa_nom']['x'],$this->posfac['pos_empresa_nom']['y']);
		$pdf->MultiCell($this->posfac['pos_empresa_nom']['w'],$this->posfac['pos_empresa_nom']['h'],$outputlangs->convToOutputCharset($this->emetteur->name),0,$this->posfac['pos_empresa_nom']['alig']);

		/*
		$title='Factura ' . $letfact . ' N°'.str_pad($this->wsfe->puntodeventa, 4,"0",STR_PAD_LEFT) ."-".str_pad($this->wsfe->cbtnro, 8,"0",STR_PAD_LEFT);
		$pdf->SetFont($this->posfac['pos_factu_nro']['font'],$this->posfac['pos_factu_nro']['style'],$this->posfac['pos_factu_nro']['size']);
		$pdf->SetXY($this->posfac['pos_factu_nro']['x'],$this->posfac['pos_factu_nro']['y']);
		$pdf->MultiCell($this->posfac['pos_factu_nro']['w'],$this->posfac['pos_factu_nro']['h'],$title,0,$this->posfac['pos_factu_nro']['alig']);
		*/

		if ($object->type != 2)
		{
			$pdf->SetFont($this->posfac['pos_factu_vto']['font'],$this->posfac['pos_factu_vto']['style'],$this->posfac['pos_factu_vto']['size']);
			$pdf->SetXY($this->posfac['pos_factu_vto']['x'],$this->posfac['pos_factu_vto']['y']);
            /*if ($conf->global->WSFE_FAC_PDF_PRINT_LABEL)*/ $label= $outputlangs->transnoentities("DateDue")." : ";
            
			$pdf->MultiCell($this->posfac['pos_factu_vto']['w'],$this->posfac['pos_factu_vto']['h'], $label. dol_print_date($object->date_lim_reglement,"day",false,$outputlangs,true),0,$this->posfac['pos_factu_fec']['alig']);

		}

	}

	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
	{
		$showdetails=0;
		//return pdf_pagefoot($pdf,$outputlangs,'FACTURE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,$showdetails,$hidefreetext);



		return;
	}


}

