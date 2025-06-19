<?php
/* Copyright (C) 2015 Catriel Rios <catrielr@gmail.com>
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

/*
    * Tabla de comprobantes

    * C�digo Descripci�n
    * 1 Facturas A
    * 2 Notas de D�bito A
    * 3 Notas de Cr�dito A
    * 4 Recibos A
    * 5 Notas de Venta al contado A
    * 6 Facturas B
    * 7 Notas de D�bito B
    * 8 Notas de Cr�dito B
    * 9 Recibos B
    * 10 Notas de Venta al contado B
    * 39 Otros comprobantes A que cumplan con la R.G. N� 3419
    * 40 Otros comprobantes B que cumplan con la R.G. N� 3419
    * 60 Cuenta de Venta y L�quido producto A
    * 61 Cuenta de Venta y L�quido producto B
    * 63 Liquidaci�n A
    * 64 Liquidaci�n B
    * 11: Factura C
    * 12: Nota de D�bito C
    * 13: Nota de Cr�dito C
    * 15: Recibo C 

    * 2. C�digos de tipo de documento

    * 80 - CUIT
    * 86 - CUIL
    * 87 - CDI
    * 89 - LE
    * 90 - LC
    * 91 - CI extranjera
    * 92 - en tr�mite
    * 93 - Acta nacimiento
    * 95 - CI Bs. As. RNP
    * 96 - DNI
    * 94 - Pasaporte
    * 00 - CI Polic�a Federal
    * 01 - CI Buenos Aires
    * 07 - CI Mendoza
    * 08 - CI La Rioja
    * 09 - CI Salta
    * 10 - CI San Juan
    * 11 - CI San Luis
    * 12 - CI Santa Fe
    * 13 - CI Santiago del Estero
    * 14 - CI Tucum�n
    * 16 - CI Chaco
    * 17 - CI Chubut
    * 18 - CI Formosa
    * 19 - CI Misiones
    * 20 - CI Neuqu�n
    * 20	CI Neuqu�n	
    * 21	CI La Pampa	
    * 22	CI R�o Negro
    * 23	CI Santa Cruz
    * 24	CI Tierra del Fuego
    * 99	Doc. (Otro)
    * 
*/

/**
 *	\file       htdocs/wsfe/actions_wsfe.inc.php
 *	\ingroup    facture
 *	\brief      Generacion de Factura Electronica
 */


require_once DOL_DOCUMENT_ROOT . '/wsfephp/class/wsaa_db.class.php';
require_once DOL_DOCUMENT_ROOT . '/wsfephp/class/wsfe_db.class.php';
require_once DOL_DOCUMENT_ROOT . '/wsfephp/exceptionhandler.php';
require_once DOL_DOCUMENT_ROOT . '/wsfephp/class/wsaa.class.php';
require_once DOL_DOCUMENT_ROOT . '/wsfephp/class/wsfev1.class.php';

function isCUIT( $cuit ) {
    $esCuit = false;
    $cuit_rearmado = "";
     //separo cualquier caracter que no tenga que ver con numeros
    for ($i = 0; $i < strlen($cuit); $i++) {
        if ((Ord(substr($cuit, $i, 1)) >= 48) && (Ord(substr($cuit, $i, 1)) <= 57)) {
            $cuit_rearmado = $cuit_rearmado . substr($cuit, $i, 1);
        }
    }
    $cuit = $cuit_rearmado;
    if ( strlen($cuit_rearmado) <> 11) {  // si to estan todos los digitos
        $esCuit = false;
    } else {
        $x = $i = $dv = 0;
        // Multiplico los d�gitos.
        $vec[0] = (substr($cuit, 0, 1)) * 5;
        $vec[1] = (substr($cuit, 1, 1)) * 4;
        $vec[2] = (substr($cuit, 2, 1)) * 3;
        $vec[3] = (substr($cuit, 3, 1)) * 2;
        $vec[4] = (substr($cuit, 4, 1)) * 7;
        $vec[5] = (substr($cuit, 5, 1)) * 6;
        $vec[6] = (substr($cuit, 6, 1)) * 5;
        $vec[7] = (substr($cuit, 7, 1)) * 4;
        $vec[8] = (substr($cuit, 8, 1)) * 3;
        $vec[9] = (substr($cuit, 9, 1)) * 2;
                    
        // Suma cada uno de los resultado.
        for( $i = 0; $i <= 9; $i++) {
            $x += $vec[$i];
        }
        $dv = (11 - ($x % 11)) % 11;
        if ($dv == (substr($cuit, 10, 1)) ) {
            $esCuit = true;
        }
    }
    return( $esCuit );
}

function wsfe_doli($db, $object, $outputlangs, $conf, $user, $idwarehouse) {
    //si esta validada sale
    if ($object->statut == 1) {
        header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id);
        $result = -1;
        return;
    }
 
    //para obtener alicuotas de IVA e impuestos locales
    $tva = array();
    $localtax1 = array();
    $localtax2 = array();
    $atleastoneratenotnull = 0;
    $atleastonediscount = 0;

    $wsaadb = new wsaa_db($db);
    $emisorcuit = str_replace("-", "", $conf->global->MAIN_INFO_SIREN);
    $wsaadb->entity = $_SESSION['dol_entity'];
    $wsaadb->fetch($emisorcuit);

    $wsfedb = new wsfe_db($db);
    $wsfedb->fk_facture = $object->id;
    $wsfedb->fetch($emisorcuit);

	//Fuerzo la fecha de la factura a la fecha de validacion
    $object->date = dol_now();
    $object->date_lim_reglement = $object->calculate_date_lim_reglement();

    /*
    * Tipos de Conceptos
    * 1 Producto
    * 2 Servicios
    * 3 Productos y Servicios
    */

	$nblignes = count($object->lines);				
    for ($i = 0; $i < $nblignes; $i++) {	
        if ($object->lines[$i]->product_type == 0) {
            $isproduct = true;
            $concepto = 1;
        }elseif ($object->lines[$i]->product_type == 1) {
            $isservice = true;
            $concepto = 2;
        }
    }
    if ($isproduct == true and $isservice == true ) $concepto = 3;

    if ($concepto != 1) {
        $fecha_venc_pago = date("Ymd",$object->date_lim_reglement); //Fechas del per�odo del servicio facturado (solo si concepto = 1?)	
        $fecha_serv_desde = date("Ymd",$object->date);
        $fecha_serv_hasta = date("Ymd",$object->date_lim_reglement);
    } else {
        $fecha_venc_pago = NULL;	
        $fecha_serv_desde = NULL;
        $fecha_serv_hasta = NULL;
    }

    //MANU
    if($object->multicurrency_code == "USD") {
        $imp_total = round(abs($object->multicurrency_total_ttc), 2); //"121.00" ;
    } else {
        $imp_total = round(abs($object->total_ttc), 2); //"121.00" ;
    }
    //FIN MANU

    //$imp_tot_conc = 0.00;  //fix localtax
    //$imp_neto = abs($object->total_ht); //"100.00"; //Fix Factura C
    //$imp_iva = abs($object->total_tva); //"21.00"; //Fix Factura C
    $imp_trib = 0.00; 
    //$imp_op_ex = 0.00; //Fix Factura C
    $fecha_cbte = date("Ymd", $object->date);

    /* Arreglo Manu */
    //$moneda_id = "PES";             # no utilizar DOL u otra moneda    
    //$moneda_ctz = 1.000;            # (deshabilitado por AFIP)
    
    //$moneda_id = "DOL";             # no utilizar DOL u otra moneda    
    //$moneda_ctz = 132.000;          # (deshabilitado por AFIP)

    if($object->multicurrency_code == "USD") {
        $moneda_id = "DOL";
        $moneda_ctz = number_format((1 / floatval($object->multicurrency_tx)),3,".",",");
    } else {
        $moneda_id = "PES";
        $moneda_ctz = 1.000;           # (deshabilitado por AFIP)
    }
    /* Fin Arreglo Manu */

    $obs_generales = $object->note_public; //"Observaciones Generales, texto libre";
    $obs_comerciales = $object->ref_client;

    /*
    if ($object->thirdparty->typent_code == "A") {
        $id_impositivo = "Responsable Inscripto";
    } elseif ($object->thirdparty->typent_code == "B") {
        $id_impositivo = "Responsable no Inscripto";
    } elseif ($object->thirdparty->typent_code == "CF") { 
        $id_impositivo = "Consumidor Final";
    } elseif ($object->thirdparty->typent_code == "EX") {
        $id_impositivo = "Exento";
    } 
    */

    $nombre_cliente     = $object->thirdparty->name;
    $domicilio_cliente  = $object->thirdparty->address;
    $localidad_cliente  = $object->thirdparty->town;
    $provincia_cliente  = $object->thirdparty->state;
    $zip_cliente        = $object->thirdparty->zip;
    
    $pais_dst_cmp = 200;             # c�digo para exportaci�n
    #$incoterms = "";
    #$idioma_cbte = 1 ;  # idioma para exportaci�n (no usado por el momento)
    #$descuento = 0;

        

    // Agrego tasas de IVA
	// Loop on each lines Linas de Productos
    $nblignes = count($object->lines);				
    for ($i = 0; $i < $nblignes; $i++) {
        //MANU
        if($object->multicurrency_code == "USD") {
            $tvaligne = $object->lines[$i]->multicurrency_total_tva;
        } else {
            $tvaligne = $object->lines[$i]->total_tva;
        }
        //FIN MANU

        $localtax1ligne = $object->lines[$i]->total_localtax1;
        $localtax2ligne = $object->lines[$i]->total_localtax2;
        
        //MANU
        if($object->multicurrency_code == "USD") {
            $basetvaligne = $object->lines[$i]->multicurrency_total_ht;
        } else {
            $basetvaligne = $object->lines[$i]->total_ht;
        }
        //FIN MANU

        if ($object->remise_percent) $tvaligne -= ($tvaligne * $object->remise_percent)/100;
        if ($object->remise_percent) $localtax1ligne -= ($localtax1ligne * $object->remise_percent)/100;
        if ($object->remise_percent) $localtax2ligne -= ($localtax2ligne * $object->remise_percent)/100;
        if ($object->remise_percent) $basetvaligne -= ($basetvaligne * $object->remise_percent)/100;
        
        $vatrate=(string) $object->lines[$i]->tva_tx;
        $localtax1rate=(string) $object->lines[$i]->localtax1_tx;
        $localtax2rate=(string) $object->lines[$i]->localtax2_tx;

        if (($object->lines[$i]->info_bits & 0x01) == 0x01) $vatrate .= '*';

        $tva[$vatrate][0] += abs($tvaligne);
        $localtax1[$localtax1rate] += abs($localtax1ligne);
        $localtax2[$localtax2rate] += abs($localtax2ligne);
        $tva[$vatrate][1] += abs($basetvaligne);
    }  

    // Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva

    /*
    * Alicuotas de IVA
    3 0%
    4 10.5%
    5 21%
    6 27%
    8 5%
    9 2.5% 
    */

    $i = 0;
    $regfeiva = array ();
    
    foreach ($tva as $tasa => $valor) {
        /*
        //MANU
        if($object->multicurrency_code == "USD") {
            $valor = $object->lines[$i]->multicurrency_total_tva;
        } else {
            $valor = $object->lines[$i]->total_tva;
        }
        //FIN MANU
        */

        if ($tasa == "0.00") {
            $regfeiva['AlicIva'][] = array (
                'Id'      => 3,
                'BaseImp' => round($valor[1],2), 
                'Importe' => round($valor[0],2),                     
            );
        } elseif ($tasa == "10.50") {
            $regfeiva['AlicIva'][] = array (
                'Id'      => 4,
                'BaseImp' => round($valor[1],2), 
                'Importe' => round($valor[0],2),
            );
        } elseif ($tasa == "21.000") {
            $regfeiva['AlicIva'][] = array (
                'Id'      => 5,
                'BaseImp' => round($valor[1],2), 
                'Importe' => round($valor[0],2),
            );
        } elseif ($tasa == "27.000") {
            $regfeiva['AlicIva'][] = array (
                'Id'      => 6,
                'BaseImp' => round($valor[1],2), 
                'Importe' => round($valor[0],2),
                );
                
        }elseif ($tasa == "5.000") {
            $regfeiva['AlicIva'][] = array (
                'Id'      => 8,
                'BaseImp' => round($valor[1],2), 
                'Importe' => round($valor[0],2),
            );
                
        } elseif ($tasa == "2.500") {
            $regfeiva['AlicIva'][] = array (
                'Id'      => 9,
                'BaseImp' => round($valor[1],2), 
                'Importe' => round($valor[0],2),
            );   
        }
        $i++;
    }


    //tipos de impuestos
        //1	Impuestos nacionales
        //2	Impuestos provinciales
        //3	Impuestos municipales
        //4	Impuestos Internos
        //99	Otro
    // Detalle de otros tributos
        //$regfetrib['Id'] = 1;
        //$regfetrib['Desc'] = 'impuesto';
        //$regfetrib['BaseImp'] = 0;
        //$regfetrib['Alic'] = 0;
        //$regfetrib['Importe'] = 0;

    $i = 0;
    $regfetrib = array();

    //MANU
    if($object->multicurrency_code == "USD") {
        $baseImp = round($object->multicurrency_total_ht, 2);
    } else {
        $baseImp = round($object->total_ht, 2);
    }
    //FIN MANU

    foreach ($localtax1 as $tasa => $valor) {
        $regfetrib['Tributo'][] = array(
            'Id'        => '2',
            'Desc'      => $outputlangs->transcountry("AmountLT1", $object->thirdparty->country_code),
            'BaseImp'   => $baseImp,
            'Alic'      => round($tasa, 2),
            'Importe'   => round($valor, 2),
        );
        $i++;
    }

    $i=0;
    foreach ($localtax2 as $tasa => $valor) {
        $regfetrib['Tributo'][] = array(
            'Id'        => '99',
            'Desc'      => $outputlangs->transcountry("AmountLT2", $object->thirdparty->country_code),
            'BaseImp'   => $baseImp,
            'Alic'      => round($tasa, 2),
            'Importe'   => round($valor, 2),
        );
        $i++;
    }

    $imp_tot_conc = round($object->total_localtax1 + $object->total_localtax2, 2);

    //Chequeo tipo empresa
    if ($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE != 2301) {  // No es Monotributista

        //MANU
        if($object->multicurrency_code == "USD") {
            $imp_iva = round(abs($object->multicurrency_total_tva), 2);
        } else {
            $imp_iva = round(abs($object->total_tva), 2);
        }
        //FIN MANU
        
        $imp_op_ex = 0.00;
        $imp_neto = abs($baseImp);
    
        // type of invoice (0=Standard invoice, 1=Replacement invoice, 2=Credit note invoice, 3=Deposit invoice) 
        if ($object->thirdparty->typent_code == "A") {
            $tipo_doc = 80;
            $nro_doc = (double)preg_replace('/[^0-9]/', '', $object->thirdparty->idprof1); //"20241952569"

            if ($object->type == 0) {
                $typeent    = "FA-";
                $tipo_cbte  = 1;
            } elseif ($object->type == 2) {
                $typeent    = "NCA-";
                $tipo_cbte  = 3;	
            } elseif ($object->type == 1) { //nota de debito A
                $typeent    = "NDA-";
                $tipo_cbte  = 2;			    
            }
        } else {
            if ($object->type == 0) {
                $typeent    = "FB-";
                $tipo_cbte  = 6;
            } elseif ($object->type == 2) {
                $typeent    = "NCB-";
                $tipo_cbte  = 8;
            } elseif ($object->type == 1) { //nota de debito B
                $typeent    = "NDB-";
                $tipo_cbte  = 7;						
            }	 
        }
    } else { //factura C
        $imp_iva        = 0.00; //no se informa imp_iva para Factura C
        $imp_op_ex      = 0.00;
        $imp_neto       = $imp_total;
        $imp_tot_conc   = 0;
        
        unset($regfeiva);
        unset($regfetrib);
        
        if ($object->type == 0) {
            $typeent    = "FC-";
            $tipo_cbte  = 11;
        } elseif ($object->type == 2) {
            $typeent    = "NCC-";
            $tipo_cbte  = 13;
        } elseif ($object->type == 1) { //nota de debito C
            $typeent    = "NDC-";
            $tipo_cbte  = 12;						
        }
    }

    //Verifico que documento informmar.

    if($tipo_cbte == 6 || $tipo_cbte == 7 || $tipo_cbte == 8 ||  $tipo_cbte == 11 ||  $tipo_cbte == 13 ||  $tipo_cbte == 12) { 
        $nro_doc = (double)preg_replace('/[^0-9]/', '', $object->thirdparty->idprof1); //"20241952569"
    
        if (strlen($nro_doc) == 8) {  //es DNI
            $tipo_doc = 96;
        } elseif (strlen($nro_doc) == 11) { //es cuit
            $tipo_doc = 80;
        } else { //consumidor final
            $tipo_doc = 99;
            $nro_doc = 0; //"20241952569"
        }
    }
    
    //WSFEPHP

    //MANU PUNTO DE VENTA DOLIPOS
    if(isset($object->pos_source) && !empty($object->pos_source)) {
        $varGlobalPosSource = "DOLIPOS_PTOVTA" . $object->pos_source;
        $ptovta = $conf->global->$varGlobalPosSource; //Punto de Venta
    } else {
        $ptovta = (int)$wsaadb->puntodeventa; //Punto de Venta
    }
    //FINMANU

    //AGREGADO MANU INFORMACION DE COMPROBANTES ASOCIADOS A ENVIAR A AFIP
    if($object->fk_facture_source > 0) {
        $facture_source = new Facture($db);
        $facture_source->fetch($object->fk_facture_source);

        $wsfedbfactureasoc = new wsfe_db($db);
        $wsfedbfactureasoc->fk_facture = $object->fk_facture_source;
        $wsfedbfactureasoc->fetch();

        //traer de wsaa segun id de factura wsaa fk_facture
        $ptoVtaAsoc     = $wsfedbfactureasoc->puntodeventa; //Punto de Venta
        $tipoCbteAsoc   = $wsfedbfactureasoc->cbttipo;      //Tipo Factura Asociada
        $nroCbteAsoc    = $wsfedbfactureasoc->cbtnro;       //Numero de comprobante de Factura Asociada 
        $fechaCbteAsoc  = dol_print_date($facture_source->date, '%Y%m%d'); //Fecha de comprobante de Factura Asociada 

        //dol_syslog("MANUEL: FK_F_S: " . $facture_source->id . " fecha: " . dol_print_date($facture_source->date, '%Y%m%d') . " pv: " . $ptoVtaAsoc. " cbtt: " . $tipoCbteAsoc. " cbtn: " . $nroCbteAsoc, LOG_DEBUG);
    }
    //FIN AGREGADO MANU

    $regfe['CbteTipo']      = $tipo_cbte;
    $regfe['Concepto']      = $concepto;
    $regfe['DocTipo']       = $tipo_doc;            //80 = CUIL
    $regfe['DocNro']        = $nro_doc;
    //$regfe['CbteDesde']   = $cbte; 	            // nro de comprobante desde (para cuando es lote)
    //$regfe['CbteHasta']   = $cbte;	            // nro de comprobante hasta (para cuando es lote)
    $regfe['CbteFch']       = $fecha_cbte; 	        // fecha emision de factura
    $regfe['ImpNeto']       = $imp_neto;			// neto gravado
    $regfe['ImpTotConc']    = $imp_tot_conc;		// no gravado
    $regfe['ImpIVA']        = $imp_iva;			    // IVA liquidado
    $regfe['ImpTrib']       = $imp_trib;			// otros tributos
    $regfe['ImpOpEx']       = $imp_op_ex;			// operacion exentas
    $regfe['ImpTotal']      = $imp_total;			// total de la factura. ImpNeto + ImpTotConc + ImpIVA + ImpTrib + ImpOpEx
    $regfe['FchServDesde']  = $fecha_serv_desde;    // solo concepto 2 o 3
    $regfe['FchServHasta']  = $fecha_serv_hasta;	// solo concepto 2 o 3
    $regfe['FchVtoPago']    = $fecha_venc_pago;		// solo concepto 2 o 3
    $regfe['MonId']         = $moneda_id; 			// Id de moneda 'PES'
    $regfe['MonCotiz']      = $moneda_ctz;			// Cotizacion moneda. Solo exportacion

    // Comprobantes asociados (solo notas de crédito y débito):
    $regfeasoc = array();

    //TODO MANU ptovta asociado a la terminal
    /*
    $regfeasoc['Tipo'] = 1; //91; //tipo 91|5			
    $regfeasoc['PtoVta'] = $ptovta;
    $regfeasoc['Nro'] = 3;
    $regfeasoc['Cuit'] = $nro_doc;
    $regfeasoc['CbteFch'] = "20211227";
    */
    //FIN TODO MANUEL

    //$regfeasoc = ["cuit"=>$nro_doc, "fecha"=>"20211227", "nro"=>3, "pto_vta"=>6,"tipo"=>1];

    if($object->fk_facture_source > 0) {
        $regfe['CbtesAsoc'] = [
            'CbteAsoc' => [
                'Tipo'      => (int)$tipoCbteAsoc,
                'PtoVta'    => (int)$ptoVtaAsoc,
                'Nro'       => (int)$nroCbteAsoc,
                'Cuit'      => $nro_doc,
                'CbteFch'   => $fechaCbteAsoc
            ]
        ];
    }

    // Detalle de otros tributos
    //$regfetrib['Id'] = 1; 			
    //$regfetrib['Desc'] = 'impuesto';
    //$regfetrib['BaseImp'] = 0;
    //$regfetrib['Alic'] = 0; 
    //$regfetrib['Importe'] = 0; 			

    // Detalle de iva
    //$regfeiva['Id'] = 4; 
    //$regfeiva['BaseImp'] = 35.76; 
    //$regfeiva['Importe'] = 3.75;

    ///----



    /*****************
    //WSAA
    ****************/
    $wsaa = new WSAA($wsaadb);
    //date('c',date('U')+60)
    //if($wsaa->get_expiration() < date("Y-m-d h:m:i")) {
    if($wsaa->get_expiration() < date('c',date('U'))) {
        if ($wsaa->generar_TA()) {
            //	echo '<br>Nuevo TA';
        } else {
            setEventMessage('Error al obtener le TA' , 'errors');
            echo '<br>Error al obtener el TA';
        }
    } else {
        //echo '<br>TA expiration:' . $wsaa->get_expiration();
    }


    /*****************
    //WSFEV1
    ****************/
    $wsfev1 = new WSFEV1($wsaadb);
    
    // Carga el archivo TA.xml
    $wsfev1->openTA();

    // Obtener el �ltimo n�mero para este tipo de comprobante / punto de venta:
    $nro = $wsfev1->FECompUltimoAutorizado($ptovta, $tipo_cbte);
    //if($nro == "") {
    //    setEventMessage('Error al obtener el ultimo numero autorizado '.'Code: '.$wsfev1->Code.' Msg: '.$wsfev1->Msg.' Obs: '.$wsfev1->ObsCode. ' Msg: '.$wsfev1->ObsMsg , 'errors');
    //    $result = -1;
    //} else {
    $nro1 = $nro + 1;
    //}

    //text catriel

    //require_once DOL_DOCUMENT_ROOT ."/wsfephp/class/array2xml.class.php";
    //$xml = Array2XML::createXML('Iva', $regfeiva);
    //echo $xml->saveXML();

    $cae = $wsfev1->FECAESolicitar(
        $nro1,      // ultimo numero de comprobante autorizado mas uno 
        $ptovta,    // el punto de venta
        $regfe,     // los datos a facturar
        $regfeasoc,
        $regfetrib,
        $regfeiva              
    );

    if($cae == false || $cae['cae'] <= 0) {
        setEventMessage('Error al obtener CAE '.'Code: '.$wsfev1->Code.' '.$wsfev1->Msg.' '.$wsfev1->ObsCode. ' Msg: '.$wsfev1->ObsMsg. ' MANUPtoVta: '.$ptovta , 'errors');
        $result = -1;	
    } else {
        $caenum = $cae['cae'];
        $caefvt = $cae['fecha_vencimiento'];
        setEventMessage('CAE '.$caenum.' Vencimiento: '.$caefvt. ' MANUPtoVta: '.$ptovta);
        $result = 1;
    }

    # Verifico que no haya rechazo o advertencia al generar el CAE
	if ($result >= 0) {	
	    $numref = $typeent . str_pad($ptovta, 4,"0",STR_PAD_LEFT) ."-".str_pad($nro1, 8,"0",STR_PAD_LEFT); //numero de factura electronica
  		$result = $object->validate($user, $numref, $idwarehouse); //VALIDO FACTURA

		 //Grabo datos en WSFE_DB
        $wsfedb->cae            = $caenum;
        $wsfedb->caevto         = $caefvt;
        $wsfedb->obs            = $wsfev1->ObsCode." ".$wsfev1->ObsMsg;
        $wsfedb->puntodeventa   = $ptovta;
        $wsfedb->cbtnro         = $nro1;
        $wsfedb->fk_facture     = $object->id;
        $wsfedb->version        = "php2";
        $wsfedb->entity_id      = $_SESSION['dol_entity'];

        if($object->multicurrency_code == "USD") {
            $wsfedb->divisa = "DOL";
        } else {
            $wsfedb->divisa = "PES";
        }

        $wsfedb->concepto       = $regfe['concepto'];
        $wsfedb->cbttipo        = $regfe['CbteTipo'];
        $wsfedb->cuitemisor     = $wsaadb->emisorcuit;
        $wsfedb->xmlrequest     = ""; //$WSFE->XmlRequest;
        $wsfedb->xmlresponse    = ""; //$WSFE->XmlResponse;                
        $wsfedb->errc           = $wsfev1->Code;
        $wsfedb->errm           = $wsfev1->error;

        $ret = $wsfedb->update();

		//Actualiza datos en factura
        $object->update($user);
        $object->fetch($id);
        $object->fetch_thirdparty();
    } else {
        if (count($object->errors)) setEventMessage($object->error, 'errors');
    }
	
	header('Location: ' . $_SERVER["PHP_SELF"] . '?facid=' . $object->id);
    $result = -1;
}				
?>