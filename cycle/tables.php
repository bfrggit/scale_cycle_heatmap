<html>
<head>
	<title>ScaleCycle Visualization</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
</head>

<body style="padding-top: 8px">
<?php
ini_set("display_errors", 1);

include $_SERVER["DOCUMENT_ROOT"]."/server.php";

if(!isset($_POST["db_name_"]))
	die("<p>Invalid rquest to this URL.</p>".PHP_EOL);
$db_name = $_POST["db_name_"];
print "<p>Using database: {$db_name}</p>".PHP_EOL;

$mysqli = new mysqli($db_host, $db_user, $db_pwd, $db_name);
if($mysqli->connect_errno)
	die("<p>Cannot connect to server: ".$db_host."</p>".PHP_EOL);

include $_SERVER["DOCUMENT_ROOT"]."/db_blacklist.php";

$db_black = false;
if(isset($db_blacklist)):
	if(isset($db_blacklist[$db_name])):
		print "<p>Current database has been marked as unsupported by ScaleCycle.</p>".PHP_EOL;
		$db_black = true;
	endif;
endif;

$res = $mysqli->query("SHOW TABLES");

if(!isset($db_black) || !$db_black)
	print "<p>Please choose from {$res->num_rows} available table(s): </p>"
?>

	<form id="tb_form" action="types.php" method="POST">
		<input
			type="hidden"
			name="db_name_"
			value=<?php print "\"{$db_name}\""; ?>
		/>
		<input
			type="submit"
			id="submit_top"
			name="submit_top"
			value="Submit"
			<?php if($db_black || $res->num_rows < 1) print "disabled"; ?>
		/>
		<p>

<?php
$type_tbs = array();
while($row = $res->fetch_assoc()) {
	$tb_name = $row["Tables_in_{$db_name}"];
	$res_t = $mysqli->query("SELECT count(*) AS count FROM {$tb_name}");
	$tb_count = NULL;
	if($res_t) {
		//var_dump($res_t);
		$row_t = $res_t->fetch_assoc();
		$tb_count = $row_t["count"];
	}
	print "<input
			type=\"radio\"
			id=\"tb_name_\"
			name=\"tb_name_\"
			value=\"{$tb_name}\"
			required".PHP_EOL;
	if($db_black) print "disabled".PHP_EOL;
	print "/>{$tb_name}";
	if(isset($tb_count))
		print " ({$tb_count})";
	print "<br>";
	if(preg_match("/^type\_(.+)$/", $tb_name, $matches) > 0) {
		$type_tbs[] = ($matches[1]);
	}
}
if($res->num_rows < 1)
	print "Empty list.";
?>

		</p>
		<input
			type="submit"
			id="submit"
			name="submit"
			value="Submit"
			<?php if($db_black || $res->num_rows < 1) print "disabled"; ?>
		/>
	</form>

<?php
include $_SERVER["DOCUMENT_ROOT"]."/db_maintain.php";

if(!isset($db_maintain) || $db_maintain != $db_name)
	die();
print "<p>Current database has been marked as maintenance-enabled by ScaleCycle.</p>".PHP_EOL;
//var_dump($type_tbs);

$type_dbs = array();
$db_res = $mysqli->query("SHOW DATABASES");
while($db_row = $db_res->fetch_assoc()):
	$type_db_name = $db_row["Database"];
	if(preg_match("/^sct\_archive\_(.+)$/", $type_db_name, $matches) > 0) {
		$type_dbs[] = ($matches[1]);
	}
endwhile;

$type_in_db = array();
$type_not_in_db = array();
foreach($type_tbs as $type_j):
	if(in_array($type_j, $type_dbs))
		$type_in_db[] = $type_j;
	else
		$type_not_in_db[] = $type_j;
endforeach;

print "<p>Found ".count($type_in_db)." table(s) with corresponding database(s): </p>".PHP_EOL;
print "<p>".PHP_EOL;
foreach($type_in_db as $type_j):
	print $type_j."<br>".PHP_EOL;
endforeach;
print "</p>".PHP_EOL;

$timestamps = array();
$timetoshow = array();
$timeranges = array();

foreach($type_tbs as $type_j):
	$ts_res = $mysqli->query("SELECT timestamp FROM type_{$type_j}");
	if(!$ts_res) continue;
	while($ts_row = $ts_res->fetch_assoc()):
		$timestamps[] = floatval($ts_row["timestamp"]);
	endwhile;
endforeach;
sort($timestamps);

for($j = 0; $j < count($timestamps); ++$j):
	if($j < 1):
		$timetoshow[] = $timestamps[$j];
		$timeranges[] = $timestamps[$j];
		continue;
	endif;
	if($timestamps[$j] - $timestamps[$j - 1] > 1800):
		$timetoshow[] = $timestamps[$j - 1];
		$timetoshow[] = $timestamps[$j];
		$timeranges[] = $timestamps[$j - 1] + ($timestamps[$j] - $timestamps[$j - 1]) / 2.0;
	endif;
	if($j > count($timestamps) - 2):
		$timetoshow[] = $timestamps[$j];
		$timeranges[] = $timestamps[$j];
	endif;
endfor;

print "<p>Found ".(count($timeranges) - 1)." data site(s): </p>".PHP_EOL;
print "<p>".PHP_EOL;
for($j = 0; $j < count($timeranges) - 1; ++$j):
	print "Data set {$j}: ";
	print date("Y-m-d h:i A", $timetoshow[$j * 2]);
	print " to ";
	print date("m-d h:i A T", $timetoshow[$j * 2 + 1]);
	print "<br>".PHP_EOL;
endfor;
if(count($timeranges) < 2)
	print "Empty list.".PHP_EOL;
print "</p>";
?>

	<p>Please fill in name(s) for data set(s): </p>
	<form id="dump_form" action="methods/dump.php" method="POST">
		<input
			type="hidden"
			name="db_name_"
			value=<?php print "\"{$db_name}\""; ?>
		/>
		<input
			type="hidden"
			name="tb_count_"
			value=<?php print "\"".count($type_in_db)."\""; ?>
		/>
		<input
			type="hidden"
			name="tr_count_"
			value=<?php print "\"".(count($timeranges) - 1)."\""; ?>
		/>
<?php
for($j = 0; $j < count($type_in_db); ++$j):
	print "<input
			type=\"hidden\"
			name=\"tb_name_{$j}\"
			value={$type_in_db[$j]}
		/>".PHP_EOL;
endfor;

for($j = 0; $j < count($timeranges) - 1; ++$j):
	print "<input
			type=\"hidden\"
			name=\"tr_b_{$j}\"
			value={$timeranges[$j]}
		/>".PHP_EOL;
	print "<input
			type=\"hidden\"
			name=\"tr_e_{$j}\"
			value={$timeranges[$j + 1]}
		/>".PHP_EOL;
endfor;
?>
		<p>
<?php
for($j = 0; $j < count($timeranges) - 1; ++$j):
	print "Data set {$j}: ";
	print "<input
			type=\"text\"
			id=\"ntb_name_{$j}\"
			name=\"ntb_name_{$j}\"
		/>";
	print "<br>".PHP_EOL;
endfor;
if(count($timeranges) < 2)
	print "Empty list.".PHP_EOL;
?>
		</p>
		<input
			type="submit"
			id="submit_dump"
			name="submit_dump"
			value="Dump"
			<?php if(count($timeranges) < 2) print "disabled"; ?>
		/>
	</form>

	<p>Please clean up the database after maintenance: </p>
	<form id="cl_tb_form" action="methods/delete_tb.php" method="POST">
		<input
			type="hidden"
			name="db_name_"
			value=<?php print "\"{$db_name}\""; ?>
		/>
		<input
			type="hidden"
			name="tb_count_"
			value=<?php print "\"".count($type_in_db)."\""; ?>
		/>
<?php
for($j = 0; $j < count($type_in_db); ++$j):
	print "<input
			type=\"hidden\"
			name=\"tb_name_{$j}\"
			value={$type_in_db[$j]}
		/>".PHP_EOL;
endfor;
?>
		<p>Delete dumped table(s): </p>
		<input
			type="submit"
			id="submit_cl_tb"
			name="submit_cl_tb"
			value="Delete"
			<?php if(count($type_in_db) < 1) print "disabled"; ?>
		/>
	</form>
	<form id="cl_all_form" action="methods/erase_db.php" method="POST">
		<input
			type="hidden"
			name="db_name_"
			value=<?php print "\"{$db_name}\""; ?>
		/>
		<p>Erase everything from current database: </p>
		<input
			type="submit"
			id="submit_cl_all"
			name="submit_cl_all"
			value="Erase"
		/>
	</form>
</body>
</html>
