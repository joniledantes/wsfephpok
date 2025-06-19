<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Catriel Rios <catriel_r@hotmail.com>
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
 *   	\file       dev/skeletons/skeleton_page.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *					Put here some comments
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
include_once(DOL_DOCUMENT_ROOT.'/wsfephp/class/wsaa_db.class.php');
include_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');

//require_once DOL_DOCUMENT_ROOT . '/wsfephp/class/wsfev1.class.php';
//require_once DOL_DOCUMENT_ROOT . '/wsfephp/class/wsaa.class.php';

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");
$langs->load("admin");

// Get parameters
$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$backtopage = GETPOST('backtopage');
$myparam	= GETPOST('myparam','alpha');

$wsaaprod= GETPOST('wsaaprod');
$wsfeprod=GETPOST('wsfeprod');
$cetificate=GETPOST('crt');
$privatekey=GETPOST('key');
$puntodeventa=GETPOST('pto');
$modo=GETPOST('modo');
$emisorcuit = str_replace("-","",$conf->global->MAIN_INFO_SIREN);


// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}

if (empty($action) && empty($id) && empty($ref)) $action='list';

// Load object if id or ref is provided as parameter


if (($id > 0 || ! empty($ref)) && $action != 'add')
{
	$result=$object->fetch($id,$ref);
	if ($result < 0) dol_print_error($db);
}

// Initialize technical object to manage hooks of modules. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('skeleton'));
$extrafields = new ExtraFields($db);

$wsaadb=new wsaa_db($db);
$wsaadb->entity=$_SESSION['dol_entity'];



/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
  //save
	if ($action == 'Modificar')
	{
//		dol_delete_file(DOL_DOCUMENT_ROOT.'/wsfephp/xml/TA.xml');
//		dol_delete_file(DOL_DOCUMENT_ROOT.'/wsfephp/xml/TRA.xml');



		//$wsaadb->emisorcuit = $_POST['cuit'];

		$wsaadb->wsaaprod = $wsaaprod;
		$wsaadb->wsfeprod = $wsfeprod;
		$wsaadb->certificate = $cetificate;
		$wsaadb->privatekey = $privatekey;
		$wsaadb->puntodeventa = $puntodeventa;
		$wsaadb->modo = $modo;
		$wsaadb->update($emisorcuit);

	}

}




/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/
$help_url='EN:Module_Users|FR:Module_Utilisateurs|ES:M&oacute;dulo_Usuarios';
llxHeader('','Configuracion Factura Electronica',$help_url);
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre('Configuracion Factura Electronica',$linkback,'title_setup');


$form=new Form($db);


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
//print '<input type="hidden" name="action" value="set">';


print '<br>';

print_titre('WSAA/WSFE');
	print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">Requerido AFIP</td>';
print '</tr>'."\n";
print '<tr></tr>';
//print '<tr><td rowspan=2">'.$langs->trans("InstructionsDownload").' <b><a href="http://www.oracle.com/technetwork/java/javase/downloads/index.html">(Java 8 '.$langs->trans("Required").')</a></b></td><td rowspan="2"><div class="inline-block divButAction"><a class="butAction" style="background: none repeat scroll 0 0 red; color:white;float:left" target="_blank" href="https://www.dropbox.com/sh/bbndwov9b6alwe0/AABWCrMqXSd3ufKbSysx1UYpa?dl=0">'.$langs->trans("Download").'</a></div></td></tr>';
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





// Put here content of your page

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});
});
</script>';




// Part to edit record
if (($id || $ref) && $action == 'edit')
{
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';

	dol_fiche_head();

	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="add" value="'.$langs->trans("Create").'"></div>';

	print '</form>';
}

// End of page
llxFooter();
$db->close();
