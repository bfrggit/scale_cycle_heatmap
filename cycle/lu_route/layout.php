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
    <script src="/js/distance.js"></script>
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
if(!$is_valid)
	die("<p>Invalid rquest to this URL.</p>".PHP_EOL);
$db_name = $_POST["db_name_"];
$tb_name = $_POST["tb_name_"];
$tp_name = $_POST["tp_name_"];
print "<p>Using database: {$db_name}</p>".PHP_EOL;
print "<p>From table: {$tb_name}</p>".PHP_EOL;
print "<p>With event type: {$tp_name}</p>".PHP_EOL;

$mysqli = new mysqli($db_host, $db_user, $db_pwd, $db_name);
if($mysqli->connect_errno)
	die("<p>Cannot connect to server: ".$db_host."</p>".PHP_EOL);

$res = $mysqli->query("SELECT timestamp, geotag, value_json FROM {$tb_name} WHERE event='{$tp_name}' AND value_json IS NOT NULL");
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

function isFloat(val) {
  if(!val || (typeof val != "string" || val.constructor != String)) {
    return(false);
  }
  var isNumber = !isNaN(new Number(val));
  if(isNumber) {
    if(val.indexOf('.') != -1) {
      return(true);
    } else {
      return(false);
    }
  } else {
    return(false);
  }
}

function drawOutput(){
  var items = [];

<?php
$avg_lat = 0;
$avg_lon = 0;
$count = 0;
while($row = $res->fetch_assoc()):
	$timestamp = $row["timestamp"];
	$geotag = json_decode(str_replace("NaN", "null", $row["value_json"]), true);
	$lat = $geotag["lat"];
	$lon = $geotag["lon"];
	if($lat == 0 || $lon == 0)
		continue;
	$avg_lat += $lat;
	$avg_lon += $lon;
	$count += 1;
    print "items.push(new google.maps.LatLng({$lat}, {$lon}));".PHP_EOL;
endwhile;
if($count > 0) {
	$avg_lat /= $count;
	$avg_lon /= $count;
}
?>

  //console.log(items);
  drawMap(items);
}

/*
function squareDist(pFrom, pTo){
  return (pFrom.lat()-pTo.lat())*(pFrom.lat()-pTo.lat())
    +(pFrom.lng()-pTo.lng())*(pFrom.lng()-pTo.lng());
}
*/

function pointDist(pFrom, pTo){
	return distance(pFrom.lat(), pFrom.lng(), pTo.lat(), pTo.lng(), "K") * 1000;
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

  // map options
  var myOptions = {
    zoom: 16,
    center: myLatlng
  };
  // standard map
  map = new google.maps.Map(document.getElementById("map-canvas"), myOptions);

  var markerDot = {
    url: "/img/red_dot.png",
    size: new google.maps.Size(9, 9),
    // The origin for this image is 0,0.
    origin: new google.maps.Point(0, 0),
    anchor: new google.maps.Point(4, 4)
  }

  // To add the marker to the map, use the 'map' property
  // var marker = new google.maps.Marker({
  //     position: new google.maps.LatLng(33.646052, -117.842745),
  //     map: map,
  //     title:"Aldrich Park"
  // });

  var segments = [];
  var segDist = 20.0;
  var connects = [];

  for (var i = 0; i < items.length; ++i) {
    if(i == 0 || pointDist(items[i-1], items[i]) > segDist){
      segments.push([]);
      if(i > 0){
        connects.push([
          items[i-1],
          items[i]
        ]);
      }
    }
    segThis = segments[segments.length-1];
    segThis.push(items[i]);
  }

  for (var i = 0; i < segments.length; ++i) {
    // new google.maps.Marker({
    //   position: segments[i][0],
    //   icon: markerDot,
    //   map: map
    // });
    if(segments[i].length > 1){
      new google.maps.Polyline({
        path: segments[i],
        geodesic: true,
        strokeColor: '#CC0000',
        strokeOpacity: 1.0,
        strokeWeight: 5,
        map: map
      });
    } else {
      new google.maps.Marker({
        position: segments[i][0],
        icon: markerDot,
        map: map
      });
    }
  }

  for (var i = 0; i < connects.length; ++i) {
    new google.maps.Polyline({
      path: connects[i],
      geodesic: true,
      strokeColor: '#EE4444',
      strokeOpacity: 0.7,
      strokeWeight: 2,
      map: map
    });
  }
};
    </script>
</body>
</html>
