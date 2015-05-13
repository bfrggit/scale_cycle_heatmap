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
if(!isset($_GET["db_name_"])) $is_valid = false;
if(!isset($_GET["tb_name_"])) $is_valid = false;
if(!isset($_GET["tp_name_"])) $is_valid = false;
if(!$is_valid)
	die("<p>Invalid rquest to this URL.</p>".PHP_EOL);
$db_name = $_GET["db_name_"];
$tb_name = $_GET["tb_name_"];
$tp_name = $_GET["tp_name_"];
print "<p>Using database: {$db_name}</p>".PHP_EOL;
print "<p>From table: {$tb_name}</p>".PHP_EOL;
print "<p>With event type: {$tp_name}</p>".PHP_EOL;

$mysqli = new mysqli($db_host, $db_user, $db_pwd, $db_name);
if($mysqli->connect_errno)
	die("<p>Cannot connect to server: ".$db_host."</p>".PHP_EOL);

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

//print "<p>Format of selected table is good.</p>".PHP_EOL;

$res = $mysqli->query("SELECT value_json FROM {$tb_name} WHERE event='{$tp_name}'");
print "<p>Fetched number of record(s): ".$res->num_rows."</p>".PHP_EOL;
$essids = array();
$bssids = array();
while($row = $res->fetch_assoc()):
	$value_json = $row["value_json"];
	$value = json_decode($value_json, true);
	//print $value_json;
	//var_dump($value);
	
	if(isset($value["essid"]) && !empty($value["essid"])) {
		if(!isset($essids[$value["essid"]]))
			$essids[$value["essid"]] = 1;
		else
			$essids[$value["essid"]] += 1;
	} elseif(isset($value["bssid"])) {
		if(!isset($bssids[$value["bssid"]]))
			$bssids[$value["bssid"]] = 1;
		else
			$bssids[$value["bssid"]] += 1;
	}
endwhile;
arsort($essids);
arsort($bssids);
?>

	<p>Please choose from <?php print count($essids); ?> discovered ESSID(s): </p>
	<form id="essid_form" action="query.php" method="POST">
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
			type="submit"
			id="submit_top"
			name="submit_top"
			value="Submit"
			<?php if(count($essids) < 1) print "disabled"; ?>
		/>
		<p>

<?php
foreach($essids as $essid => $essid_count):
	print "<input
			type=\"radio\"
			id=\"essid_\"
			name=\"essid_\"
			value=\"{$essid}\"
			required 
		/>{$essid} ($essid_count)";
	print "<br>";
endforeach;
if(count($essids) < 1)
	print "Empty list.";
?>

		</p>
		<input
			type="submit"
			id="submit"
			name="submit"
			value="Submit"
			<?php if(count($essids) < 1) print "disabled"; ?>
		/>
	</form>
	<p>Found <?php print count($bssids); ?> ESSID-less BSSID(s): </p>
	<p>

<?php
foreach($bssids as $bssid => $bssid_count):
	print "{$bssid} ({$bssid_count})<br>";
endforeach;
if(count($bssids) < 1)
	print "Empty list.";
?>

	</p>
</body>
</html>
