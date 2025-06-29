<?php

$res=@include("../../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../../main.inc.php");                // For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
dol_include_once('/pos/class/cash.class.php');
dol_include_once('/pos/class/place.class.php');
dol_include_once('/pos/backend/lib/pos.lib.php');

global $langs,$db,$mysoc,$conf;

$langs->load("main");
$langs->load("pos@pos");
$langs->load("rewards@rewards");
$langs->load("bills");
header("Content-type: text/html; charset=".$conf->file->character_set_client);
$id=GETPOST('id');
?>
<html>
<head>
<title>Print facture</title>

<style type="text/css">

	body {
		font-size: 14px;
		position: relative;
		font-family: monospace,courier,arial,helvetica,system;
        margin: 35px;
	}

	.entete {
/* 		position: relative; */
	}

		.adresse {
/* 			float: left; */
			font-size: 12px;
		}

		.date_heure {
			float: right;
		font-size: 12px;
		width: 100%;
		text-align: center;
		}

		.infos {
			position: relative;
			font-size: 14px;
		}


	.liste_articles {
		width: 100%;
		border-bottom: 1px solid #000;
		text-align: center;
		font-size: 12px;
	}

		.liste_articles tr.titres th {
			border-bottom: 1px solid #000;
			font-size: 13px;
		}

		.liste_articles td.total {
			text-align: right;
			font-size: 13px;
		}

	.total_tot {
	    font-size: 15px;
	    font-weight: bold;
	    text-align: right;
	}

	.totaux {
		margin-top: 20px;
		width: 40%;
		float: right;
		text-align: right;
		font-size: 14px;
	}

	.totpay {
		margin-left: 50%;
		width: 30%;
		float: right;
		text-align: right;
		font-size: 14px;
	}

	.note{
		float: right;
		font-size: 12px;
		width: 100%;
		text-align: center;
	}

	.lien {
		position: absolute;
		top: 0;
		left: 0;
		display: none;
		font-size: 14px;
	}

	@media print {

		.lien {
			display: none;
		}
		@page{

		    margin: 0;

		}

	}

</style>

</head>

<body onload="window.print()" onafterprint="<?php echo ($conf->global->POS_CLOSE_WIN ?'window.close()':''); ?>">

<div class="entete">
	<?php if (! empty($conf->global->POS_TICKET_LOGO)) { ?>
	<div class="logo">
	<?php
		print '<img src="' . DOL_URL_ROOT . get_mycompanylogo() . '">';
	?>
	</div>
	<?php } ?>
	<div class="infos">
		<p class="adresse"><?php echo $mysoc->name; ?><br>
		<?php echo $mysoc->idprof1;?><br>
		<?php echo $mysoc->address; ?><br>
		<?php echo $mysoc->zip.' '.$mysoc->town; ?><br>
		<?php echo (!empty($mysoc->state_id)?getState($mysoc->state_id):''); ?><br>
		<?php echo $mysoc->phone; ?><br><br>

		<?php

			// Variables

			$object=new Facture($db);
			$result=$object->fetch($id,$ref);

			$userstatic=new User($db);
			$userstatic->fetch($object->user_valid);
			print $langs->trans("VendorPOS").': '.$userstatic->firstname.' '.$userstatic->lastname.'<br><br>';

			$client=new Societe($db);
			$client->fetch($object->socid);
			print $client->name.'<br>';
			print $client->idprof1.'<br>';
			print $client->address.'<br>';
			print $client->zip.' '.$client->town.'<br>';
			print $client->state.'</p>';

			$sql = "SELECT fk_place,fk_cash FROM ".MAIN_DB_PREFIX."pos_facture WHERE fk_facture =".$object->id;
			$result=$db->query($sql);

			if ($result)
			{
				$objp = $db->fetch_object($result);
				if($objp->fk_place > 0){
					$place = new Place($db);
					$place->fetch($objp->fk_place);
					print $langs->trans("Place").': '.$place->name.'</p>';
				}
			}

		?>
	</div>
</div>

<?php
if ($result){
	if (! empty($object->lines)){
		$onediscount = false;
		foreach ($object->lines as $line){
			if($line->remise_percent)
				$onediscount = true;
		}
	}
}

?>
<div class="infos"><?php print $object->note_private?></div>
<table class="liste_articles">
	<tr class="titres"><th><?php print $langs->trans("Label"); ?></th><th><?php print $langs->trans("Qty")."/".$langs->trans("Price"); ?></th><?php if($onediscount)print '<th>'.$langs->trans("DiscountLineal").'</th>'; ?><th><?php print $langs->trans("Total"); ?></th></tr>

	<?php

		if ($result)
		{
			//$object->getLinesArray();
			if (! empty($object->lines))
			{
				//$subtotal=0;
				$promos=0;

                if (!empty($conf->global->MAIN_MULTILANGS)) {
                    $outputlangs = new Translate("",$conf);
                    $outputlangs->setDefaultLang($userstatic->lang);
                }

                foreach ($object->lines as $line)
				{

					if($conf->discounts->enabled){
						dol_include_once('/discounts/class/discount_doc.class.php');
						$langs->load("discounts@discounts");
						$dis_doc = new Discounts_doc($db);
						$res = $dis_doc->fetch(3, $line->rowid);
						if($res > 0){
							$are_promo = true;
						}
						else{
							$are_promo = false;
						}
					}


					if(empty($line->product_label))
						$line->product_label = $line->desc;

                    $label = $line->product_label;

                    if (! empty($conf->global->MAIN_MULTILANGS)) {
                        $prodser = new Product($db);
                        if ($line->fk_product) {
                            $prodser->fetch($line->fk_product);

                            if (!empty($conf->global->MAIN_MULTILANGS)) {
                                $label = $prodser->label;
                            }
                        }
                    }
					$labeltxt = $label;
                    $batch = str_replace($line->product_desc,'',$line->desc);
                    $label = $label.$batch;
                    $batch = str_replace('<br>','',$batch);

                    if($conf->global->POS_PRINT_MODE==1){
                        $label .= '&nbsp;&nbsp;&nbsp;<b>Ref: </b>'.$line->ref;
                    }

                    if (preg_match('/\(CREDIT_NOTE\)/', $line->product_label)) $line->product_label=preg_replace('/\(CREDIT_NOTE\)/', $langs->trans("CreditNote"), $line->product_label);
                    if (preg_match('/\(DEPOSIT\)/', $line->product_label)) $line->product_label=preg_replace('/\(DEPOSIT\)/', $langs->trans("Deposit"), $line->product_label);

					if($are_promo){
						echo ('<tr><td align="left">'.$label.'</td><td align="left">'.$line->qty." * ".price(price2num($conf->global->POS_TICKET_TTC?$dis_doc->ori_subprice*(1+$line->tva_tx/100):$dis_doc->ori_subprice),0,'',1,-1,$conf->global->MAIN_MAX_DECIMALS_TOT).'</td>'.($onediscount?'<td align="right">'.$line->remise_percent.'%</td>':'').'<td class="total">'.price(price2num($conf->global->POS_TICKET_TTC?$dis_doc->ori_totalht*(1+$line->tva_tx/100):$dis_doc->ori_totalht),0,'',1,-1,$conf->global->MAIN_MAX_DECIMALS_TOT).'</td></tr>');
						echo ('<tr><td align="left">'.$dis_doc->descr.'</td><td align="left"></td>'.($onediscount?'<td align="right"></td>':'').'<td class="total">-'.price(price2num($conf->global->POS_TICKET_TTC?$dis_doc->ori_totalht*(1+$line->tva_tx/100) - $line->total_ttc:$dis_doc->ori_totalht - $line->total_ht),0,'',1,-1,$conf->global->MAIN_MAX_DECIMALS_TOT).'</td></tr>');
						$linepromo=$conf->global->POS_TICKET_TTC?$dis_doc->ori_totalht*(1+$line->tva_tx/100) - $line->total_ttc:$dis_doc->ori_totalht - $line->total_ht;
						$promos+=$linepromo;
					}
					else{
						echo ('<tr><td align="left">'.$label.'</td><td align="left">'.$line->qty." * ".price(price2num($conf->global->POS_TICKET_TTC?$line->subprice*(1+$line->tva_tx/100):$line->subprice),0,'',1,-1,$conf->global->MAIN_MAX_DECIMALS_TOT).'</td>'.($onediscount?'<td align="right">'.$line->remise_percent.'%</td>':'').'<td class="total">'.price(price2num($conf->global->POS_TICKET_TTC?$line->total_ttc:$line->total_ht),0,'',1,-1,$conf->global->MAIN_MAX_DECIMALS_TOT).'</td></tr>');
					}
					$subtotal[$line->tva_tx] += $line->total_ht;
					$subtotaltva[$line->tva_tx] += $line->total_tva;
					if(!empty($line->total_localtax1)){
						$localtax1 = $line->localtax1_tx;
					}
					if(!empty($line->total_localtax2)){
						$localtax2 = $line->localtax2_tx;
					}
				}
			}
			else
			{
				echo ('<p>'. $langs->trans("ErrNoArticles").'</p>'."\n");
			}

		}
	?>
</table>
<?php if($promos > 0){?>
<div class="total_tot"><?php echo $langs->trans("InPromo").'   -'.price(price2num($promos, 4)).' '.$langs->trans(currency_name($conf->currency));?></div>
<?php } ?>
<div class="total_tot"><?php echo $langs->trans("TotalTTC").'   '.price(price2num($object->total_ttc)).' '.$langs->trans(currency_name($conf->currency));?></div>
<table class="totaux">
	<?php

	echo '<tr><th nowrap="nowrap" style="width:50%;">'.$langs->trans("TotalHT").'</th><th nowrap="nowrap" style="width:25%;">'.$langs->trans("VAT").'</th><th nowrap="nowrap" style="width:25%;">'.$langs->trans("TotalVAT").'</th></tr>';
	if(! empty($subtotal)){
		foreach($subtotal as $totkey => $totval){
			echo '<tr><td nowrap="nowrap" style="text-align:left;">'.price(price2num($subtotal[$totkey])).'</td><td nowrap="nowrap">'.price(price2num($totkey)).'%</td><td nowrap="nowrap">'.price(price2num($subtotaltva[$totkey])).'</td></tr>';
		}
	}

	echo '<tr><td nowrap="nowrap" style="border-top: 1px dashed #000000;text-align:left;">'.price(price2num($object->total_ht)).'</td><td style="border-top: 1px dashed #000000;">--</td><td nowrap="nowrap" style="border-top: 1px dashed #000000;">'.price(price2num($object->total_tva))."</td></tr>";

		if($object->total_localtax1!=0){
			echo '<tr><td></td><th nowrap="nowrap">'.$langs->transcountrynoentities("TotalLT1",$mysoc->country_code).' '.price(price2num($localtax1)).'%</th><td nowrap="nowrap">'.price(price2num($object->total_localtax1))."</td></tr>";
		}
		if($object->total_localtax2!=0){
			echo '<tr><td></td><th nowrap="nowrap">'.$langs->transcountrynoentities("TotalLT2",$mysoc->country_code).' '.price(price2num($localtax2)).'%</th><td nowrap="nowrap">'.price(price2num($object->total_localtax2))."</td></tr>";
		}


		?>
		</table>

		<table class="totpay">
		<?php
		echo '<tr><td></td></tr>';
		echo '<tr><td></td></tr>';

		$terminal = new Cash($db);
		$sql = 'SELECT fk_cash, customer_pay FROM '.MAIN_DB_PREFIX.'pos_facture WHERE fk_facture = '.$object->id;
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$customer_pay = $obj->customer_pay;
		$terminal->fetch($obj>fk_cash);

		if (! empty($conf->rewards->enabled)){
            dol_include_once('/rewards/class/rewards.class.php');
			$rewards = new Rewards($db);
			$points = $rewards->getInvoicePoints($object->id);
		}
		if ($object->type==0)
		{
			$pay = $object->getSommePaiement();
            $coupon = $object->getSumCreditNotesUsed();
            $coupon += $object->getSumDepositsUsed();
            $pay += $coupon;

			if (! empty($conf->rewards->enabled)){
				$usepoints= abs($rewards->getInvoicePoints($object->id,1));
				$moneypoints = abs($usepoints*$conf->global->REWARDS_DISCOUNT);//falta fer algo per aci
				if($customer_pay > $pay-$moneypoints)
					$pay = $customer_pay;
				else
					$pay = $pay-$moneypoints;
			}
			else{
				if($customer_pay > $pay)
					$pay = $customer_pay;
			}
		}
		if ($object->type==2)
		{
			$customer_pay = $customer_pay*-1;
			$pay = $object->getSommePaiement();

			if (! empty($conf->rewards->enabled)){
				$usepoints= abs($rewards->getInvoicePoints($object->id,0));
				$moneypoints = -1*($usepoints*$conf->global->REWARDS_DISCOUNT);//falta fer algo per aci
				if($customer_pay > $pay-$moneypoints)
					$pay = $customer_pay;
				else
					$pay = $pay-$moneypoints;
			}
			else{
				if($customer_pay > $pay)
					$pay = $customer_pay;
			}
		}
		$diff_payment = $object->total_ttc -$moneypoints - $pay;
		$listofpayments=$object->getListOfPayments();
		foreach($listofpayments as $paym)
		{
			if($paym['type'] != 'PNT'){
				if ($paym['type'] != '(CREDIT_NOTE)' && $paym['type'] != '(EXCESS RECEIVED)') {
					if ($paym['type'] != 'LIQ') {
						echo '<tr><th nowrap="nowrap">' . $terminal->select_Paymentname(dol_getIdFromCode($db,
								$paym['type'],
								'c_paiement')) . '</th><td nowrap="nowrap">' . price(price2num($paym['amount'])) . " " . $langs->trans(currency_name($conf->currency)) . "</td></tr>";
					} else {
						echo '<tr><th nowrap="nowrap">' . $terminal->select_Paymentname(dol_getIdFromCode($db,
								$paym['type'],
								'c_paiement')) . '</th><td nowrap="nowrap">' . price(price2num($paym['amount'] - (($object->type > 1 ? $diff_payment * -1 : $diff_payment) < 0 ? $diff_payment : 0))) . " " . $langs->trans(currency_name($conf->currency)) . "</td></tr>";
					}
				}
			}
		}
		if ($coupon > 0){
            echo '<tr><th nowrap="nowrap">'.$langs->trans("Discount").'</th><td nowrap="nowrap">'.price(price2num($coupon))." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
        }
		if (! empty($conf->rewards->enabled)){
			if ($moneypoints!=0){
				echo '<tr><th nowrap="nowrap">'.$usepoints." ".$langs->trans("Points").'</th><td nowrap="nowrap">'.price(price2num($moneypoints))." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
			}
		}
		$discount = new DiscountAbsolute($db);
		$result = $discount->fetch(0, $object->id);
		if ($result > 0) {
			echo '<tr><td nowrap="nowrap">'.$langs->trans("ReductionConvert").'</td><td nowrap="nowrap">'.price(price2num($discount->amount_ttc)).'</td></tr>';
		}
		else{
			echo '<tr><th nowrap="nowrap">'.(($object->type>1?$diff_payment*-1:$diff_payment)<0?$langs->trans("CustomerRet"):$langs->trans("CustomerDeb")).'</th><td nowrap="nowrap">'.price(abs(price2num($diff_payment)))." ".$langs->trans(currency_name($conf->currency))."</td></tr>";
		}
		if ($points != 0 && ! empty($conf->rewards->enabled))
		{
			echo '<tr><th nowrap="nowrap">'.$langs->trans("TotalPointsInvoice").'</th><td nowrap="nowrap">'.price(price2num($points))." ".$langs->trans('Points')."</td></tr>";
			$total_points = $rewards->getCustomerPoints($object->socid);
			echo '<tr><th nowrap="nowrap">'.$langs->trans("DispoPoints").'</th><td nowrap="nowrap">'.price(price2num($total_points))." ".$langs->trans('Points')."</td></tr>";
		}
	?>
</table>

<div class="note"><p><?php print $conf->global->POS_PREDEF_MSG; ?> </p></div>
<div><?php 	$now = dol_now();
			print '<p class="date_heure" align="right">'.$object->ref." ".dol_print_date($object->date_creation,'dayhour').'</p>';?></div>



</body>
