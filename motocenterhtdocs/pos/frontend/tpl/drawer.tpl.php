<?php




$res=@include("../../../main.inc.php");                                   // For root directory
if (! $res) $res=@include("../../../../main.inc.php");                // For "custom" directory

dol_include_once('/pos/class/ticket.class.php');
dol_include_once('/pos/class/cash.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
global $langs, $db, $mysoc, $conf;

$langs->load("main");
$langs->load("pos@pos");
$langs->load('users');
$langs->load('client');
header("Content-type: text/html; charset=".$conf->file->character_set_client);
$id=GETPOST('id');
//$terminal=GETPOST('terminal');
$form = new Form($db);
?>
<html>
<head>
<title>Open Drawer</title>
</head>

<body onload="window.print()" onafterprint="<?php echo ($conf->global->POS_CLOSE_WIN ?'window.close()':''); ?>">
    <h1><?php echo $langs->trans('PosOpenDrawer') ?></h1>
</body>