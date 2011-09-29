<?php

    //panel to export missions

    include("inc/dbinit.php");
    include("report_arginit.php");
    require_once("inc/plan_param.php");

    $pr = $dbo->profile->ID($_COOKIE['stored_profile_id']);
    

    //if there is no plan id, we error
    if (isset($GET->plan_id))
    {
        $pl = $dbo->plan->ID($GET->plan_id->int);
    }
    else
    {
        if (!isset($_COOKIE["stored_plan_id"]))
        {
            die("Need a plan... <a href='panel_plans.php'>Pick from one of these</a>");
        }
        $pl = $dbo->plan->ID($_COOKIE['stored_plan_id']);
    }


?>
<html>
<head>
<title>Ian's AUV PlanIt - Export Plan</title>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="/gui.css" rel="stylesheet" type="text/css">
<script type='text/javascript' src='/js/geodesy.js'></script>
<body>

   <div id="state"></div>
<?php include("panel_menu_start.php"); ?>

   <h1>Exporting Plan #<?php echo "{$pl->plan_id} - {$pl->name}"; ?></h1>
   <div id="plan_param"></div>
<?php

//TODO... put lat/lon in personal settings and use them here
//todo... put shell command for file transfer (with % placeholder for filename) into personal settings
//TODO... form for filename, etc
//TODO... target and redirect back to plans page


?>


<?php include("panel_menu_end.php"); ?>
 
</body>
</html>
