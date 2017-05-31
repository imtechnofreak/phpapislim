<?php
require '.././libs/Slim/Slim.php';
require_once 'dbHelper.php';
require_once 'auth.php';
require_once 'gcm.php';


\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app = \Slim\Slim::getInstance();
$db = new dbHelper();

date_default_timezone_set("Asia/Kolkata");
//$base_url = "https://mbuapp2017.motorbabu.net:8443/"; 
$base_url = "http://base3.engineerbabu.com/chingon_app";
$dateTime = date("Y-m-d H:i:s", time()); 
$militime=round(microtime(true) * 1000);
define('dateTime', $dateTime);
define('base_url', $base_url);
define('image_url', $image_url);
define('militime', $militime);


//get apk version start
$app->get('/version',function(){
	global $db;
		$version_query = $db->select("apk_version","*",array());
		if($version_query["status"] == "success")
		{
			$version_query['message'] = "successfully";
			$version_query['apk_version'] = $version_query['data'][0]['version'];
			unset($version_query['status']);
			unset($version_query['data']);
			echoResponse(200,$version_query);
		}
});
//get apk version start

//add user(register) start
$app->post('/register',function() use ($app){

	$json1 = file_get_contents('php://input');
	if(!empty($json1))
	{
	    $data = json_decode($json1);
		$first_name = $data->first_name;
	    $last_name =  $data->last_name;
	    $email =  $data->email;
	    $password =  md5($data->password);
		$device_id = $data->device_id; 
		$device_type = $data->device_type; 
		$device_token = $data->device_token; 
		
		$token = bin2hex(openssl_random_pseudo_bytes(16));
		$token = $token.militime;
	    
		global $db;
		if(!empty($email) && !empty($password))
		{
			$user_data = array( 'user_fname'=>$first_name,
								'user_lname'=>$last_name,
								'user_email'=>$email,
								'user_password'=>$password,
								'user_add_dt'=>date('Y-m-d h:i'),
								'device_id'=>$device_id,
								'device_type'=>$device_type,
								'device_token'=>$device_token,
								'token'=>$token
								 );
			$condition = array('user_email'=>$email);
			$query_login = $db->select("users","*",$condition);
			if($query_login["status"] == "success")
			{	
				$query_login['message'] ="Email id Already Exist Please Login!!";
				unset($query_login['status']);
				unset($query_login['data']);
				$query_login['status'] ="false";
				echoResponse(200,$query_login);
			}
			else
			{
					$insert_user = $db->insert("users",$user_data,array());
					//print_r($insert_user);
					if($insert_user["status"] == "success")
					{
						$user_id=$insert_user["data"];
						$user_data = array( 'user_id'=>$insert_user["data"],
								 'user_name'=>$first_name." ".$last_name
								);
						
						//email verification mail send start
						$code = substr(randomTxn(),0,4);
						$passid=md5($user_id);
						$display_name=$first_name.' '.$last_name;
						$message = 'Hi '.$first_name.' '.$last_name.', To activate you account please click on below link http://base3.engineerbabu.com/chingon_app/verify.php?u='.$passid.'&pcode='.$code;
						//$message="<table width='610' bgcolor='#fcfcfc' cellpadding='0' cellspacing='0' border='0' id='backgroundTable' st-sortable='preheader'><tbody><tr><td></td></tr></tbody></table><table width='610' bgcolor='#fcfcfc' cellpadding='0' cellspacing='0' border='0' id='backgroundTable' st-sortable='header'><tbody><tr><td><table width='600' cellpadding='0' cellspacing='0' border='0' align='center' class='devicewidth'><tbody><tr><td width='100%'><table width='600' cellpadding='0' cellspacing='0' border='0' align='center' class='devicewidth'><tbody><tr><td><img src='http://base3.engineerbabu.com/chingon_app/logo.jpg' width='100%' border='0'/></td></tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table><table width='610' bgcolor='#fcfcfc' cellpadding='0' cellspacing='0' border='0' id='backgroundTable' st-sortable='full-text'><tbody><tr><td><table width='600' cellpadding='0' cellspacing='0' border='0' align='center' class='devicewidth'><tbody><tr><td width='100%'><table bgcolor='#ffffff' width='600' cellpadding='0' cellspacing='0' border='0' align='center' class='devicewidth'><tbody><tr><td height='20' style='font-size:1px; line-height:1px; mso-line-height-rule: exactly;'>&nbsp;</td></tr><tr><td><table width='560' align='center' cellpadding='0' cellspacing='0' border='0' class='devicewidthinner'><tbody><tr><td style='font-family: Helvetica, arial, sans-serif; font-size: 18px; color: #282828; text-align:center; line-height: 24px;'>Hi $display_name, </td></tr><tr><td width='100%' height='15' style='font-size:1px; line-height:1px; mso-line-height-rule: exactly;'>&nbsp;</td></tr><tr><td style='font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #889098; text-align:center; line-height: 24px;'>I just wanted to extend a warm welcome to Chingon App <br /><br />The verification link for your cieker account is given below. To verify your e-mail please click below link.<br /><br /><a href='http://base3.engineerbabu.com/chingon_app/verify.php?u=$passid&pcode=$code' style='text-decoration:none'><table width='100%' height='32' bgcolor='#72b800' align='center' valign='middle' border='0' cellpadding='0' cellspacing='0' style='border-radius:3px;' st-button='learnmore'><tbody><tr><td height='9' align='center' style='font-size:1px; line-height:1px;'>&nbsp;</td></tr><tr><td height='14' align='center' valign='middle' style='font-family: Helvetica, Arial, sans-serif; font-size: 13px; font-weight:bold;color: #ffffff; text-align:center; line-height: 14px; ; -webkit-text-size-adjust:none;' st-title='fulltext-btn'>Click here to verify your E-mail</td></tr><tr><td height='9' align='center' style='font-size:1px; line-height:1px;'>&nbsp;</td></tr></tbody></table></a></td></tr><tr><td width='100%' height='15' style='font-size:1px; line-height:1px; mso-line-height-rule: exactly;'>&nbsp;</td></tr></tbody></table></td></tr><tr><td height='20' style='font-size:1px; line-height:1px; mso-line-height-rule: exactly;'>&nbsp;</td></tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table><table width='610' bgcolor='#fcfcfc' cellpadding='0' cellspacing='0' border='0' id='backgroundTable' st-sortable='right-image'><tbody><tr><td><table width='600' cellpadding='0' cellspacing='0' border='0' align='center' class='devicewidth'><tbody><tr><td width='100%'><table width='600' cellpadding='0' cellspacing='0' border='0' align='center' class='devicewidth'><tbody><tr><td>&nbsp;</td></tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table><table width='610' bgcolor='#fcfcfc' cellpadding='0' cellspacing='0' border='0' id='backgroundTable' st-sortable='seperator'><tbody><tr><td><table width='600' align='center' cellspacing='0' cellpadding='0' border='0' class='devicewidth'><tbody><tr><td align='center' height='30' style='font-size:1px; line-height:1px;'><table width='100%' cellpadding='0' cellspacing='0' border='0' class='devicewidthinner'><tbody><tr></tr><tr><td style='font-family: Helvetica, arial, sans-serif; font-size: 14px; color: #889098; line-height: 24px;'>Cheers, <span style='font-family: Helvetica, arial, sans-serif; font-size: 18px; color: #282828; line-height: 24px;'>Chingon</span></td></tr><tr></tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table><table width='610' bgcolor='#fcfcfc' cellpadding='0' cellspacing='0' border='0' id='backgroundTable' st-sortable='footer'><tbody><tr><td><table width='600' cellpadding='0' cellspacing='0' border='0' align='center' class='devicewidth'><tbody><tr><td width='100%'><table bgcolor='#72b800' width='600' cellpadding='0' cellspacing='0' border='0' align='center' class='devicewidth'><tbody><tr></tr><tr></tr><tr><td height='3' style='font-size:1px; line-height:1px; mso-line-height-rule: exactly;'>&nbsp;</td></tr></tbody></table></td></tr></tbody></table></td></tr></tbody></table>";
						//$message = 'Hi this is just a test message';
						$subject = "Chingon: Email Verification";
						$email_from='no-repply@app.chingon.com';
						$headers  = 'MIME-Version: 1.0' . "\r\n";
						$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
						$headers .= 'From: '.$email_from. '\r\n';            // Mail it
				
						@mail($email, $subject, $message, $headers);
						$auth_update = $db->update("users",array('email_verify_code'=>$code),array('user_id'=>$user_id),array());
						//email verification mail send end
						
						$insert_user['message'] ="Registration done successfully...Please open your mail and verify your Email ID!!";
						unset($insert_user['status']);
						unset($insert_user['data']);
						$insert_user['status'] ="true";
						$insert_user['running']=$user_data;
						echoResponse(200,$insert_user);
					}
			}
		}
		else
		{
			$insert_user['message'] ="Request parameter not valid";
			$insert_user['status'] ="false";
			echoResponse(200,$insert_user);
		}
	}
	else
		{
			$check_otp['message']= "No Request parameter";
			$check_otp['status'] ="false";
			echoResponse(200,$check_otp);
		}
});
//add user(register) start

//add login start
$app->post('/login',function() use ($app){
	//echo "asdasd";exit;
	$json1 = file_get_contents('php://input');
	if(!empty($json1))
	{
	    $data = json_decode($json1);
		global $db;

		$email =  $data->email;
	    $password =  md5($data->password);
		$device_id = $data->device_id; 
		$device_type = $data->device_type; 
		$device_token = $data->device_token; 
		
		$token = bin2hex(openssl_random_pseudo_bytes(16));
		$token = $token.militime;
		
		$condition = array('user_email'=>$email);
		$query_login = $db->select("users","*",$condition);
		if($query_login["status"] == "success")
		{
			if(!empty($email) && !empty($password))
			{
				$condition = array('user_email'=>$email, 'user_password'=>$password);
				$query_login = $db->select("users","*",$condition);
				if($query_login["status"] == "success")
				{	
					$user_id = $query_login['data'][0]['user_id']; 
					$user_status = $query_login['data'][0]['user_status']; 
					if($user_status==1)
					{
						$user_data = array( 'user_id'=>$query_login['data'][0]['user_id'],
										'email'=>$query_login['data'][0]['user_email'],
									 'first_name'=>$query_login['data'][0]['user_fname'],
									 'last_name'=>$query_login['data'][0]['user_lname'],
									 'secret_key'=>$token
									);
					
						$auth_update = $db->update("users",array('token'=>$token, 'device_id'=>$device_id, 'device_type'=>$device_type, 'device_token'=>$device_token),array('user_id'=>$user_id),array());
					
						$query_login['message'] ="Login successfully";
						unset($query_login['status']);
						unset($query_login['data']);
						$query_login['status'] ="true";
						$query_login['running']=$user_data;
						echoResponse(200,$query_login);
					}
					else
					{
							$query_login['message'] ="Your account is inactive to activate your account please verify your email address. A verification link already mailed to your email address please click on that link to verify your email address.";
							unset($query_login['status']);
							unset($query_login['data']);
							$query_login['status'] ="false";
							echoResponse(200,$query_login);
					}
				}
				else
				{ 
					$query_login['message'] ="Invalid Email or Password!!";
					unset($query_login['status']);
					unset($query_login['data']);
					$query_login['status'] ="false";
					echoResponse(200,$query_login);
				}
			}
			else
			{
				$insert_user['message'] ="Request parameter not valid";
				$insert_user['status'] ="false";
				echoResponse(200,$insert_user);
			}
		}
		else
		{
			$query_login['message'] ="EInvalid Email or Password!!";
			unset($query_login['status']);
			unset($query_login['data']);
			$query_login['status'] ="false";
			echoResponse(200,$query_login);
		}
	}
	else
		{
			$check_otp['message']= "No Request parameter";
			$check_otp['status'] ="false";
			echoResponse(200,$check_otp);
		}
});
//login end

//social login start
$app->post('/social_login',function() use ($app){
	//echo "asdasd";exit;
	$json1 = file_get_contents('php://input');
	if(!empty($json1))
	{
	    $data = json_decode($json1);
		global $db;
		
		$email =  $data->email;
		$first_name = $data->first_name;
	    $last_name =  $data->last_name;
	    $photourl =  $data->profile_pic;
	    $device_id = $data->device_id; 
		$device_type = $data->device_type; 
		$device_token = $data->device_token; 
		
		$token = bin2hex(openssl_random_pseudo_bytes(16));
		$token = $token.militime;
		
		$password_reset="No";
		
			$condition = array('user_email'=>$email);
			$query_login = $db->select("users","*",$condition);
			if($query_login["status"] == "success")
			{	
				$user_id = $query_login['data'][0]['user_id']; 
				
				$user_data = array( 'user_id'=>$query_login['data'][0]['user_id'],
									'email'=>$query_login['data'][0]['user_email'],
								 'first_name'=>$query_login['data'][0]['user_fname'],
								 'last_name'=>$query_login['data'][0]['user_lname'],
								 'user_profice_pic'=>$photourl,
								 'password_reset'=>$password_reset,
								 'secret_key'=>$token
								);
				$auth_update = $db->update("users",array('token'=>$token, 'device_id'=>$device_id, 'device_type'=>$device_type, 'device_token'=>$device_token, 'user_fname'=>$first_name, 'user_lname'=>$last_name,  'photourl'=>$photourl, 'user_status'=>1),array('user_id'=>$user_id),array());
				
				$query_login['message'] = "Login successfully!!";
				unset($query_login['status']);
				unset($query_login['data']);
				$query_login['status'] ="true";
				$query_login['data']=$user_data;
				echoResponse(200,$query_login);
			}
			else
			{ 
					$user_data = array( 'user_fname'=>$first_name,
								'user_lname'=>$last_name,
								'user_email'=>$email,
								'user_add_dt'=>date('Y-m-d h:i'),
								'user_status'=>1,
								'device_id'=>$device_id,
								'device_type'=>$device_type,
								'device_token'=>$device_token,
								'token'=>$token
								 );
					
					$insert_user = $db->insert("users",$user_data,array());
					//print_r($insert_user);
					if($insert_user["status"] == "success")
					{
						$user_data = array( 'user_id'=>$insert_user["data"],
								'first_name'=>$first_name,
								'last_name'=>$last_name,
								'user_profice_pic'=>$photourl,
								'email'=>$email,
								'secret_key'=>$token
						);
						
						$insert_user['message'] = "Login successfully!!";
						unset($insert_user['status']);
						unset($insert_user['data']);
						$insert_user['status'] ="true";
						$insert_user['data']=$user_data;
						echoResponse(200,$insert_user);
					}
			}
	}
	else
		{
			$check_otp['message']= "No Request parameter!!";
			$query_login['type'] = "error";
			$check_otp['status'] ="false";
			echoResponse(200,$check_otp);
		}
});
//social login end

//forget password start
$app->post('/forgot_password',function() use ($app){
	
	$json1 = file_get_contents('php://input');
	if(!empty($json1))
	{
	    $data = json_decode($json1);
		global $db;

		$email =  $data->email;
	    if(!empty($email))
		{
			$condition = array('user_email'=>$email);
			$query_login = $db->select("users","*",$condition);
			if($query_login["status"] == "success")
			{	
				$user_id = $query_login['data'][0]['user_id']; 
				$user_status = $query_login['data'][0]['user_status'];
				//$query_login['data'] =$u;
				/*$email="zubearansari@gmail.com";
				$msg="Your password is 1234.";
				sendmail($email,"Reset Your Password",$msg);*/
				$code = substr(randomTxn(),0,6);
				$message = 'Your Chingon new password is: '.$code.'';
				$subject = "Chingon: Forget Password";
				$email_from='no-repply@app.chingon.com';
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= 'From: '.$email_from. '\r\n';            // Mail it
        
				@mail($email, $subject, $message, $headers);
				
				$password =  md5($code);
				$auth_update = $db->update("users",array('user_password'=>$password, 'password_reset'=>1),$condition,array());
				
				$query_login['message'] ="Password sent to your email successfully...Please check your mail.";
				unset($query_login['status']);
				unset($query_login['data']);
				$query_login['status'] ="true";
				echoResponse(200,$query_login);
			}
			else
			{ 
				$query_login['message'] ="Invalid Email or Password!!";
				unset($query_login['status']);
				unset($query_login['data']);
				$query_login['status'] ="false";
				echoResponse(200,$query_login);
			}
		}else
		{
			$insert_user['message'] ="Request parameter not valid";
			$insert_user['status'] ="false";
			echoResponse(200,$insert_user);
		}
	}
	else
		{
			$check_otp['message']= "No Request parameter";
			$check_otp['status'] ="false";
			echoResponse(200,$check_otp);
		}
});
//forget password end






function distance($lat1, $lon1, $lat2, $lon2, $unit)
{
	$theta = $lon1 - $lon2;
	$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	$dist = acos($dist);
	$dist = rad2deg($dist);
	$miles = $dist * 60 * 1.1515;
	$unit = strtoupper($unit);
	if ($unit == "K") {
	return ($miles * 1.609344);
	} else if ($unit == "N") {
	  return ($miles * 0.8684);
	} else {
	    return $miles;
	}
}
function echoResponse($status_code, $response) {
    global $app;
    $app->status($status_code);
    $app->contentType('application/json');
    echo json_encode($response,JSON_NUMERIC_CHECK);
}
$app->run();
?>