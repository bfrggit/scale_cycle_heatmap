<!DOCTYPE html>
<html>
<head>
	<title>ScaleCycle Visualization</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
</head>

<body>
<?php
include $_SERVER["DOCUMENT_ROOT"]."/server.php";

$is_valid = true;
if(!isset($_POST["db_name_"])) $is_valid = false;
if(!isset($_POST["tb_name_"])) $is_valid = false;
if(!$is_valid)
	die("<p>Invalid rquest to this URL.</p>".PHP_EOL);
$db_name = $_POST["db_name_"];
$tb_name = $_POST["tb_name_"];
print "<p>Using database: {$db_name}</p>".PHP_EOL;
print "<p>From table: {$tb_name}</p>".PHP_EOL;

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
			It seems that this table is NOT created by ScaleCycle.</p>".PHP_EOL);
}

$res_t = $mysqli->query("SELECT count(*) AS count FROM {$tb_name}");
$row_t = $res_t->fetch_assoc();
$tb_count = $row_t["count"];
print "<p>Fetched number of record(s): ".$tb_count."</p>".PHP_EOL;

include $_SERVER["DOCUMENT_ROOT"]."/event_support.php";

$res = $mysqli->query("SELECT event, count(*) AS count FROM {$tb_name} GROUP BY event");
//print "<p>Fetched number of record(s): ".$res->num_rows."</p>".PHP_EOL;
$events_s = array();
$events_n = array();
while($row = $res->fetch_assoc()):
	$e_name = $row["event"];
	if(isset($event_support[$e_name]))
		$events_s[$e_name] = $row["count"]; //XXX: Using as set
	else
		$events_n[$e_name] = $row["count"]; //XXX: Using as set
endwhile;
?>

	<p>Please choose from <?php print count($events_s); ?> supported type(s): </p>
	<form id="tp_form" action="forward.php" method="POST">
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
		<p>

<?php
$can_submit = false;
foreach($events_s as $tp_name => $tp_count):
	$event_description = NULL;
	include $_SERVER["DOCUMENT_ROOT"]."/".$event_support[$tp_name]."/description.php";
	if(isset($event_description))
		$has_description = true;
	print "<input
			type=\"radio\"
			id=\"tp_name_\"
			name=\"tp_name_\"
			value=\"{$tp_name}\"
			required ";
	if(!$has_description)
		print "disabled";
	print "/>";
	if($has_description)
		print $event_description.": ";
	print "{$tp_name} ($tp_count)";
	print "<br>";
	if($has_description)
		$can_submit = true;
endforeach;
if(count($events_s) < 1)
	print "Empty list.";
?>

		</p>
		<input
			type="submit"
			id="submit"
			name="submit"
			value="Submit"
			<?php if(!$can_submit) print "disabled"; ?>
		/>
	</form>
	<p>Found <?php print count($events_n); ?> unsupported type(s): </p>
	<p>

<?php
foreach($events_n as $tp_name => $tp_count):
	print "{$tp_name} ({$tp_count})<br>";
endforeach;
if(count($events_n) < 1)
	print "Empty list.";
?>

	</p>
</body>
</html>
