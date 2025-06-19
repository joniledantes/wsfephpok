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
 * \file admin/support.php
 * \ingroup financial
 * Module support page
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}

require_once '../lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '../wsfephp/class/wsfev1.class.php';
require_once DOL_DOCUMENT_ROOT . '../wsfephp/class/wsaa.class.php';

global $langs, $user;

$langs->load('wsfephp@wsfephp');
$langs->load('admin');
$langs->load('help');

// only readable by admin
if (!$user->admin) {
    accessforbidden();
}


// Parameters
$action = GETPOST('action', 'alpha');
// Get parameters
$cbttipo = GETPOST('cbttipo');
$ptovta	= GETPOST('ptovta');
$cbtnro=GETPOST('cbtnro');

$wsaadb=new wsaa_db($db);
$emisorcuit = str_replace("-","",$conf->global->MAIN_INFO_SIREN);
$wsaadb->entity=$_SESSION['dol_entity'];
$wsaadb->fetch($emisorcuit);

/*****************
//WSFEV1
 ****************/
$wsfev1 = new WSFEV1($wsaadb);
if ($wsfev1->error) {
    $error .= $wsfev1->error;
}
/*****************
 * //WSAA
 ****************/
$wsaa = new WSAA($wsaadb);
if ($wsaa->error) {
    $error .= $wsaa->error;
}



if ($error) {
    $action='';
    $dummy=$error;
}else{
    $dummy = json_encode($wsfev1->FEDummy());

}

/*
 * Actions
 */

if ($action =='request'){
    $wsfev1->openTA();
    $comprobate=$wsfev1->FECompConsultar($cbttipo,$cbtnro,$ptovta);
}

if ($action == 'renewta') {
   $wsaa->generar_TA();
}



/*
 * View
 */

// Little folder on the html page
llxHeader();
/// Navigation in the modules
dol_htmloutput_mesg($mesg);
$form = new Form($db);
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
// Folder icon title
print load_fiche_titre($langs->trans("wsfePHPConfig"), $linkback, 'setup');
$head = wsfeAdminPrepareHead();
dol_fiche_head($head, 'serverstatus', $langs->trans("Module1050002Name"), 0, 'wsfephp@wsfephp');


print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="renewta" />';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("ServerStatus").'</td>';
print '</tr>'."\n";
print '<tr>';
print '<td><textarea readonly rows="4" cols="100">'.$dummy.'</textarea></td>';
print '</tr>';
print '<br>';

print '</table>';
print '<input class="button"  value="'.$langs->trans("Renewta").'" type="submit">';
print '</form>';


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="request" />';
print '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("InvoiceInfo").'</td>';
print '</tr>'."\n";
print '<td width="17">'.$langs->trans("TypeInvoice").'</td>';
print '<td width="26"><input name="cbttipo" type="text" id="cbttipo" value="" maxlength="50" /></td>';
print '</tr>';
print '<tr>';
print '<td width="174">'.$langs->trans("POS").'</td>';
print '<td width="267"><input name="ptovta" type="text" id="ptovta" value="" maxlength="200" /></td>';
print '</tr>';
print '<tr>';
print '<td width="174">'.$langs->trans("InvoiceNumber").'</td>';
print '<td width="267"><input name="cbtnro" type="text" id="cbtnro" value="" maxlength="200" /></td>';
print '</tr>';

if ($comprobate) {
print '<tr>';
print '<td width="174">'.$langs->trans("Result").'</td>';
print '<td><textarea readonly rows="4" cols="100">'.json_encode($comprobate).'</textarea></td>';
}
print '</tr>';

print '</table>';

print '<input class="button"  value="'.$langs->trans("Request").'" type="submit">';

print '<br>';
print '</form>';


if (file_exists('../xml/request-FECAESolicitar.xml')) {
    $strReqCae = file_get_contents('../xml/request-FECAESolicitar.xml');
}
if (file_exists('../xml/response-FECAESolicitar.xml')) {
    $strResCae = file_get_contents('../xml/response-FECAESolicitar.xml');
}
if (file_exists('../xml/response-FECompUltimoAutorizado.xml')) {
    $strResUlt = file_get_contents('../xml/response-FECompUltimoAutorizado.xml');
}
if (file_exists('../xml/request-FECompUltimoAutorizado.xml')) {
    $strReqUlt = file_get_contents('../xml/request-FECompUltimoAutorizado.xml');
}



/*
 * Notifications
 */
print '<br>';
print load_fiche_titre($langs->trans("DebugConsole"),'','');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>request-FECAESolicitar.xml</td>';
print '<td align="center" width="60"></td>';
print '<td width="90">&nbsp;</td>';
print "</tr>\n";
print '<tr ><td colspan="2">';
print '<textarea readonly rows="4" cols="100">'.$strReqCae.'</textarea><br>';
print '</td><td align="right">';
print "</td></tr>\n";

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>response-FECAESolicitar.xml</td>';
print '<td align="center" width="60"></td>';
print '<td width="90">&nbsp;</td>';
print "</tr>\n";
print '<tr ><td colspan="2">';
print '<textarea readonly rows="4" cols="100">'.$strResCae.'</textarea><br>';
print '</td><td align="right">';
print "</td></tr>\n";


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>request-FECompUltimoAutorizado.xml</td>';
print '<td align="center" width="60"></td>';
print '<td width="90">&nbsp;</td>';
print "</tr>\n";
print '<tr ><td colspan="2">';
print '<textarea readonly rows="4" cols="100">'.$strReqUlt.'</textarea><br>';
print '</td><td align="right">';
print "</td></tr>\n";

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>response-FECompUltimoAutorizado.xml</td>';
print '<td align="center" width="60"></td>';
print '<td width="90">&nbsp;</td>';
print "</tr>\n";
print '<tr ><td colspan="2">';
print '<textarea readonly rows="4" cols="100">'.$strResUlt.'</textarea><br>';
print '</td><td align="right">';
print "</td></tr>\n";


print '</table>';



dol_fiche_end();
llxFooter();
