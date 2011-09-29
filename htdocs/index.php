<html>
<head>
<title>Ian's AUV PlanIt</title>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="/gui.css" rel="stylesheet" type="text/css">
<script type='text/javascript' src='/js/mybic.js'></script>
<?php 
    require_once("inc/profile.php");
    require_once("inc/entity.php");
    require_once("inc/mission.php");

    //all page content is done through AJAX

    $onload = "";

    $ajaxp = new profile();
    echo $ajaxp->header_javascript("profile", 'profileform', false);
    $onload .= $ajaxp->onload_javascript(false);
    
    $ajaxe = new entity();
    echo $ajaxe->header_javascript("entity", 'entityform', false);
    $onload .= $ajaxe->onload_javascript(false);
    
    $ajaxm = new mission();
    echo $ajaxm->header_javascript("mission", 'missionform', false);
    $onload .= $ajaxm->onload_javascript(false);

?>

</head>
<body onload='<?php echo $onload; ?>' >
<span style="color:red; font-size:smaller; font-weight:bold;">Warning: this prototype system does not yet sanitize inputs</span>
<h1>Profiles</h1>
<div id="profile"></div>
<h1>Trackable Objects</h1>
<div id="entity"></div>
<h1>Mission Types</h1>
<div id="mission"></div>
</body>
</html>

