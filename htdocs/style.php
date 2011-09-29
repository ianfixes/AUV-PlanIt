<?php
   //styles for KML
   include("kml_head.php");

    echo "  
  <Style id=\"auvIcon\">
   <IconStyle>
    <Icon>
     <href>http://$s/img/google_marker_auv.png</href>
    </Icon>
   </IconStyle>
  </Style>
  <Style id=\"pathGPS\">
   <LineStyle>
    <color>7f00ff00</color>
    <width>3</width>
   </LineStyle>
   <PolyStyle>
    <color>7f00ffff</color>
   </PolyStyle>
  </Style>
  <Style id=\"pathDR\">
   <LineStyle>
    <color>7f0149ff</color>
    <width>4</width>
   </LineStyle>
  </Style>
  <Style id=\"pathList\">
   <ListStyle>
    <listItemType>checkHideChildren</listItemType>
   </ListStyle>
  </Style>
";
?>
