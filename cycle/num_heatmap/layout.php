<!DOCTYPE html>
<html>
<head>
<?php include "description.php"; ?>
	<title><?php print $event_description; ?> - ScaleCycle Visualization</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <meta charset="utf-8">
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <style>
      html { height: 100% }
      body { height: 100%; margin: 0; padding: 0 }
      #map-canvas { height: 100% }
      #tool { top: 0; position: absolute }
      h1 { position:absolute; }
    </style>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>
    <script src="/js/heatmap.js"></script>
    <script src="/js/gmaps-heatmap.js"></script>
</head>

<body>
	<div id="tool" style="padding-left: 8px; padding-right: 8px">
<?php
ini_set("display_errors", 1);

include $_SERVER["DOCUMENT_ROOT"]."/server.php";

$is_valid = true;
if(!isset($_POST["db_name_"])) $is_valid = false;

if(isset($_POST["tb_name_"])) $tb_x = false;
elseif(isset($_POST["tb_name_x"])) $tb_x = true;
else $is_valid = false;

if(!isset($_POST["tp_name_"])) $is_valid = false;
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

$mysqli = new mysqli($db_host, $db_user, $db_pwd, $db_name);
if($mysqli->connect_errno)
	die("<p>Cannot connect to server: ".$db_host."</p>".PHP_EOL);

$query_str = "";
for($j = 0; $j < count($tb_names); ++$j):
	$query_str .= "SELECT event, timestamp, geotag, value_json FROM {$tb_names[$j]}";
	$query_str .= " WHERE event='{$tp_name}' AND geotag IS NOT NULL";
	if($j < count($tb_names) - 1)
		$query_str = $query_str." UNION ALL ";
endfor;
print "<!-- {$query_str} -->".PHP_EOL;

$res = $mysqli->query($query_str);
print "<p>Fetched number of geotagged record(s): ".$res->num_rows."</p>".PHP_EOL;
?>

		<button onclick="drawOutput()">Layout</button>
	</div>
	<div id="map-canvas"></div>

  <script>
function isNormalInteger(str) {
  var n = ~~Number(str);
  return String(n) === str && n >= 0;
}

function drawOutput(){
  var items = [];

<?php
$avg_lat = 0;
$avg_lon = 0;
$count = 0;
while($row = $res->fetch_assoc()):
	$timestamp = $row["timestamp"];
	$geotag = json_decode(str_replace("NaN", "null", $row["geotag"]), true);
	$value = json_decode($row["value_json"], true);
	$lat = $geotag["lat"];
	$lon = $geotag["lon"];
	$map_value = $value;
	if($lat == 0 || $lon == 0)
		continue;
	$avg_lat += $lat;
	$avg_lon += $lon;
	$count += 1;
	print "items.push(".json_encode(array("lat" => $lat, "lon" => $lon, "count" => $map_value)).");".PHP_EOL;
endwhile;
if($count > 0) {
	$avg_lat /= $count;
	$avg_lon /= $count;
}
?>

  console.log(items);
  drawMap(items);
}


function drawMap(items){
/*
  // map center: Aldrich Park, University of California, Irvine
  var myLatlng = new google.maps.LatLng(33.646052, -117.842745);
  */

  // map center
<?php
print "var avgLat = ".$avg_lat.";".PHP_EOL;
print "var avgLon = ".$avg_lon.";".PHP_EOL;
?>
  var myLatlng = new google.maps.LatLng(avgLat, avgLon);

  // map options,
  var myOptions = {
    zoom: 16,
    center: myLatlng
  };
  // standard map
  map = new google.maps.Map(document.getElementById("map-canvas"), myOptions);
  console.log("Standard map created");

  // heatmap layer
  heatmap = new HeatmapOverlay(map, 
    {
      // radius should be small ONLY if scaleRadius is true (or small radius is intended)
      "radius": 0.00015,
      "maxOpacity": 0.5, 
      // scales the radius based on map zoom
      "scaleRadius": true, 
      // if set to false the heatmap uses the global maximum for colorization
      // if activated: uses the data maximum within the current map boundaries 
      //   (there will always be a red spot with useLocalExtremas true)
      "useLocalExtrema": false,
      // which field name in your data represents the latitude - default "lat"
      latField: 'lat',
      // which field name in your data represents the longitude - default "lng"
      lngField: 'lon',
      // which field name in your data represents the data value - default "value"
      valueField: 'count'
    }
  );
  console.log("Heatmap layer created");

  var testData = {
    max: 1023,
    data: items
  };
  console.log("Ready to set data");

  heatmap.setData(testData);
};
  </script>
</body>
</html>
