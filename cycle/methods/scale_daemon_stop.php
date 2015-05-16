<!DOCTYPE html>
<html>
<head>
	<title>ScaleCycle</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
</head>

<body>
<?php
exec("/usr/bin/sudo /usr/sbin/service scale_daemon stop 2>&1", $e_output, $e_return);
//var_dump($e_output);
?>

	<p>SCALE daemon is 
<?php
exec("/usr/sbin/service scale_daemon status 2>&1", $e_output, $e_return);
//var_dump($e_return);
if($e_return == 0)
	print "running.";
else
	print "not running.";
?>
	</p>
</body>
</html>
