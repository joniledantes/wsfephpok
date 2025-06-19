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
 * \file lib/admin.lib.php
 * \ingroup financial
 * Module functions library
 */

/**
 * \function zfPrepareHead
 * Display tabs in module admin page
 *
 * @return array
 */
function wsfeAdminPrepareHead()
{
    global $langs, $conf;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/wsfephp/admin/conf.php", 1);
    $head[$h][1] = $langs->trans("Config");
    $head[$h][2] = 'conf';
    $h++;

    
    $head[$h][0] = dol_buildpath("/wsfephp/admin/status.php",1);
    $head[$h][1] = $langs->trans("AFIPServerStatus");
    $head[$h][2] = 'serverstatus';
    $h++;
    

    $head[$h][0] = dol_buildpath("/wsfephp/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';

    return $head;
}
