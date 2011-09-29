<?php

//make sure we are using a valid profile or bomb in a kml-friendly way

$kml_profile_error = "";

if (!isset($GET->profile_id))
{
    $kml_profile_error = "Profile ID not specified";
}
else if ("" == $dbo->profile->name->Of($GET->profile_id->int))
{
    $kml_profile_error = "Profile ID of '{$GET->profile_id->int}' is invalid";
}

if ("" != $kml_profile_error)
{
    $lng = $GET->lookatTerrainLon->float;
    $lat = $GET->lookatTerrainLat->float;
 
    $s = $_SERVER['SERVER_NAME'];

    echo "<?xml version='1.0' encoding='UTF-8' ?>
<kml xmlns='http://www.opengis.net/kml/2.2'>
 <Placemark>
  <name>AUV PlanIt Error: $kml_profile_error</name>
  <description>" . print_r($_GET, true) . "</description>
  <Point>
   <coordinates>$lng,$lat</coordinates>
  </Point>
  <Style>
   <IconStyle>
    <Icon>
     <href>http://$s/img/warning-red.png</href>
    </Icon>
    <hotSpot x='0.5' y='0.5' xunits='fraction' yunits='fraction' />
   </IconStyle>
  </Style>    
 </Placemark>
</kml>
";
    die();
}

?>
