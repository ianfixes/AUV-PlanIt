<?php

require_once("db/cDbObjects.php");

$dsn = "mysql://user:pass@localhost/ap";
$dbo = new cDbObjects($dsn);
$db = cDb::singleton($dsn);

?>
