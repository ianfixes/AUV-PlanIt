<?php 

include("kml_head.php"); 
include("../inc/dbinit.php");
//include("kml_validprofile.php");

//this builds the kml file that makes this whole operation work.  it's subtle,
// but the trick is telling GE to give us a whole lot of data about the 
// current viewport.  like so:

//build up the structure of the query string that GE will send to our webserver`
$getable_params = array(
    "bboxWest",
    "bboxSouth",
    "bboxEast",
    "bboxNorth",
    "lookatLon",
    "lookatLat",
    "lookatRange",
    "lookatTilt",
    "lookatHeading",
    "lookatTerrainLon",
    "lookatTerrainLat",
    "lookatTerrainAlt",
    "cameraLon",
    "cameraLat",
    "cameraAlt",
    "horizFov",
    "vertFov",
    "horizPixels",
    "vertPixels",
    "terrainEnabled"
);

$vf = "";
$profile_name = "";
if (isset($_GET["profile_id"]))
{
    $profile_id = intval($_GET["profile_id"]);
    $vf = "profile_id=$profile_id&amp;";
    $profile_name = " for {$dbo->profile->name->Of($profile_id)}";
}

foreach ($getable_params as $gp)
{
    $vf .= "$gp=[$gp]&amp;";
}


$s = $_SERVER['SERVER_NAME'];

//entity network link
//past plans network link


?>
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Folder>
    <name>Ian's AUV PlanIt</name>
    <visibility>0</visibility>
    <open>0</open>

    <NetworkLink>
      <name>GUI<?php echo $profile_name; ?></name>
      <visibility>1</visibility>
      <refreshVisibility>1</refreshVisibility>
      <open>0</open>
      <refreshVisibility>0</refreshVisibility>
      <flyToView>0</flyToView>
      <Link>
        <href>http://<?php echo $s; ?>/kml/interactive.php</href>
        <refreshMode>onInterval</refreshMode>
        <refreshInterval>2</refreshInterval>
        <viewRefreshMode>onStop</viewRefreshMode>
        <viewRefreshTime>0</viewRefreshTime>
        <viewFormat><?php echo $vf; ?></viewFormat>
      </Link>

    </NetworkLink>

    <NetworkLink>
      <name>Trackable Objects</name>
      <visibility>1</visibility>
      <open>1</open>
      <refreshVisibility>1</refreshVisibility>
      <flyToView>0</flyToView>
      <Link>
        <href>http://<?php echo $s; ?>/kml/entities.php</href>
        <refreshMode>onInterval</refreshMode>
        <refreshInterval>10</refreshInterval>
        <viewRefreshMode>onStop</viewRefreshMode>
        <viewRefreshTime>0</viewRefreshTime>
        <viewFormat><?php echo $vf; ?></viewFormat>
      </Link>
    </NetworkLink>

    <NetworkLink>
      <name>Mission Plans</name>
      <visibility>1</visibility>
      <open>1</open>
      <refreshVisibility>1</refreshVisibility>
      <flyToView>0</flyToView>
      <Link>
        <href>http://<?php echo $s; ?>/kml/plans.php?profile_id=<?php echo $profile_id; ?></href>
        <refreshMode>onInterval</refreshMode>
        <refreshInterval>60</refreshInterval>
        <viewRefreshMode>onRequest</viewRefreshMode>
        <viewFormat><?php echo $vf; ?></viewFormat>
      </Link>

    </NetworkLink>

    <NetworkLink>
      <name>3rd Party KML</name>
      <visibility>0</visibility>
      <open>1</open>
      <refreshVisibility>1</refreshVisibility>
      <flyToView>0</flyToView>
      <Link>
        <href>http://<?php echo $s; ?>/kml/3rdparty/kml_index.php</href>
        <refreshMode>onRequest</refreshMode>
        <refreshInterval>60</refreshInterval>
      </Link>

    </NetworkLink>

  </Folder>
</kml>
