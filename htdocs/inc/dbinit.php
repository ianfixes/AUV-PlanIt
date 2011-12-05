<?php

// we'll need a config file. check for it 

$_cfg_file_path = $_SERVER["DOCUMENT_ROOT"] . "_CONFIG.php";

if (file_exists($_cfg_file_path))
{
    require_once($_cfg_file_path);
}
else
{
    echo "<html><head><title>Ian's AUV PlanIt (First use?)</title></head>\n";
    echo "<body>\n\n<h1>AUV PlanIt</h1>\n<h3>First Use Guide</h3>\n";
    echo "<p>It looks like you're using AUV PlanIt for the first time.\n";
    echo "<b>You'll need a config file</b>, which you can get by copying\n";
    echo "_CONFIG-example.php to <b>_CONFIG.php</b> and editing it to\n";
    echo "match your database (and other) preferences.</p>\n\n";
    echo "<p>If you find bugs in this software, please send them to\n";
    echo "<a href='ijk5@mit.edu?subject=AUV Planit Bug'>ijk5@mit.edu</a></p>";
    echo "\n</body>\n</html>\n";

    die();
}

//actual db initialization
require_once("db/cDbObjects.php");

$dsn = $_CONFIG["DSN"];
$dbo = new cDbObjects($dsn);
$db = cDb::singleton($dsn);

setcookie("_cookie_detection", "obviously working");

?>