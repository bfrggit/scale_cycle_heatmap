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
if(!isset($_POST["tb_name_"])) $is_valid = false;
if(!isset($_POST["tp_name_"])) $is_valid = false;
if(!isset($_POST["essid_"])) $is_valid = false;
if(!isset($_POST["attr_"])) $is_valid = false;
if(!$is_valid)
	die("<p>Invalid rquest to this URL.</p>".PHP_EOL);
$db_name = $_POST["db_name_"];
$tb_name = $_POST["tb_name_"];
$tp_name = $_POST["tp_name_"];
$essid = $_POST["essid_"];
$attr = $_POST["attr_"];
print "<p>Using database: {$db_name}</p>".PHP_EOL;
print "<p>From table: {$tb_name}</p>".PHP_EOL;
print "<p>With event type: {$tp_name}</p>".PHP_EOL;
print "<p>With ESSID: {$essid}</p>".PHP_EOL;
print "<p>Mapping on: {$attr}</p>".PHP_EOL;

$mysqli = new mysqli($db_host, $db_user, $db_pwd, $db_name);
if($mysqli->connect_errno)
	die("<p>Cannot connect to server: ".$db_host."</p>".PHP_EOL);

$like_str = "\"essid\": \"".$essid."\"";
$like_str = str_replace("'", "''", $like_str);
$like_str = str_replace("_", "\_", $like_str);
$res = $mysqli->query("SELECT timestamp, geotag, value_json FROM {$tb_name} WHERE event='{$tp_name}' AND value_json LIKE '%{$like_str}%'");
print "<p>Fetched number of record(s): ".$res->num_rows."</p>".PHP_EOL;
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

<?php
$ls_data = array();
while($row = $res->fetch_assoc()):
	$timestamp = $row["timestamp"];
	$geotag = json_decode(str_replace("NaN", "null", $row["geotag"]), true);
	$value = json_decode($row["value_json"], true);
	$lat = $geotag["lat"];
	$lon = $geotag["lon"];
	$map_value = $value[$attr];
	if($lat == 0 && $lon == 0)
		continue;
	$ls_data[] = array(
			"lat" => $lat,
			"lon" => $lon,
			"count" => $map_value
		);
endwhile;
print "var items = ".str_replace("{", "\n{", json_encode($ls_data)).";".PHP_EOL;
?>

  console.log("Data collected and converted");
  console.log(items);
  drawMap(items);
}


function drawMap(items){
  console.log("Layout started");
  /*
  // map center: Aldrich Park, University of California, Irvine
  var myLatlng = new google.maps.LatLng(33.646052, -117.842745);
  */

  // map center

<?php
$avg_lat = 0;
$avg_lon = 0;
foreach($ls_data as $item):
	$avg_lat += $item["lat"];
	$avg_lon += $item["lon"];
endforeach;
if(count($ls_data) > 0) {
	$avg_lat /= count($ls_data);
	$avg_lon /= count($ls_data);
}
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
      "radius": 0.0001,
      "maxOpacity": 0.5, 
      // scales the radius based on map zoom
      "scaleRadius": true, 
      // if set to false the heatmap uses the global maximum for colorization
      // if activated: uses the data maximum within the current map boundaries 
      //   (there will always be a red spot with useLocalExtremas true)
      "useLocalExtrema": true,
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
    max: 100,
    data: items
  };
  console.log("Ready to set data");

  heatmap.setData(testData);
};
  </script>
</body>
</html>
