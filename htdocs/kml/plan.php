<?php

//print the kml for a fully-completed plan

include("kml_head.php");
include("report_arginit.php");
include("../inc/dbinit.php");

require_once("utils/cGeodesy.php");
require_once("../inc/cPrimitive.php");

$plan_id = $GET->plan_id->int;

$s = $_SERVER['SERVER_NAME'];

echo "<?xml version='1.0' encoding='UTF-8' ?>
<kml xmlns='http://www.opengis.net/kml/2.2'>
 <Folder>
";



if (true)
{

    //get completed primitives
    $q = "select * from (
    select 
        plan_id,
        plan_name, 
        mission_primitive_rank, 
        mission_primitive_id, 
        primitive_id,
        mission_primitive_name, 
        count(value) / count(*) ratio_complete 
    from plan_data 
    group by plan_id, 
        plan_name, 
        mission_primitive_rank,
        mission_primitive_id, 
        mission_primitive_name
    ) x
   where plan_id=$plan_id
      and ratio_complete=1
    order by plan_id asc, mission_primitive_rank asc
    ";
    //echo "<!-- $q -->";

    $rs = $db->query($q);
    $points = array();
    while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
    {
        //get params for this primitive 
        $q2 = "select param_id, value from plan_param 
               where plan_id={$r->plan_id} and mission_primitive_id={$r->mission_primitive_id}";

        $rs2 = $db->query($q2);
        $paramset = array();
        while ($r2 = $rs2->fetchRow(MDB2_FETCHMODE_OBJECT))
        {
            $paramset[$r2->param_id] = $r2->value;
        }

        //print the placemark 
        $label = "Plan {$r->plan_id}.{$r->mission_primitive_rank}: {$r->mission_primitive_name}";
        echo cPrimitive::kml_representation($r->primitive_id, $paramset, $label);

        //save the point for later, when we draw the interconnects
        $p2 = $paramset;
        $points[$r->mission_primitive_rank] = 
             array("rank"         => $r->mission_primitive_rank,
                   "params"       => $p2,
                   "primitive_id" => $r->primitive_id);
                   
    }


    //need to do this for iterative purposes     
    ksort($points);
         
    //go through points and draw interconnects
    $lastpoint = NULL;
    foreach ($points as $rank => $p)
    {
        //skip the first one because we look back on it
        if (NULL != $lastpoint)
        {
            //pick up data from db or state
            $linestart = cPrimitive::end_point_params($lastpoint["primitive_id"], $lastpoint["params"]);
            $lineend = cPrimitive::start_point_params($p["primitive_id"], $p["params"]);
                
            if (isset($linestart["LAT"]) 
             && isset($linestart["LON"])
             && isset($lineend["LAT"])
             && isset($lineend["LON"]))
            {
                $lat1 = $linestart["LAT"];
                $lon1 = $linestart["LON"];
                $lat2 = $lineend["LAT"];
                $lon2 = $lineend["LON"];

                $bearing = cGeoDesy::BearingBetween($lat1, $lon1, $lat2, $lon2);
                $dist = cGeoDesy::distVincenty($lat1, $lon1, $lat2, $lon2);
 
                $d = ($dist > 3000) ? round($dist / 1000, 2) . " km" : round($dist, 0) . " m";

                //not accurate, but it's just a label
                $mid_lat = ($lat1 + $lat2) / 2;
                $mid_lon = ($lon1 + $lon2) / 2;

                $h = $linestart["ALT"];
                echo "
                 <Placemark>
                  <name>Segment</name>
                  <Style>
                   <LineStyle>
                    <color>ff00ffff</color>
                    <width>2</width>
                   </LineStyle>
                  </Style>
                  <LineString>
                   <tessellate>1</tessellate>
                   <extrude>1</extrude>
                   <altitudeMode>{$dbo->altitudemode->name->Of($linestart["MODE"])}</altitudeMode>
                   <coordinates>
                    $lon1,$lat1,$h
                    $lon2,$lat2,$h
                   </coordinates>
                  </LineString>
                 </Placemark>

                 <Placemark>
                  <name>$d, {$bearing}°</name>
                  <description>Path from step $plan_id.{$lastpoint["rank"]} to step $plan_id.{$p["rank"]}</description>
                  <Style>
                   <IconStyle>
                    <Icon>
                     <href>http://$s/img/measure.png</href>
                    </Icon>
                    <hotSpot x='0.5' y='0.0' xunits='fraction' yunits='fraction' />
                   </IconStyle>
                  </Style>    
                  <Point>
                   <coordinates>$mid_lon,$mid_lat</coordinates>
                  </Point>
                 </Placemark>

                ";

            }

        }

        $lastpoint = $p;
    }

}

echo "
</Folder>
</kml>
";

?>

