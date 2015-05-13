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

if(!isset($_POST["db_name_"]))
	die("<p>Invalid rquest to this URL.</p>".PHP_EOL);
$db_name = $_POST["db_name_"];
print "<p>Using database: {$db_name}</p>".PHP_EOL;

$mysqli = new mysqli($db_host, $db_user, $db_pwd, $db_name);
if($mysqli->connect_errno)
	die("<p>Cannot connect to server: ".$db_host."</p>".PHP_EOL);

$res = $mysqli->query("SHOW TABLES");
?>

	<p>Please choose from <?php print $res->num_rows; ?> available table(s): </p>
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
			<?php if($res->num_rows < 1) print "disabled"; ?>
		/>
		<p>

<?php
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
			required
		/>{$tb_name}";
	if(isset($tb_count))
		print " ({$tb_count})";
	print "<br>";
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
			<?php if($res->num_rows < 1) print "disabled"; ?>
		/>
	</form>
</body>
</html>
