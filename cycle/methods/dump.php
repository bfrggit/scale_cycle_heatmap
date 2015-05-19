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
if(!isset($_POST["tr_count_"])) $is_valid = false;
if(!$is_valid)
	die("<p>Invalid rquest to this URL.</p>".PHP_EOL);
$db_name = $_POST["db_name_"];
$tb_count = $_POST["tb_count_"];
$tr_count = $_POST["tr_count_"];

$types = array();
$timeranges = array();
for($j = 0; $j < $tb_count; ++$j):
	if(!isset($_POST["tb_name_{$j}"])) $is_valid = false;
	else
		$types[] = $_POST["tb_name_{$j}"];
endfor;

$tb_create_ls = array();
for($j = 0; $j < $tr_count; ++$j):
	if(!isset($_POST["tr_b_{$j}"])) $is_valid = false;
	elseif(!isset($_POST["tr_e_{$j}"])) $is_valid = false;
	elseif(!isset($_POST["ntb_name_{$j}"])) $is_valid = false;
	else {
		$timeranges[] = array(
				"b" => $_POST["tr_b_{$j}"],
				"e" => $_POST["tr_e_{$j}"],
				"name" => $_POST["ntb_name_{$j}"]
			);
		$ntb_name = $_POST["ntb_name_{$j}"];
		if(!empty($ntb_name) && strlen(trim($ntb_name)) > 0)
			$tb_create_ls[] = $ntb_name;
	}
endfor;
$tb_create_ls = array_unique($tb_create_ls);
if(!$is_valid)
	die("<p>Invalid rquest to this URL.</p>".PHP_EOL);

print "<p>Using database: {$db_name}</p>".PHP_EOL;

$mysqli = new mysqli($db_host, $db_user, $db_pwd, $db_name);
if($mysqli->connect_errno)
	die("<p>Cannot connect to server: ".$db_host."</p>".PHP_EOL);

/*
include $_SERVER["DOCUMENT_ROOT"]."/db_blacklist.php";

$db_black = false;
if(isset($db_blacklist)):
	if(isset($db_blacklist[$db_name])):
		print "<p>Current database has been marked as unsupported by ScaleCycle.</p>".PHP_EOL;
		$db_black = true;
	endif;
endif;
*/

foreach($types as $type):
	print "<p>With event type: ".$type."</p>".PHP_EOL;

	$tb_name = "type_".$type;
	$ndb_name = "sct_archive_".$type;

	print "<p>";
	foreach($tb_create_ls as $tb_create):
		$create_res = $mysqli->query("CREATE TABLE {$ndb_name}.{$tb_create} (id INTEGER AUTO_INCREMENT NOT NULL PRIMARY KEY, sensor VARCHAR(16) NOT NULL, event VARCHAR(64) NOT NULL, priority INTEGER NOT NULL, timestamp DOUBLE PRECISION NOT NULL, geotag LONGTEXT, value_json LONGTEXT NOT NULL, upload_time DOUBLE PRECISION, `condition` LONGTEXT)");
		if(!$create_res)
			print "Failed creating table with error: ".$mysqli->error."<br>".PHP_EOL;
		else
			print "Created table: ".$ndb_name.".".$tb_create."<br>".PHP_EOL;
	endforeach;
	print "</p>";

	print "<p>Copying from: {$db_name}.{$tb_name}<br>".PHP_EOL;
	print "Copying to: {$ndb_name}</p>".PHP_EOL;
	print "<div style=\"padding-left: 24px\">";
	foreach($timeranges as $range):
		$ntb_name = $range["name"];
		if(empty($ntb_name) || strlen(trim($ntb_name)) < 1)
			continue;
		$res = $mysqli->query("SELECT count(*) AS count FROM {$tb_name} WHERE timestamp >= {$range["b"]} AND timestamp <= {$range["e"]}");
		if(!$res) continue;
		$row = $res->fetch_assoc();
		print "<p>";
		print "Fetched number of record(s): {$row["count"]}<br>".PHP_EOL;
		$res = $mysqli->query("SELECT count(*) AS count FROM {$tb_name} WHERE timestamp >= {$range["b"]} AND timestamp <= {$range["e"]} AND (geotag IS NOT NULL OR (event = 'location_update' AND value_json IS NOT NULL))");
		if(!$res) continue;
		$row = $res->fetch_assoc();
		print "Fetched number of geotagged record(s): {$row["count"]}<br>".PHP_EOL;

		$res = $mysqli->query("INSERT INTO {$ndb_name}.{$ntb_name} SELECT * FROM {$tb_name} WHERE timestamp >= {$range["b"]} AND timestamp <= {$range["e"]} AND (geotag IS NOT NULL OR (event = 'location_update' AND value_json IS NOT NULL))");
		print "Copied {$row["count"]} record(s) to: {$ndb_name}.{$ntb_name}".PHP_EOL;
		print "</p>";
	endforeach;
	print "</div>";
endforeach;
?>
	<p>Done.</p>
	<p>Please <a href="/">start over</a>.</p>
</body>
</html>
