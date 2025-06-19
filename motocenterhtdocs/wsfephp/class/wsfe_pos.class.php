<?php
/* Copyright (C) 2017      Catriel Rios <catriel_r@hotmail.com>
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
 */

/**
 *       \file       htdocs/wsfephp/class/wsfe_pos.class.php
 *       \ingroup    facture
 *       \brief      File of class to manage POS to Electronic Invoice
 */

class wsfe_pos {
	var $db;
  var $isPos;
  
  	
    
	public function __construct($db){
    $this->db = $db;
	}
  
  
   function isPos($id){

		   unset($sql);
		   $sql  = ' SELECT fk_facture ';
		   $sql .= ' FROM '.MAIN_DB_PREFIX.'pos_facture ';
		   $sql .= ' WHERE fk_facture ='.$id;
		   $res = $this->db->query($sql);
		   if($this->db->num_rows($res)>0){
			   return 1; //isPOS
		   }else{
			   return 0; //not in POS
		   }

	   }
     
    function goToDraft($id,$modo)
	{
		global $conf, $user, $langs, $db;
		unset($sql);
		$sql = ' UPDATE ' . MAIN_DB_PREFIX . 'facture  SET';
		if ($modo==0) {   //ToValid
			$sql .= ' ref = "(PROV' . $id . ')", fk_statut = 0, model_pdf="fe" ';
		}elseif ($modo==1) {  //reopen
			$sql .= ' fk_statut = 0';
		}elseif ($modo==2) { //post Valid
			$sql .= ' fk_statut = 1, paye = 0';
		}
		$sql .= ' WHERE rowid =' . $id;
		$res = $this->db->query($sql);
		if ($res) {
			//OK
		} else {
			dol_print_error($db, '');
		}
	}



  
}
?> 
  