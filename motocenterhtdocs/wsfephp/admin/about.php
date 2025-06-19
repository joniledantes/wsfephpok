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
 * \file admin/about.php
 * \ingroup financial
 * Module about page
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}


require_once '../lib/admin.lib.php';

global $conf, $db, $user, $langs;

$langs->load('wsfephp@wsfephp');
$langs->load('admin');
$langs->load('help');

// only readable by admin
if (!$user->admin) {
    accessforbidden();
}


/*
 * View
 */

// Little folder on the html page
llxHeader();
/// Navigation in the modules
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
// Folder icon title
print load_fiche_titre($langs->trans("wsfePHPConfig"), $linkback, 'setup');

$head = wsfeAdminPrepareHead();

dol_fiche_head(
    $head,
    'about',
    $langs->trans("Module1050002Name"),
    0,
    'wsfephp@wsfephp'
);

echo '<h3>',
$langs->trans("Module1050002Name"),
' — ',
$langs->Trans('Module1050002Desc'),
'</h3>';
echo '<em>', $langs->trans("Version"), ' ',
$module->version, '</em><br>';
echo '<em>&copy;2011-2017 Catriel Rios<br><em>';
echo '<a target="_blank" href="http://www.catrielr.com.ar/">',
'<img src="../img/logo_catrielr.png" alt="Logo catrielr"></a>';

echo '<h3>', $langs->trans("Publisher"), '</h3>';
echo '<address>Catriel Rios<br>',
'<a href="mailto:catriel_r@hotmail.com">catriel_r@hotmail.com</a>';



dol_fiche_end();
llxFooter();
