<?php

    //show mission primitives

    include("inc/dbinit.php");
    include("report_arginit.php");
    require_once("inc/mission_primitive.php");

    $m = $dbo->mission->ID($GET->mission_id->int);

    setcookie("stored_mission_id", $m->mission_id);
?>
<html>
<head>
<title>Ian's AUV PlanIt - BACKEND: Definition of Mission Primitives</title>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="/gui.css" rel="stylesheet" type="text/css">
<script type='text/javascript' src='/js/mybic.js'></script>
<?php 


    $onload = "";

    $ajaxm = new mission_primitive();
    echo $ajaxm->header_javascript("missionprimitive", 'mission_primitiveform', $_CONFIG["DEBUG"]);
    $onload .= $ajaxm->onload_javascript(false);

?>

</head>
<body onload='<?php echo $onload; ?>' >

<table width="100%" border="0" cellpadding="0" cellspacing="0">
 <tr>
  <td class="tabnavtbl">
   <ul id="tabnav">
    <li class="tabinact"><a href="/">Home</a></li>
    <li class="tabact">Mission Definition</li>
   </ul>
  </td>
 </tr>
 <tr> 
  <td class="tabcont">

   <h1>Components of "<?php echo $m->name; ?>"</h1>
   I hope you know what you're doing, especially if you change something besides name/rank...
   <br />
   <br />
   <div id="missionprimitive"></div>

  </td>
 </tr>
</table>
 

</body>
</html>
