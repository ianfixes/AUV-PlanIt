<?php

//building a mission.  the real magic is getting values from the DB (from google earth)
// using AJAX

    include("inc/dbinit.php");
    include("report_arginit.php");
    require_once("inc/plan_param.php");

    $pr = $dbo->profile->ID($_COOKIE['stored_profile_id']);
    

    //if there is no plan id, we error
    if (!isset($GET->plan_id))
    {
        if (!isset($_COOKIE["stored_plan_id"]))
        {
            die("Need a plan... <a href='panel_plans.php'>Pick from one of these</a>");
        }
        $pl = $dbo->plan->ID($_COOKIE['stored_plan_id']);
    }
    else
    {
        
        //if there is no rank, we need to query for the rank and set it
        //if plan is different than the cookie stored_plan, we need to reset rank
        if (0 == $dbo->plan->editing_rank->Count(array("profile_id" => "={$pr->profile_id}")) || 
            $GET->plan_id->int != @$_COOKIE["stored_plan_id"])
        {
            setcookie("stored_plan_id", $GET->plan_id->int);

            //rank query
            $q = "
            select mission_primitive_rank 
            from plan_data 
            where value is null
              and plan_id = {$GET->plan_id->int}
            order by plan_id asc, 
                mission_primitive_rank asc
            ";
            
            $rank = $db->getOne($q);

            $db->query("update plan set editing_rank = null where profile_id = {$pr->profile_id}");
            if ("" != $rank)
            {
                $db->query("update plan set editing_rank = $rank where plan_id={$GET->plan_id->int}");
            }
            
            $pl = $dbo->plan->ID($GET->plan_id->int);
        }
        else
        {
            $pl = $dbo->plan->ID($_COOKIE['stored_plan_id']);
        }
    }


?>
<html>
<head>
<title>Ian's AUV PlanIt - Build Mission</title>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="/gui.css" rel="stylesheet" type="text/css">
<script type='text/javascript' src='/js/mybic.js'></script>
<script type='text/javascript' src='/js/geodesy.js'></script>
<?php

    $onload = "";

    $ajaxp = new plan_param();
    echo $ajaxp->header_javascript("plan_param", 'plan_paramform', false);
    $onload .= $ajaxp->onload_javascript(false);

?>
<script type='text/javascript'>
    //this makes google earth more magical than a unicorn

    function respJSON(resp) {
        if(!resp) {
            alert('error in getting state variables from server');
        } else {
            var allstate = "";
            var c; //checkbox
            try {
                //try to fill in the 1-for-1 googleearth-to-variable fields
                for (var key in resp) {
                     //read carefully... we check for existence and assign at the same time
                     if (c = (f = document.forms["plan_paramform"]).elements["ap_" + key + "_chk"]) {
                         if (c.checked)
                         {
                             f.elements["ap_" + key].value = resp[key];
                         }
                     }

                     //allstate += "[" + key + "] => " + resp[key] + "<br />";
                }

                //now look for the complex stuff (currently just box)
                // 1. if ap_box_output exists, proceed
                // 2. if ap_box_chk is checked
                // 2.1. do slantrange and FOV calculations
                // 2.2. fill ap_box_v and ap_box_h from calculations
                // 3. update ap_box_output from ap_box_v, ap_box_h
                if ((f = document.forms["plan_paramform"]).elements["ap_box_output"])
                {
                    //if we want input from the map, get it
                    if (f.elements["ap_box_chk"].checked)
                    {
                        var lat1 = resp["lookatTerrainLat"];
                        var lon1 = resp["lookatTerrainLon"];
                        var lat2 = resp["cameraLat"];
                        var lon2 = resp["cameraLon"];
                        var alt1 = resp["cameraAlt"];

                        //distance, slantrange, FOV, and dimension calculations
                        var d = distVincenty(lat1, lon1, lat2, lon2);  //over-earth distance
                        var sl = Math.sqrt(Math.pow(alt1, 2) + Math.pow(d, 2)); //slantrange
                        var box_h = sl * 2 * Math.tan(deg2rad(resp["horizFov"]) / 2);
                        var box_v = sl * 2 * Math.tan(deg2rad(resp["vertFov"]) / 2);

                        //write values if they are sane
                        if (!isNaN(box_h) && !isNaN(box_v)) {
                            f.elements["ap_box_h"].value = box_h;
                            f.elements["ap_box_v"].value = box_v;
                        }

                    }

                    //update output field from 2 text fields
                    f.elements["ap_box_output"].value = 
                        f.elements["ap_box_v"].value + "," + f.elements["ap_box_h"].value
                }

            } catch (err) {
                //assume that form elements have dropped off page and don't sweat it
            }
            document.getElementById('state').innerHTML = allstate;
            setTimeout("stateloop()", 250);
        }
    }
    
    function stateloop() {
        if (!document.forms["plan_paramform"]) {
            //don't bother fetching... just schedule next call
            setTimeout("stateloop()", 250);
            return;
        }

        var ajaxObj = new XMLHTTP("mybic_server.php");
        ajaxObj.call("action=livestate", respJSON);
       
    }
</script>
</head>
<body onload='<?php echo $onload; ?> stateloop();' >

   <div id="state"></div>
<?php include("panel_menu_start.php"); ?>

   <h1>Editing Plan #<?php echo "{$pl->plan_id} - {$pl->name}"; ?></h1>
   <div id="plan_param"></div>

<?php include("panel_menu_end.php"); ?>
 
</body>
</html>
