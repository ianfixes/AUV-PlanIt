<?php

//print a box around the current view

include("kml_head.php");

$box = $_GET["BBOX"];

list($w, $s, $e, $n) = explode(",", $box);

//echo "$n<br>$e<br>$s<br>$w";

$lng = (($e - $w) / 2) + $w;
$lat = (($n - $s) / 2) + $s;

$span_lng = $e - $w;
$span_lat = $n - $s;

$fudge = 0.2;

$ee = $e - ($span_lng * $fudge);
$ww = $w + ($span_lng * $fudge);
$nn = $n - ($span_lat * $fudge);
$ss = $s + ($span_lat * $fudge);

$h = 10;

echo "<?xml version='1.0' encoding='UTF-8' ?>
<kml xmlns='http://www.opengis.net/kml/2.2'>
 <Placemark>
  <name>Migration path</name>
  <Style>
   <LineStyle>
    <color>ff0000ff</color>
    <width>3</width>
   </LineStyle>
  </Style>
  <LineString>
   <tessellate>1</tessellate>
   <extrude>1</extrude>
   <altitudeMode>relativeToSeaFloor</altitudeMode>
   <coordinates>
    $ww,$nn,$h
    $ee,$nn,$h
    $ee,$ss,$h
    $ww,$nn,$h
    $ww,$ss,$h
    $ee,$nn,$h
    $ee,$ss,$h
    $ww,$ss,$h
   </coordinates>
  </LineString>
 </Placemark>

</kml>
";

?>

