<?php
/*
 * ZenFusion OAuth - A Google OAuth authentication module for Dolibarr
 * Copyright (C) 2011 Sebastien Bodrero <sbodrero@gpcsolutions.fr>
 * Copyright (C) 2011-2016 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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
 * \ingroup zenfusionoauth
 * Module support page
 */

// Load Dolibarr environment
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
    require '../../../main.inc.php'; // From "custom" directory
}

require_once '../lib/admin.lib.php';

global $langs, $user;

$langs->load('zenfusionoauth@zenfusionoauth');
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
print load_fiche_titre("ZenFusion", $linkback, 'setup');

$head = zfPrepareHead();

dol_fiche_head($head, 'help', $langs->trans("Module105001Name"), 0, 'oauth@zenfusionoauth');

echo '<a target="_blank" href="http://assistance.gpcsolutions.fr">',
//	'<img src="../img/logo_assist.png" alt="', $langs->trans("HelpCenter"),'">',
$langs->trans("Support"),
'</a>',
'<br>';

dol_fiche_end();
llxFooter();
