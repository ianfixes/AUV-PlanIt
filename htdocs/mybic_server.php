<?php
ob_start();

if(!defined("SERVER_ROOT")){
       define("SERVER_ROOT", dirname(__FILE__).'/');
}
if(!defined("INC_PATH")){
       define("INC_PATH", SERVER_ROOT . "inc/");
}

require_once("inc/cRequest.php");

/**
 * This is an errorlog writing function, pass messages you want logged for invalid requests or bad authorization
 * Make sure your SERVER_ROOT/logs/myBicErrors.txt file is writable!
 * @param string The message you want written to the error file
 */
function logErrorToFile($msg) {
	if($fp = @fopen(SERVER_ROOT.'logs/myBicErrors.txt', 'ab+')) {
		@fwrite($fp, $msg."\r\n");
	}
}
// dynamically instantiate adapter object to handle incoming request

$file = explode("?", basename($_REQUEST['action']));
$php_class = $file[0];
$file = $file[0].'.php';
if(is_file(INC_PATH.$file)) {
	include_once(INC_PATH.$file);
	
	$xmlhttp_response = new $php_class($_REQUEST);
	if($xmlhttp_response->is_authorized()) {
		$response = $xmlhttp_response->return_response();
		// if you want xml or text returned just pass in json=false will be passed in the query
		if(isset($_REQUEST['json']) && $_REQUEST['json'] === 'false') {
			echo $response;
		} else {
			if (function_exists('json_encode')) {
				echo json_encode($response); // uses the C extension for encoding JSON
			} else {
				require_once(SERVER_ROOT.'mybic_json.php');
				$JSON = new Services_JSON();
				echo $JSON->encode($response);
			}
		}
	} else {
		// log failed authorization to file
		logErrorToFile("Authorization Failed- IP:{$_SERVER['REMOTE_ADDR']} QueryVars:".serialize($_REQUEST));
		echo 'ajax_msg_failed|notauth';
	}
	
} else {
	// log no action found
	logErrorToFile("No PHP Class Found for Action: {$_REQUEST['action']}. Failed- IP:{$_SERVER['REMOTE_ADDR']}");
	echo "ajax_msg_failed|No Action Found that matches query string: {$_REQUEST['action']}. This means the server cannot find your PHP class file, check your paths";
}
		
	
?>
