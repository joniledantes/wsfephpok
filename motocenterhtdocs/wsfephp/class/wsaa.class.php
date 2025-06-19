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

class WSAA {

	//const CERT = "keys/reingart.crt";        	# The X.509 certificate in PEM format. Importante setear variable $path
	//const PRIVATEKEY = "keys/reingart.key";  	# The private key correspoding to CERT (PEM). Importante setear variable $path
	const PASSPHRASE = "";         				# The passphrase (if any) to sign
	const PROXY_ENABLE = false;
	//https://wsaahomo.afip.gov.ar/ws/services/LoginCms?WSDL // para obtener WSDL
	//const URL = "https://wsaahomo.afip.gov.ar/ws/services/LoginCms/"; // homologacion (testing)
	// CONST URL = "https://wsaa.afip.gov.ar/ws/services/LoginCms"; // produccion 
	
	const TA 	= "xml/TA.xml";        			# Archivo con el Token y Sign
	//const WSDL 	= "wsaa.wsdl";      			# The WSDL corresponding to WSAA

  	/*
	* path real del directorio principal terminado en /
	*/
	//private $path = '/www/afipfev1/'; //caso linux
	//private $path = 'c:/xampp/htdocs/dolibarr35/htdocs/wsfephp/'; //caso windows (no importa que las barras esten como en linux)
	/*
	* manejo de errores
	*/
	public $error = '';

	/**
	* Cliente SOAP
	*/
	private $client;

	/*
	* servicio del cual queremos obtener la autorizacion
	*/
	private $service; 
  
 var $wsaadb;

	/*
	* Constructor
	*/
	public function __construct($wsaadb)
	{
		$service = 'wsfe';
		$this->service = $service;
		//$this->path =$GLOBALS['dolibarr_main_document_root'].'/wsfephp/';
		$this->path=DOL_DOCUMENT_ROOT.'/wsfephp/';
		$this->cert=$this->path.'keys/'.$wsaadb->certificate;
		$this->key=$this->path.'keys/'.$wsaadb->privatekey;
		//$this->cuitemisor=$wsaadb->emisorcuit;

    
   if ($wsaadb->modo !=1) { //modo =1 produccion
	   $this->url = 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms?WSDL'; //HOMO
   }else{
	   $this->url = $wsaadb->wsaaprod;
   };
    // seteos en php
    ini_set("soap.wsdl_cache_enabled", "0");    
    
    // validar archivos necesarios
    if (!file_exists($this->cert)) $this->error .= " Failed to open ".$this->cert;
    if (!file_exists($this->key)) $this->error .= " Failed to open ".$this->key;

		// Chequero URL

		if (!file_get_contents($this->url)) $this->error .= " Failed to open ".$this->url; //chequea la url

		if(!empty($this->error)) {
		return ($this->error);
		}

   	$this->client = new SoapClient($this->url, array(
				'soap_version'   => SOAP_1_2,
				'location'       =>$this->url,
				'trace'          => 1,
				'exceptions'     => 0
				)
    );
	}
  
	/*
	* Crea el archivo xml de TRA
	*/
	private function create_TRA()
	{
		unlink($this->path."xml/TRA.xml");  //Parche para la renovacion de TRA
	$TRA = new SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>' .
			'<loginTicketRequest version="1.0">'.
			'</loginTicketRequest>');
	$TRA->addChild('header');
	$TRA->header->addChild('uniqueId', date('U'));
	$TRA->header->addChild('generationTime', date('c',date('U')-60));
	$TRA->header->addChild('expirationTime', date('c',date('U')+60));
	$TRA->addChild('service', $this->service);
	$TRA->asXML($this->path.'xml/TRA.xml');
	}
  
	/*
	* This functions makes the PKCS#7 signature using TRA as input file, CERT and
	* PRIVATEKEY to sign. Generates an intermediate file and finally trims the 
	* MIME heading leaving the final CMS required by WSAA.
	* 
	* devuelve el CMS
	*/
	private function sign_TRA()
	{
    $STATUS = openssl_pkcs7_sign($this->path . "xml/TRA.xml", $this->path . "xml/TRA.tmp", "file://" . $this->cert,
		array("file://" . $this->key, self::PASSPHRASE),
		array(),
		!PKCS7_DETACHED
    );
    
    if (!$STATUS)
		throw new Exception("ERROR generating PKCS#7 signature");
      
    $inf = fopen($this->path."xml/TRA.tmp", "r");
    $i = 0;
    $CMS = "";
    while (!feof($inf)) { 
		$buffer = fgets($inf);
		if ( $i++ >= 4 ) $CMS .= $buffer;
    }
    
    fclose($inf);
    unlink($this->path."xml/TRA.tmp");
    
    return $CMS;
	}
  
	/*
	* Conecta con el web service y obtiene el token y sign
	*/
	private function call_WSAA($cms)
	{     
	$results = $this->client->loginCms(array('in0' => $cms));

	// para logueo
	file_put_contents($this->path."xml/request-loginCms.xml", $this->client->__getLastRequest());
	file_put_contents($this->path."xml/response-loginCms.xml", $this->client->__getLastResponse());

	if (is_soap_fault($results)) 
		throw new Exception("SOAP Fault: ".$results->faultcode.': '.$results->faultstring);

	return $results->loginCmsReturn;
	}
  
	/*
	* Convertir un XML a Array
	*/
	private function xml2array($xml) 
	{    
		$json = json_encode( simplexml_load_string($xml));
		return json_decode($json, TRUE);
	}    
  
	/*
	* funcion principal que llama a las demas para generar el archivo TA.xml
	* que contiene el token y sign
	*/
	public function generar_TA()
	{
	$this->create_TRA();
	unlink($this->path."xml/TA.xml"); //parche para la renovacion de TA
	$TA = $this->call_WSAA( $this->sign_TRA() );
					
	if (!file_put_contents($this->path.self::TA, $TA))
		throw new Exception("Error al generar al archivo TA.xml");

	$this->TA = $this->xml2Array($TA);
	  
	return true;
	}
  
	/*
	* Obtener la fecha de expiracion del TA
	* si no existe el archivo, devuelve false
	*/
	public function get_expiration() 
	{    
	// si no esta en memoria abrirlo
	if(empty($this->TA)) {
		$TA_file = file($this->path.self::TA, FILE_IGNORE_NEW_LINES);
		if($TA_file) {
			$TA_xml = '';
			for($i=0; $i < sizeof($TA_file); $i++)
				$TA_xml.= $TA_file[$i];        
			$this->TA = $this->xml2Array($TA_xml);
			$r = $this->TA['header']['expirationTime'];
		} else {
			$r = false;
		}
	} else {
		$r = $this->TA['header']['expirationTime'];
	}
	return $r;
	}
	
}
?>
