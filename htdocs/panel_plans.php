<?php
    //panel to list all plans

    include("inc/dbinit.php");
    include("report_arginit.php");
    require_once("inc/plan.php");

    $p = $dbo->profile->ID($_COOKIE['stored_profile_id']);

?>
<html>
<head>
<title>Ian's AUV PlanIt - Plans for <?php echo $p->name; ?></title>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="/gui.css" rel="stylesheet" type="text/css">
<script type='text/javascript' src='/js/mybic.js'></script>
<?php 


    $onload = "";

    $ajaxm = new plan();
    echo $ajaxm->header_javascript("plan", 'planform', false);
    $onload .= $ajaxm->onload_javascript(false);

?>

</head>
<body onload='<?php echo $onload; ?>' >

<?php include("panel_menu_start.php"); ?>

   <h1>Plans by <?php echo $p->name; ?></h1>
   <div id="plan"></div>

<?php include("panel_menu_end.php"); ?>
 

</body>
</html>
