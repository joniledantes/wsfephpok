<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010	   Denis Martin	<denimartin@hotmail.fr>
 * Copyright (C) 2017      Ferran Marcet        <fmarcet@2byte.es>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/pointofsale/inc/model/num/mod_facture_pointofsale.php
 *	\ingroup    pointofsale
 *	\brief      File containing class for numbering module Pointofsale
 *	\version    $Id: mod_facture_pointofsale.php,v 1.1 2010/10/29 16:40:50 hregis Exp $
 */
require_once(DOL_DOCUMENT_ROOT ."/core/modules/facture/modules_facture.php");


/**
 *	\class      mod_facture_pointofsale
 *	\brief      Classe du modele de numerotation de reference de facture Pointofsale
 */
class mod_facsim_alcoy extends ModeleNumRefFactures
{
    var $version = 'dolibarr';        // 'development', 'experimental', 'dolibarr'
    var $prefixinvoice = 'FS';
    var $prefixcreditnote = 'AS';
    var $error = '';

    /**     \brief      Renvoi la description du modele de numerotation
     *      \return     string      Texte descripif
     */
    function info()
    {
        global $langs;
        $langs->load("bills");
        return $langs->trans('POSNumRefModelDesc', $this->prefixinvoice);
    }

    /**     \brief      Renvoi un exemple de numerotation
     *      \return     string      Example
     */
    function getExample()
    {
        return $this->prefixinvoice . "0501-0001";
    }

    /**     \brief      Test si les numeros deja en vigueur dans la base ne provoquent pas de
     *                  de conflits qui empechera cette numerotation de fonctionner.
     *      \return     boolean     false si conflit, true si ok
     */
    function canBeActivated()
    {
        global $langs, $conf, $db;

        $refDoli9or10 = null;
        if(version_compare(DOL_VERSION, 10.0) >= 0){
            $refDoli9or10 = 'ref';
        } else {
            $refDoli9or10 = 'facnumber';
        }

        $langs->load("bills");

        // Check invoice num
        $fayymm = '';
        $max = '';

        $posindice = 8;

		$refDoli9or10 = null;
		if(version_compare(DOL_VERSION, 10.0) >= 0){
			$refDoli9or10 = 'ref';
		} else {
			$refDoli9or10 = 'facnumber';
		}

        $sql = "SELECT MAX(SUBSTRING(".$refDoli9or10." FROM " . $posindice . ")) as max";    // This is standard SQL
        $sql .= " FROM " . MAIN_DB_PREFIX . "facture";
        $sql .= " WHERE ".$refDoli9or10." LIKE '" . $this->prefixinvoice . "____-%'";
        $sql .= " AND entity = " . $conf->entity;

        $resql = $db->query($sql);
        if ($resql) {
            $row = $db->fetch_row($resql);
            if ($row) {
                $fayymm = substr($row[0], 0, 6);
                $max = $row[0];
            }
        }
        if ($fayymm && !preg_match('/' . $this->prefixinvoice . '[0-9][0-9][0-9][0-9]/i', $fayymm)) {
            $langs->load("errors");
            $this->error = $langs->trans('ErrorNumRefModel', $max);
            return false;
        }

        return true;
    }

    /**     \brief      Renvoi prochaine valeur attribuee
     *      @param      object      objsoc  societe
     *      @param      object      facture
     *      @return     string      Valeur
     */
    function getNextValue($objsoc, $facture, $mode='next')
    {
        global $db, $conf;

        if ($facture->type == 2) $prefix = $this->prefixcreditnote;
        else $prefix = $this->prefixinvoice;

		$refDoli9or10 = null;
		if(version_compare(DOL_VERSION, 10.0) >= 0){
			$refDoli9or10 = 'ref';
		} else {
			$refDoli9or10 = 'facnumber';
		}

        // D'abord on recupere la valeur max
        $posindice = 8;
        $sql = "SELECT MAX(SUBSTRING(".$refDoli9or10." FROM " . $posindice . ")) as max";    // This is standard SQL
        $sql .= " FROM " . MAIN_DB_PREFIX . "facture";
        $sql .= " WHERE ".$refDoli9or10." LIKE '" . $prefix . "____-%'";
        $sql .= " AND entity = " . $conf->entity;

        $resql = $db->query($sql);
        dol_syslog("mod_facsim_alcoy::getNextValue sql=" . $sql);
        if ($resql) {
            $obj = $db->fetch_object($resql);
            if ($obj) $max = intval($obj->max);
            else $max = 0;
        } else {
            dol_syslog("mod_facsim_alcoy::getNextValue sql=" . $sql, LOG_ERR);
            return -1;
        }

		if ($mode == 'last')
		{
			if ($max >= (pow(10, 4) - 1)) $num=$max;	// If counter > 9999, we do not format on 4 chars, we take number as it is
			else $num = sprintf("%04s", $max);

			$ref='';
			$sql = "SELECT ref as ref";
			$sql.= " FROM ".MAIN_DB_PREFIX."facture";
			$sql.= " WHERE ".$refDoli9or10." LIKE '".$prefix."____-".$num."'";
			$sql.= " AND entity IN (".getEntity('invoicenumber', 1, $facture).")";
			$sql.= " ORDER BY ref DESC";

			$resql=$db->query($sql);
			if ($resql)
			{
				$obj = $db->fetch_object($resql);
				if ($obj) $ref = $obj->ref;
			}
			else dol_print_error($db);

			return $ref;
		}
		elseif ($mode == 'next') {
			$date = $facture->date;    // This is invoice date (not creation date)
			if (!$date) $date = dol_now();
			$yymm = strftime("%y%m", $date);
			$num = sprintf("%04s", $max + 1);

			dol_syslog("mod_facsim_alcoy::getNextValue return " . $prefix . $yymm . "-" . $num);
			return $prefix . $yymm . "-" . $num;
		}
    }

    /**        \brief      Return next free value
     *        @param      object      Object third party
     *        @param        object    Object for number to search
     *    @return     string      Next free value
     */
    function getNumRef($objsoc, $objforref,$mode='next')
    {
        return $this->getNextValue($objsoc, $objforref, $mode);
    }

}