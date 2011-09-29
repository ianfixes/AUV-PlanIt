<?php

    //panel to show all settings

    include("inc/dbinit.php");
    include("report_arginit.php");
    require_once("inc/profile_settings.php");

    //we need a profile to use
    if (!isset($GET->profile_id) && !isset($_COOKIE["stored_profile_id"]))
    {
        die("No profile specified.  <a href='/'>Go back here and try again</a>");
        
    }


    if (isset($_COOKIE["stored_profile_id"]))
    {
        $p = $dbo->profile->ID($_COOKIE["stored_profile_id"]);
    }
    else
    {
        $p = $dbo->profile->ID($GET->profile_id->int);


        //clear out any stale editing stuff
        $db->query("
            update plan 
            set editing_primitive_id = null 
            where profile_id = {$p->profile_id}");

        setcookie("stored_profile_id", $p->profile_id);
    }    
    $gui_href = "http://{$_SERVER["SERVER_NAME"]}/kml/initial.php?profile_id={$p->profile_id}";
   
?>
<html>
<head>
<title>Ian's AUV PlanIt: Settings for <?php echo $p->name; ?></title>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="/gui.css" rel="stylesheet" type="text/css">
<script type='text/javascript' src='/js/mybic.js'></script>
<?php 


    $onload = "";

    $ajax = new profile_settings();
    echo $ajax->header_javascript("profilesetting", 'profile_settingsform', false);
    $onload .= $ajax->onload_javascript(false);

?>

</head>
<body onload='<?php echo $onload; ?>' >

<?php include("panel_menu_start.php"); ?>

   <h1>Settings for <?php echo $p->name; ?></h1>
   <h2>KML</h2>
    To enable Google Earth integration, copy <a href='<?php echo $gui_href; ?>'>this link location</a>
    and paste into Google Earth as a new network link.
   <div id="profilesetting"></div>

<?php include("panel_menu_end.php"); ?>

</body>
</html>
