<?php 

// the panel menu is generated as tabs for the various pieces.  different tabs
//  are valid at different times, so we assign each tab a callback to determine
//  that

?>
<span style="color:red; font-size:smaller; font-weight:bold;">Warning: this prototype system does not yet sanitize inputs</span>
<br />
<br />
<table width="100%" border="0" cellpadding="0" cellspacing="0">
 <tr>
  <td class="tabnavtbl">
   <ul id="tabnav">
<?php 

function always()
{
    return true;
}

function actively_planning_something()
{
    global $dbo;
    $profile_id = @$_COOKIE["stored_profile_id"];
    return 0 < $dbo->plan->RecordCount(array("editing_rank" => "is not null"));
}

function exporting_something()
{
    return "/panel_export.php" == $_SERVER["PHP_SELF"];
}

//pages with the callback functions that decide if they should be displayed
$panel_menu_pages = array(
    array("/panel_settings.php", "Settings",    "always"), 
    array("/panel_plans.php",    "Plans",       "always"),
    array("/panel_planit.php",   "Plan Editor", "actively_planning_something"),
    array("/panel_export.php",   "Plan Exporter", "exporting_something"),
);

foreach ($panel_menu_pages as $pmp)
{
    list($apage, $pagename, $should_show) = $pmp;
    if ($should_show() || $apage == $_SERVER["SCRIPT_NAME"])
    {
        if ($apage == $_SERVER["SCRIPT_NAME"])
        {
            echo "    <li class='tabact'>$pagename</li>\n";
        }
        else
        {
            echo "    <li class='tabinact'><a href='$apage'>$pagename</a></li>\n";
        }
    }
}


?>
   </ul>
  </td>
 </tr>
 <tr> 
  <td class="tabcont">

