<?php
/* Copyright (C) 2011-2012 Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012-2017 Ferran Marcet           <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU  *General Public License as published by
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
 *	\file       htdocs/pos/backend/liste.php
 *	\ingroup    ticket
 *	\brief      Page to list tickets
 */

$res=@include("../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");                // For "custom" directory

dol_include_once('/pos/class/ticket.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
dol_include_once('/pos/class/cash.class.php');
require_once(DOL_DOCUMENT_ROOT ."/core/lib/date.lib.php");
dol_include_once('/pos/class/pos.class.php');

$langs->load('pos@pos');
$langs->load('deliveries');
$langs->load('companies');
$langs->load('users');

$ticketyear=GETPOST("ticketyear","int");
$ticketmonth=GETPOST("ticketmonth","int");
$deliveryyear=GETPOST("deliveryyear","int");
$deliverymonth=GETPOST("deliverymonth","int");
$socid=GETPOST('socid','int');
$userid=GETPOST('userid','int');
$viewstatut=GETPOST('viewstatut');
$viewtype=GETPOST('viewtype');
$closeid=GETPOST('closeid','int');
$placeid=GETPOST('placeid','int');
$cashid=GETPOST('cashid','int');
$action=GETPOST('action','string');
$toselect = GETPOST('toselect', 'array');
$massaction = GETPOST('massaction','alpha');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$month    =GETPOST('month','int');
$year     =GETPOST('year','int');

$limit = $conf->liste_limit;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='f.date_ticket';

if ($action == 'send')
{
	$langs->load('mails');
	$actiontypecode='';$subject='';$actionmsg='';$actionmsg2='';

	if (GETPOST('sendto'))
	{
		// Le destinataire a ete fourni via le champ libre
		$sendto = GETPOST('sendto');
		$sendtoid = 0;
	}
	if (dol_strlen($sendto))
	{
		$langs->load("commercial");

		$from =  $conf->global->MAIN_INFO_SOCIETE_NOM."<".$conf->global->MAIN_INFO_SOCIETE_MAIL.">";
		$message = GETPOST('message','alpha');

		if (GETPOST('action','alpha') == 'send')
		{
			if (dol_strlen(GETPOST('subject','alpha'))) $subject = GETPOST('subject','alpha');
			else $subject = $langs->transnoentities('Bill').' '.$object->ref;
			$actiontypecode='AC_EMAIL';
			$actionmsg=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto.".\n";
			if ($message)
			{
				$actionmsg.=$langs->transnoentities('MailTopic').": ".$subject."\n";
				$actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody').":\n";
				$actionmsg.=$message;
			}
		}


		// Send mail
		require_once(DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php');
		$mailfile = new CMailFile($subject,$sendto,$from,$message);
		if(!preg_match("/^(?:[\w\d]+\.?)+@(?:(?:[\w\d]\-?)+\.)+\w{2,4}$/", $sendto)) {
			$mailfile->error = $langs->trans('ErrorFailedToSendMail',$from,$sendto);
		}

		if ($mailfile->error)
		{
			setEventMessage($mailfile->error,"errors");
		}
		else
		{
			$result=$mailfile->sendfile();
			if ($result)
			{
				setEventMessage($langs->trans('MailSuccessfulySent',$from,$sendto));		// Must not contain "

				Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id.'&mesg=1');
				//exit;

			}
			else
			{
				$langs->load("other");
				if ($mailfile->error)
				{
					setEventMessage($langs->trans('ErrorFailedToSendMail',$from,$sendto).'<br>'.$mailfile->error,"errors");
				}
				else
				{
					setEventMessage('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS',"errors");
				}

			}
		}
	}
	else
	{
		$langs->load("other");
		setEventMessage($langs->trans('ErrorMailRecipientIsEmpty'),"errors");
		dol_syslog('Recipient email is empty');
	}

	$_POST['action'] = 'presend';
}

if(count($toselect)>0 && !GETPOST('invoince')){
	foreach ($toselect as $ticket){
		$ticketdel = new TicketPOS($db);
		$ticketdel->id = $ticket;
		$ticketdel->delete_ticket();
	}
}
if(count($toselect)>0 && GETPOST('invoince')){
	$tickets = array();
	foreach ($toselect as $ticketid){
		$ticket = new TicketPOS($db);
		$ticket->fetch($ticketid);

		if(!array_key_exists($ticket->socid, $tickets)) {
			$tickets[$ticket->socid][0] = $ticketid;
		}
		else{
			array_push($tickets[$ticket->socid], $ticketid);
		}
	}

	if($tickets) {
		foreach ($tickets[1] as $client) {
			$pos = new TicketPOS($db);
			$pos->fetch($client,'');
			$pos->fetchObjectLinked();
			if(!$pos->linkedObjects['facture']) {
				$result = $pos->create_facture();
				if (!$result) {
					setEventMessages('Error to create invoice for ticket -> ' . $pos->ref,'','errors');
					$viewstatut = 1;
				}
				else{
					setEventMessage('Invoice for ticket -> ' . $pos->ref . ' created');
				}
			}
			else{
				setEventMessages('Error: invoice already exist for ticket -> ' . $pos->ref,'','errors');
				$viewstatut = 1;
			}
		}
	}
}
/*
 * View
 */
$helpurl='EN:Module_DoliPos|FR:Module_DoliPos_FR|ES:M&oacute;dulo_DoliPos';
llxHeader("",$langs->trans("Tickets"),$helpurl);
dol_htmloutput_events();

$html = new FormOther($db);
$ticketstatic=new TicketPOS($db);
$now=dol_now();

if (!$user->rights->pos->backend)
{
	print '<a href="'.dol_buildpath('/pos/frontend/index.php',1).'"><img src='.dol_buildpath('/pos/frontend/img/bgback.png',1).' WIDTH="50%" HEIGHT="50%" ></a>';
}
else {
	if ($page == -1) $page = 0 ;

	$sql = 'SELECT ';
	$sql.= ' f.rowid as ticketid, f.type, f.ticketnumber, f.total_ht, f.total_ttc,';
	$sql.= ' f.date_creation as df, f.fk_user_close,';
	$sql.= ' f.paye as paye, f.fk_statut, f.customer_pay, f.difpayment, ';
	$sql.= ' s.nom, s.rowid as socid,';
	$sql.= ' u.firstname, u.lastname,';
	$sql.= ' t.name, f.fk_cash';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
	$sql.= ', '.MAIN_DB_PREFIX.'pos_ticket as f';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON f.fk_user_close = u.rowid';
	$sql.= ', '.MAIN_DB_PREFIX.'pos_cash as t';
	$sql.= ' WHERE f.fk_soc = s.rowid';
	$sql.= " AND f.entity = ".$conf->entity;
	$sql.= " AND f.fk_cash = t.rowid";
	//$sql.= " AND f.fk_user_close = u.rowid";
	if ($socid) $sql.= ' AND s.rowid = '.$socid;
	if ($userid) {
		$sql.= ' AND u.rowid = '.$userid;
		//$sql.= ' AND f.fk_user_close = u.rowid';
	}
	if ($viewstatut <> '') $sql.= ' AND f.fk_statut = '.$viewstatut;
	if ($viewtype <> '') $sql.= ' AND f.type = '.$viewtype;
	if ($closeid <> '') $sql.= ' AND f.fk_control = '.$closeid;
	if ($placeid <> '') $sql.= ' AND f.fk_place = '.$placeid;
	if ($cashid <> '') $sql.= ' AND f.fk_cash = '.$cashid;
	if ($_POST['filtre'])
	{
		$filtrearr = explode(',', $_POST['filtre']);
		foreach ($filtrearr as $fil)
		{
			$filt = explode(':', $fil);
			$sql .= ' AND ' . trim($filt[0]) . ' = ' . trim($filt[1]);
			}
	}
	if ($_POST['search_ref'])
	{
		 $sql.= ' AND f.ticketnumber LIKE \'%'.$db->escape(trim($_POST['search_ref'])).'%\'';
	}
	if ($_POST['search_cash'])
	{
		$sql.= ' AND t.name LIKE \'%'.$db->escape(trim($_POST['search_cash'])).'%\'';
	}
	if ($_POST['search_user'])
	{
		$sql.= ' AND (u.firstname LIKE \'%'.$db->escape(trim($_POST['search_user'])).'%\'';
		$sql.= ' OR u.lastname LIKE \'%'.$db->escape(trim($_POST['search_user'])).'%\')';
	}
	if ($_POST['search_societe'])
	{
		$sql.= ' AND s.nom LIKE \'%'.$db->escape(trim($_POST['search_societe'])).'%\'';
	}
	if ($_POST['search_montant_ht'])
	{
		$sql.= ' AND f.total_ht like \'%'.$db->escape(trim($_POST['search_montant_ht'])).'%\'';
	}
	if ($_POST['search_montant_ttc'])
	{
		$sql.= ' AND f.total_ttc like \'%'.$db->escape(trim($_POST['search_montant_ttc'])).'%\'';
	}
	if ($month > 0)
	{
		if ($year > 0)
			$sql.= " AND f.date_ticket BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
		else
		$sql.= " AND date_format(f.date_ticket, '%m') = '".$month."'";
	}
	else if ($year > 0)
	{
		$sql.= " AND f.date_ticket BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
	}
	if ($_POST['sf_ref'])
	{
		$sql.= ' AND f.ticketnumber LIKE \'%'.$db->escape(trim($_POST['sf_ref'])) . '%\'';
		}
	if ($sall)
	{
		$sql.= ' AND (s.nom LIKE \'%'.$db->escape($sall).'%\' OR f.ticketnumber LIKE \'%'.$db->escape($sall).'%\' OR f.note LIKE \'%'.$db->escape($sall).'%\' OR fd.description LIKE \'%'.$db->escape($sall).'%\')';
	}
	if (! $sall)
	{
		$sql.= ' GROUP BY f.rowid, f.ticketnumber, f.total_ht, f.total_ttc,';
		$sql.= ' f.date_ticket,';
		$sql.= ' f.paye, f.fk_statut,';
		$sql.= ' s.nom, s.rowid,';
        $sql.= ' u.firstname, u.lastname,';
        //mysql strict
        $sql.= ' f.type, f.date_creation, f.fk_user_close, f.customer_pay, f.difpayment, t.name, f.fk_cash';
        //
	}
	$sql.= ' ORDER BY ';
	$listfield=explode(',',$sortfield);
	foreach ($listfield as $key => $value) $sql.= $listfield[$key].' '.$sortorder.',';
	$sql.= ' f.rowid DESC ';
	$sql.= $db->plimit($limit+1,$offset);
	        //print $sql;

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$arrayofselected=is_array($toselect)?$toselect:array();

		if ($socid)
		{
			$soc = new Societe($db);
			$soc->fetch($socid);
		}

		$arrayofmassactions=array(
			'presend'=>$langs->trans("SendByMail"),
			'builddoc'=>$langs->trans("PDFMerge")
		);
		if ($user->rights->facture->supprimer)
		{
			//if (! empty($conf->global->STOCK_CALCULATE_ON_BILL) || empty($conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED))
			if (empty($conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED))
			{
				// mass deletion never possible on invoices on such situation
			}
			else
			{
				$arrayofmassactions['delete']=$langs->trans("Delete");
			}
		}
		if ($massaction == 'presend') $arrayofmassactions=array();
		$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

		$param='&amp;socid='.$socid;
		if ($month) $param.='&amp;month='.$month;
		if ($year)  $param.='&amp;year=' .$year;

		$txtListe = $langs->trans('TicketsCustomers');

		if ($viewstatut <> '')
		{
			$txtListe = $txtListe." - ".$ticketstatic->LibStatut($viewstatut);
		}

		if ($viewtype <> '')
		{
			$txtListe = $txtListe." - ".$langs->trans("StatusTicketReturned");
		}

		print_barre_liste($txtListe.' '.($socid?' '.$soc->nom:''),$page,'liste.php',$param,$sortfield,$sortorder,'',$num);

		$i = 0;
		print '<form method="POST" name="liste_ticket" action="'.$_SERVER["PHP_SELF"].'">'."\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		print '<input type="hidden" name="action" value="list">';
		print '<table class="liste" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'f.ticketnumber','',$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'f.date_ticket','',$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Terminal'),$_SERVER['PHP_SELF'],'t.name','',$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('User'),$_SERVER['PHP_SELF'],'u.lastname','',$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Customer'),$_SERVER['PHP_SELF'],'s.nom','',$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('AmountHT'),$_SERVER['PHP_SELF'],'f.total_ht','',$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('AmountTTC'),$_SERVER['PHP_SELF'],'f.total_ttc','',$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Received'),$_SERVER['PHP_SELF'],'f.total_ttc','',$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('AmountDiff'),$_SERVER['PHP_SELF'],'f.difpayment','',$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Status'),$_SERVER['PHP_SELF'],'f.fk_statut','',$param,'align="right"',$sortfield,$sortorder);
	    print '<td class="liste_titre">&nbsp;</td>';
		print '</tr>';

		// Lignes des champs de filtre

		print '<tr class="liste_titre">';
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_POST['search_ref'].'">';
		print '<td class="liste_titre" colspan="1" align="center">';
		print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
		//$syear = $year;
	    //if ($syear == '') $syear = date("Y");
		$html->select_year($syear?$syear:-1,'year',1, 20, 5);
		print '</td>';

		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" name="search_cash" value="'.$_POST['search_cash'].'">';
		print '</td>';

		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" name="search_user" value="'.$_POST['search_user'].'">';
		print '</td>';

		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" name="search_societe" value="'.$_POST['search_societe'].'">';
		print '</td><td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$_POST['search_montant_ht'].'">';
		print '</td><td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="10" name="search_montant_ttc" value="'.$_POST['search_montant_ttc'].'">';
		print '</td>';
		print '<td class="liste_titre" align="right">';
		print '&nbsp;';
		print '</td>';
		print '<td class="liste_titre" align="right">';
		print '&nbsp;';
		print '</td>';

		// Action column
		if(version_compare(DOL_VERSION,4.0)>=0) {
			print '<td class="liste_titre" align="right">';
			$searchpitco = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
			print $searchpitco;
			print '</td>';
		}
		else{
			print '<td class="liste_titre" align="right">';
			print '</td>';
		}
		print '<td class="liste_titre" align="left">&nbsp;</td>';
		print "</td></tr>\n";

		if ($num > 0)
		{
			$var=True;
			$total=0;
			$totalrecu=0;

			while ($i < min($num,$limit))
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;

				print '<tr '.$bc[$var].'>';
				print '<td nowrap="nowrap">';

				$ticketstatic->id=$objp->ticketid;
				$ticketstatic->ref=$objp->ticketnumber;
				$paiement = $ticketstatic->getSommePaiement();

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';

				print '<td class="nobordernopadding" nowrap="nowrap">';
				//print $ticketstatic->ref;
				print $ticketstatic->getNomUrl(1);
				print '</td>';

				print '</tr></table>';

				print "</td>\n";

				// Date
				print '<td align="center" nowrap>';
				print dol_print_date($db->jdate($objp->df),'day');
				print '</td>';

				print '<td>';
				$cash=new Cash($db);
				$cash->fetch($objp->fk_cash);
				print $cash->getNomUrl(1);
				print '</td>';
				print '<td>';
				if ($objp->fk_user_close>0)
				{
					$userstatic=new User($db);
		        	$userstatic->fetch($objp->fk_user_close);
		       	 	print $userstatic->getNomUrl(1);
				}
				print '</td>';

				print '<td>';
				if(!$user->rights->societe->client->voir)
				{
					print $objp->nom;
				}
				else
				{
					$thirdparty=new Societe($db);
					$thirdparty->id=$objp->socid;
					$thirdparty->nom=$objp->nom;
					print $thirdparty->getNomUrl(1,'customer');
				}
				print '</td>';

				if($objp->type==0)
				{
					$objtotal=$objp->total_ht;
					$objttc=$objp->total_ttc;
					$objcustpay=$objp->total_ttc>$objp->customer_pay?$objp->customer_pay:$objp->total_ttc;
					$objdifpay=$objp->total_ttc-$objcustpay;
				}
				else
				{
					$objtotal=$objp->total_ht *-1;
					$objttc=$objp->total_ttc *-1;
					$objcustpay=$objp->total_ttc *-1;
					$objdifpay=$objp->difpayment  *-1;
				}

				print '<td align="right">'.price($objtotal).'</td>';
				print '<td align="right">'.price($objttc).'</td>';
				print '<td align="right">'.price($objcustpay).'</td>';
				print '<td align="right">'.price($objdifpay).'</td>';

				// Affiche statut de la ticket
				print '<td align="left" nowrap="nowrap">';
				print $ticketstatic->LibStatut($objp->fk_statut,1);
				print "</td>";
				// Action column
				if(version_compare(DOL_VERSION,4.0)>=0) {
					print '<td class="nowrap" align="center">';
					if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
					{
						$selected = 0;
						if (in_array($objp->ticketid, $arrayofselected)) $selected = 1;
						print '<input id="cb' . $objp->ticketid . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $objp->ticketid . '"' . ($selected ? ' checked="checked"' : '') . '>';
					}
					print '</td>';
				}
				else {
					print '<td class="nowrap" align="center">';
					print '</td>';
				}
				print "</tr>\n";
				$total+=$objtotal;
				$total_ttc+=$objttc;
				$totalrecu+=$objcustpay;
				$totaldif+=$objdifpay;
				$i++;
			}

			if (($offset + $num) <= $limit)
			{
				// Print total
				print '<tr class="liste_total">';
				print '<td class="liste_total" colspan="3" align="left">'.$langs->trans('Total').'</td>';
				print '<td class="liste_total" align="center">&nbsp;</td>';
				print '<td class="liste_total" align="center">&nbsp;</td>';
				print '<td class="liste_total" align="right">'.price($total).'</td>';
				print '<td class="liste_total" align="right">'.price($total_ttc).'</td>';
				print '<td class="liste_total" align="right">'.price($totalrecu).'</td>';
				print '<td class="liste_total" align="right">'.price($total_ttc-$totalrecu).'</td>';
				print '<td class="liste_total" align="center">&nbsp;</td>';
				print "<td>&nbsp;</td>";
				print '</tr>';
			}
		}

		print "</table>\n";
		print '<div class="tabsAction">';
		print '<input type="submit" class="butActionDelete" value="'.$langs->trans("Delete").'" />';
		if($viewstatut==1) {
			print '<input type="submit" name="invoince" class="butAction" value="' . $langs->trans("Invoince") . '" /></div>';
		}
		print "</form>\n";
		$db->free($resql);

		print '<div class="tabsAction">';
		if ($closeid)
		{
			$url = '../frontend/tpl/closecash.tpl.php?id='.$closeid;
			print '<a class="butAction" href='.$url.' target="_blank">'.$langs->trans('PrintCopy').'</a>';

			print '<a class="butAction" href="'.dol_buildpath('/pos/backend/liste.php',1).'?closeid='.$closeid.'&viewstatut=2&action=mail">'.$langs->trans('MailCopy').'</a>';

		}
		print '</div>';

		if( GETPOST('action','string') == 'mail')
		{
			include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
			$formmail = new FormMail($db);

			$action='send';
			$modelmail='body';

			print '<br>';

			print_titre($langs->trans($titre));

			$formmail->fromtype = 'user';
			$formmail->fromid   = $user->id;
			$formmail->fromname = $conf->global->MAIN_INFO_SOCIETE_NOM;
			$formmail->frommail = $conf->global->MAIN_INFO_SOCIETE_MAIL;
			$formmail->withfrom=0;
			$formmail->withto=empty($_POST["sendto"])?1:GETPOST('sendto');
			$formmail->withtocc=0;
			$formmail->withtoccsocid=0;
			$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
			$formmail->withtocccsocid=0;
			$formmail->withtopic=$conf->global->MAIN_INFO_SOCIETE_NOM.': '.$langs->trans("CopyOfCloseCash").' '.$closeid;
			$formmail->withfile=0;
			$formmail->withbody= POS::FillMailCloseCashBody($closeid);
			$formmail->withdeliveryreceipt=0;
			$formmail->withcancel=1;

			$formmail->param['action']=$action;
			$formmail->param['models']=$modelmail;
			$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$id;
			$formmail->show_form();

			print '<br>';
		}
	}
	else
	{
		dol_print_error($db);
	}
}
llxFooter();

$db->close();
?>
