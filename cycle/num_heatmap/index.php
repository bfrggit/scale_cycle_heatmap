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

if(isset($_GET["tb_name_"])) $tb_x = false;
elseif(isset($_GET["tb_name_x"])) $tb_x = true;
else $is_valid = false;

if(!isset($_GET["tp_name_"])) $is_valid = false;
if(!$is_valid)
	die("<p>Invalid rquest to this URL.</p>".PHP_EOL);
$db_name = $_GET["db_name_"];
print "<p>Using database: {$db_name}</p>".PHP_EOL;

if(!$tb_x):
	$tb_name = $_GET["tb_name_"];
	$tb_names = array($tb_name);
else:
	$tb_names = $_GET["tb_name_x"];
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

$tp_name = $_GET["tp_name_"];
print "<p>With event type: {$tp_name}</p>".PHP_EOL;

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

$query_str = "";
for($j = 0; $j < count($tb_names); ++$j):
	$query_str .= "SELECT value_json FROM {$tb_names[$j]}";
	if($j < count($tb_names) - 1)
		$query_str .= " UNION ALL ";
endfor;
//print "<!-- {$query_str} -->".PHP_EOL;

$res = $mysqli->query($query_str);
print "<p>Fetched number of record(s): ".$res->num_rows."</p>".PHP_EOL;

$query_str = "";
for($j = 0; $j < count($tb_names); ++$j):
	$query_str .= "SELECT event, geotag FROM {$tb_names[$j]}";
	$query_str .= " WHERE event='{$tp_name}' AND geotag IS NOT NULL";
	if($j < count($tb_names) - 1)
		$query_str = $query_str." UNION ALL ";
endfor;
print "<!-- {$query_str} -->".PHP_EOL;

$res = $mysqli->query($query_str);
print "<p>Fetched number of geotagged record(s): ".$res->num_rows."</p>".PHP_EOL;
?>

	<form id="attr_form" action="layout.php" method="POST">
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

		<input
			type="hidden"
			name="tp_name_"
			value=<?php print "\"{$tp_name}\""; ?>
		/>
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
