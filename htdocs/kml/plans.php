<?php

//kml for a folder of plans

include("kml_head.php");
include("report_arginit.php");
include("../inc/dbinit.php");
include("kml_validprofile.php");


echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<kml xmlns="http://www.opengis.net/kml/2.2">' . "\n";

echo "<Folder>
       <name>Plan list</name>
       <open>1</open>

       <Style>
        <ListStyle>
         <listItemType>checkOffOnly</listItemType>
        </ListStyle>
       </Style>
";

//get links to plan kml for every un-hidden plan in profile

$pid = $GET->profile_id->int;
$s = $_SERVER["SERVER_NAME"];
$where = array("profile_id" => "=$pid",
               "hidden" => "<>1");
$order = array("when_updated" => "desc"); 

foreach ($dbo->plan->Records($where, $order) as $rec)
{
    $comp = $db->getOne("select count(value) / count(*) from plan_data 
                         where plan_id = {$rec->plan_id}");

    if ($comp < 1) continue;

    $p = $rec->plan_id;
    $l = trim($rec->name);
    echo " 
      <NetworkLink> 
       <name><![CDATA[Plan $p - $l]]></name>
       <visibility>0</visibility>
       <open>0</open>
       <flyToView>0</flyToView>
       <refreshVisibility>0</refreshVisibility>
       <Link>
        <href>http://$s/kml/plan.php?plan_id=$p</href>
        <refreshMode>onExpire</refreshMode>
        <refreshInterval>3600</refreshInterval>
        <viewRefreshMode>never</viewRefreshMode>
       </Link>
       
       <Style>
        <ListStyle>
         <listItemType>checkHideChildren</listItemType>
        </ListStyle>
       </Style>
      </NetworkLink>\n";

}


echo "</Folder></kml>\n";

?>
