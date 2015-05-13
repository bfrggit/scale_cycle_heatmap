<!DOCTYPE html>
<html>
<head>
	<title>ScaleCycle Visualization</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
</head>

<body>
<?php
include $_SERVER["DOCUMENT_ROOT"]."/server.php";

$mysqli = new mysqli($db_host, $db_user, $db_pwd);
if($mysqli->connect_errno)
	die("<p>Cannot connect to server: ".$db_host."</p>".PHP_EOL);

$res = $mysqli->query("SHOW DATABASES");
?>

	<p>Please choose from <?php print $res->num_rows; ?> available database(s): </p>
	<form id="db_form" action="tables.php" method="POST">
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
	$db_name = $row["Database"];
	print "<input
			type=\"radio\"
			id=\"db_name_\"
			name=\"db_name_\"
			value=\"{$db_name}\"
			required
		/>{$db_name}";
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
