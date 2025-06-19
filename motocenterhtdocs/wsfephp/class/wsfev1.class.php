<?php
/*
 * Argentina Electronic Invoice module for Dolibarr
 * 2015 Pablo <pablin.php@gmail.com>
 * Copyright (C) 2017 Catriel Rios <catriel_r@hotmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


include_once DOL_DOCUMENT_ROOT . '/wsfephp/class/wsaa_db.class.php';


class WSFEV1 {
	//const CUIT 	= 20267565393;                 		# CUIT del emisor de las facturas. Solo numeros sin comillas.
  	const TA 	= "xml/TA.xml";        				# Archivo con el Token y Sign
	//https://wswhomo.afip.gov.ar/wsfev1/service.asmx // Funciones
	//https://wswhomo.afip.gov.ar/wsfev1/service.asmx?WSDL // para obtener WSDL
	const WSDL = "wsfev1.wsdl";                   	# The WSDL corresponding to WSFEV1	
	const LOG_XMLS = true;                     		# For debugging purposes
	const WSFEURL = "https://wswhomo.afip.gov.ar/wsfev1/service.asmx"; // homologacion wsfev1 (testing)
	//const WSFEURL = "?????????/wsfev1/service.asmx"; // produccion  


	/*
	* manejo de errores
	*/
	public $error = '';
	public $ObsCode = '';
	public $ObsMsg = '';
	public $Code = '';
	public $Msg = '';
	/**
	* Cliente SOAP
	*/
	private $client;
  
	/*
	* objeto que va a contener el xml de TA
	*/
	private $TA;
    var $wsaadb;

	/*
	* Constructor
	*/
	public function __construct($wsaadb)
	{
		if ($wsaadb->modo !=1) { //modo =1 produccion
			$this->url = 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx?WSDL'; //HOMO
		}else{
			$this->url = $wsaadb->wsfeprod;
		};

	$this->path = DOL_DOCUMENT_ROOT.'/wsfephp/';
	$this->cuitemisor = (float)$wsaadb->emisorcuit;
    // seteos en php
    ini_set("soap.wsdl_cache_enabled", "0");

    // validar archivos necesarios
        if (!file_get_contents($this->url)) $this->error .= " Failed to open ".$this->url; //chequea la url

		if(!empty($this->error)) {
			return ($this->error);
		}



    $this->client = new SoapClient($this->url, array(
				'soap_version' => SOAP_1_2,
				'location'     => $this->url,
				'exceptions'   => 0,
				'trace'        => 1)
    );


	}



	/*
	* Chequea los errores en la operacion, si encuentra algun error falta lanza una exepcion
	* si encuentra un error no fatal, loguea lo que paso en $this->error
	*/
	private function _checkErrors($results, $method)
	{
    if (self::LOG_XMLS){
		file_put_contents($this->path."xml/request-".$method.".xml",$this->client->__getLastRequest());
		file_put_contents($this->path."xml/response-".$method.".xml",$this->client->__getLastResponse());
    }
    
    if (is_soap_fault($results)) {
		throw new Exception('WSFE class. FaultString: ' . $results->faultcode.' '.$results->faultstring);
    }
    
    if ($method == 'FEDummy') {return;}
    
    $XXX=$method.'Result';
	if ($results->$XXX->Errors->Err->Code != 0) {
		$this->error = "Method=$method errcode=".$results->$XXX->Errors->Err->Code." errmsg=".$results->$XXX->Errors->Err->Msg;
    }
    	
	
	//asigna error a variable
	if ($method == 'FECAESolicitar') {
		if ($results->$XXX->FeDetResp->FECAEDetResponse->Observaciones->Obs->Code){	
			$this->ObsCode = $results->$XXX->FeDetResp->FECAEDetResponse->Observaciones->Obs->Code;
			$this->ObsMsg = $results->$XXX->FeDetResp->FECAEDetResponse->Observaciones->Obs->Msg;
		}
		
		//if ($results->$XXX->FeDetResp->FECAEDetResponse->Observaciones->Obs[0]->Code){	
		//	$this->ObsCode = $results->$XXX->FeDetResp->FECAEDetResponse->Observaciones->Obs[0]->Code;
		//	$this->ObsMsg = $results->$XXX->FeDetResp->FECAEDetResponse->Observaciones->Obs[0]->Msg;
		//}		
	}
	$this->Code = $results->$XXX->Errors->Err->Code;
    $this->Msg = $results->$XXX->Errors->Err->Msg;	
	//fin asigna error a variable
		
	return $results->$XXX->Errors->Err->Code != 0 ? true : false;
	}



	/**
	* Abre el archivo de TA xml,
	* si hay algun problema devuelve false
	*/
	public function openTA()
	{
	$this->TA = simplexml_load_file($this->path.self::TA);

	return $this->TA == false ? false : true;
	}
  
	/*
	* Retorna el ultimo número autorizado.
	*/ 
	public function FECompUltimoAutorizado($ptovta, $tipo_cbte)
	{
	$results = $this->client->FECompUltimoAutorizado(
		array('Auth'=>array('Token' => $this->TA->credentials->token,
							'Sign' => $this->TA->credentials->sign,
							'Cuit' => $this->cuitemisor),
			'PtoVta' => $ptovta,
			'CbteTipo' => $tipo_cbte));
			
    $e = $this->_checkErrors($results, 'FECompUltimoAutorizado');
	
    return $e == false ? $results->FECompUltimoAutorizadoResult->CbteNro : false;
	} //end function FECompUltimoAutorizado


	/*
	* Retorna el estado de servidores.
	*/
	public function FEDummy()
	{
		$results = $this->client->FEDummy();

		$e = $this->_checkErrors($results, 'FEDummy');

		return $e == false ? $results->FEDummyResult : false;
	} //end function FEDummy


	/*
	* Retorna el ultimo comprobante autorizado para el tipo de comprobante /cuit / punto de venta ingresado.
	*/ 
	public function recuperaLastCMP ($ptovta, $tipo_cbte)
	{
	$results = $this->client->FERecuperaLastCMPRequest(
		array('argAuth' =>  array('Token' => $this->TA->credentials->token,
								'Sign' => $this->TA->credentials->sign,
								'cuit' => $this->cuitemisor),
			'argTCMP' => array('PtoVta' => $ptovta,
								'TipoCbte' => $tipo_cbte)));
	$e = $this->_checkErrors($results, 'FERecuperaLastCMPRequest');
	
	return $e == false ? $results->FERecuperaLastCMPRequestResult->cbte_nro : false;
	} //end function recuperaLastCMP

	/*
* Retorna comprobantes autorizados.
*/
	public function FECompConsultar ($tipo_cbte, $cbte_nro, $ptovta)
	{
		$results = $this->client->FECompConsultar(
			array(
				'Auth' =>
					array( 'Token' => $this->TA->credentials->token,
						'Sign' => $this->TA->credentials->sign,
						'Cuit' => $this->cuitemisor),
				'FeCompConsReq' =>
					array(
						'PtoVta' => $ptovta,
						'CbteTipo' => $tipo_cbte,
						'CbteNro' => $cbte_nro)));
		$e = $this->_checkErrors($results, 'FeCompConsReq');

		return $e == false ? $results->FECompConsultarResult: false;
	} //end function FECompConsultar



	/*
	* Solicitud CAE y fecha de vencimiento 
	*/	
	public function FECAESolicitar($cbte, $ptovta, $regfe, $regfeasoc, $regfetrib, $regfeiva)
	{
		$params = array( 
			'Auth' => 
			array( 'Token' => $this->TA->credentials->token,
					'Sign' => $this->TA->credentials->sign,
					'Cuit' => $this->cuitemisor),
			'FeCAEReq' => 
			array( 'FeCabReq' => 
				array( 'CantReg' => 1,
						'PtoVta' => $ptovta,
						'CbteTipo' => $regfe['CbteTipo'] ),
			'FeDetReq' => 
			array( 'FECAEDetRequest' => 
				array( 'Concepto' => $regfe['Concepto'],
						'DocTipo' => $regfe['DocTipo'],
						'DocNro' => $regfe['DocNro'],
						'CbteDesde' => $cbte,
						'CbteHasta' => $cbte,
						'CbteFch' => $regfe['CbteFch'],
						'ImpNeto' => $regfe['ImpNeto'],
						'ImpTotConc' => $regfe['ImpTotConc'], 
						'ImpIVA' => $regfe['ImpIVA'],
						'ImpTrib' => $regfe['ImpTrib'],
						'ImpOpEx' => $regfe['ImpOpEx'],
						'ImpTotal' => $regfe['ImpTotal'], 
						'FchServDesde' => $regfe['FchServDesde'], //null
						'FchServHasta' => $regfe['FchServHasta'], //null
						'FchVtoPago' => $regfe['FchVtoPago'], //null
						'MonId' => $regfe['MonId'], //PES 
						'MonCotiz' => $regfe['MonCotiz'], //1 
						//'Tributos' => 
						//	array( 'Tributo' => 
						//		array ( 'Id' =>  $regfetrib['Id'], 
						//				'Desc' => $regfetrib['Desc'],
						//				'BaseImp' => $regfetrib['BaseImp'], 
						//				'Alic' => $regfetrib['Alic'], 
						//				'Importe' => $regfetrib['Importe'] ),
						//		), 
						'Iva' => 
							//array ( 'AlicIva' => 
							//	array ( 'Id' => $regfeiva['Id'], 
							//			'BaseImp' => $regfeiva['BaseImp'], 
							//			'Importe' => $regfeiva['Importe'] ),
							$regfeiva,//),	 
						
						
						// Agregado por Manuel
						'CbtesAsoc' => $regfe['CbtesAsoc']
						//TODO MANU este funciona!
						/*'CbtesAsoc' => [ 'CbteAsoc' => ['Tipo' => 1,
							'PtoVta' => 6,
							'Nro' => 3,
							'Cuit' => $regfe['DocNro'],
							'CbteFch' => "20211227"
							//'CbteFch' => intval(date('Ymd', strtotime($fact_asoc->fecha))
						]],*/
						//Fin Agregado por Manuel
					),
				), 
			), 
		);

		require_once DOL_DOCUMENT_ROOT ."/wsfephp/class/array2xml.class.php";
		$xml = Array2XML::createXML('FECAESolicitar', $params);
		file_put_contents(DOL_DOCUMENT_ROOT ."/wsfephp/xml/request-params.xml",$xml->saveXML());

		
		$results = $this->client->FECAESolicitar($params);

		$e = $this->_checkErrors($results, 'FECAESolicitar');
		
		//asigno respuesta 
		$resp_cae = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAE;
		$resp_caefvto = $results->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAEFchVto;

		return $e == false ? Array( 'cae' => $resp_cae, 'fecha_vencimiento' => $resp_caefvto ): false;
		} //end function FECAESolicitar
		
	} // class

?>
