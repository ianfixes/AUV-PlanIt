<?php 

include("kml_head.php"); 
include("../../inc/dbinit.php");
//include("kml_validprofile.php");

$s = $_SERVER['SERVER_NAME'];

//entity network link
//past plans network link


?>
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Folder>
    <name>3rd Party KMLs</name>
    <visibility>1</visibility>
    <open>1</open>

<?php

foreach (new DirectoryIterator(".") as $file)
{
    if (!$file->isDot()
        && $file->getFilename() != basename($_SERVER['PHP_SELF'])
        && !$file->isDir()
        && ".kml" == strtolower(substr($file->getFilename(), -4)))
    {
        $f = $file->getFilename();

        echo '
    <NetworkLink>
      <name>' . $f . '</name>
      <visibility>1</visibility>
      <refreshVisibility>1</refreshVisibility>
      <open>0</open>
      <refreshVisibility>0</refreshVisibility>
      <flyToView>0</flyToView>
      <Link>
        <href>http://' . $s . '/kml/3rdparty/' . $f . '</href>
        <refreshMode>onRequest</refreshMode>
        <refreshInterval>2</refreshInterval>
      </Link>

    </NetworkLink>
    ';
    }
}

?>

  </Folder>
</kml>
