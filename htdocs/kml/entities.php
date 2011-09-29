<?php

//print out any entities that we are tracking -- any that have location info

include("kml_head.php");
include("../inc/dbinit.php");
require_once("../inc/cPrimitive.php");

?>
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
<Document>
<?php

$p = $_GET['profile_id'];
$s = $_SERVER["SERVER_NAME"];

$ents = $dbo->entity_location->entity_id->Distinct(
        array("updated" => ">date_sub(now(), INTERVAL 2 HOUR)"));



foreach ($ents as $entity_id)
{
    $e = $dbo->entity->ID($entity_id);
    echo "
    <Folder>
     <name>{$e->name}</name>
     <visibility>1</visibility>
     <open>1</open>

     <Style>
      <ListStyle>
       <listItemType>checkHideChildren</listItemType>
      </ListStyle>
     </Style>

     <!-- placemark and icon stuff for current location -->

    ";

    $hist_minutes = $db->getOne("select value from profile_setting where profile_id=$p and setting_id=3");    
 
    $q = "
        select entity_location.lat, 
            entity_location.lng, 
            entity_location.alt,
            entity_location.heading
        from entity_location
            inner join (
            select entity_id, 
                max(updated) latest_update
            from entity_location
            where entity_id = $entity_id
            ) entity_lastpos
            using (entity_id)
        where entity_location.entity_id = $entity_id
            and updated >  date_sub(now(), INTERVAL 2 HOUR)
            and updated >  date_sub(entity_lastpos.latest_update, INTERVAL $hist_minutes MINUTE)
        order by updated asc
    ";


    $rs = $db->query($q);
    $locs = array();
    while ($r = $rs->fetchRow(MDB2_FETCHMODE_ASSOC))
    {
        $locs[] = $r;
    }

    //bomb if nothing to report
    if (1 >= count($locs))
    {
        echo "
    </Folder>";
        continue;
    }

    //make the nav line
    echo "  <Placemark>\n";
    echo "   <name>Path</name>\n";
    echo "   <LineString>\n";
    echo "    <tessellate>1</tessellate>\n";
    echo "    <extrude>1</extrude>\n";
    echo "    <altitudeMode>{$dbo->altitudemode->name->Of($dbo->entity->altitudemode_id->Of($entity_id))}</altitudeMode>\n";
    echo "    <coordinates>\n";
    foreach ($locs as $l)
    {
        $where = "{$l["lng"]},{$l["lat"]}";
        if ("" != $l["alt"])
        {
            $where .= ",{$l["alt"]}";
        }
        echo "
           $where ";
    }
    echo "    </coordinates>\n";
    echo "   </LineString>\n";
    echo "  </Placemark>\n";

    //if a heading exists, place that
    if ("" != $l["heading"])
    {
        echo cPrimitive::ui_arrow($l["lat"], $l["lng"], $l["heading"], 0.12, $_GET);
    }

    //place the entity
    echo "
            <Placemark>
             <name>{$e->name}</name>
             <Style>
              <IconStyle>
               <Icon>
                <href>http://$s{$e->icon_img_url}</href>
               </Icon>
               <hotSpot x='0.5' y='0.5' xunits='fraction' yunits='fraction' />
              </IconStyle>
             </Style>    
             <Point>
              <coordinates>$where</coordinates>
             </Point>
            </Placemark>
    ";


    echo "
    </Folder>";
}


?>
</Document>
</kml>

