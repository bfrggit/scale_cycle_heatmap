<!DOCTYPE html>
<html>
<head>
	<title>ScaleCycle</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
</head>

<body>
<?php
ini_set("display_errors", 1);

include $_SERVER["DOCUMENT_ROOT"]."/server.php";

$is_valid = true;
if(!isset($_POST["db_name_"])) $is_valid = false;
if(!isset($_POST["tb_count_"])) $is_valid = false;
if(!$is_valid)
	die("<p>Invalid rquest to this URL.</p>".PHP_EOL);
$db_name = $_POST["db_name_"];
$tb_count = $_POST["tb_count_"];

$types = array();
$timeranges = array();
for($j = 0; $j < $tb_count; ++$j):
	if(!isset($_POST["tb_name_{$j}"])) $is_valid = false;
	else
		$types[] = $_POST["tb_name_{$j}"];
endfor;
if(!$is_valid)
	die("<p>Invalid rquest to this URL.</p>".PHP_EOL);

print "<p>Using database: {$db_name}</p>".PHP_EOL;

$mysqli = new mysqli($db_host, $db_user, $db_pwd, $db_name);
if($mysqli->connect_errno)
	die("<p>Cannot connect to server: ".$db_host."</p>".PHP_EOL);

foreach($types as $type):
	print "<p>With event type: ".$type."</p>".PHP_EOL;

	$tb_name = "type_".$type;

	print "<p>";
	$drop_res = $mysqli->query("DROP TABLE {$tb_name}");
	print "Deleted table: ".$tb_name."<br>".PHP_EOL;
	print "</p>";
endforeach;
?>
	<p>Done.</p>
</body>
</html>
