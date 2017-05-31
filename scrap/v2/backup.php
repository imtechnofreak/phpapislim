<?php
require '.././libs/Slim/Slim.php';
require_once 'dbHelper.php';
require_once 'auth.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app = \Slim\Slim::getInstance();
$db = new dbHelper();

date_default_timezone_set("Asia/Kolkata");
$base_url = "http://base3.engineerbabu.com/motorbabu"; 
$dateTime = date("Y-m-d H:i:s", time()); 
$militime=round(microtime(true) * 1000);
define('dateTime', $dateTime);
define('base_url', $base_url);
define('militime', $militime);

$app->get('/back',function() use ($app){

	global $db;
	$insert_order = $db->select('userAuth',"*",array());
	if($insert_order["status"] == "success")
	{
		foreach ($insert_order['data'] as $key) {
			$upd = $db->update('user',array('token'=>$key['token']),array('user_id'=>$key['user_id']),array());
		}
		$insert_order['message'] ="successfully";
		unset($insert_order['status']);
		echoResponse(200,$insert_order);
	}
});


function echoResponse($status_code, $response) {
    global $app;
    $app->status($status_code);
    $app->contentType('application/json');
    echo json_encode($response,JSON_NUMERIC_CHECK);
}
$app->run();
?>