<?php
/*
 * Argentina Electronic Invoice module for Dolibarr
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


class wsaa_db {
	var $db;

//	var $main_db_prefix;
var $emisorcuit;
var $modo;
var $wsstatusconf;
var $entity;
var $wsaaprod;
var $wsfeprod;
var $certificate;
var $privatekey;
var $puntodeventa;

	public function __construct($db){
		$this->db = $db;
	}
	function fetch($cuit) {
		unset($sql);
		$sql  = ' SELECT emisor_cuit, ws_modo_timbrado, wsaaprod, wsfeprod, certificate, privatekey, puntodeventa ';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'wsaa ';
		$sql .= ' WHERE entity_id ='.$this->entity. ' AND emisor_cuit='.$cuit;
   
		$res = $this->db->query($sql);
		if($this->db->num_rows($res)>0){
			$wsaadb = $this->db->fetch_object($res);

			$this->emisorcuit = $wsaadb->emisor_cuit;
			$this->modo = $wsaadb->ws_modo_timbrado;
			$this->wsaaprod = $wsaadb->wsaaprod;
			$this->wsfeprod = $wsaadb->wsfeprod;			
			$this->certificate = $wsaadb->certificate;
			$this->privatekey = $wsaadb->privatekey;
			$this->puntodeventa = $wsaadb->puntodeventa;
			return 1;
		}else{
			return 0;
		}
	}
	function insert($cuit) {

		//$this->emisorcuit = isset($this->emisorcuit)?$this->emisorcuit:null;
		$this->modo = isset($this->modo)?$this->modo:null;
		$this->wsaaprod = isset($this->wsaaprod)?$this->wsaaprod:null;
		$this->wsfeprod = isset($this->wsfeprod)?$this->wsfeprod:null;
	    $this->certificate = isset($this->certificate)?$this->certificate:null;
		$this->privatekey = isset($this->privatekey)?$this->privatekey:null;
		$this->wsstatusconf = isset($this->wsstatusconf)?$this->wsstatusconf:null;
		$this->entity = isset($this->entity)?$this->entity:null;
		$this->puntodeventa = isset($this->puntodeventa)?$this->puntodeventa:null;

		unset($sql);
		$sql  = 'INSERT INTO ';
		$sql .= MAIN_DB_PREFIX.'wsaa ';
		$sql .= ' (emisor_cuit, ws_modo_timbrado, wsaaprod, ';
		$sql .= ' wsfeprod, certificate, privatekey,  ws_status_conf, entity_id, puntodeventa)';
		$sql .= ' VALUES("'.$cuit.'"';
		$sql .= ' , "'.$this->modo.'", "'.$this->wsaaprod.'"';
		$sql .= ' , "'.$this->wsfeprod.'", "'.$this->certificate.'"';
		$sql .= ' , "'.$this->privatekey.'" ,"'.$this->wsstatusconf.'", "'.$this->entity.'"';
		$sql .= ' , "'.$this->puntodeventa.'")';
		
		$res = $this->db->query($sql);

		if($res){
			return 1;
		}else{
			print $sql;
			return 0;
		}
	}
	function delete() {

	}
	function update($cuit) {
		//$this->emisocuit = isset($this->emisorcuit)?$this->emisorcuit:null;
		$modo = isset($this->modo)?$this->modo:null;
		$wsaaprod = isset($this->wsaaprod)?$this->wsaaprod:null;
        $wsfeprod = isset($this->wsfeprod)?$this->wsfeprod:null;
	    $certificate = isset($this->certificate)?$this->certificate:null;
		$privatekey = isset($this->privatekey)?$this->privatekey:null;
		
		$wsstatusconf = isset($this->wsstatusconf)?$this->wsstatusconf:null;
		$entity = isset($this->entity)?$this->entity:null;
		$puntodeventa=isset($this->puntodeventa)?$this->puntodeventa:null;

		$res = $this->fetch($cuit);
		if($res){
			//if 1 update
			unset($sql);
			$sql  = ' UPDATE '.MAIN_DB_PREFIX.'wsaa SET';
			$sql .= ' ws_modo_timbrado = "'.$modo.'"';
			$sql .= ' ,wsaaprod = "'.$wsaaprod.'"';
			$sql .= ' ,wsfeprod = "'.$wsfeprod.'"';
			$sql .= ' ,certificate = "'.$certificate.'"';
			$sql .= ' ,privatekey = "'.$privatekey.'"';
			$sql .= ' ,ws_status_conf = "'.$wsstatusconf.'"';
			$sql .= ' ,puntodeventa = "'.$puntodeventa.'"';
			$sql .= ' ,entity_id = "'.$entity.'"';
			$sql .= ' WHERE emisor_cuit = "'.$cuit.'"';
			$res = $this->db->query($sql);
			if($res){
				return 1;
			}else{
				//print $sql;
				return 0;
			}
		}else{
			//if 0 insert
			$res = $this->insert($cuit);
			if ($res) {
				return 1;
			}else{
				return 0;
			}
		}
	}
	function fetch_const() {

		$sql  = ' SELECT * ';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'const' ;
		$sql .= ' WHERE entity = '.$this->entity;
		$sql .= ' AND name = "MAIN_MODULE_WSFE_WS"';
		$res = $this->db->query($sql);
		$num = $this->db->num_rows($res);
		if ($num){
			return 1;
		}else{
			return 0;
		}
	}
	function update_const() {
		//print 'Busca const<br>';

		$res = $this->fetch_const();
		if ($this->modo == 1) {
			 $this->wsvalue = $this->wsprod;
		}elseif($this->modo == 2){
			 $this->wsvalue = $this->wspruebas;
		}
		if($res){
			//update
			//print 'Actualiza const<br>';
			$sql  = ' UPDATE '.MAIN_DB_PREFIX.'const SET ';
			$sql .= ' value = "'. $this->wsvalue.'"';
			$sql .= ' WHERE name = "MAIN_MODULE_WSFE_WS"';
			$sql .= ' AND entity = '. $this->entity;
			$res = $this->db->query($sql);
			if($res){
				return $res;
			}else{
				return $res;
			}
		}else{
			//insert
			//print 'Inserta const<br>';
			$res = $this->insert_const();
			if($res){
				return $res;
			}else{
				return $res;
			}
		}
	}
	function insert_const() {
		$sql  = 'INSERT INTO '.MAIN_DB_PREFIX.'const ';
		$sql .= ' (name, entity, value';
		$sql .= '  , type, visible, note)';
		$sql .= ' VALUES("MAIN_MODULE_WSFE_WS", "'.$this->entity.'", "'.$this->wsvalue.'"';
		$sql .= ' , "chaine", "1", "ws cfdimx")';

		$res = $this->db->query($sql);
		if($res){
			return $res;
		}else{
			return $res;
		}
	}
}

?>