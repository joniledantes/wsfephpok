<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2013      Juanjo Menent		<jmenent@2byte.es>
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
 *	\file       htdocs/core/modules/facture/mod_facture_wsfe.php
 *	\ingroup    facture
 *	\brief      Clase de modelo de numeracion para Factuea Electronica
 */
require_once DOL_DOCUMENT_ROOT .'/core/modules/facture/modules_facture.php';

/**
 *	\class      mod_facture_wsfe
 *	\brief      Clase de modelo de numeracion para Factuea Electronica
 */
class mod_facture_wsfe extends ModeleNumRefFactures
{
    var $version='dolibarr';		// 'development', 'experimental', 'dolibarr'
    var $error = '';


    /**
     *  Renvoi la description du modele de numerotation
     *
     *  @return     string      Texte descripif
     */
    function info()
    {
        global $conf,$langs;

        $langs->load("bills");

        $form = new Form($this->db);

        $texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
        $texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        $texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        $texte.= '<input type="hidden" name="action" value="updateMask">';
        $texte.= '<input type="hidden" name="maskconstinvoice" value="FACTURE_MERCURE_MASK_INVOICE">';
        $texte.= '<input type="hidden" name="maskconstreplacement" value="FACTURE_MERCURE_MASK_REPLACEMENT">';
        $texte.= '<input type="hidden" name="maskconstcredit" value="FACTURE_MERCURE_MASK_CREDIT">';
		$texte.= '<input type="hidden" name="maskconstdeposit" value="FACTURE_MERCURE_MASK_DEPOSIT">';
        $texte.= '<table class="nobordernopadding" width="100%">';

        $tooltip=$langs->trans("GenericMaskCodes",$langs->transnoentities("Invoice"),$langs->transnoentities("Invoice"));
        $tooltip.=$langs->trans("GenericMaskCodes2");
        $tooltip.=$langs->trans("GenericMaskCodes3");
        $tooltip.=$langs->trans("GenericMaskCodes4a",$langs->transnoentities("Invoice"),$langs->transnoentities("Invoice"));
        $tooltip.=$langs->trans("GenericMaskCodes5");

        // Parametrage du prefix
        $texte.= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("InvoiceStandard").'):</td>';
        $texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskinvoice" value="'.$conf->global->FACTURE_MERCURE_MASK_INVOICE.'">',$tooltip,1,1).'</td>';

        $texte.= '<td align="left" rowspan="3">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';

        $texte.= '</tr>';

        // Parametrage du prefix des replacement
        $texte.= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("InvoiceReplacement").'):</td>';
        $texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskreplacement" value="'.$conf->global->FACTURE_MERCURE_MASK_REPLACEMENT.'">',$tooltip,1,1).'</td>';
        $texte.= '</tr>';
        
        // Parametrage du prefix des avoirs
        $texte.= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("InvoiceAvoir").'):</td>';
        $texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskcredit" value="'.$conf->global->FACTURE_MERCURE_MASK_CREDIT.'">',$tooltip,1,1).'</td>';
        $texte.= '</tr>';

        // Parametrage du prefix des acomptes
        $texte.= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("InvoiceDeposit").'):</td>';
        $texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskdeposit" value="'.$conf->global->FACTURE_MERCURE_MASK_DEPOSIT.'">',$tooltip,1,1).'</td>';
        $texte.= '</tr>';

        $texte.= '</table>';
        $texte.= '</form>';

        return $texte;
    }

    /**
     *  Return an example of number value
     *
     *  @return     string      Example
     */
    function getExample()
    {
        global $conf,$langs,$mysoc;

        $old_code_client=$mysoc->code_client;
        $old_code_type=$mysoc->typent_code;
        $mysoc->code_client='CCCCCCCCCC';
        $mysoc->typent_code='TTTTTTTTTT';
        $numExample = $this->getNextValue($mysoc,'');
        $mysoc->code_client=$old_code_client;
        $mysoc->typent_code=$old_code_type;

        if (! $numExample)
        {
            $numExample = 'NotConfigured';
        }
        return $numExample;
    }

    /**
     * Return next value
     *
     * @param	Societe		$objsoc     Object third party
     * @param   Facture		$facture	Object invoice
     * @param   string		$mode       'next' for next value or 'last' for last value
     * @return  string      			Value if OK, 0 if KO
     */
    function getNextValue($objsoc,$facture,$mode='next')
    {
        global $db,$conf;

        require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        

        // Get Mask value
        $mask = '';
        if (is_object($facture) && $facture->type == 1) 
        {
        	$mask=$conf->global->FACTURE_MERCURE_MASK_REPLACEMENT;
        	if (! $mask)
        	{
        		$mask=$conf->global->FACTURE_MERCURE_MASK_INVOICE;
        	}
        }
        else if (is_object($facture) && $facture->type == 2) $mask=$conf->global->FACTURE_MERCURE_MASK_CREDIT;
		else if (is_object($facture) && $facture->type == 3) $mask=$conf->global->FACTURE_MERCURE_MASK_DEPOSIT;
        else $mask=$conf->global->FACTURE_MERCURE_MASK_INVOICE;
        if (! $mask)
        {
            $this->error='NotConfigured';
            return 0;
        }

        $where='';
        //if ($facture->type == 2) $where.= " AND type = 2";
        //else $where.=" AND type != 2";
      
      //WSFE  
      	   if ($facture->modelpdf == 'fe' && $facture->mode_reglement_code!="ECO")   //agregar ExtraField en fatura llamado fe
         {  
          $numFinal="FacturaElectronica";	
         }
     else
        {
      
      	if ($facture->client->typent_code=="A")
            {
        	$mask="FA-".$conf->global->FACTURE_MERCURE_MASK_INVOICE;  
            }
            elseif  ($facture->client->typent_code=="B")
            { 
            $mask="FB-".$conf->global->FACTURE_MERCURE_MASK_INVOICE;  
            }else{
            $mask="FC-".$conf->global->FACTURE_MERCURE_MASK_INVOICE;            
            }
          
        if ($facture->mode_reglement_code=="ECO") $mask="FX-".$conf->global->FACTURE_MERCURE_MASK_INVOICE;	
        
        
        if (! $mask)
        {
            $this->error='NotConfigured';
            return 0;
        }
  
        $numFinal=get_next_value($db,$mask,'facture','ref',$where,$objsoc,$facture->date,$mode);
        if (! preg_match('/([0-9])+/',$numFinal)) $this->error = $numFinal;
    	
        }
        return  $numFinal;
    }


    /**
     * Return next free value
     *
     * @param	Societe		$objsoc     	Object third party
     * @param	string		$objforref		Object for number to search
     * @param   string		$mode       	'next' for next value or 'last' for last value
     * @return  string      				Next free value
     */
    function getNumRef($objsoc,$objforref,$mode='next')
    {
        return $this->getNextValue($objsoc,$objforref,$mode);
    }

}
