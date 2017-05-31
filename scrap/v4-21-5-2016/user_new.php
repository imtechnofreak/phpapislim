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
$base_url = "https://mbuapp2017.motorbabu.net:8443/"; 
$dateTime = date("Y-m-d H:i:s", time()); 
$militime=round(microtime(true) * 1000);
define('dateTime', $dateTime);
define('base_url', $base_url);
define('image_url', $image_url);
define('militime', $militime);

$app->post('/login',function() use ($app){

	$json1 = file_get_contents('php://input');
	if(!empty($json1))
	{
	    $data = json_decode($json1);
		global $db;

		$otp = substr(randomTxn(),0,4);
		$otp1 ="Your OTP for MotorBabu is:".$otp;
		$phone_no = $data->phone_no;

		if(strlen($phone_no)==10 && ctype_digit($phone_no))
		{
			$user_data = array( 'phone_no'=>$phone_no,
								 'mobile_otp'=>$otp );
			$condition = array('phone_no'=>$phone_no);
			$query_login = $db->select("user","*",$condition);
			if($query_login["status"] == "success")
			{	
				$u = $query_login['data'][0]['user_id']; 
				$query_login['data'] =$u;
				$row =$db->update("user",array('mobile_otp'=>$otp,'status'=>0),array('user_id'=>$u),array());
				$query_login['message'] ="Login successfully";
				sms_send($phone_no,$otp1);
				$query_login['first_time'] ="2";
				unset($query_login['status']);
				echoResponse(200,$query_login);
			}
			else
			{ 
				$query_login['message'] ="Please Register First!!";
				unset($query_login['status']);
				unset($query_login['data']);
				echoResponse(200,$query_login);
			}
		}else
		{
			$insert_user['message'] ="Request parameter not valid";
			echoResponse(200,$insert_user);
		}
	}
	else
		{
			$check_otp['message']= "No Request parameter";
			echoResponse(200,$check_otp);
		}
});

$app->post('/register',function() use ($app){

	$json1 = file_get_contents('php://input');
	if(!empty($json1))
	{
	    $data = json_decode($json1);
		$name = $data->name;
	    $phone_no =  $data->phone_no;
	    $city =  $data->city;
	    $gender =  $data->gender;
	    $email =  $data->email;
	    $refer_by = $data->referal_code;

		global $db;
		$otp = substr(randomTxn(),0,4);
		$otp1 ="Your OTP for MotorBabu is:".$otp;

		$city = ucfirst($city); 
		if(strlen($phone_no)==10 && ctype_digit($phone_no) && ctype_digit($city))
		{

			$code = substr(randomuniqueCode(),0,8);
			$chech_new_code = $db->select("user","referal_code",array('referal_code'=>$code));
			if($chech_new_code['status']=="success")
			{
				$referal_code=substr(randomuniqueCode(),0,8);
			}else
			{
				$referal_code=$code;
			}

			$user_data = array( 'phone_no'=>$phone_no,
								'city'=>$city,
								'mobile_otp'=>$otp,
								'name'=>$name,
								'gender'=>$gender,
								'referal_code'=>$referal_code,
								'refered_by'=>$refer_by,
								'email'=>$email
								 );
			$condition = array('phone_no'=>$phone_no);
			$query_login = $db->select("user","*",$condition);
			if($query_login["status"] == "success")
			{	
				$query_login['message'] ="Mobile No Already Exist Please Login!!";
				unset($query_login['status']);
				unset($query_login['data']);
				echoResponse(200,$query_login);
			}
			else
			{ 
				$insert_user = $db->insert("user",$user_data,array());
				if($insert_user["status"] == "success")
				{
					if(!empty($refer_by))
					{
						$condition_refer_by = array('referal_code'=>$refer_by);
						$query_login_refer_by = $db->select("user","*",$condition_refer_by);
						if(!empty($query_login_refer_by['data'][0]['user_id']))
						{
							$coupon_referal_master = $db->select("coupon_referal_master","*",array('coupon_referal_id'=>'3'));
							$no_days = $coupon_referal_master['data'][0]['valid_day'];
							$date = date("Y-m-d");
							$valid_date = date( 'Y-m-d', strtotime( $date . " $no_days day" ) );
							$code = substr(randomuniqueCode(),0,6);
							//inser to user offer by inserting refer code start
							$user_id_in=$insert_user['data'];
							$coupon_offer_user_1 = array( 'user_id'=>$insert_user['data'],
									'coupon_referal_id'=>$coupon_referal_master['data'][0]['coupon_referal_id'],
									'coupon_code'=>$code,
									'valid_date'=>$valid_date,
									'create_at'=>militime,
									'update_at'=>militime,
									'coupon_offer_get_type'=>1,
									'by_user_id'=>$query_login_refer_by['data'][0]['user_id']
									 );
							$insert_user = $db->insert("coupon_offer_user",$coupon_offer_user_1,array());
							
							$device_token_user_insert = $db->select("user","device_token",array('user_id'=>$user_id_in));
							$device_token= $device_token_user_insert['data'][0]['device_token'];
							$ab['message']= "You got the Amazing Offers by your referal code";
							$message= array('title'=>'Motorbabu','status'=>'referral_code','message'=>'You got the Amazing Offers by your referal code!!');
							AndroidNotification($device_token,$message);
							$notif_insert = $db->insert("app_notification",array('user_id'=>$user_id_in,'title'=>'Motorbabu','status'=>'referral_code','create_at'=>militime,'message'=>'Thanks for using referal code of your friend. Congrats you got the '.$coupon_referal_master['data'][0]['coupon_referal_title'].' by Motorbabu!!'),array());
							//inser to user offer by inserting refer code end
							
							//inser to user offer refer by code start
							$code = substr(randomuniqueCode(),0,6);
							$user_id_reffer_by=$query_login_refer_by['data'][0]['user_id'];
							$coupon_offer_user_2 = array( 'user_id'=>$query_login_refer_by['data'][0]['user_id'],
									'coupon_referal_id'=>1,
									'coupon_code'=>$code,
									'valid_date'=>$valid_date,
									'create_at'=>militime,
									'update_at'=>militime,
									'coupon_offer_get_type'=>0,
									'by_user_id'=>$insert_user['data']
									 );
							$insert_user = $db->insert("coupon_offer_user",$coupon_offer_user_2,array());
							
							$device_token= $query_login_refer_by['data'][0]['device_token'];
							$ab['message']= "You got the Amazing Offers by your referal code";
							$message= array('title'=>'Motorbabu','status'=>'referral_code','message'=>'You got the Amazing Offers by your referal code!!');
							AndroidNotification($device_token,$message);
							$notif_insert = $db->insert("app_notification",array('user_id'=>$user_id_reffer_by,'title'=>'Motorbabu','status'=>'referral_code','create_at'=>militime,'message'=>'Thanks for referring Motorbabu to your friend. Congrats you got the '.$coupon_referal_master['data'][0]['coupon_referal_title'].' by Motorbabu!!'),array());
							//inser to user offer refer by code end
						}
					}
					
					$insert_user['message'] ="Register successfully";
					sms_send($phone_no,$otp1);
					unset($insert_user['status']);
					echoResponse(200,$insert_user);
				}
			}
		}else
		{
			$insert_user['message'] ="Request parameter not valid";
			echoResponse(200,$insert_user);
		}
	}
	else
		{
			$check_otp['message']= "No Request parameter";
			echoResponse(200,$check_otp);
		}
});

$app->post('/verify',function() use ($app){
	$json1 = file_get_contents('php://input');
	if(!empty($json1))
	{
	    $data = json_decode($json1);
		global $db; 
		$user_id = $data->user_id;
		$mobile_otp = $data->mobile_otp; //this will be generated.
		$device_token = $data->device_token; 

		$token = bin2hex(openssl_random_pseudo_bytes(16));
		$token = $token.militime;

		if(strlen($mobile_otp)==4 && ctype_digit($mobile_otp) && ctype_digit($user_id))
		{
			$condition = array('user_id'=>$user_id, 'mobile_otp'=>$mobile_otp);
			$check_otp = $db->select("user","*",$condition);
			if($check_otp["status"]=="success")
			{
				$otp_conf = $db->update("user",array('status'=>1),array('user_id'=>$user_id),array());
				if($otp_conf["status"]=="success")
				{
					$auth_update = $db->update("user",array('token'=>$token,'device_token'=>$device_token),array('user_id'=>$user_id),array());
					if($auth_update['status']=="success")
					{
						$ab['message']= "Your Mobile No. has been verified successfully";
						$message= array('title'=>'Motorbabu','status'=>'register','message'=>'welcome to motorbabu app!!');
						AndroidNotification($device_token,$message);

						$sel_notifi = $db->select("app_notification","*",array('user_id'=>$user_id,'title'=>'Motorbabu','status'=>'register','message'=>'welcome to motorbabu app!!'),array());
						if($sel_notifi['status']=="success")
						{
							$notif_insert = $db->update("app_notification",array('create_at'=>militime),array('user_id'=>$user_id,'title'=>'Motorbabu','status'=>'register','message'=>'welcome to motorbabu app!!'),array());
						}
						else{
							$notif_insert = $db->insert("app_notification",array('user_id'=>$user_id,'title'=>'Motorbabu','status'=>'register','create_at'=>militime,'message'=>'welcome to motorbabu app!!'),array());
						}
						
						$review_status=0;
						$check_reviev = $db->select("review","*",array('user_id'=>$user_id));
						if($check_reviev["status"] == "success")
						{
							$review_status=1;
						}
						$ab['review_status']= $review_status;
						$ab['token']= $token;
						
						$vehicle=array();
						$vehicle_query = $db->customQuery("SELECT user_vehicle.vehicle_id,user_vehicle.variant_id,user_vehicle.vehicle_reg,model_variant.variant_name,model.model_name,make.make_name,make.make_type,make.make_logo,model.model_pic FROM user_vehicle left join model_variant on user_vehicle.variant_id=model_variant.variant_id left join model on model_variant.model_id=model.model_id left join make on model.make_id=make.make_id WHERE user_vehicle.user_id='$user_id' AND user_vehicle.status='0' ORDER BY user_vehicle.vehicle_id DESC LIMIT 1");
						if($vehicle_query["status"] == "success")
						{
							foreach ($vehicle_query['data'] as $value11) 
							{
								if($value11['model_pic'])
								{
									$model_pic=base_url.$value11['model_pic'];
								}else
								{
									$model_pic="";
								}

								$vehicle= array(
									"vehicle_id"=>$value11['vehicle_id'],
									"variant_id"=>$value11['variant_id'],
									"vehicle_reg"=>$value11['vehicle_reg'],
									"variant_name"=>$value11['variant_name'],
									"model_name"=>$value11['model_name'],
									"make_name"=>$value11['make_name'],
									"make_type"=>$value11['make_type'],
									"vehicle_image"=>$model_pic,
									"make_logo"=>base_url."/images/make/640x960/images/".$value11['make_logo'],
									);
							}
						}

						$city="";
						$sel_city = $db->select("city","*",array('CityId'=>$check_otp['data'][0]['city']),array());
						if($sel_city["status"] == "success")
						{
							$city=$sel_city['data'][0]['City'];
						}
						$aa= array(
		    					"user_id"=>$check_otp['data'][0]['user_id'],
		    					"mobile"=>$check_otp['data'][0]['phone_no'],
		    					"name"=>$check_otp['data'][0]['name'],
		    					"email"=>$check_otp['data'][0]['email'],
		    					"gender"=>$check_otp['data'][0]['gender'],
		    					"city_id"=>$check_otp['data'][0]['city'],
		    					"city"=>$city,
		    					"DOB"=>$check_otp['data'][0]['DOB'],
		    					"occupation"=>$check_otp['data'][0]['occupation'],
		    					"profile_pic"=>base_url."images/user_profile/".$check_otp['data'][0]['profile_pic'],
		    					"referal_code"=>$check_otp['data'][0]['referal_code'],
		    					"vehicle"=>$vehicle
		    				);

						$ab['data'] =  $aa;
						unset($ab['status']);
						echoResponse(200,$ab);
						
					}
				}else
				{
					if($check_otp["data"][0]['status']==1)
					{
						$check_otp['message']= "please login again.";
						unset($check_otp['data']);
						unset($check_otp['status']);
						echoResponse(200,$check_otp);
					}

				}

			}
			else
			{
				$check_otp['message']= "otp not mached, please try again.";
				unset($check_otp['data']);
				echoResponse(200,$check_otp);
			}
		}
		else
		{
			$check_otp['message']= "Request parameter not valid";
			echoResponse(200,$check_otp);
		}
	}
	else
		{
			$check_otp['message']= "No Request parameter";
			echoResponse(200,$check_otp);
		}
});

$app->put('/take_location',function() use ($app){

	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$user_id=$check['data'][0]['user_id'];
			$upd_data = json_decode($app->request->getBody());
			if(!empty($upd_data))
			{
				global $db;
				if(ctype_digit($user_id))
				{
					
					$condition = array('user_id'=>$user_id,'status'=>1);
					
					$location_query = $db->select("user","*",$condition);
					if($location_query["status"] == "success")
					{
						$update_location = $db->update("user", $upd_data,array('user_id'=>$user_id),array());
						if($update_location['status']=="success")
						{
							$update_location['message'] = "Location taken";
							unset($update_location['status']);
							echoResponse(200,$update_location);
						}
						else
						{
							$update_location['message'] = "Location already taken";
							unset($update_location['status']);
							echoResponse(200,$update_location);
						}
					}
				}
				else
				{
					$check_otp['message']= "Request parameter not valid";
					echoResponse(200,$check_otp);
				}
			}else
				{
					$check_otp['message']= "No Request parameter";
					echoResponse(200,$check_otp);
				}
			
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}
	else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});

$app->post('/notification',function(){

	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$user_id=$check['data'][0]['user_id'];
			$json1 = file_get_contents('php://input');
			if(!empty($json1))
			{
				$data = json_decode($json1);
				$create_at= $data->create_at;
				global $db;
				if(ctype_digit($user_id))
				{
					if($create_at==0)
					{
						$create_at1="";
					}else
					{
						$create_at1="AND `create_at` < '$create_at'";
					}
					$notifi_query = $db->customQuery("SELECT * FROM `app_notification` WHERE `user_id`='$user_id' ".$create_at1." ORDER BY `notif_id` DESC LIMIT 10");
					if($notifi_query["status"] == "success")
					{
						foreach ($notifi_query['data'] as $key) 
						{
						   $my_date = $key['create_at'];
                           $seconds = $my_date / 1000;
                           //king_time(date("Y-m-d H:i:s", ($key['create_at'] / 1000)))
                           $date = date("d-m-Y h:i:s", $seconds); 
							$arr[]=array(
									"notif_id"=>$key['notif_id'],
									"title"=>$key['title'],
									"status"=>$key['status'],
									"message"=>$key['message'],
									"order_id"=>$key['order_id'],//add by zubear
									"image"=>$key['image'],
									"create_at"=>$key['create_at'],
									"date"=>date("d-m-Y H:i:s", ($key['create_at'] / 1000))
								);
						}
						$notifi_query['message'] = "successfully";
						$notifi_query['data'] = $arr;
						unset($notifi_query['status']);
						echoResponse(200,$notifi_query);
						
					}else
					{
						$notifi_query['message'] = "No notification";
						unset($notifi_query['status']);
						echoResponse(200,$notifi_query);
					}
				}
				else
				{
					$check_otp['message']= "Request parameter not valid";
					echoResponse(200,$check_otp);
				}
			}else
				{
					$check_otp['message']= "No Request parameter";
					echoResponse(200,$check_otp);
				}
			
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}
	else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});

$app->post('/address',function() use ($app){

	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$json1 = file_get_contents('php://input');
			if(!empty($json1))
			{
				$data = json_decode($json1);
				global $db;
				if(ctype_digit($check['data'][0]['user_id']))
				{
					$insert_data = array(
						'user_id'=>$check['data'][0]['user_id'],
						'address'=>$data->address,
						'city'=>$data->city_id,
						'lat'=>$data->lat,
						'lng'=>$data->lng,
						'create_at'=>militime,
						'update_at'=>militime,
						);
					
						$insert_address = $db->insert("user_address",$insert_data,array());
						if($insert_address["status"] == "success")
						{
							$insert_address['message'] ="address registered successfully";
							unset($insert_address['status']);
							/*unset($insert_address['data']);*/
							echoResponse(200,$insert_address);
						}
					
				}
				else
				{
					$check_otp['message']= "Request parameter not valid";
					echoResponse(200,$check_otp);
				}
			}else
				{
					$check_otp['message']= "No Request parameter";
					echoResponse(200,$check_otp);
				}
			
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}
	else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});

$app->get('/address',function(){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$user_id=$check['data'][0]['user_id'];
			$json1 = file_get_contents('php://input');
			$data = json_decode($json1);
			global $db;
			if(ctype_digit($user_id))
			{
				$vehicle_query = $db->select("user_address","*",array('user_id'=>$user_id));
				if($vehicle_query["status"] == "success")
				{
					$vehicle_query['message'] = "successfully";
					unset($vehicle_query['status']);
					//unset($vehicle_query['data']);
					echoResponse(200,$vehicle_query);
					
				}else
				{
					$vehicle_query['message'] = "No address";
					unset($vehicle_query['status']);
					//unset($vehicle_query['data']);
					echoResponse(200,$vehicle_query);
				}
			}
			else
			{
				$check_otp['message']= "Request parameter not valid";
				echoResponse(200,$check_otp);
			}
			
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});

$app->delete('/address/:id',function($address_id) use ($app){

	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$user_id=$check['data'][0]['user_id'];
			if(!empty($address_id))
			{
				//$data = json_decode($json1);
				global $db;
				if(ctype_digit($check['data'][0]['user_id']))
				{
					//$address_id=$data->address_id;
					$vehicle_query = $db->select("user_address","*",array('user_id'=>$user_id,'address_id'=>$address_id));
					if($vehicle_query["status"] == "success")
					{
						$delete_address = $db->delete("user_address", array('address_id'=>$address_id));
						if($delete_address["status"] == "success")
						{
							$delete_address['message'] ="address remove successfully";
							unset($delete_address['status']);
							unset($delete_address['data']);
							echoResponse(200,$delete_address);
						}
					}else
					{
						$vehicle_query['message'] ="address already removed OR not exist!!";
						unset($vehicle_query['status']);
						unset($vehicle_query['data']);
						echoResponse(200,$vehicle_query);
					}
					
				}
				else
				{
					$check_otp['message']= "Request parameter not valid";
					echoResponse(200,$check_otp);
				}
			}else
				{
					$check_otp['message']= "No Request parameter";
					echoResponse(200,$check_otp);
				}
			
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});

$app->put('/address',function() use ($app){

	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$upd_data = json_decode($app->request->getBody());
			$address_id=$upd_data->address_id;
			$user_id=$check['data'][0]['user_id'];
			if(!empty($address_id))
			{
				global $db;
				if(ctype_digit($check['data'][0]['user_id']))
				{
					$vehicle_query = $db->select("user_address","*",array('user_id'=>$user_id,'address_id'=>$address_id));
					if($vehicle_query["status"] == "success")
					{
						$update_address = $db->update("user_address", $upd_data, array('user_id'=>$user_id,'address_id'=>$address_id),array());
						if($update_address["status"] == "success")
						{
							$update_address['message'] ="address updated successfully";
							unset($update_address['status']);
							unset($update_address['data']);
							echoResponse(200,$update_address);
						}else
						{
							$update_address['message'] ="address already updated";
							unset($update_address['status']);
							unset($update_address['data']);
							echoResponse(200,$update_address);
						}
					}else
					{
						$vehicle_query['message'] ="address not exist!!";
						unset($vehicle_query['status']);
						unset($vehicle_query['data']);
						echoResponse(200,$vehicle_query);
					}
					
				}
				else
				{
					$check_otp['message']= "Request parameter not valid";
					echoResponse(200,$check_otp);
				}
			}else
				{
					$check_otp['message']= "No Request parameter";
					echoResponse(200,$check_otp);
				}
			
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});

$app->get('/wallet',function(){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$user_id=$check['data'][0]['user_id'];
			global $db;

		    $wallet_query = $db->select("user","wallet",array('user_id'=>$user_id));
		    if($wallet_query["status"] == "success")
		    {
	    		$wallet_query['message'] = "successfully";
	    		unset($wallet_query['status']);
	    		$wallet_query['data'] =  $wallet_query['data'][0]['wallet'];	
		    	echoResponse(200,$wallet_query);
		    }
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
	{
		$msg['message'] = "Unauthorised access";
		echoResponse(200,$msg);
	}
});

$app->post('/referral',function() use ($app){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			global $db;
			$user_id=$check['data'][0]['user_id'];
			$upd_data = json_decode($app->request->getBody());
			$refered_by = $upd_data->refered_by;

			$chech_refferal = $db->customQuery("SELECT * FROM user WHERE `user_id`= '$user_id' AND `refered_by`!=''");
			if($chech_refferal['status']=="success")
			{
				$chech_refferal['message'] = "You already used code!!";
	    		unset($chech_refferal['status']);	
	    		unset($chech_refferal['data']);	
	    		unset($chech_refferal['row']);	
		    	echoResponse(200,$chech_refferal);
			}else
			{
				$chech_code = $db->select("user","*",array('referal_code'=>$refered_by));
				if($chech_code['status']=="success")
				{
					$refered_by_user_id =  $chech_code['data'][0]['user_id'];
					$get_wallet = $db->select("user","wallet",array('user_id'=>$refered_by_user_id));
					if($get_wallet['status']=="success")
					{
						$wallet =  $get_wallet['data'][0]['wallet'];
					}else
					{
						$wallet =  "50";
					}
					$get_amount = $db->select("refer_earn","*",array());
					$amount = $get_amount['data'][0]['refer_by'];
					$refer_to_amount = $get_amount['data'][0]['refer_to'];

					$new_wallet=$wallet+$amount;
					
					$updaye_wallet = $db->update("user",array('wallet'=>$new_wallet),array('user_id'=>$refered_by_user_id),array());
					if($updaye_wallet["status"] == "success")
					{
						$get_de_token = $db->select("user","device_token",array('user_id'=>$refered_by_user_id));
						$device_token= $get_de_token['data'][0]['device_token'];
						$message= array('title'=>'Motorbabu','status'=>'Refer&Earn','message'=>'Thanks to refer Motorbabu app to your friend. We added '.$amount.' Rs. to your wallet!!');
						AndroidNotification($device_token,$message);
						$notif_insert = $db->insert("app_notification",array('user_id'=>$refered_by_user_id,'title'=>'Motorbabu','status'=>'Refer&Earn','create_at'=>militime,'message'=>'Thanks to refer Motorbabu app to your friend. We added '.$amount.' Rs. to your wallet!!'),array());
						

						$get_u_de_token = $db->select("user","device_token",array('user_id'=>$refered_by_user_id));
						$u_device_token= $get_u_de_token['data'][0]['device_token'];
						$u_message= array('title'=>'Motorbabu','status'=>'Refer&Earn','message'=>'Wow you used refferal code and get '.$refer_to_amount.' Rs.');
						AndroidNotification($u_device_token,$u_message);
						$u_notif_insert = $db->insert("app_notification",array('user_id'=>$user_id,'title'=>'Motorbabu','status'=>'Refer&Earn','create_at'=>militime,'message'=>'Wow you used refferal code and get '.$refer_to_amount.' Rs.'),array());
										
					}

				    $wallet_query = $db->update("user",array('refered_by'=>$refered_by),array('user_id'=>$user_id),array());
				    if($wallet_query["status"] == "success")
				    {
			    		$wallet_query['message'] = "successfully";
			    		unset($wallet_query['status']);	
			    		unset($wallet_query['data']);	
				    	echoResponse(200,$wallet_query);
				    }
				}else
				{
					$msg['message'] = "Invalid code";
					echoResponse(200,$msg);
				}
			}
			
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
	{
		$msg['message'] = "Unauthorised access";
		echoResponse(200,$msg);
	}
});

$app->get('/profile',function(){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$user_id=$check['data'][0]['user_id'];
			global $db;

		    $profile_query = $db->select("user","*",array('user_id'=>$user_id));
		    if($profile_query["status"] == "success")
		    {
	    		$profile_query['message'] = "successfully";
	    		if($profile_query['data'][0]['profile_pic'])
	    		{
	    			$pro = base_url."images/user_profile/".$profile_query['data'][0]['profile_pic'];
	    		}else
	    		{
	    			$pro="";
	    		}

	    		unset($profile_query['status']);
	    		$aa= array(
	    					"user_id"=>$profile_query['data'][0]['user_id'],
	    					"name"=>$profile_query['data'][0]['name'],
	    					"email"=>$profile_query['data'][0]['email'],
	    					"profile_pic"=>$pro,
	    					"referal_code"=>$profile_query['data'][0]['referal_code'],
	    					"occupation"=>$profile_query['data'][0]['occupation']
	    				);

	    		$profile_query['data'] =  $aa;	
		    	echoResponse(200,$profile_query);
		    }
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
	{
		$msg['message'] = "Unauthorised access";
		echoResponse(200,$msg);
	}
});

$app->post('/profile',function() use ($app){

	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			if(isset($_FILES['file']['name']))
	      	{
	        	$image=$_FILES["file"]["name"];
	        	$new_image = militime.$image;
	        	move_uploaded_file($_FILES["file"]["tmp_name"],"../../images/user_profile/" .$new_image);
	        	$path = $new_image;

	        	$upd_data = array(
							'name'=>$app->request->params('name'),
							'email'=>$app->request->params('email'),
							'gender'=>$app->request->params('gender'),
							'DOB'=>$app->request->params('DOB'),
							'profile_pic'=>$path
				);
	      	}
	      	else
	      	{
	      		$path="";
	      		$upd_data = array(
							'name'=>$app->request->params('name'),
							'email'=>$app->request->params('email'),
							'gender'=>$app->request->params('gender'),
							'DOB'=>$app->request->params('DOB')
				);
	      	}
			
			$user_id=$check['data'][0]['user_id'];
			if(!empty($user_id))
			{
				global $db;
				if(ctype_digit($check['data'][0]['user_id']))
				{
					$update_address = $db->update("user", $upd_data, array('user_id'=>$user_id),array());
					if($update_address["status"] == "success")
					{
						$update_address['message'] ="successfully";
						$profile_query = $db->select("user","*",array('user_id'=>$user_id));
					    if($profile_query["status"] == "success")
					    {
					    	if($profile_query['data'][0]['profile_pic']!="")
					    	{
					    		$pro= base_url."images/user_profile/".$profile_query['data'][0]['profile_pic'];
					    	}else
					    	{
					    		$pro = "";
					    	}
				    		$aa= array(
				    					"user_id"=>$profile_query['data'][0]['user_id'],
				    					"name"=>$profile_query['data'][0]['name'],
				    					"email"=>$profile_query['data'][0]['email'],
				    					"gender"=>$profile_query['data'][0]['gender'],
				    					//"DOB"=>$profile_query['data'][0]['DOB'],
				    					"DOB"=>date('d-M-Y', strtotime($profile_query['data'][0]['DOB'])),
				    					"occupation"=>$profile_query['data'][0]['occupation'],
				    					"profile_pic"=>$pro,
				    					"referal_code"=>$profile_query['data'][0]['referal_code']
				    				);
					    }
					    $update_address['data'] = $aa;
						unset($update_address['status']);

						echoResponse(200,$update_address);
					}else
					{
						$update_address['message'] ="successfully";
						$profile_query = $db->select("user","*",array('user_id'=>$user_id));
					    if($profile_query["status"] == "success")
					    {
					    	if($profile_query['data'][0]['profile_pic']!="")
					    	{
					    		$pro= base_url."images/user_profile/".$profile_query['data'][0]['profile_pic'];
					    	}else
					    	{
					    		$pro = "";
					    	}
				    		$aa= array(
				    					"user_id"=>$profile_query['data'][0]['user_id'],
				    					"name"=>$profile_query['data'][0]['name'],
				    					"email"=>$profile_query['data'][0]['email'],
				    					"gender"=>$profile_query['data'][0]['gender'],
				    					"DOB"=>date('d-M-Y', strtotime($profile_query['data'][0]['DOB'])),
				    					"occupation"=>$profile_query['data'][0]['occupation'],
				    					"profile_pic"=>$pro,
				    					"referal_code"=>$profile_query['data'][0]['referal_code']
				    				);
					    }
					    $update_address['data'] = $aa;
						unset($update_address['status']);
						echoResponse(200,$update_address);
					}
				}
				else
				{
					$check_otp['message']= "Request parameter not valid";
					echoResponse(200,$check_otp);
				}
			}else
				{
					$check_otp['message']= "No Request parameter";
					echoResponse(200,$check_otp);
				}
			
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});

$app->post('/feedback',function() use ($app){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))

	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$json1 = file_get_contents('php://input');
			if(!empty($json1))
			{
				$data1 = json_decode($json1);
				global $db;
				if(ctype_digit($check['data'][0]['user_id']))
				{
					$insert_data = array(
						'user_id'=>$check['data'][0]['user_id'],
						'feedback'=>$data1->feedback,
						'create_at'=>militime
						);
						$insert_feedback = $db->insert("app_feedback",$insert_data,array());
						if($insert_feedback["status"] == "success")
						{
							$insert_feedback['message'] ="feedback registered successfully";
							unset($insert_feedback['status']);
							unset($insert_feedback['data']);
							echoResponse(200,$insert_feedback);
						}
				}
				else
				{
					$check_otp['message']= "Request parameter not valid";
					echoResponse(200,$check_otp);
				}
			}else
				{
					$check_otp['message']= "No Request parameter";
					echoResponse(200,$check_otp);
				}	
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}
	else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});

$app->get('/feedback',function(){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$user_id=$check['data'][0]['user_id'];
			$json1 = file_get_contents('php://input');
			$data = json_decode($json1);
			global $db;
			if(ctype_digit($user_id))
			{
				$feedback_query = $db->customQuery("SELECT app_feedback.*,user.name,user.email,user.profile_pic FROM app_feedback INNER JOIN `user` ON app_feedback.user_id=user.user_id");
				if($feedback_query["status"] == "success")
				{
					foreach ($feedback_query['data'] as $key) 
					{	
						//$create= date("Y-m-d H:i:s", ($key['create_at'] / 1000));
						$a[] = array(
								'feedback_id'=>$key['feedback_id'],
								'user_id'=>$key['user_id'],
								'name'=>$key['name'],
								'email'=>$key['email'],
								'profile_pic'=>$key['profile_pic'],
								'feedback'=>$key['feedback'],
								'create_at'=>king_time(date("Y-m-d H:i:s", ($key['create_at'] / 1000)))
							);
					}
					$feedback_query['message'] = "successfully";
					$feedback_query['data'] = $a;
					unset($feedback_query['status']);
					unset($feedback_query['row']);
					echoResponse(200,$feedback_query);
					
				}else
				{
					$feedback_query['message'] = "No feedback";
					unset($feedback_query['status']);
					unset($feedback_query['data']);
					unset($feedback_query['row']);
					echoResponse(200,$feedback_query);
				}
			}
			else
			{
				$check_otp['message']= "Request parameter not valid";
				echoResponse(200,$check_otp);
			}
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});

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

$app->get('/static',function()use ($app){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$id = $app->request()->params('id');
			if(isset($_GET['id']))
			{
				$user_id=$check['data'][0]['user_id'];
				global $db;
				if(ctype_digit($user_id))
				{
					$version_query = $db->select("static_content","*",array('content_id'=>$id));
					if($version_query["status"] == "success")
					{
						$version_query['message'] = "successfully";
						$version_query['data'] = $version_query['data'][0]['description'];
						unset($version_query['status']);
						//unset($version_query['data']);
						echoResponse(200,$version_query);
						
					}else
					{
						$version_query['message']= "Request parameter not valid";
						echoResponse(200,$version_query);
					}
				}
				else
				{
					$check_otp['message']= "Request parameter not valid";
					echoResponse(200,$check_otp);
				}
			}else
			{
				$check_otp['message']= "Request Id Required for static content";
				echoResponse(200,$check_otp);
			}
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});

$app->get('/city',function(){
	global $db;
    $city_query = $db->select("city","*",array('CountryID'=>'113','status'=>1));
    if($city_query["status"] == "success")
    {
		$city_query['message'] = "successfully";
		unset($city_query['status']);
		
		foreach ($city_query['data'] as $key) 
		{
			$aa[]= array(
					"CityId"=>$key['CityId'],
					"City"=>$key['City']
				
				);
		}
		$city_query['data'] =  $aa;	
    	echoResponse(200,$city_query);
    }
});

$app->get('/center_likes',function() {
	$headers = apache_request_headers();
	$check = token_auth($headers['secret_key']);
	if($check['status']=="true")
	{
		global $db;
		$user_id = $check['data'][0]['user_id'];
		if(!empty($user_id))
		{
			$find_like_cen = $db->customQuery("SELECT DISTINCT center_id FROM service_center_like WHERE user_id='$user_id'");
			if($find_like_cen["status"] == "success")
			{
				foreach ($find_like_cen['data'] as $value) 
				{
					$center_id=$value['center_id'];
					$find_all_station = $db->customQuery("SELECT service_center.*,city_location.area_name FROM service_center  INNER JOIN city_location ON service_center.area_id=city_location.area_id WHERE center_id='$center_id' AND status='0'");
				    if($find_all_station["status"] == "success")
				    {
			    		/*$sel_services= $db->customQuery("SELECT center_services.*,services.service_name FROM center_services INNER JOIN services ON center_services.service_id=services.service_id WHERE center_services.center_id='$center_id'");
			    		$v4 = ""; 
			    		if ($sel_services['status']=="success") 
			    		{
				    		foreach($sel_services['data'] as $k)
			    			{
								$v4[] = array('service_id'=>$k['ser_id'], 
		    					'service_name'=>$k['service_name'],
		    					'service_desc'=>$k['desc']
		    					);
			    			} 
			    		}	*/
			    		
			    		$avg_rate=0;
			    		$find_avg_rate = $db->customQuery("SELECT avg(rating) AS avg_rate FROM review WHERE `center_id`='".$find_all_station['data'][0]['center_id']."'");
			    		if($find_avg_rate['status']=="success")
			    		{
			    			 $avg_rate=round($find_avg_rate['data'][0]['avg_rate'],1);
			    		}
			    		

			    		$center_pictures=array();
			    		$center_image = $db->select("service_center_image","*",array('center_id'=>$find_all_station['data'][0]['center_id']));
			    		if($center_image['status']=="success")
			    		{
			    			foreach ($center_image['data'] as $value11) 
			    			{
			    				$center_pictures[]=array('image_id'=>$value11['image_id'],
			    					'image'=>base_url.$value11['image']
			    				);
			    			}
			    			
			    		}

			    		$is_favorite=0;
			    		$chack_favorite=$db->select("service_center_like","*",array("center_id"=>$center_id,"user_id"=>$user_id));
			    		if($chack_favorite['status']=="success")
			    		{
			    			$is_favorite=1;
			    		}

			    		$can_review=0;
			    		$chack_order=$db->select("service_order","*",array("center_id"=>$center_id,"user_id"=>$user_id,"status"=>"2"));
			    		if($chack_order['status']=="success")
			    		{
			    			$can_review=1;
			    		}
			    		
			    	//	$dis_cal = distance($latitude,$longitude, $find_all_station['data'][0]['center_lat'], $find_all_station['data'][0]['center_lng'],'K');
			    		
		    			$a[] = array('center_id'=>$find_all_station['data'][0]['center_id'],

		    			  'center_name'=>$find_all_station['data'][0]['center_name'],
					      'center_address'=>$find_all_station['data'][0]['center_address'],
					      'center_lat'=> $find_all_station['data'][0]['center_lat'],
					      'center_lng'=> $find_all_station['data'][0]['center_lng'],
					      'center_mobile'=>$find_all_station['data'][0]['center_mobile'],
					      'center_desc'=> $find_all_station['data'][0]['center_desc'],
					      'center_create_at'=> $find_all_station['data'][0]['center_create_at'],
					      'center_owner'=>$find_all_station['data'][0]['center_owner'],
					      'center_email'=>$find_all_station['data'][0]['center_email'],
					      'center_phone'=>$find_all_station['data'][0]['center_phone'],
					      'area_name'=>$find_all_station['data'][0]['area_name'],
					      'response_rate'=>"NA",
					      'is_favorite'=>$is_favorite,
					      'can_review'=>$can_review,
					    // 'distance'=>round($dis_cal,2),
					      //'services'=> $v4,
					      'avg_rate'=>$avg_rate,
					      'center_pictures'=>$center_pictures
		    			);
				    }
				  		
				}
				$find_like_cen['data'] = $a;	
		    	$find_like_cen['message'] = "successfully";
		    	unset($find_like_cen['status']);
		    	unset($find_like_cen['row']);
		    	echoResponse(200,$find_like_cen);
			}else
		    {
		    	$find_like_cen['data'] = array();
		    	unset($find_like_cen['status']);
		    	$find_like_cen['message'] = "No station found";

		    	echoResponse(200,$find_like_cen);
		    }
		}
		else
			{
				$check_otp['message']= "No Request parameter";
				echoResponse(200,$check_otp);
			}
    }
	else
	{
		$msg['message'] = "Invalid Token";
		echoResponse(200,$msg);
	}	 
});

$app->get('/feed',function()use ($app){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$user_id=$check['data'][0]['user_id'];
			$vehicle_id=$app->request->params('vehicle_id');

			$json1 = file_get_contents('php://input');
			$data = json_decode($json1);
			global $db;
			if(ctype_digit($user_id))
			{
				$baneer_query = $db->select("banner_offer","*",array());
				if($baneer_query["status"] == "success")
				{
					foreach ($baneer_query['data'] as $key) 
					{
						$a[] = array(
								'banner_id'=>$key['banner_id'],
								'banner'=>base_url."/images/banner_offer/".$key['banner']
							);
					}
				}

				$baneer_query1 = $db->select("banner_feedback","*",array());
				if($baneer_query1["status"] == "success")
				{
					foreach ($baneer_query1['data'] as $key1) 
					{
						$b[] = array(
								'banner_id'=>$key1['banner_id'],
								'banner'=>base_url."/images/banner_feedback/".$key1['banner']
							);
					}
				}

				$c=null;
				//echo "SELECT * FROM service_order WHERE service_order.user_id='$user_id' AND service_order.vehicle_id='$vehicle_id' AND service_order.status!='8' AND service_order.status!='2' ORDER BY service_order.order_id DESC LIMIT 1";exit;
				$last_ser = $db->customQuery("SELECT * FROM service_order WHERE service_order.user_id='$user_id' AND service_order.vehicle_id='$vehicle_id' AND service_order.status!='8' AND service_order.status!='2' AND service_order.status='7' ORDER BY service_order.order_id DESC LIMIT 1");
				if($last_ser["status"] == "success")
				{
					foreach ($last_ser['data'] as $key2) 
					{

						$order_id= $key2['order_id'];
						$v4 = "";
						$sel_ser= $db->customQuery("SELECT service_order_serviceDetail.service_id,services.service_name FROM service_order_serviceDetail LEFT JOIN services ON service_order_serviceDetail.service_id=services.service_id WHERE service_order_serviceDetail.order_id='$order_id'");
						//$sel_ser = $db->customQuery("SELECT service_order_serviceDetail.service_id, service_order_serviceDetail.service_part_name, services.service_name FROM service_order_serviceDetail left JOIN services ON service_order_serviceDetail.service_id = services.service_id left JOIN service_category ON service_order_serviceDetail.service_category = service_category.service_category_id WHERE service_order_serviceDetail.order_id = '$order_id'");
						if($sel_ser["status"] == "success")
						{
							foreach ($sel_ser["data"] as $serv) 
							{
								/*$service_name='';
								if($serv['service_id']=="0")
									$service_name=$serv['service_part_name'];
								else
									$service_name=$serv['service_name'];
								
								$v4[] = $service_name;*/
								$v4[] = $serv['service_name'];
							}
							//print_r($v4);exit;
							$v4 =implode(", ",$v4);
						}


						$c = array(
								'order_id'=>$key2['order_id'],
								'order_date'=>date('d-M-Y', strtotime($key2['order_date'])),
								'km'=>$key2['km'],
								'services'=>$v4
							);
					}
				}

				$total_user="500";
				$last_ser = $db->customQuery("SELECT count(*) AS count FROM user");
				if($last_ser["status"] == "success")
				{
					$total_user=$last_ser['data'][0]['count'];
				}

				$today_order="120";
				$totalorders = $db->customQuery("SELECT count(*) AS count FROM service_order");
				if($totalorders["status"] == "success")
				{
					$all_order=$totalorders['data'][0]['count'];
				}

				$current_date =date("Y-m-d", time());
				$current_date = str_replace('-', '/', $current_date);
				$yes_date = date('Y-m-d',strtotime($current_date . "-1 days"));
				$tow_date = date('Y-m-d',strtotime($current_date . "+1 days"));
				$yes_date_mili = strtotime($yes_date) * 1000 ;
				$tow_date_mili = strtotime($tow_date) * 1000 ;

				$ongoing_booking="14";
				$ongoing_booking11 = $db->customQuery("SELECT count(*) AS count FROM `service_order` WHERE create_at BETWEEN '$yes_date_mili'  AND '$tow_date_mili'");
				if($ongoing_booking11["status"] == "success")
				{
					$ongoing_booking=$ongoing_booking11['data'][0]['count'];
				}

				$baneer_query['message'] = "successfully";
				$baneer_query['total_user'] = $total_user;
				$baneer_query['today_order'] = $all_order+10;//all order
				$baneer_query['ongoing_booking'] = $ongoing_booking;

				//$baneer_query['test'] = $tow_date_mili;


				$baneer_query['last_service'] = $c;
				$baneer_query['offer_banner'] = $a;
				$baneer_query['offer_feedback'] = $b;


				unset($baneer_query['status']);
				unset($baneer_query['data']);
				unset($baneer_query['row']);

				echoResponse(200,$baneer_query);
			}
			else
			{
				$check_otp['message']= "Request parameter not valid";
				echoResponse(200,$check_otp);
			}
			
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});


$app->get('/referralMessage',function() use ($app){
    $headers = apache_request_headers();
    if(!empty($headers['secret_key']))
    {
        $check = token_auth($headers['secret_key']);
		//$referal_code = token_auth($headers['referal_code']);
        if($check['status']=="true")
        {
            global $db;
            $user_id=$check['data'][0]['user_id'];
			$referal_code=$check['data'][0]['referal_code'];
            $chech_refferal = $db->customQuery("SELECT * FROM refer_earn ");
            if($chech_refferal['status']=="success")
            {
                $sel_offer = $db->customQuery("SELECT * FROM coupon_offer_user INNER JOIN coupon_referal_master ON coupon_offer_user.coupon_referal_id = coupon_referal_master.coupon_referal_id WHERE coupon_offer_user.user_id = '$user_id'");
                $arr = array();
                if($sel_offer['status'] == "success")
                {  
                    foreach($sel_offer['data'] as $offerkey)
                    {
                        $arr[] = array(
                                    'offer'=>$offerkey['coupon_referal_title'],
                                    'valid_till'=>$offerkey['valid_date'],
                                    'coupon_code'=>$offerkey['coupon_code'],
                                    'details'=>$offerkey['coupon_referal_radeem_msg'],
									'referral_code'=>$referal_code
                                    );
                    }
                }
                $chech_refferal['message'] = "successfully";
                //$chech_refferal['msg_display'] = $chech_refferal['data'][0]['msg_display'];
				$msg_share=str_replace("[referal_code]",$referal_code,$chech_refferal['data'][0]['msg_share']);
				$chech_refferal['share_text'] = $msg_share;
                $chech_refferal['referral_code'] = $referal_code;
                $chech_refferal['list_offers'] = $arr;
            
                unset($chech_refferal['status']);    
                unset($chech_refferal['data']);    
                unset($chech_refferal['row']);    
                echoResponse(200,$chech_refferal);
            }
        }else
        {
            $msg['message'] = "Invalid Token";
            echoResponse(200,$msg);
        }
    }else
    {
        $msg['message'] = "Unauthorised access";
        echoResponse(200,$msg);
    }
});

//add by zubear start
$app->get('/get_services',function()use ($app){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$order_id = $app->request()->params('order_id');
			if(isset($_GET['order_id']))
			{
				$user_id=$check['data'][0]['user_id'];
				global $db;
				//$order_services = $db->customQuery("SELECT * FROM service_order_serviceDetail Left JOIN services ON service_order_serviceDetail.service_id = services.service_id WHERE service_order_serviceDetail.order_id = '$order_id'");
				$order_services = $db->customQuery("SELECT service_order_serviceDetail.service_detail_id, service_order_serviceDetail.order_id, service_order_serviceDetail.service_id, service_order_serviceDetail.service_part_name, service_order_serviceDetail.service_category, service_order_serviceDetail.service_price, services.service_name, services.service_type, service_category.service_category_id, service_category.service_category_name FROM service_order_serviceDetail left JOIN services ON service_order_serviceDetail.service_id = services.service_id left JOIN service_category ON service_order_serviceDetail.service_category = service_category.service_category_id WHERE service_order_serviceDetail.order_id = '$order_id'");
                $arr = array();
				$total_price=0;
                if($order_services['status'] == "success")
                {  
                    foreach($order_services['data'] as $order_services1)
                    {
						$service_name='';
						if($order_services1['service_id']=="0")
							$service_name=$order_services1['service_part_name'];
						else
							$service_name=$order_services1['service_name'];
							
                        $arr[] = array(
                                    'service_id'=>$order_services1['service_detail_id'],
                                    'service_name'=>$service_name,
                                    'service_price'=>$order_services1['service_price']
                                    );
						$total_price=$total_price+$order_services1['service_price'];
                    }
                }
                $order_services['message'] = "successfully";
				//$order_services['order_id'] = $order_id;
				$order_services['list_services'] = $arr;
            	$order_services['total_price'] = $total_price;
                //print_r($order_services);exit;
                unset($order_services['status']);    
                unset($order_services['data']);    
                unset($order_services['row']);    
                echoResponse(200,$order_services);
				
			}else
			{
				$check_otp['message']= "Request Order Id Required";
				echoResponse(200,$order_services);
			}
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});
//add by zubear end 

//add by zubear check error start
$app->get('/service_center_list',function()use ($app){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$city_id = $app->request()->params('city_id');
			$lat = $app->request()->params('lat');
			$lng = $app->request()->params('lng');
			$sort_type = $app->request()->params('sort_type');
			$vehicle_type = $app->request()->params('vehicle_type');
			$page_no = $app->request()->params('page_no');
			
			$sql_order='';
			if($sort_type==2)
				$sql_order=" order by service_center.rating desc ";

			if(isset($_GET['city_id']))
			{
				$user_id=$check['data'][0]['user_id'];
				global $db;
				if($sort_type=="1")
				{
					$service_center = $db->customQuery("SELECT 
  service_center.center_name, service_center.center_id, service_center.center_code, service_center.center_address, service_center.rating, service_center.is_pickup_available, service_center.center_lat, service_center.center_lng, city.City, 
   (1.609344 * (( 3959 * acos( cos( radians($lat) ) * cos( radians( service_center.center_lat ) ) 
   * cos( radians(service_center.center_lng) - radians($lng)) + sin(radians($lat)) 
   * sin( radians(service_center.center_lat)))))) AS distance 
FROM service_center  LEFT JOIN city ON service_center.center_city = city.CityId
WHERE service_center.center_lat!='0' and service_center.center_lng!='0'
ORDER BY distance");
					
				}
				else
				{
					$service_center = $db->customQuery("SELECT * FROM service_center LEFT JOIN city ON service_center.center_city = city.CityId WHERE service_center.center_city='$city_id' and service_center.status = '0' and service_center.center_type in (0,$vehicle_type) $sql_order ");
				}
                $arr = array();
				if($service_center['status'] == "success")
                {  
					foreach($service_center['data'] as $center_data)
                    {
						$geo = file_get_contents('https://maps.googleapis.com/maps/api/distancematrix/json?origins='.$lat.','.$lng.'&destinations='.$center_data['center_lat'].','.$center_data['center_lng'].'&mode=driving');
						$geo = json_decode($geo, true);
						$distance=$geo['rows'][0]['elements'][0]['distance']['text'];
						//$distance_value=$geo['rows'][0]['elements'][0]['distance']['value'];
						
						
						//$distance_type="K";
						//$dis="";
						//$dis_cal = distance($lat,$lng, $center_data['center_lat'], $center_data['center_lng'],$distance_type);
					 	//$dis=round($dis_cal,2);
						 $arr[] = array(
							'distance'=>$distance,
							//'distance_value'=>$distance_value,
						   	'center_id'=>$center_data['center_id'],
                           	'center_name'=>$center_data['center_name'],
                           	'center_code'=>$center_data['center_code'],
						   	'center_address'=>$center_data['center_address'].", ".$center_data['City'],
                         	'rating'=>$center_data['rating'],
							'is_pickup_available'=>$center_data['is_pickup_available'],
						 	"center_lat"=>$center_data['center_lat'],
						 	"center_lng"=>$center_data['center_lng']
						 );
						 
                        
					}
                }
                $service_center['message'] = "successfully";
				//$order_services['order_id'] = $order_id;
				
				$service_center['list_center'] = $arr;
            	
                unset($service_center['status']);    
                unset($service_center['data']);    
                unset($service_center['row']);    
                echoResponse(200,$service_center);
				
			}else
			{
				$check_otp['message']= "Request City Id Required";
				echoResponse(200,$order_services);
			}
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});
//add by zubear check error end

//add by zubear make order payment start
$app->post('/make_payment',function() use ($app){ 

	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$json1 = file_get_contents('php://input');
			if(!empty($json1))
			{
				$data = json_decode($json1);
				//print_r($data);exit;
				global $db;
				if(ctype_digit($check['data'][0]['user_id']))
				{
					$user_id=$check['data'][0]['user_id'];
					$order_id=$data->order_id;
					$online_amount=$data->amount_to_pay+$data->wallet_amount;
					if($data->wallet_amount<0)
						$wallet_amount=abs($data->wallet_amount);
					else
						$wallet_amount=$data->wallet_amount;
					$final_wallet_amount=$check['data'][0]['wallet']-$wallet_amount;
		   
					//get center_id from order start
					$center_id='';
					$service_center = $db->customQuery("SELECT center_id FROM service_order WHERE order_id='$order_id'");
					if($service_center['status'] == "success")
					{
						$center_id=$service_center['data'][0]['center_id'];
					}
					//get center_id from order end
					$data_insert = array(
						'user_id'=>$check['data'][0]['user_id'],
						'order_id'=>$data->order_id,
						'transaction_amount'=>$online_amount,
						'wallet_amount'=>$wallet_amount,
						'online_amount'=>$data->amount_to_pay,
						'final_wallet_amount'=>$final_wallet_amount,
						'payment_method'=>1,
						'transaction_type'=>1,
						'user_type'=>0,
						'user_type_id'=>$check['data'][0]['user_id'],
						'transaction_date_time'=>date("Y-m-d H:i:s"),
						'payment_gateway_id'=>$data->pg_id,
						'payment_gateway_trasaction_id'=>$data->payment_id,
						'transaction_status'=>$data->status
						);
					$insert_payment = $db->insert("service_order_transaction",$data_insert,array());
					if($insert_payment["status"] == "success")
					{
						$final_amount=0;
						$order_payment_status=0;
						$paid_amount=0;
						
						$wallet_upd = $db->update("user",array("wallet"=>$final_wallet_amount),array('user_id'=>$user_id),array());
						
						//add data in user_wallet_transaction table start
						if($wallet_amount>0)
						{
							$data_wallet_tra = array(
							'user_id'=>$check['data'][0]['user_id'],
							'order_id'=>$data->order_id,
							'amount'=>$wallet_amount,
							'final_wallet'=>$final_wallet_amount,
							'transaction_type'=>1,
							'transaction_date_time'=>date("Y-m-d H:i:s"),
							'payment_gateway_id'=>$data->pg_id,
							'payment_gateway_trasaction_id'=>$data->payment_id,
							'transaction_status'=>$data->status
							);
							$insert_wallet = $db->insert("user_wallet_transaction",$data_wallet_tra,array());
						}
						//add data in user_wallet_transaction table start
						
						$order_paid_c= $db->customQuery("SELECT is_final_amount,final_amount FROM service_order where order_id='$order_id'");
						if($order_paid_c['status']=="success")
						{
							$is_final_amount=$order_paid_c['data'][0]['is_final_amount'];
							$final_amount=$order_paid_c['data'][0]['final_amount'];
						}
						
						
						
						$transaction_query = $db->customQuery("SELECT transaction_amount FROM `service_order_transaction` WHERE `user_id`='$user_id' and order_id='$order_id'");
						if($transaction_query["status"] == "success")
						{
							foreach ($transaction_query['data'] as $key) 
							{
								$paid_amount=$paid_amount+$key['transaction_amount'];
							}
						}
							
						if($is_final_amount==1)
						{
							$remain_amount=$final_amount-$paid_amount;
							if($remain_amount<=0)
							{
								$order_payment_status=1;
							}
							else if($remain_amount<$final_amount and $remain_amount!=$final_amount)
							{
								$order_payment_status=2;
							}
						}
						else
						{
							$remain_amount=$final_amount-$paid_amount;
							if($paid_amount>0)
							{
								$order_payment_status=2;
							}
						}
						$up_order = $db->update("service_order",array("order_payment_status"=>$order_payment_status),array('order_id'=>$order_id),array());
						
						//send notification to user start
						$device_token=$check['data'][0]['device_token'];
						$msg="Rs. ".$online_amount." paid for your order No:".$order_id;
						$message= array('title'=>'Motorbabu','status'=>'order_book','message'=>$msg,'order_id'=>$order_id);
						AndroidNotification($device_token,$message);
						$notif_insert = $db->insert("app_notification",array('user_id'=>$user_id,'title'=>'Motorbabu','status'=>'order_book','create_at'=>militime,'message'=>$msg,'order_id'=>$order_id),array());
						//send notification to user start
						
						//send notification to center start
						$center_user = $db->customQuery("SELECT device_token FROM `service_center_user` WHERE `center_id`='$center_id'");
						if($center_user["status"] == "success")
						{
							foreach ($center_user['data'] as $key_z) 
							{
								$device_token=$key_z['device_token'];
								$msg="Rs. ".$online_amount." paid for your order No:".$order_id;
								$message= array('title'=>'Motorbabu','status'=>'order_book','message'=>$msg,'order_id'=>$order_id);
								AndroidNotification($device_token,$message);
							}
							$notif_insert = $db->insert("app_notification",array('center_id'=>$center_id,'title'=>'Motorbabu','status'=>'order_book','create_at'=>militime,'message'=>$msg,'order_id'=>$order_id),array());
						}
						//send notification to center start
						
						$insert_payment['message'] = "successfully";
						//$order_services['order_id'] = $order_id;
						
						unset($insert_payment['status']);    
						unset($insert_payment['data']);    
						unset($insert_payment['row']);    
						echoResponse(200,$insert_payment);
					}
				}
				else
				{
					$check_otp['message']= "Request parameter not valid";
					echoResponse(200,$check_otp);
				}
			}else
				{
					$check_otp['message']= "No Request parameter";
					echoResponse(200,$check_otp);
				}
			
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});
//add by zubear make order payment end


//add by zubear add_amount_wallet start
$app->post('/add_amount_wallet',function() use ($app){ 

	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$json1 = file_get_contents('php://input');
			if(!empty($json1))
			{
				$data = json_decode($json1);
				//print_r($data);exit;
				global $db;
				if(ctype_digit($check['data'][0]['user_id']))
				{
					$user_id=$check['data'][0]['user_id'];
					$amount=$data->amount;
					$final_wallet_amount=$check['data'][0]['wallet']+$amount;
		   

					$data_insert = array(
							'user_id'=>$check['data'][0]['user_id'],
							'transaction_amount'=>$amount,
							'online_amount'=>$amount,
							'final_wallet_amount'=>$final_wallet_amount,
							'payment_method'=>1,
							'transaction_type'=>2,
							'user_type'=>0,
							'user_type_id'=>$check['data'][0]['user_id'],
							'transaction_date_time'=>date("Y-m-d H:i:s"),
							'payment_gateway_id'=>$data->pg_id,
							'payment_gateway_trasaction_id'=>$data->payment_id,
							'transaction_status'=>$data->status
							/*'user_id'=>$check['data'][0]['user_id'],
							'amount'=>$amount,
							'final_wallet'=>$final_wallet_amount,
							'transaction_type'=>2,
							'transaction_date_time'=>date("Y-m-d H:i:s"),
							'payment_gateway_id'=>$data->pg_id,
							'payment_gateway_trasaction_id'=>$data->payment_id,
							'transaction_status'=>$data->status*/
					);
					$insert_payment = $db->insert("service_order_transaction",$data_insert,array());
					if($insert_payment["status"] == "success")
					{
						$wallet_upd = $db->update("user",array("wallet"=>$final_wallet_amount),array('user_id'=>$user_id),array());
						
						//send notification to user start
						$device_token=$check['data'][0]['device_token'];
						$msg="Amount added in wallet successfully! Your transaction id is :".$data->payment_id;
						$message= array('title'=>'Motorbabu','status'=>'order_book','message'=>$msg,'payment_gateway_trasaction_id'=>$data->payment_id);
						AndroidNotification($device_token,$message);
						$notif_insert = $db->insert("app_notification",array('user_id'=>$user_id,'title'=>'Motorbabu','status'=>'order_book','create_at'=>militime,'message'=>$msg),array());
						//send notification to user start
						
						$insert_payment['message'] = "successfully";
						//$order_services['order_id'] = $order_id;
						
						unset($insert_payment['status']);    
						unset($insert_payment['data']);    
						unset($insert_payment['row']);    
						echoResponse(200,$insert_payment);
					}
				}
				else
				{
					$check_otp['message']= "Request parameter not valid";
					echoResponse(200,$check_otp);
				}
			}else
				{
					$check_otp['message']= "No Request parameter";
					echoResponse(200,$check_otp);
				}
			
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});
//add by zubear add_amount_wallet end

//add by zubear transaction_list start
$app->get('/transaction_list',function()use ($app){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			global $db;
			$user_id=$check['data'][0]['user_id'];
			$transaction = $db->customQuery("SELECT * FROM service_order_transaction LEFT JOIN payment_gateway ON service_order_transaction.payment_gateway_id = payment_gateway.payment_gateway_id WHERE service_order_transaction.user_id='$user_id' order by service_order_transaction_id desc");
			if($transaction['status'] == "success")
            {
					foreach($transaction['data'] as $transactionr_data)
                    {
						if($transactionr_data['payment_method']==0)
						{
							$payment_method="Online";
							$payment_gateway_name=$transactionr_data['payment_gateway_name'];
						}
						else if($transactionr_data['payment_method']==1)
						{
							$payment_method="By Cash";
							$payment_gateway_name="Cash";
						}
						
						if($transactionr_data['transaction_type']==1)
							$transaction_type="Credit";
						else if($transactionr_data['transaction_type']==2)
							$transaction_type="Debit";
						
						if($transactionr_data['transaction_status']==1)
							$transaction_status="Success";
						else if($transactionr_data['transaction_status']==0)
							$transaction_status="Fail";
						else if($transactionr_data['transaction_status']==2)
							$transaction_status="Decline";
						
						$user_type_id=$transactionr_data['user_type_id'];
						$user_name="";
						$by="";
							
						if($transactionr_data['user_type']==0)
						{
							$user_d = $db->customQuery("SELECT name FROM user WHERE user_id='$user_type_id'");
							if($user_d['status'] == "success")
            				{
								$user_name=$user_d['data'][0]['name'];
							}
							$by="By User";
						}
						else if($transactionr_data['user_type']==1)
						{
							$user_d = $db->customQuery("SELECT name,center_id FROM service_center_user WHERE center_user_id='$user_type_id'");
							$center_id=$user_d['data'][0]['center_id'];
							$center_data_user=$user_d['data'][0]['name'];
							
							$center_d = $db->customQuery("SELECT center_name FROM service_center WHERE center_id='$center_id'");
							if($center_d['status'] == "success")
            				{
								$user_name=$center_data_user."(".$center_d['data'][0]['center_name'].")";
							}
							$by="By Service Center";
						}
						else if($transactionr_data['user_type']==2)
						{
							$user_d = $db->customQuery("SELECT username FROM MB_admin WHERE id='$user_type_id'");
							if($user_d['status'] == "success")
            				{
								$user_name=$user_d['data'][0]['username'];
							}
							$by="By MotorBabu";
						}
						
						
						$arr[] = array(
							'order_id'=>$transactionr_data['order_id'],
							'transaction_amount'=>$transactionr_data['transaction_amount'],
						   	'payment_method'=>$payment_method,
                           	'transaction_type'=>$transaction_type,
                           	'payment_by'=>$by,
						   	'payment_name'=>$user_name,
                         	'transaction_date_time'=>date("d, M-Y, h:i A", strtotime($transactionr_data['transaction_date_time'])),
							'payment_gateway_name'=>$payment_gateway_name,
						 	"trasaction_id"=>$transactionr_data['payment_gateway_trasaction_id'],
						 	"transaction_status"=>$transaction_status
						 );
						 
                        
					}
					$transaction['message'] = "successfully";
					unset($transaction['status']);
					//unset($version_query['data']);
					unset($transaction['data']);
					$transaction['running']=$arr;
					echoResponse(200,$transaction);
            }
			else
			{
					$transaction['message']= "No transaction history!!";
					echoResponse(200,$transaction);
			}
			
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});
//add by zubear check transaction_list end


//add by zubear FAQ list start
$app->get('/faq_list',function()use ($app){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			global $db;
			$faq = $db->customQuery("SELECT * FROM MB_frequently_asked_questions WHERE frequently_asked_questions_status='1' order by frequently_asked_questions_order");
			if($faq['status'] == "success")
            {
					foreach($faq['data'] as $faq_data)
                    {
						$arr[] = array(
							'questions'=>$faq_data['frequently_asked_questions'],
							'answer'=>$faq_data['frequently_asked_questions_answer']
						 );
					}
					$faq['message'] = "successfully";
					unset($faq['status']);
					//unset($version_query['data']);
					unset($faq['data']);
					$faq['running']=$arr;
					echoResponse(200,$faq);
            }
			else
			{
					$transaction['message']= "No FAQ!!";
					echoResponse(200,$transaction);
			}
			
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});
//add by zubear check FAQ list end

//add by zubear check error start
/*$app->get('/check_notification',function()use ($app){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$id = $app->request()->params('id');
			if(isset($_GET['id']))
			{
				$user_id=$check['data'][0]['user_id'];
				global $db;
				if(ctype_digit($user_id))
				{
					$version_query = $db->select("static_content","*",array('content_id'=>$id));
					if($version_query["status"] == "success")
					{
						$version_query['message'] = "successfully";
						$version_query['data'] = $version_query['data'][0]['description'];
						unset($version_query['status']);
						//unset($version_query['data']);
						echoResponse(200,$version_query);
						
					}else
					{
						$version_query['message']= "Request parameter not valid";
						echoResponse(200,$version_query);
					}
				}
				else
				{
					$check_otp['message']= "Request parameter not valid";
					echoResponse(200,$check_otp);
				}
			}else
			{
				$check_otp['message']= "Request Id Required for static content";
				echoResponse(200,$check_otp);
			}
		}else
		{
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});*/
//add by zubear check error end 



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