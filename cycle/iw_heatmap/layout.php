<!DOCTYPE html>
<html>
<head>
<?php include "description.php"; ?>
	<title><?php print $event_description; ?> - ScaleCycle Visualization</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
</head>

<body>
<?php
ini_set("display_errors", 1);

include $_SERVER["DOCUMENT_ROOT"]."/server.php";

$is_valid = true;
if(!isset($_POST["db_name_"])) $is_valid = false;
if(!isset($_POST["tb_name_"])) $is_valid = false;
if(!isset($_POST["tp_name_"])) $is_valid = false;
if(!isset($_POST["essid_"])) $is_valid = false;
if(!isset($_POST["attr_"])) $is_valid = false;
if(!$is_valid)
	die("<p>Invalid rquest to this URL.</p>".PHP_EOL);
$db_name = $_POST["db_name_"];
$tb_name = $_POST["tb_name_"];
$tp_name = $_POST["tp_name_"];
$essid = $_POST["essid_"];
$attr = $_POST["attr_"];
print "<p>Using database: {$db_name}</p>".PHP_EOL;
print "<p>From table: {$tb_name}</p>".PHP_EOL;
print "<p>With event type: {$tp_name}</p>".PHP_EOL;
print "<p>With ESSID: {$essid}</p>".PHP_EOL;
print "<p>Mapping on: {$attr}</p>".PHP_EOL;

$mysqli = new mysqli($db_host, $db_user, $db_pwd, $db_name);
if($mysqli->connect_errno)
	die("<p>Cannot connect to server: ".$db_host."</p>".PHP_EOL);

$like_str = "\"essid\": \"".$essid."\"";
$like_str = str_replace("'", "''", $like_str);
$like_str = str_replace("_", "\_", $like_str);
$res = $mysqli->query("SELECT timestamp, geotag, value_json FROM {$tb_name} WHERE event='{$tp_name}' AND value_json LIKE '%{$like_str}%'");
print "<p>Fetched number of record(s): ".$res->num_rows."</p>".PHP_EOL;
?>
</body>
</html>
