<?php

include("kml_head.php");
include("report_arginit.php");

//figure out where the camera in google earth is looking by analyzing 
// the view request

$lng = $GET->lookatTerrainLon->float;
$lat = $GET->lookatTerrainLat->float;

//INCORRECTLY assumed that the lookatTilt was the lookatTerrain
//$slantrange = $GET->cameraAlt->float / sin(deg2rad($GET->lookatTilt->float));
$slantrange = sqrt(pow($GET->cameraAlt->float, 2) +
            pow(cGeodesy::distVincenty($lat, $lng, 
                             $GET->cameraLat->float, $GET->cameraLon->float), 2));
//maybe they use haversine?  there is a slight change in size as the tilt increases

$h = min($slantrange / 2, max(10, $GET->lookatTerrainAlt->float / -2));


//half the width and height of the box (we are starting from center)
$fov_h = deg2rad($GET->horizFov->float);
$fov_v = deg2rad($GET->vertFov->float);

$box_h = $slantrange * tan($fov_h / 2);
$box_v = $slantrange * tan($fov_v / 2);


$radhead = deg2rad($GET->lookatHeading->float);

$er = cGeodesy::EarthRadius($lng);


//calculate displacements and convert to lon/lat
$disp_x =  $box_v * sin($radhead) - $box_h * cos($radhead);
$disp_y =  $box_h * sin($radhead) + $box_v * cos($radhead);
$toplt = xy2lonlat($lat, $lng, $er, $disp_x, $disp_y);

$disp_x =  $box_v * sin($radhead) + $box_h * cos($radhead);
$disp_y = -$box_h * sin($radhead) + $box_v * cos($radhead);
$toprt = xy2lonlat($lat, $lng, $er, $disp_x, $disp_y);

$disp_x = -$box_v * sin($radhead) + $box_h * cos($radhead);
$disp_y = -$box_h * sin($radhead) - $box_v * cos($radhead);
$botrt = xy2lonlat($lat, $lng, $er, $disp_x, $disp_y);

$disp_x = -$box_v * sin($radhead) - $box_h * cos($radhead);
$disp_y =  $box_h * sin($radhead) - $box_v * cos($radhead);
$botlt = xy2lonlat($lat, $lng, $er, $disp_x, $disp_y);

$path = $_SERVER['DOCUMENT_ROOT'] . "/img/reticle";

$reticles = array();
foreach (new DirectoryIterator($path) as $fileinfo)
{
    if (!$fileinfo->isDot() && !$fileinfo->isDir())
    {
        $reticles[] = pathinfo($fileinfo->getFilename());
    }
}

$s = $_SERVER['SERVER_NAME'];

echo "<?xml version='1.0' encoding='UTF-8' ?>
<kml xmlns='http://www.opengis.net/kml/2.2'>
 <Document>
 <Style id='radioFolderExample'>
  <ListStyle>
       <listItemType>radioFolder</listItemType>
  </ListStyle>
 </Style>
";

foreach ($reticles as $r)
{
    echo "
  <Style id=\"reticle-{$r['filename']}\">
   <IconStyle>
    <Icon>
     <href>http://$s/img/reticle/{$r['basename']}</href>
    </Icon>
    <hotSpot x='0.5' y='0.5' xunits='fraction' yunits='fraction' />
   </IconStyle>
  </Style>    
";
}

echo "
 <Folder>
  <name>Points</name>
";

echo "
 <Placemark>
  <name>Select Region</name>
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
    $toplt,$h
    $toprt,$h
    $botrt,$h
    $toplt,$h
    $botlt,$h
    $toprt,$h
    $botrt,$h
    $botlt,$h
   </coordinates>
  </LineString>
 </Placemark>
";

/*
echo "
 <Placemark>
  <name>Topright</name>
  <Point>
   <coordinates>$toprt</coordinates>
  </Point>
 </Placemark>

";

echo "
 <Placemark>
  <name>LookAt {$GET->lookatRange->float}, {$GET->lookatTilt->float}, {$GET->lookatHeading->float}</name>
  <description>" . print_r($_GET, true) . "</description>
  <Point>
   <coordinates>{$GET->lookatLon->float},{$GET->lookatLat->float}</coordinates>
  </Point>
 </Placemark>

";

echo "
 <Placemark>
  <name>Camera {$GET->lookatRange->float}, {$GET->lookatTilt->float}, {$GET->lookatHeading->float}</name>
  <description>" . print_r($_GET, true) . "</description>
  <Point>
   <coordinates>{$GET->cameraLon->float},{$GET->cameraLat->float}</coordinates>
  </Point>
 </Placemark>

";

*/

echo "
<Folder>
 <name>Reticles</name>
 <styleUrl>#radioFolderExample</styleUrl>
 <Placemark>
  <name>Terrain " . date("h:i:s") . "</name>
  <description>" . print_r($_GET, true) . "</description>
  <Point>
   <coordinates>{$GET->lookatTerrainLon->float},{$GET->lookatTerrainLat->float}</coordinates>
  </Point>
 </Placemark>

";

foreach ($reticles as $r)
{
echo "
 <Placemark>
  <name>{$r['filename']}</name>
  <Point>
   <coordinates>{$GET->lookatTerrainLon->float},{$GET->lookatTerrainLat->float}</coordinates>
  </Point>
  <styleUrl>reticle-{$r['filename']}</styleUrl>
 </Placemark>
";
}

echo "
 </Folder>
 </Folder>
";

/*
echo " 
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
";

*/

echo "
</Document>
</kml>
";

?>

