<?php

//this complicated mess of code statelessly (unless you count the database) figures out
// what step of plan-editing we are on, and draws appropriate UI elements.

include("kml_head.php");
include("report_arginit.php");
include("../inc/dbinit.php");
include("kml_validprofile.php");

require_once("utils/cGeodesy.php");
require_once("../inc/cPrimitive.php");

$profile_id = $GET->profile_id->int;

//pick up state variables
$db->query("delete from profile_state where profile_id = $profile_id");
$state = array();
foreach ($dbo->state->Records() as $r)
{
    if ($GET->__isset($r->name))
    {
        $v = $GET->__get($r->name)->float;
        $db->query("insert into profile_state(profile_id, state_id, value) 
                    values($profile_id, {$r->state_id}, $v)");
        $state[$dbo->state->name->Of($r->state_id)] = $v;
    }
}

//get user prefs
$rs = $db->query("select setting_id, value from profile_setting where profile_id=$profile_id");
$user_prefs = "";
while ($r = $rs->fetchRow(MDB2_FETCHMODE_OBJECT))
{
    $user_prefs[$dbo->setting->name->Of($r->setting_id)] = $r->value;
}


$lng = $GET->lookatTerrainLon->float;
$lat = $GET->lookatTerrainLat->float;


$s = $_SERVER['SERVER_NAME'];

$reticle = $db->getOne("select value from profile_setting 
                        where setting_id=1 and profile_id=$profile_id");

echo "<?xml version='1.0' encoding='UTF-8' ?>
<kml xmlns='http://www.opengis.net/kml/2.2'>
 <Document>
  <Style id=\"reticle\">
   <IconStyle>
    <Icon>
     <href>http://$s$reticle</href>
    </Icon>
    <hotSpot x='0.5' y='0.5' xunits='fraction' yunits='fraction' />
   </IconStyle>
  </Style>    
";


$plan_text = "";

//check for an active plan and print it if we do
$current_plan = $dbo->plan->plan_id->Some(array("profile_id" => "=$profile_id", 
                                                "editing_rank" => "is not null"));

if (0 < count($current_plan))
{
    $plan_id = $current_plan[0];
    $plan_text = " for plan #$plan_id";

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


    //fetch data for printing the appropriate ui widget
    $editing_rank = $dbo->plan->editing_rank->Of($plan_id);
    $primitive_id = $db->getOne("select distinct primitive_id from plan_data
                                 where plan_id = $plan_id and mission_primitive_rank = $editing_rank");
    $primitive_name = $dbo->primitive->name->Of($primitive_id);
    $label = "Plan $plan_id.$editing_rank: $primitive_name";
    echo cPrimitive::kml_ui($primitive_id, $state, $user_prefs, $label);

    //save the point for later, when we draw interconnects
    $points[$editing_rank] = 
        array("rank"         => $editing_rank,
              "state"        => $state,
              "primitive_id" => $primitive_id);

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
            $linestart = ($editing_rank == $lastpoint["rank"]) 
                ? cPrimitive::end_point_state($lastpoint["primitive_id"], $lastpoint["state"])
                : cPrimitive::end_point_params($lastpoint["primitive_id"], $lastpoint["params"]);

            $lineend = ($editing_rank == $p["rank"])
                ? cPrimitive::start_point_state($p["primitive_id"], $p["state"])
                : cPrimitive::start_point_params($p["primitive_id"], $p["params"]);
                
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
                  <name>Segment {$lastpoint["rank"]}-{$p["rank"]}</name>
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

/*

between-points: 

get list of all completed primitives + the one in progress


for i in primitives 1 to n - 1
 - linestart = (editing_rank == [i-1].rank) ? ui(primitive, state) :  i.data
 - lineend = (editing_rank == i.rank) ? ui(primitive, state) : i.data ... altitude_mode=1, alt=0
 - if primtives[i-1].endpoint and [i].startpoint have coords, draw the line. 

*/


}

echo "
</Document>
</kml>
";

?>

