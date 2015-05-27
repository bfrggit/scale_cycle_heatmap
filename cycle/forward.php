<!DOCTYPE html>
<html>
<head>
	<title>ScaleCycle Visualization</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
</head>

<body>
<?php
$is_valid = true;
if(!isset($_POST["db_name_"])) $is_valid = false;

if(isset($_POST["tb_name_"])) $tb_x = false;
elseif(isset($_POST["tb_name_x"])) $tb_x = true;
else $is_valid = false;

if(!isset($_POST["tp_name_"])) $is_valid = false;
if(!$is_valid)
	die("<p>Invalid rquest to this URL.</p>".PHP_EOL);
$db_name = $_POST["db_name_"];

if(!$tb_x):
	$tb_name = $_POST["tb_name_"];
	$tb_names = array($tb_name);
else:
	$tb_names = $_POST["tb_name_x"];
endif;

$tp_name = $_POST["tp_name_"];

include $_SERVER["DOCUMENT_ROOT"]."/event_support.php";

$dir = $event_support[$tp_name];
$target = $dir."?db_name_=".$db_name."&tp_name_=".$tp_name;

if(!$tb_x):
	$target .= "&tb_name_=".$tb_name;
else:
	for($j = 0; $j < count($tb_names); ++$j):
		$target .= "&tb_name_x[]=".$tb_names[$j];
	endfor;
endif;

header("Location: {$target}");
?>
</body>
</html>
