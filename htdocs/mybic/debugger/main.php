<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Index</title>
</head>
<body>
    
<?php

include('mybic_debugger.php');
$mybic = new MybicDebugger('debug.txt');

// IF YOU WANT THE MYBIC FIREFOX EXTENSION TO GET NOTIFICATIONS UNCOMMENT OUT THIS LINE
//$mybic->enableExtension(true);

$mybic->deb("test this variable");

$test_array = array("hi", "friend", "how", "are", "you");
$mybic->deb($test_array);

$test_array = array("one"=>"myvalue", "two", "three", "EXPANDO"=>array("JIMMY", "JAM!"=>array("multi", "nest")), "four");
$mybic->deb($test_array, "TEST ARRAY");

//$mybic->deb($_SERVER, "SERVER VARS");


$mybic->deb('/Volumes/Crypto/Code/pana/apps/generic/modules/boarding_music/appregistry.xml', "XML FILE");


include('/Volumes/Crypto/Code/pana/lib/inc/class_http_transport.php');
$pac = new HttpTransport("TEST", "ONE", "TWO");

$xml = simplexml_load_file('myxmlfile.xml');
$mybic->deb($xml, "MYBIC OBJECT");

require('/Volumes/Crypto/Code/pana/lib/inc/class_strings.php');
$string = new LoadStrings("test", "hi", "yo");
$mybic->deb($string, "STRINGS");

$mybic->deb("test this one too to see how long we can make things appear on the screen");

echo $mybic->render();

?>

</body>
</html>
