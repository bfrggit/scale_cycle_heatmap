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
if(!$is_valid)
	die("<p>Invalid rquest to this URL.</p>".PHP_EOL);
$db_name = $_POST["db_name_"];
$tb_name = $_POST["tb_name_"];
$tp_name = $_POST["tp_name_"];
$essid = $_POST["essid_"];
print "<p>Using database: {$db_name}</p>".PHP_EOL;
print "<p>From table: {$tb_name}</p>".PHP_EOL;
print "<p>With event type: {$tp_name}</p>".PHP_EOL;
print "<p>With ESSID: {$essid}</p>".PHP_EOL;

$mysqli = new mysqli($db_host, $db_user, $db_pwd, $db_name);
if($mysqli->connect_errno)
	die("<p>Cannot connect to server: ".$db_host."</p>".PHP_EOL);
/*
$res = $mysqli->query("SHOW COLUMNS FROM {$tb_name}");
$columns = array();
while($row = $res->fetch_assoc()) {
	$col_name = $row["Field"];
	$columns[$col_name] = 9; //XXX: Using as set, giving a random integer
}
$is_valid = true;
if(!isset($columns["event"])) $is_valid = false;
if(!isset($columns["timestamp"])) $is_valid = false;
if(!isset($columns["geotag"])) $is_valid = false;
if(!isset($columns["value_json"])) $is_valid = false;
if(!$is_valid) {
	// Probably NOT a table created by ScaleCycle client.
	die("<p>Columns in this table cannot be recognized.
			Is this table created by ScaleCycle client?</p>".PHP_EOL);
}
*/
//print "<p>Format of selected table is good.</p>".PHP_EOL;

$like_str = "\"essid\": \"".$essid."\"";
$like_str = str_replace("'", "''", $like_str);
$like_str = str_replace("_", "\_", $like_str);
$res = $mysqli->query("SELECT timestamp, geotag, value_json FROM {$tb_name} WHERE event='{$tp_name}' AND value_json LIKE '%{$like_str}%'");
print "<p>Fetched number of record(s): ".$res->num_rows."</p>".PHP_EOL;
?>
	<p>Please choose an attribute for mapping: </p>
	<form id="attr_form" action="layout.php" method="POST">
		<input
			type="hidden"
			name="db_name_"
			value=<?php print "\"{$db_name}\""; ?>
		/>
		<input
			type="hidden"
			name="tb_name_"
			value=<?php print "\"{$tb_name}\""; ?>
		/>
		<input
			type="hidden"
			name="tp_name_"
			value=<?php print "\"{$tp_name}\""; ?>
		/>
		<input
			type="hidden"
			name="essid_"
			value=<?php print "\"{$essid}\""; ?>
		/>
		<p>
		<input
			type="radio"
			id="attr_"
			name="attr_"
			value="quality"
			required
			checked
		/>Link quality<br>
		<input
			type="radio"
			id="attr_"
			name="attr_"
			value="level"
			required
		/>Signal level<br>
		</p>
		<input
			type="submit"
			id="submit"
			name="submit"
			value="Submit"
			<?php if($res->num_rows < 1) print "disabled"; ?>
		/>
	</form>
</body>
</html>
