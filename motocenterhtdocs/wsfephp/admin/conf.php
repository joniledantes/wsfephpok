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

/**
 * \file admin/conf.php
 * \ingroup finacial
 * Module configuration page
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}

require_once '../lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
include_once(DOL_DOCUMENT_ROOT.'/wsfephp/class/wsaa_db.class.php');
global $conf, $db, $user, $langs;

$mesg = ""; // User message

$langs->load('wsfephp@wsfephp');
$langs->load('admin');
$langs->load('help');

// Access control
if (!$user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
// Get parameters
$id			= GETPOST('id','int');
$backtopage = GETPOST('backtopage');
$myparam	= GETPOST('myparam','alpha');

$wsaaprod= GETPOST('wsaaprod');
$wsfeprod=GETPOST('wsfeprod');
$cetificate=GETPOST('crt');
$privatekey=GETPOST('key');
$puntodeventa=GETPOST('pto');
$modo=GETPOST('modo');
$emisorcuit = str_replace("-","",$conf->global->MAIN_INFO_SIREN);


/*
 * Actions
 */

$wsaadb=new wsaa_db($db);
$wsaadb->entity=$_SESSION['dol_entity'];

	if ($action == 'Modificar')
	{
		$wsaadb->wsaaprod = $wsaaprod;
		$wsaadb->wsfeprod = $wsfeprod;
		$wsaadb->certificate = $cetificate;
		$wsaadb->privatekey = $privatekey;
		$wsaadb->puntodeventa = $puntodeventa;
		$wsaadb->modo = $modo;
		$wsaadb->update($emisorcuit);

	}




/**
 * view
 */
llxHeader();
// Error / confirmation messages
dol_htmloutput_mesg($mesg);
$form = new Form($db);
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
// Folder icon title
print load_fiche_titre($langs->trans("wsfePHPConfig"), $linkback, 'setup');

$head = wsfeAdminPrepareHead();
dol_fiche_head(
    $head,
    'conf',
    $langs->trans("Module1050002Name"),
    0,
    'wsfephp@wsfephp'
);

//print load_fiche_titre($langs->trans("wsfePHPConfig"));



$wsaadb->fetch($emisorcuit);

if ($wsaadb->modo== '1') {
	$checprod = 'checked="checked"';
	$chechomo = '';
}else{
	$checprod = '';
	$chechomo = 'checked="checked"';

};

print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">Requerido AFIP</td>';
print '</tr>'."\n";
print '<tr></tr>';
print '    <tr>
      <td width="17">Cuit Emisor</td>
      <td width="26"><input name="cuit" type="text" id="cuit" value="'.$emisorcuit.'" maxlength="50" disabled="disabled"/></td>
    </tr>
	<tr>
      <td width="174">WSAA Produccion</td>
      <td width="267"><input name="wsaaprod" type="text" id="wsaa" value="'.$wsaadb->wsaaprod.'" maxlength="200" /></td>
    </tr>
    <tr>
      <td width="174">WSFE Produccion</td>
      <td width="267"><input name="wsfeprod" type="text" id="wsfe" value="'.$wsaadb->wsfeprod.'" maxlength="200" /></td>
    </tr>
    <tr>
      <td width="174">Archivo CRT</td>
      <td width="267"><input name="crt" type="text" id="crt" value="'.$wsaadb->certificate.'" maxlength="200" /></td>
    </tr>
    <tr>
      <td width="174">Archivo KEY</td>
      <td width="267"><input name="key" type="text" id="key" value="'.$wsaadb->privatekey.'" maxlength="200" /></td>
    </tr>
    <tr>
      <td width="174">Punto de Venta</td>
      <td width="267"><input name="pto" type="text" id="pto" value="'.$wsaadb->puntodeventa.'" maxlength="2" /></td>
    </tr>
    <tr>
        <td width="174">Modo</td>
         <td width="267"><input id="element_7_1" name="modo" class="element radio" type="radio" value="2" '.$chechomo.' />
         <label>Homologacion (testing)</label>
         <input id="element_7_2" name="modo" class="element radio" type="radio" value="1"  '.$checprod.' />
         <label>Produccion</label>
    </tr>

';

print '</table>';
print '<br>';
print '<input class="button" value="Modificar" name="action" type="submit">';

print '</form>';


dol_fiche_end();
llxFooter();
