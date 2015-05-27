<!DOCTYPE html>
<html>
<head>
	<title>ScaleCycle Visualization</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
</head>

<body>
<?php
ini_set("display_errors", 1);

include $_SERVER["DOCUMENT_ROOT"]."/server.php";

$is_valid = true;
if(!isset($_POST["db_name_"])) $is_valid = false;

if(isset($_POST["tb_name_"])) $tb_x = false;
elseif(isset($_POST["tb_name_x"])) $tb_x = true;
else $is_valid = false;

if(!$is_valid)
	die("<p>Invalid rquest to this URL.</p>".PHP_EOL);
$db_name = $_POST["db_name_"];
print "<p>Using database: {$db_name}</p>".PHP_EOL;

if(!$tb_x):
	$tb_name = $_POST["tb_name_"];
	$tb_names = array($tb_name);
else:
	$tb_names = $_POST["tb_name_x"];
endif;

print "<p>From table(s): ".PHP_EOL;
for($j = 0; $j < count($tb_names); ++$j):
	print $tb_names[$j];
	if($j < count($tb_names) - 1)
		print ", ";
	print PHP_EOL;
endfor;
if(count($tb_names) < 1)
	print "-";
print "</p>".PHP_EOL;
if(count($tb_names) < 1)
	die("<p>Please choose at least one table.</p>".PHP_EOL);

$mysqli = new mysqli($db_host, $db_user, $db_pwd, $db_name);
if($mysqli->connect_errno)
	die("<p>Cannot connect to server: ".$db_host."</p>".PHP_EOL);

$is_valid = true;
for($j = 0; $j < count($tb_names); ++$j):
	$res = $mysqli->query("SHOW COLUMNS FROM {$tb_names[$j]}");
	$columns = array();
	while($row = $res->fetch_assoc()) {
		$col_name = $row["Field"];
		$columns[$col_name] = 9; //XXX: Using as set, giving a random integer
	}

	if(!isset($columns["event"])) $is_valid = false;
	if(!isset($columns["timestamp"])) $is_valid = false;
	if(!isset($columns["geotag"])) $is_valid = false;
	if(!isset($columns["value_json"])) $is_valid = false;
endfor;

if(!$is_valid) {
	// Probably NOT a table created by ScaleCycle client.
	die("<p>Columns in this table cannot be recognized.
			It seems that this table is NOT created by ScaleCycle.</p>".PHP_EOL);
}

$query_str = "SELECT SUM(count) AS sum FROM (";
for($j = 0; $j < count($tb_names); ++$j):
	$query_str .= "SELECT COUNT(*) AS count FROM {$tb_names[$j]}";
	if($j < count($tb_names) - 1)
		$query_str .= " UNION ALL ";
endfor;
$query_str = $query_str.") t";
//print "<!-- {$query_str} -->".PHP_EOL;

$res_t = $mysqli->query($query_str);
$row_t = $res_t->fetch_assoc();
$tb_count = $row_t["sum"];
print "<p>Fetched number of record(s): ".$tb_count."</p>".PHP_EOL;

if(!$tb_x):
	include $_SERVER["DOCUMENT_ROOT"]."/event_support.php";
else:
	include $_SERVER["DOCUMENT_ROOT"]."/event_support_x.php";
endif;

$query_str = "SELECT event, COUNT(*) AS count FROM (";
for($j = 0; $j < count($tb_names); ++$j):
	$query_str .= "SELECT event FROM {$tb_names[$j]}";
	if($j < count($tb_names) - 1)
		$query_str .= " UNION ALL ";
endfor;
$query_str = $query_str.") t GROUP BY event";
//print "<!-- {$query_str} -->".PHP_EOL;

$res = $mysqli->query($query_str);
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

<?php
if(!$tb_x):
	print "<input
			type=\"hidden\"
			name=\"tb_name_\"
			value=\"{$tb_name}\"
		/>".PHP_EOL;
else:
	for($j = 0; $j < count($tb_names); ++$j):
		print "<input
				type=\"hidden\"
				name=\"tb_name_x[{$j}]\"
				value=\"{$tb_names[$j]}\"
			/>".PHP_EOL;
	endfor;
endif;
?>
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

<?php
if(!$can_submit):
	print "<p>Please <a href=\"/\">start over</a>.</p>".PHP_EOL;
endif;
?>

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
