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

if(isset($_POST["tb_name_"])) $tb_x = false;
elseif(isset($_POST["tb_name_x"])) $tb_x = true;
else $is_valid = false;

if(!isset($_POST["tp_name_"])) $is_valid = false;
if(!isset($_POST["essid_"])) $is_valid = false;
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

$tp_name = $_POST["tp_name_"];
print "<p>With event type: {$tp_name}</p>".PHP_EOL;
$essid = $_POST["essid_"];
print "<p>With ESSID: {$essid}</p>".PHP_EOL;

$mysqli = new mysqli($db_host, $db_user, $db_pwd, $db_name);
if($mysqli->connect_errno)
	die("<p>Cannot connect to server: ".$db_host."</p>".PHP_EOL);

$like_str = "\"essid\": \"".$essid."\"";
$like_str = str_replace("'", "''", $like_str);
$like_str = str_replace("_", "\_", $like_str);

$query_str = "SELECT COUNT(*) AS count FROM (";
for($j = 0; $j < count($tb_names); ++$j):
	$query_str .= "SELECT event, geotag, value_json FROM {$tb_names[$j]}";
	$query_str .= " WHERE event='{$tp_name}' AND value_json LIKE '%{$like_str}%'";
	if($j < count($tb_names) - 1)
		$query_str = $query_str." UNION ALL ";
endfor;
$query_str .= ") t";

$res = $mysqli->query($query_str);
$row = $res->fetch_assoc();
print "<p>Fetched number of record(s): ".$row["count"]."</p>".PHP_EOL;

/*
$query_str = "SELECT COUNT(*) AS count FROM (";
for($j = 0; $j < count($tb_names); ++$j):
	$query_str = $query_str."SELECT event, geotag, value_json FROM {$tb_names[$j]}";
	if($j < count($tb_names) - 1)
		$query_str = $query_str." UNION ALL ";
endfor;
$query_str .= ") t WHERE event='{$tp_name}' AND geotag IS NOT NULL AND value_json LIKE '%{$like_str}%'";
*/
$query_str .= " WHERE geotag IS NOT NULL";

$res_n = $mysqli->query($query_str);
$row_n = $res_n->fetch_assoc();
print "<p>Fetched number of geotagged record(s): ".$row_n["count"]."</p>".PHP_EOL;
?>
	<p>Please choose an attribute for mapping: </p>
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
			<?php if($row_n["count"] < 1) print "disabled"; ?>
		/>
	</form>
</body>
</html>
