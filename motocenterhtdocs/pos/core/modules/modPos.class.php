<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011-2102 Juanjo Menent        <jmenent@2bye.es>
 * Copyright (C) 2012-2017 Ferran Marcet        <fmarcet@2byte.es>
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
 */

/**
 * 		\defgroup   mymodule     Module MyModule
 *      \brief      Example of a module descriptor.
 *					Such a file must be copied into htdocs/includes/module directory.
 */

/**
 *      \file       htdocs/includes/modules/modMyModule.class.php
 *      \ingroup    mymodule
 *      \brief      Description and activation file for module MyModule
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 * 		\class      modMyModule
 *      \brief      Description and activation class for module MyModule
 */
class modPos extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB		$db      Database handler
     */
	public function __construct($db)
	{
        global $conf;

        $this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 400004;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'pos';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = 'products';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = 'POS module';
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '14.1.1';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='pos.png@pos';

		$this->editor_name = '2byte.es';
		$this->editor_url = 'www.2byte.es';

		// Defined if the directory /mymodule/inc/triggers/ contains triggers or not
		$this->triggers = 0;

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array('/pos/temp');
		$r=0;

		// Relative path to module style sheet if exists. Example: '/mymodule/css/mycss.css'.
		//$this->style_sheet = '/mymodule/mymodule.css.php';

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array('pos.php@pos');

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			'hooks' => array(
				'mainloginpage',
				'categorycard',
				'productcard',
				'invoicecard'
			),
			'css' => array(
				'/pos/css/pos.css'
			),
			'triggers' => 1
		);

		// Dependencies
		$this->depends = array('modBanque','modFacture','modProduct','modStock','modCommande');		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(5,6);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(7,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array('pos@pos');

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
		//                             2=>array('MAIN_MODULE_MYMODULE_NEEDSMARTY','chaine',1,'Constant to say module need smarty',1)
		$this->const = array();

		$r++;
		$this->const[$r][0] = 'POS_TICKET_ADDON';
		$this->const[$r][1] = 'chaine';
		$this->const[$r][2] = 'mod_ticket_barx';
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des tickets';
		$this->const[$r][4] = 0;

        $this->const[$r][0] = "FACSIM_ADDON";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "mod_facsim_alcoy";
        $this->const[$r][3] = 'Name of numbering numerotation rules of simplified invoice';
        $this->const[$r][4] = 0;
        $r++;

        $this->const[$r][0] = "CLOSECASH_ADDON";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "mod_closecash_fideua";
        $this->const[$r][3] = 'Name of numbering numerotation rules of closecash';
        $this->const[$r][4] = 0;


		// Array to add new pages in new tabs
		// Example: $this->tabs = array('objecttype:+tabname1:Title1:@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__',  // To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:Title2:@mymodule:$user->rights->othermodule->read:/mymodule/mynewtab2.php?id=__ID__',  // To add another new tab identified by code tabname2
        //                              'objecttype:-tabname');                                                     // To remove an existing tab identified by code tabname
		// where objecttype can be
		// 'thirdparty'       to add a tab in third party view
		// 'intervention'     to add a tab in intervention view
		// 'order_supplier'   to add a tab in supplier order view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'invoice'          to add a tab in customer invoice view
		// 'order'            to add a tab in customer order view
		// 'product'          to add a tab in product view
		// 'stock'            to add a tab in stock view
		// 'propal'           to add a tab in propal view
		// 'member'           to add a tab in fundation member view
		// 'contract'         to add a tab in contract view
		// 'user'             to add a tab in user view
		// 'group'            to add a tab in group view
		// 'contact'          to add a tab in contact view
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
        $this->tabs = array();

        // Dictionnaries
        $this->dictionaries=array();
        /*
        $this->dictionaries=array(
            'langs'=>'cabinetmed@cabinetmed',
            'tabname'=>array(MAIN_DB_PREFIX."cabinetmed_diaglec",MAIN_DB_PREFIX."cabinetmed_examenprescrit",MAIN_DB_PREFIX."cabinetmed_motifcons"),
            'tablib'=>array("DiagnostiqueLesionnel","ExamenPrescrit","MotifConsultation"),
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'cabinetmed_diaglec as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'cabinetmed_examenprescrit as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'cabinetmed_motifcons as f'),
            'tabsqlsort'=>array("label ASC","label ASC","label ASC"),
            'tabfield'=>array("code,label","code,label","code,label"),
            'tabfieldvalue'=>array("code,label","code,label","code,label"),
            'tabfieldinsert'=>array("code,label","code,label","code,label"),
            'tabrowid'=>array("rowid","rowid","rowid"),
            'tabcond'=>array($conf->cabinetmed->enabled,$conf->cabinetmed->enabled,$conf->cabinetmed->enabled)
        );
        */

        // Boxes
		// Add here list of php file(s) stored in includes/boxes that contains class to show a box.
        $this->boxes = array();			// List of boxes
		//$r=0;
		// Example:
		/*
		$this->boxes[$r][1] = "myboxa.php";
		$r++;
		$this->boxes[$r][1] = "myboxb.php";
		$r++;
		*/

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$this->rights_class = 'pos';

		$r=0;

		$r++;
		$this->rights[$r][0] = 4000051;
		$this->rights[$r][1] = 'Use POS';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'frontend';

		$r++;
		$this->rights[$r][0] = 4000052;
		$this->rights[$r][1] = 'Use Backend';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'backend';

		$r++;
		$this->rights[$r][0] = 4000053;
		$this->rights[$r][1] = 'Make Transfers';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'transfer';

		/*$r++;
		$this->rights[$r][0] = 400054;
		$this->rights[$r][1] = 'Read';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'read';
		*/
		$r++;
		$this->rights[$r][0] = 4000055;
		$this->rights[$r][1] = 'Stats';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'stats';

		$r++;
		$this->rights[$r][0] = 4000056;
		$this->rights[$r][1] = 'Make Closecash';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'closecash';

		$r++;
		$this->rights[$r][0] = 4000057;
		$this->rights[$r][1] = 'ApplyDiscount';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'discount';

		$r++;
		$this->rights[$r][0] = 4000058;
		$this->rights[$r][1] = 'TicketAvoir';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'return';

		$r++;
		$this->rights[$r][0] = 4000059;
		$this->rights[$r][1] = 'CreateProduct';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'createproduct';


		// Main menu entries
		$this->menus = array();			// List of menus to add
		$r=0;
		if($conf->global->POS_FACTURE){$url='/pos/backend/listefac.php';}
		else {$url='/pos/backend/liste.php';}

		// Add here entries to declare new menus
		// Example to declare the Top Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>0,			// Put 0 if this is a top menu
									'type'=>'top',			// This is a Top menu entry
									'titre'=>'POS',
									'mainmenu'=>'pos',
									'leftmenu'=>'1',		// Use 1 if you also want to add left menu entries using this descriptor.
									'url'=> $url,
									'langs'=>'pos@pos',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		 $r++; // 1

		// Example to declare a Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=0',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'Tickets',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/liste.php',
									'langs'=>'pos@pos',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->global->POS_TICKET',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0=Menu for internal users, 1=external users, 2=both
		 $r++; //2
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=1',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'List',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/liste.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->global->POS_TICKET',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0=Menu for internal users, 1=external users, 2=both
		 $r++; //3
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=2',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'StatusTicketDraft',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/liste.php?viewstatut=0',
									'langs'=>'pos@pos',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->global->POS_TICKET',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0
		  $r++; //4
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=2',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'StatusTicketClosed',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/liste.php?viewstatut=1',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->global->POS_TICKET',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0
		$r++; //5
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=2',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'StatusTicketProcessed',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/liste.php?viewstatut=2',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->global->POS_TICKET',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0

		$r++; //6
		//
		// Example to declare another Left Menu entry:
		$this->menu[$r]=array(	'fk_menu'=>'r=2',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'StatusTicketCanceled',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/liste.php?viewstatut=3',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->global->POS_TICKET',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0

		$r++; //7
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=2',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'StatusTicketReturned',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/liste.php?viewtype=1',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->global->POS_TICKET',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0
		$r++; //8

		// Example to declare a Left Menu entry:
		$this->menu[$r]=array(	'fk_menu'=>'r=0',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'Invoices',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/listefac.php',
									'langs'=>'bills',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->global->POS_FACTURE',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0=Menu for internal users, 1=external users, 2=both
		$r++; //9
		//
		// Example to declare another Left Menu entry:
		$this->menu[$r]=array(	'fk_menu'=>'r=8',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'List',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/listefac.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->global->POS_FACTURE',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0=Menu for internal users, 1=external users, 2=both
		$r++; //10
		//
		// Example to declare another Left Menu entry:
		$this->menu[$r]=array(	'fk_menu'=>'r=9',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'BillStatusDraft',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/liste.php?viewstatut=0',
									'langs'=>'bills',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->global->POS_FACTURE',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0
		$r++; //11
		//
		// Example to declare another Left Menu entry:
		$this->menu[$r]=array(	'fk_menu'=>'r=9',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'BillStatusValidated',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/listefac.php?viewstatut=1',
									'langs'=>'bills',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->global->POS_FACTURE',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0
		$r++; //12
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=9',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									 'type'=>'left',			// This is a Left menu entry
									 'titre'=>'BillStatusPaid',
									 'mainmenu'=>'pos',
									 'url'=>'/pos/backend/listefac.php?viewstatut=2',
									 'langs'=>'bills',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									 'position'=>100,
									 'enabled'=>'$conf->global->POS_FACTURE',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									 'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									 'target'=>'',
									 'user'=>0);				// 0

		$r++; //13
		//
		// Example to declare another Left Menu entry:
		$this->menu[$r]=array(	'fk_menu'=>'r=9',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'BillStatusCanceled',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/listefac.php?viewstatut=3',
									'langs'=>'bills',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->global->POS_FACTURE',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0

		$r++; //14
		//
		// Example to declare another Left Menu entry:
		$this->menu[$r]=array(	'fk_menu'=>'r=9',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									 'type'=>'left',			// This is a Left menu entry
									 'titre'=>'BillStatusReturned',
									 'mainmenu'=>'pos',
									 'url'=>'/pos/backend/listefac.php?search_type=2',
									 'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									 'position'=>100,
									 'enabled'=>'$conf->global->POS_FACTURE',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									 'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									 'target'=>'',
									 'user'=>0);				// 0
		$r++; //15
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=0',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'POS',
									'mainmenu'=>'pos',
									'url'=>'/pos/frontend/index.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->frontend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'_pos',
									'user'=>0);				// 0
		 $r++; //16
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=15',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'TerminalAccess',
									'mainmenu'=>'pos',
									'url'=>'/pos/frontend/index.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->frontend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'_pos',
									'user'=>0);				// 0
		 $r++; //17
		 //
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=15',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'CloseandArching',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/closes.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>101,
									'enabled'=>'$user->rights->pos->closecash && $user->rights->pos->backend',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0

		$r++; //18
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=0',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'Cash',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/terminal/cash.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0

		 $r++; //19
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=18',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'NewCash',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/terminal/fiche.php?action=create',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0
		  $r++; //20
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=18',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'List',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/terminal/cash.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>101,
									'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0

		$r++; //21
		//
		// Example to declare another Left Menu entry:
		$this->menu[$r]=array(	'fk_menu'=>'r=0',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
				'type'=>'left',			// This is a Left menu entry
				'titre'=>'Place',
				'mainmenu'=>'pos',
				'url'=>'/pos/backend/place/place.php',
				'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>100,
				'enabled'=>'$conf->global->POS_PLACES',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
				'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>0);				// 0

		$r++; //22
		//
		// Example to declare another Left Menu entry:
		$this->menu[$r]=array(	'fk_menu'=>'r=21',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
				'type'=>'left',			// This is a Left menu entry
				'titre'=>'NewPlace',
				'mainmenu'=>'pos',
				'url'=>'/pos/backend/place/fiche.php?action=create',
				'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>100,
				'enabled'=>'$conf->global->POS_PLACES',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
				'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>0);				// 0
		$r++; //23
		//
		// Example to declare another Left Menu entry:
		$this->menu[$r]=array(	'fk_menu'=>'r=21',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
				'type'=>'left',			// This is a Left menu entry
				'titre'=>'List',
				'mainmenu'=>'pos',
				'url'=>'/pos/backend/place/place.php',
				'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
				'position'=>101,
				'enabled'=>'$conf->global->POS_PLACES',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
				'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>0);				// 0

		$r++; //24
		//

		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=17',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'NewClose',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/newcloses.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>101,
									'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0
		$r++; //25

		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=17',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'Arqueo',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/closes.php?viewstatut=0',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>101,
									'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0
		$r++; //26
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=17',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'Closes',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/closes.php?viewstatut=1',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>102,
									'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->backend',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0

		$r++; //27
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=0',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'Transfer',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/transfers.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>102,
									'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->transfer',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0
		 $r++; //28
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=0',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'RapportTicket',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/resultat/index.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>102,
									'enabled'=>'$conf->global->POS_TICKET',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0

		$r++; //29
		//
		// Example to declare another Left Menu entry:
		$this->menu[$r]=array(	'fk_menu'=>'r=28',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'Tickets',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/resultat/ticket.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>102,
									'enabled'=>'$conf->global->POS_TICKET',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0

		$r++; //30
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=28',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'ReportsCustomer',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/resultat/casoc.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>102,
									'enabled'=>'$conf->global->POS_TICKET',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0

		$r++; //31
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=28',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'ReportsProduct',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/resultat/caproduct.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>102,
									'enabled'=>'$conf->global->POS_TICKET',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0

		$r++; //32
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=28',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'ReportsUser',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/resultat/causer.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>102,
									'enabled'=>'$conf->global->POS_TICKET',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0

		$r++; //33
		//
		// Example to declare another Left Menu entry:
		$this->menu[$r]=array(	'fk_menu'=>'r=28',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'Terminal',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/resultat/terminal.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>102,
									'enabled'=>'$conf->global->POS_TICKET',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0

		$r++; //34
		//
		// Example to declare another Left Menu entry:
		$this->menu[$r]=array(	'fk_menu'=>'r=28',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'Place',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/resultat/place.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>102,
									'enabled'=>'$conf->global->POS_TICKET && $conf->global->POS_PLACES',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0

		$r++; //35
		//
		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=28',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'ReportsSells',
									'mainmenu'=>'pos',
									'url'=>'/pos/backend/resultat/sellsjournal.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>102,
									'enabled'=>'$conf->global->POS_TICKET',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>0);				// 0

		 $r++; //36
		 //
		 // Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=0',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
							 		'type'=>'left',			// This is a Left menu entry
							 		'titre'=>'RapportFacture',
							 		'mainmenu'=>'pos',
							 		'url'=>'/pos/backend/resultat/indexfac.php',
							 		'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
							 		'position'=>102,
							 		'enabled'=>'$conf->global->POS_FACTURE',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
							 		'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
							 		'target'=>'',
							 		'user'=>0);				// 0

		 $r++; //37
		 //
		 // Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=36',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
							 		'type'=>'left',			// This is a Left menu entry
							 		'titre'=>'Invoices',
							 		'mainmenu'=>'pos',
							 		'url'=>'/pos/backend/resultat/facture.php',
							 		'langs'=>'bills',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
							 		'position'=>102,
							 		'enabled'=>'$conf->global->POS_FACTURE',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
							 		'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
							 		'target'=>'',
							 		'user'=>0);				// 0

		 $r++; //38
		 //
		 // Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=36',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
							 		'type'=>'left',			// This is a Left menu entry
							 		'titre'=>'ReportsCustomer',
									'mainmenu'=>'pos',
							 		'url'=>'/pos/backend/resultat/casocfac.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
							 		'position'=>102,
							 		'enabled'=>'$conf->global->POS_FACTURE',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
							 		'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
							 		'target'=>'',
							 		'user'=>0);				// 0

		$r++; //39
		//
		 // Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=36',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
							 		'type'=>'left',			// This is a Left menu entry
							 		'titre'=>'ReportsProduct',
									'mainmenu'=>'pos',
							 		'url'=>'/pos/backend/resultat/caproductfac.php',
									'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
							 		'position'=>102,
							 		'enabled'=>'$conf->global->POS_FACTURE',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
							 		'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
							 		'target'=>'',
							 		'user'=>0);				// 0

		$r++; //40
		//
		 		// Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=36',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
							 		'type'=>'left',			// This is a Left menu entry
							 		'titre'=>'ReportsUser',
							 		'mainmenu'=>'pos',
							 		'url'=>'/pos/backend/resultat/causerfac.php',
							 		'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
							 		'position'=>102,
							 		'enabled'=>'$conf->global->POS_FACTURE',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
							 		'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
							 		'target'=>'',
							 		'user'=>0);				// 0

		 $r++; //41
		 //
		 // Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=36',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
							 		'type'=>'left',			// This is a Left menu entry
							 		'titre'=>'Terminal',
							 		'mainmenu'=>'pos',
							 		'url'=>'/pos/backend/resultat/terminalfac.php',
							 		'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>102,
							 		'enabled'=>'$conf->global->POS_FACTURE',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
							 		'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
							 		'user'=>0);				// 0

		 $r++; //42
		 //
		 // Example to declare another Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=36',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
							 		'type'=>'left',			// This is a Left menu entry
							 		'titre'=>'Place',
							 		'mainmenu'=>'pos',
							 		'url'=>'/pos/backend/resultat/placefac.php',
							 		'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
							 		'position'=>102,
							 		'enabled'=>'$conf->global->POS_FACTURE && $conf->global->POS_PLACES',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
							 		'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
							 		'target'=>'',
							 		'user'=>0);				// 0

		 $r++; //43
 		 //
 		 // Example to declare another Left Menu entry:
 		$this->menu[$r]=array(	'fk_menu'=>'r=36',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
					 				'type'=>'left',			// This is a Left menu entry
					 				'titre'=>'ReportsSells',
					 				'mainmenu'=>'pos',
					 				'url'=>'/pos/backend/resultat/sellsjournalfac.php',
					 				'langs'=>'main',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
					 				'position'=>102,
					 				'enabled'=>'$conf->global->POS_FACTURE',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					 				'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					 				'target'=>'',
					 				'user'=>0);				// 0
        $r++; //434
        //
        // Example to declare another Left Menu entry:
        $this->menu[$r]=array(	'fk_menu'=>'r=36',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
            'type'=>'left',			// This is a Left menu entry
            'titre'=>'ReportByQuarter',
            'mainmenu'=>'pos',
            'url'=>'/pos/backend/resultat/quadri_detail.php',
            'langs'=>'companies',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>103,
            'enabled'=>'$conf->global->POS_FACTURE',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
            'perms'=>'$user->rights->pos->stats',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0);				// 0

		 // Exports
		//$r=1;

		// Example:
		// $this->export_code[$r]=$this->rights_class.'_'.$r;
		// $this->export_label[$r]='CustomersInvoicesAndInvoiceLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        // $this->export_enabled[$r]='1';                               // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
		// $this->export_permission[$r]=array(array("facture","facture","export"));
		// $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.cp'=>'Zip','s.ville'=>'Town','s.fk_pays'=>'Country','s.tel'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"InvoiceId",'f.facnumber'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus','f.note'=>"InvoiceNote",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_tx'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalTVA",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",'fd.date_end'=>"DateEnd",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef');
		// $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.cp'=>'company','s.ville'=>'company','s.fk_pays'=>'company','s.tel'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
		// $this->export_sql_start[$r]='SELECT DISTINCT ';
		// $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd, '.MAIN_DB_PREFIX.'societe as s)';
		// $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		// $this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
		// $r++;
	}

    /**
     * Function called when module is enabled.
     * The init function adds tabs, constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     * It also creates data directories
     *
     * @param string $options   Options when enabling module ('', 'newboxdefonly', 'noboxes')
     *                          'noboxes' = Do not insert boxes
     *                          'newboxdefonly' = For boxes, insert def of boxes only and not boxes activation
     * @return int				1 if OK, 0 if KO
     */
    public function init($options = '')
	{
		global $db, $conf;
		require_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');
		$dirodt=DOL_DATA_ROOT.'/produit';
		dol_mkdir($dirodt);
		dol_copy(dol_buildpath('/pos/frontend/img/noimage.jpg',0),$dirodt.'/noimage.jpg',0,0);

		if(empty($conf->global->POS_TICKET) && empty($conf->global->POS_FACTURE))
		{
			dolibarr_set_const($db,"POS_FACTURE", '1','chaine',0,'',$conf->entity);
		}
		if(empty($conf->global->POS_MAX_TTC))
		{
			dolibarr_set_const($db,"POS_MAX_TTC", '100','chaine',0,'',$conf->entity);
		}
		if(empty($conf->global->POS_NO_TWITTER)){
			dolibarr_set_const($db,"POS_NO_TWITTER", '0','chaine',1,'If value 1, tweet box from frontend disappear',$conf->entity);
		}

		$sql = array();
		$this->load_tables();
		return $this->_init($sql);
	}

    /**
     * Function called when module is disabled.
     * The remove function removes tabs, constants, boxes, permissions and menus from Dolibarr database.
     * Data directories are not deleted
     *
     * @param      string	$options    Options when enabling module ('', 'noboxes')
     * @return     int             		1 if OK, 0 if KO
     */
    public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql);
	}


	/**
	 *		\brief		Create tables, keys and data required by module
	 * 					Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 					and create data commands must be stored in directory /mymodule/sql/
	 *					This function is called by this->init.
	 * 		\return		int		<=0 if KO, >0 if OK
	 */
	public function load_tables()
	{
		return $this->_load_tables('/pos/sql/');
	}
}
