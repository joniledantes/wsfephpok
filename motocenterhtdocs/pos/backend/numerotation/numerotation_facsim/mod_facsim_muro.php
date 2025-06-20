<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2015-2017 Ferran Marcet 		<fmarcet@2byte.es>
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
 *	\file       htdocs/pos/backend/numerotation/mod_ticket_simat.php
 *	\ingroup    ticket
 *	\brief      File containing class for numbering module simat
 */
require_once(DOL_DOCUMENT_ROOT ."/core/modules/facture/modules_facture.php");


/**
 *	\class      mod_facsim_muro
 *	\brief      Classe du modele de numerotation de reference de ticket simat
 */
class mod_facsim_muro extends ModeleNumRefFactures
{
    var $version = 'dolibarr';        // 'development', 'experimental', 'dolibarr'
    var $error = '';


    /**     \brief      Renvoi la description du modele de numerotation
     *      \return     string      Texte descripif
     */
    function info()
    {
        global $conf, $langs, $db;

        $langs->load("pos@pos");

        $form = new Form($db);

        $texte = $langs->trans('GenericNumRefModelDesc') . "<br>\n";
        $texte .= '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
        $texte .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        $texte .= '<input type="hidden" name="action" value="updateMask">';
        $texte .= '<input type="hidden" name="maskconstfacsim" value="FACSIM_MURO_MASK">';
        $texte .= '<input type="hidden" name="maskconstfacsimcredit" value="FACSIM_MURO_MASK_CREDIT">';
        $texte .= '<table class="nobordernopadding" width="100%">';

        $tooltip = $langs->trans("GenericMaskCodes", $langs->transnoentities("Facsim"));
        $tooltip .= $langs->trans("GenericMaskCodes2");
        $tooltip .= $langs->trans("POSMaskCodes");
        $tooltip .= $langs->trans("GenericMaskCodes3");
        $tooltip .= $langs->trans("GenericMaskCodes4a", $langs->transnoentities("Facsim"), $langs->transnoentities("facsim"));
        $tooltip .= $langs->trans("GenericMaskCodes5");

        // Parametrage du prefix
        $texte .= '<tr><td>' . $langs->trans("Mask") . ' (' . $langs->trans("Facsim") . '):</td>';
        $texte .= '<td align="right">' . $form->textwithpicto('<input type="text" class="flat" size="24" name="maskfacsim" value="' . $conf->global->FACSIM_MURO_MASK . '">', $tooltip, 1, 1) . '</td>';

        $texte .= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="' . $langs->trans("Modify") . '" name="Button"></td>';

        $texte .= '</tr>';

        // Parametrage du prefix des avoirs
        $texte .= '<tr><td>' . $langs->trans("Mask") . ' (' . $langs->trans("FacsimAvoir") . '):</td>';
        $texte .= '<td align="right">' . $form->textwithpicto('<input type="text" class="flat" size="24" name="maskfacsimcredit" value="' . $conf->global->FACSIM_MURO_MASK_CREDIT . '">', $tooltip, 1, 1) . '</td>';
        $texte .= '</tr>';

        $texte .= '</table>';
        $texte .= '</form>';

        return $texte;
    }

    /**     \brief      Return an example of number value
     *      \return     string      Example
     */
    function getExample()
    {
        global $langs, $mysoc;

        $old_code_client = $mysoc->code_client;
        $old_code_type = $mysoc->typent_code;
        $mysoc->code_client = 'CCCCCCCCCC';
        $mysoc->typent_code = 'TTTTTTTTTT';
        $numExample = $this->getNextValue($mysoc, '');
        $mysoc->code_client = $old_code_client;
        $mysoc->typent_code = $old_code_type;

        if (!$numExample) {
            $numExample = $langs->trans('NotConfigured');
        }
        return $numExample;
    }

    /**        Return next value
     * @param      object      third party
     * @param      object      ticket
     * @param      string      'next' for next value or 'last' for last value
     * @return     string      Value if OK, 0 if KO
     */
    function getNextValue($objsoc, $facsim, $mode = 'next')
    {
        global $db, $conf;

        require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php");

        // Get Mask value
        if (is_object($facsim) && $facsim->type == 2) $mask = $conf->global->FACSIM_MURO_MASK_CREDIT;
        else $mask = $conf->global->FACSIM_MURO_MASK;
        if (!$mask) {
            $this->error = 'NotConfigured';
            return 0;
        }

        $where = '';

        //ww para warehouse
        if (preg_match('/\{(w+)\}/i', $mask, $regWare)) {
            dol_include_once("/pos/class/cash.class.php");
            require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
            $terminal = new Cash($db);
            $terminal->fetch($facsim->fk_cash);
            $warehouse = new Entrepot($db);
            $warehouse->fetch($terminal->fk_warehouse);

            $maskware = $regWare[1];
            $maskware_value = substr($warehouse->libelle, 0, dol_strlen($regWare[1]));//get n first characters of warehouse ref where n is length in mask
            $maskware_value = str_pad($maskware_value, dol_strlen($regWare[1]), "#", STR_PAD_RIGHT);

            $maskware_maskbefore = '{' . $maskware . '}';
            $maskware_maskafter = $maskware_value;
            $mask = str_replace($maskware_maskbefore, $maskware_maskafter, $mask);
        }
        //kk para terminal
        if (preg_match('/\{(k+)\}/i', $mask, $regTerm)) {
            dol_include_once("/pos/class/cash.class.php");
            $terminal = new Cash($db);
            $terminal->fetch($facsim->fk_cash);

            $maskterm = $regTerm[1];
            $maskterm_value = substr($terminal->ref, 0, dol_strlen($regTerm[1]));//get n first characters of warehouse ref where n is length in mask
            $maskterm_value = str_pad($maskterm_value, dol_strlen($regTerm[1]), "#", STR_PAD_RIGHT);

            $maskterm_maskbefore = '{' . $maskterm . '}';
            $maskterm_maskafter = $maskterm_value;
            $mask = str_replace($maskterm_maskbefore, $maskterm_maskafter, $mask);
        }


		$refDoli9or10 = null;
		if(version_compare(DOL_VERSION, 10.0) >= 0){
			$refDoli9or10 = 'ref';
		} else {
			$refDoli9or10 = 'facnumber';
		}
        $numFinal = get_next_value($db, $mask, 'facture', $refDoli9or10, $where, $objsoc, time(), $mode);
        if (!preg_match('/([0-9])+/', $numFinal)) $this->error = $numFinal;

        return $numFinal;
    }


    /**        Return next free value
     * @param      object       Object third party
     * @param      object       Object for number to search
     * @param      string  'next' for next value or 'last' for last value
     * @return     string      Next free value
     */
    function getNumRef($objsoc, $objforref, $mode = 'next')
    {
        return $this->getNextValue($objsoc, $objforref, $mode);
    }

}