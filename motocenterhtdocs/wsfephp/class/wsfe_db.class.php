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

class wsfe_db {
	var $db;
	var $cae;
	var $caevto;
	var $puntodeventa;
	var $cbtnro;
	var $concepto;
	var $obs;
	var $fk_facture;
	var $xmlrequest;
    var $xmlresponse;
	var $divisa;
	var $version;
    var $entity_id;
    var $cbttipo;
    var $cuitemisor;
    var $errc;
    var $errm;
	

	
    
	public function __construct($db){
		$this->db = $db;
	}
	function fetch() {
		unset($sql);
		$sql  = ' SELECT * ';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'wsfe ';
		$sql .= ' WHERE fk_facture ='.$this->fk_facture;
		$res = $this->db->query($sql);
		if($this->db->num_rows($res)>0){
			$wsfedb = $this->db->fetch_object($res);

			$this->cae = $wsfedb->cae;
			$this->caevto = $wsfedb->caevto;
			$this->puntodeventa = $wsfedb->puntodeventa;
			$this->cbtnro = $wsfedb->cbtnro;
			$this->obs = $wsfedb->obs;
			$this->concepto = $wsfedb->concepto;
			$this->fk_facture = $wsfedb->fk_facture;			
			$this->xmlrequest = $wsfedb->xmlrequest;
			$this->xmlresponse = $wsfedb->xmlresponse;
			$this->divisa =$wsfedb->divisa;
			$this->version = $wsfedb->version;
			$this->entity_id = $wsfedb->entity_id;
			$this->cbttipo = $wsfedb->cbttipo;
			$this->cuitemisor = $wsfedb->cuitemisor;
			$this->errc = $wsfedb->errc;
			$this->errm = $wsfedb->errm;
			return 1;
		}else{
			return 0;
		}
	}
	function insert() {

	    	$this->cae = isset($this->cae)?$this->cae:null;
	    	$this->caevto = isset($this->caevto)?$this->caevto:null;
	        $this->puntodeventa = isset($this->puntodeventa)?$this->puntodeventa:null;
	        $this->cbtnro = isset($this->cbtnro)?$this->cbtnro:null;
	        $this->obs = isset($this->obs)?$this->obs:null;
	        $this->concepto = isset($this->concepto)?$this->concepto:null;
	        $this->fk_facture = isset($this->fk_facture)?$this->fk_facture:null;
	        $this->xmlrequest = isset($this->xmlrequest)?$this->xmlrequest:null;
	        $this->xmlresponse = isset($this->xmlresponse)?$this->xmlresponse:null;
	        $this->divisa = isset($this->divisa)?$this->divisa:null;
	        $this->version = isset($this->version)?$this->version:null;
	        $this->entity_id = isset($this->entity_id)?$this->entity_id:null;
	        $this->cbttipo = isset($this->cbttipo)?$this->cbttipo:null;
	        $this->cuitemisor = isset($this->cuitemisor)?$this->cuitemisor:null;
	        $this->errc = isset($this->errc)?$this->errc:null;
	        $this->errm = isset($this->errm)?$this->errm:null;
	        
		unset($sql);
		$sql  = 'INSERT INTO ';
		$sql .=  MAIN_DB_PREFIX.'wsfe ';
		$sql .= ' (cae, caevto, puntodeventa, cbtnro ';
		$sql .= '  ,obs, concepto, fk_facture, xmlrequest, xmlresponse,  divisa, version, entity_id, cbttipo, cuitemisor ';
		$sql .= '  ,errc, errm)';
		$sql .= ' VALUES("'.$this->cae.'", "'.$this->caevto.'", "'.$this->puntodeventa.'" , "'.$this->cbtnro.'"';
		$sql .= ' ,"'.$this->obs.'","'.$this->concepto.'", "'.$this->fk_facture.'", "'.$this->xmlrequest.'"';
		$sql .= ' ,"'.$this->xmlresponse.'" ,"'.$this->divisa.'", "'.$this->version.'"';
		$sql .= ' ,"'.$this->entity_id.'", "'.$this->cbttipo.'", "'.$this->cuitemisor.'"';
		$sql .= ' ,"'.$this->errc.'", "'.$this->errm.'")';
		
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
	function update() {
		$cae = isset($this->cae)?$this->cae:null;
		$caevto = isset($this->caevto)?$this->caevto:null;
		$puntodeventa = isset($this->puntodeventa)?$this->puntodeventa:null;
        $cbtnro = isset($this->cbtnro)?$this->cbtnro:null;
	    $obs = isset($this->obs)?$this->obs:null;
	    $concepto = isset($this->concepto)?$this->concepto:null;
		$fk_facture = isset($this->fk_facture)?$this->fk_facture:null;
		$xmlrequest = isset($this->xmlrequest)?$this->xmlrequest:null;
		$xmlresponse = isset($this->xmlresponse)?$this->xmlresponse:null;
		$divisa=isset($this->divisa)?$this->divisa:null;
		$version=isset($this->version)?$this->version:null;
		$entity_id=isset($this->entity_id)?$this->entity_id:null;
		$cbttipo=isset($this->cbttipo)?$this->cbttipo:null;
		$cuitemisor=isset($this->cuitemisor)?$this->cuitemisor:null;
		$errc=isset($this->errc)?$this->errc:null;
		$errm=isset($this->errm)?$this->errm:null;
		
		$res = $this->fetch();
		if($res){
			//if 1 update
			unset($sql);
			$sql  = ' UPDATE '.MAIN_DB_PREFIX.'wsfe SET';
			$sql .= ' cae = "'.$cae.'"';
			$sql .= ' ,caevto = "'.$caevto.'"';
			$sql .= ' ,puntodeventa = "'.$puntodeventa.'"';
			$sql .= ' ,cbtnro = "'.$cbtnro.'"';
			$sql .= ' ,obs = "'.$obs.'"';
			$sql .= ' ,concepto = "'.$concepto.'"';
			$sql .= ' ,xmlrequest = "'.$xmlrequest.'"';
			$sql .= ' ,xmlresponse = "'.$xmlresponse.'"';
			$sql .= ' ,divisa = "'.$divisa.'"';
			$sql .= ' ,version = "'.$version.'"';
			$sql .= ' ,entity_id = "'.$entity_id.'"';
			$sql .= ' ,cbttipo = "'.$cbttipo.'"';
			$sql .= ' ,cuitemisor = "'.$cuitemisor.'"';
			$sql .= ' ,errc = "'.$errc.'"';
			$sql .= ' ,errm = "'.$errm.'"';
			$sql .= ' WHERE fk_facture = "'.$fk_facture.'"';
			$res = $this->db->query($sql);
			if($res){
				return 1;
			}else{
				print $sql;
				return 0;
			}
		}else{
			//if 0 insert
			$res = $this->insert();
			if ($res) {
				return 1;
			}else{
				return 0;
			}
		}
	}



}


?>