<?php

//print a point at the current view center

include("kml_head.php");

$box = $_GET["BBOX"];

list($w, $s, $e, $n) = explode(",", $box);

//echo "$n<br>$e<br>$s<br>$w";

$lng = (($e - $w) / 2) + $w;
$lat = (($n - $s) / 2) + $s;


echo "<?xml version='1.0' encoding='UTF-8' ?>
<kml xmlns='http://www.opengis.net/kml/2.2'>
<Document>
 <name>Reticle test</name>
 <Style id='radioFolderExample'>
  <ListStyle>
       <listItemType>radioFolder</listItemType>
  </ListStyle>
 </Style>
 <Folder>
  <name>reticles</name>
";

echo "
  <Placemark>
   <name>View-centered placemark</name>
   <Point>
    <coordinates>$lng,$lat</coordinates>
   </Point>
  </Placemark>
";

echo "
  <styleUrl>#radioFolderExample</styleUrl>
 </Folder>
</Document>
</kml>
";

?>

