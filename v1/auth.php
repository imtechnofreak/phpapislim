<?php 
function token_auth($token)
{
	global $db;
	$auth = $db->select("user","*",array('token'=>$token));
	if($auth["status"] == "success")
	{
		$auth["status"] = "true";
		return $auth;
	}else
	{
		$auth["status"] = "false";
		return $auth;
	}
}
function randomuniqueCode() 
{
    $alphabet = "ABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $pass = array(); /*remember to declare $pass as an array*/
    $alphaLength = strlen($alphabet) - 1; /*put the length -1 in cache*/
    for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); /*turn the array into a string*/
}

function randomTxn() {
    $alphabet = "0123456789";
    $pass = array(); /*remember to declare $pass as an array*/
    $alphaLength = strlen($alphabet) - 1; /*put the length -1 in cache*/
    for ($i = 0; $i < 20; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); /*turn the array into a string*/
}

function king_time($a) {
    //get current timestampt
    $b = strtotime("now");
    //get timestamp when tweet created
    $c = strtotime($a);
    //get difference
    $d = $b - $c;
    //calculate different time values
    $minute = 60;
    $hour = $minute * 60;
    $day = $hour * 24;
    $week = $day * 7;
        
    if(is_numeric($d) && $d > 0) {
        //if less then 3 seconds
        if($d < 3) return "Right now";
        //if less then minute
        if($d < $minute) return floor($d) . " seconds ago";
        //if less then 2 minutes
        if($d < $minute * 2) return "about 1 minute ago";
        //if less then hour
        if($d < $hour) return floor($d / $minute) . " minutes ago";
        //if less then 2 hours
        if($d < $hour * 2) return "about 1 hour ago";
        //if less then day
        if($d < $day) return floor($d / $hour) . " hours ago";
        //if more then day, but less then 2 days
        if($d > $day && $d < $day * 2) return "yesterday";
        //if less then year
        if($d < $day * 365) return floor($d / $day) . " days ago";
        //else return more than a year
        return "over a year ago";
    }
}

function sms_send($mobileNumber,$message1)
{
    $authKey = "#";
    //$mobileNumber = "9999999";
    $senderId = "MotorB";
    $message = urlencode($message1);
    //Define route 
    $route = "4";
    $postData = array(
        'authkey' => $authKey,
        'mobiles' => $mobileNumber,
        'message' => $message,
        'sender' => $senderId,
        'route' => $route
    );
    $url="https://control.msg91.com/api/sendhttp.php";
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData
        //,CURLOPT_FOLLOWLOCATION => true
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    //get response
    $output = curl_exec($ch);
    if(curl_errno($ch))
    {
        echo 'error:' . curl_error($ch);
    }
    curl_close($ch);
   // echo $output;exit;
}
/*function sms_send($mobileNumber,$message1)
{
    $authKey = "101948AwicN6g7U5696006f";
    //$mobileNumber = "9999999";
    $senderId = "MotorB";
    $message = urlencode($message1);
    //Define route 
    $route = "4";
    $postData = array(
        'authkey' => $authKey,
        'mobiles' => $mobileNumber,
        'message' => $message,
        'sender' => $senderId,
        'route' => $route
    );
    $url="http://sms.provanic.com/api/sendhttp.php";
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData
        //,CURLOPT_FOLLOWLOCATION => true
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    //get response
    $output = curl_exec($ch);
    if(curl_errno($ch))
    {
        echo 'error:' . curl_error($ch);
    }
    curl_close($ch);
   // echo $output;exit;
}*/
