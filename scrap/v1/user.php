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
$base_url = "http://base3.engineerbabu.com/motorbabu"; 
$dateTime = date("Y-m-d H:i:s", time()); 
$militime=round(microtime(true) * 1000);
define('dateTime', $dateTime);
define('base_url', $base_url);
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

		$city = ucfirst($data->city); 
		if(strlen($phone_no)==10 && ctype_digit($phone_no) && ctype_digit($city))
		{
			$user_data = array( 'phone_no'=>$phone_no,
								 'city'=>$city,
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
				$insert_user = $db->insert("user",$user_data,array());
				if($insert_user["status"] == "success")
				{
					$insert_user['message'] ="Login successfully";
					sms_send($phone_no,$otp1);
					unset($insert_user['status']);
					$insert_user['first_time'] ="1";
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
					$check_profile = $db->select("userProfile","*",array('user_id'=>$user_id));
					if($check_profile["status"]=="success")
					{
						$auth_update = $db->update("userAuth",array('token'=>$token,'create_at'=>militime),array('user_id'=>$user_id),array());
						if($auth_update['status']=="success")
						{
							$ab = $db->select("userProfile","*",array('user_id'=>$user_id));
							if($ab["status"] == "success")
							{
								$update_device = $db->update("userProfile",array('device_token'=>$device_token),array('user_id'=>$user_id),array());
								$ab['message']= "Your Mobile No. has been verified successfully";
								$message= array('title'=>'Motorbabu','status'=>'register','message'=>'welcome back to motorbabu app!!');
								AndroidNotification($device_token,$message);
							//	$notif_insert = $db->insert("notification",array('user_id'=>$user_id,'title'=>'Motorbabu','status'=>'register','create_at'=>militime,'message'=>'welcome back to motorbabu app!!'),array());
								
								$review_status=0;
								$check_reviev = $db->select("review","*",array('user_id'=>$user_id));
								if($check_reviev["status"] == "success")
								{
									$review_status=1;
								}
								$ab['review_status']= $review_status;
								$ab['token']= $token;
								
								$aa= array(
				    					"user_id"=>$ab['data'][0]['user_id'],
				    					"mobile"=>$check_otp['data'][0]['phone_no'],
				    					"name"=>$ab['data'][0]['name'],
				    					"email"=>$ab['data'][0]['email'],
				    					"occupation"=>$ab['data'][0]['occupation'],
				    					"profile_pic"=>base_url."/dashboard/images/user_profile/".$ab['data'][0]['profile_pic'],
				    					"referal_code"=>$ab['data'][0]['referal_code']
				    				);

	    						$ab['data'] =  $aa;
								unset($ab['status']);
								echoResponse(200,$ab);
							} 
						}
						
					}else
					{
						
						$Waleet = $db->select("refer_earn","*",array('id'=>1));
						$register_amount = $Waleet['data'][0]['register'];

						$code = substr(randomuniqueCode(),0,8);
						$chech_new_code = $db->select("userProfile","referal_code",array('referal_code'=>$code));
						if($chech_new_code['status']=="success")
						{
							$referal_code=substr(randomuniqueCode(),0,8);
						}else
						{
							$referal_code=$code;
						}
						

						$prodfile_insert = $db->insert("userProfile",array('user_id'=>$user_id,'create_at'=>militime,'update_at'=>militime,'device_token'=>$device_token,'referal_code'=>$referal_code),array());
						if($prodfile_insert["status"]=="success")
						{	
							$auth_insert = $db->insert("userAuth",array('user_id'=>$user_id,'token'=>$token,'create_at'=>militime),array());
							if($auth_insert["status"]=="success")
							{
								$ab = $db->select("userProfile","*",array('user_id'=>$user_id));
								if($ab["status"] == "success")
								{
									
									$wallet_insert = $db->insert("userWallet",array('user_id'=>$user_id,'wallet'=>$register_amount,'update_at'=>militime),array());


									$ab['message']= "Your Mobile No. has been verified successfully";
									$message= array('title'=>'Motorbabu','status'=>'register','message'=>'welcome to motorbabu app!!');
									AndroidNotification($device_token,$message);
									$notif_insert = $db->insert("notification",array('user_id'=>$user_id,'title'=>'Motorbabu','status'=>'register','create_at'=>militime,'message'=>'welcome to motorbabu app!!'),array());
									
									$review_status=0;
									$check_reviev = $db->select("review","*",array('user_id'=>$user_id));
									if($check_reviev["status"] == "success")
									{
										$review_status=1;
									}
									$ab['review_status']= $review_status;

									$ab['token']= $token;
									$aa= array(
				    					"user_id"=>$ab['data'][0]['user_id'],
				    					"mobile"=>$check_otp['data'][0]['phone_no'],
				    					"name"=>$ab['data'][0]['name'],
				    					"email"=>$ab['data'][0]['email'],
				    					"occupation"=>$ab['data'][0]['occupation'],
				    					"profile_pic"=>base_url."/dashboard/images/user_profile/".$ab['data'][0]['profile_pic'],
				    					"referal_code"=>$ab['data'][0]['referal_code']
				    				);

	    							$ab['data'] =  $aa;
									//unset($ab['data']);
									unset($ab['status']);
									echoResponse(200,$ab);
								} 
							}
						}
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
						$update_location = $db->update("userProfile", $upd_data,array('user_id'=>$user_id),array());
						//$update_location = $db->customQuery("UPDATE userProfile SET latitude='$data->latitude',longitude='$data->longitude' WHERE user_id='$user_id'");
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
						$create_at1="AND `create_at` > '$create_at'";
					}
					$notifi_query = $db->customQuery("SELECT * FROM `notification` WHERE `user_id`='$user_id' ".$create_at1." ORDER BY `notif_id` DESC LIMIT 10");
					if($notifi_query["status"] == "success")
					{
						foreach ($notifi_query['data'] as $key) 
						{
						   $my_date = $key['create_at'];
                           $seconds = $my_date / 1000;
                           $date = date("d-m-Y h:i:s", $seconds); 
							$arr[]=array(
									"notif_id"=>$key['notif_id'],
									"title"=>$key['title'],
									"status"=>$key['status'],
									"message"=>$key['message'],
									"image"=>$key['image'],
									"create_at"=>$date,
									"date"=>king_time(date("Y-m-d H:i:s", ($key['create_at'] / 1000)))
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
						'street'=>$data->street,
						'location'=>$data->location,
						'zip_code'=>$data->zip_code,
						'lat'=>$data->lat,
						'lng'=>$data->lng,
						'city'=>$data->city,
						'state'=>$data->state,
						'create_at'=>militime,
						'update_at'=>militime,
						);
					
						$insert_address = $db->insert("address",$insert_data,array());
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
				$vehicle_query = $db->select("address","*",array('user_id'=>$user_id));
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
					$vehicle_query = $db->select("address","*",array('user_id'=>$user_id,'address_id'=>$address_id));
					if($vehicle_query["status"] == "success")
					{
						$delete_address = $db->delete("address", array('address_id'=>$address_id));
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
					$vehicle_query = $db->select("address","*",array('user_id'=>$user_id,'address_id'=>$address_id));
					if($vehicle_query["status"] == "success")
					{
						$update_address = $db->update("address", $upd_data, array('user_id'=>$user_id,'address_id'=>$address_id),array());
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

		    $wallet_query = $db->select("userWallet","wallet",array('user_id'=>$user_id));
		    if($wallet_query["status"] == "success")
		    {
	    		$wallet_query['message'] = "successfully";
	    		unset($wallet_query['status']);
	    		$wallet_query['data'] =  $wallet_query['data'][0]['wallet'];	
		    	echoResponse(200,$wallet_query);
		    }else
		    {
		    	$wallet_query['message'] = "successfully";
	    		unset($wallet_query['status']);
	    		$wallet_query['data'] =  "50";	
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

			$chech_refferal = $db->customQuery("SELECT * FROM userProfile WHERE `user_id`= '$user_id' AND `refered_by`!=''");
			if($chech_refferal['status']=="success")
			{
				$chech_refferal['message'] = "You already used code!!";
	    		unset($chech_refferal['status']);	
	    		unset($chech_refferal['data']);	
	    		unset($chech_refferal['row']);	
		    	echoResponse(200,$chech_refferal);
			}else
			{

				$chech_code = $db->select("userProfile","*",array('referal_code'=>$refered_by));
				if($chech_code['status']=="success")
				{
					$refered_by_user_id =  $chech_code['data'][0]['user_id'];
					$get_wallet = $db->select("userWallet","wallet",array('user_id'=>$refered_by_user_id));
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
					
					$updaye_wallet = $db->update("userWallet",array('wallet'=>$new_wallet),array('user_id'=>$refered_by_user_id),array());
					if($updaye_wallet["status"] == "success")
					{
						$get_de_token = $db->select("userProfile","device_token",array('user_id'=>$refered_by_user_id));
						$device_token= $get_de_token['data'][0]['device_token'];
						$message= array('title'=>'Motorbabu','status'=>'Refer&Earn','message'=>'Thanks to refer Motorbabu app to your friend. We added '.$amount.' Rs. to your wallet!!');
						AndroidNotification($device_token,$message);
						$notif_insert = $db->insert("notification",array('user_id'=>$refered_by_user_id,'title'=>'Motorbabu','status'=>'Refer&Earn','create_at'=>militime,'message'=>'Thanks to refer Motorbabu app to your friend. We added '.$amount.' Rs. to your wallet!!'),array());
						

						$get_u_de_token = $db->select("userProfile","device_token",array('user_id'=>$refered_by_user_id));
						$u_device_token= $get_u_de_token['data'][0]['device_token'];
						$u_message= array('title'=>'Motorbabu','status'=>'Refer&Earn','message'=>'Wow you used refferal code and get '.$refer_to_amount.' Rs.');
						AndroidNotification($u_device_token,$u_message);
						$u_notif_insert = $db->insert("notification",array('user_id'=>$user_id,'title'=>'Motorbabu','status'=>'Refer&Earn','create_at'=>militime,'message'=>'Wow you used refferal code and get '.$refer_to_amount.' Rs.'),array());
										
					}

				    $wallet_query = $db->update("userProfile",array('refered_by'=>$refered_by),array('user_id'=>$user_id),array());
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

		    $profile_query = $db->select("userProfile","*",array('user_id'=>$user_id));
		    if($profile_query["status"] == "success")
		    {
	    		$profile_query['message'] = "successfully";
	    		unset($profile_query['status']);
	    		$aa= array(
	    					"user_id"=>$profile_query['data'][0]['user_id'],
	    					"name"=>$profile_query['data'][0]['name'],
	    					"email"=>$profile_query['data'][0]['email'],
	    					"profile_pic"=>base_url."/dashboard/images/user_profile/".$profile_query['data'][0]['profile_pic'],
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
	        	move_uploaded_file($_FILES["file"]["tmp_name"],"../../dashboard/images/user_profile/" .$new_image);
	        	$path = $new_image;

	        	$upd_data = array(
							'name'=>$app->request->params('name'),
							'email'=>$app->request->params('email'),
							'occupation'=>$app->request->params('occupation'),
							'profile_pic'=>$path
				);
	      	}
	      	else
	      	{
	      		$path="";
	      		$upd_data = array(
							'name'=>$app->request->params('name'),
							'email'=>$app->request->params('email'),
							'occupation'=>$app->request->params('occupation')
				);
	      	}

			
			
			$user_id=$check['data'][0]['user_id'];
			if(!empty($user_id))
			{
				global $db;
				if(ctype_digit($check['data'][0]['user_id']))
				{
					$update_address = $db->update("userProfile", $upd_data, array('user_id'=>$user_id),array());
					if($update_address["status"] == "success")
					{
						$update_address['message'] ="successfully";
						$profile_query = $db->select("userProfile","*",array('user_id'=>$user_id));
						//print_r($profile_query);
					    if($profile_query["status"] == "success")
					    {
					    	if($profile_query['data'][0]['profile_pic']!="")
					    	{
					    		$pro= base_url."/dashboard/images/user_profile/".$profile_query['data'][0]['profile_pic'];
					    	}else
					    	{
					    		$pro = "";
					    	}
				    		$aa= array(
				    					"user_id"=>$profile_query['data'][0]['user_id'],
				    					"name"=>$profile_query['data'][0]['name'],
				    					"email"=>$profile_query['data'][0]['email'],
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
						$profile_query = $db->select("userProfile","*",array('user_id'=>$user_id));
					    if($profile_query["status"] == "success")
					    {
					    	if($profile_query['data'][0]['profile_pic']!="")
					    	{
					    		$pro= base_url."/dashboard/images/user_profile/".$profile_query['data'][0]['profile_pic'];
					    	}else
					    	{
					    		$pro = "";
					    	}
				    		$aa= array(
				    					"user_id"=>$profile_query['data'][0]['user_id'],
				    					"name"=>$profile_query['data'][0]['name'],
				    					"email"=>$profile_query['data'][0]['email'],
				    					"occupation"=>$profile_query['data'][0]['occupation'],
				    					"profile_pic"=>$pro,
				    					"referal_code"=>$profile_query['data'][0]['referal_code']
				    				);
					    }
					   // exit;
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
						$insert_feedback = $db->insert("feedback",$insert_data,array());
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
				$feedback_query = $db->customQuery("SELECT feedback.*,userProfile.name,userProfile.email,userProfile.profile_pic FROM feedback INNER JOIN `userProfile` ON feedback.user_id=userProfile.user_id");
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
    $city_query = $db->select("city","*",array('CountryID'=>'113'));
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
			$find_like_cen = $db->customQuery("SELECT DISTINCT center_id FROM center_like WHERE user_id='$user_id'");
			if($find_like_cen["status"] == "success")
			{
				foreach ($find_like_cen['data'] as $value) 
				{
					$center_id=$value['center_id'];
					$find_all_station = $db->customQuery("SELECT service_center.*,city_location.area_name FROM service_center  INNER JOIN city_location ON service_center.area_id=city_location.area_id WHERE center_id='$center_id' AND status='0'");
				    if($find_all_station["status"] == "success")
				    {
			    		$sel_services= $db->customQuery("SELECT center_services.*,services.service_name FROM center_services INNER JOIN services ON center_services.service_id=services.service_id WHERE center_services.center_id='$center_id'");
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
			    		}	
			    		
			    		$avg_rate=0;
			    		$find_avg_rate = $db->customQuery("SELECT avg(rating) AS avg_rate FROM review WHERE `center_id`='".$find_all_station['data'][0]['center_id']."'");
			    		if($find_avg_rate['status']=="success")
			    		{
			    			 $avg_rate=round($find_avg_rate['data'][0]['avg_rate'],1);
			    		}
			    		

			    		$center_pictures=array();
			    		$center_image = $db->select("center_image","*",array('center_id'=>$find_all_station['data'][0]['center_id']));
			    		if($center_image['status']=="success")
			    		{
			    			foreach ($center_image['data'] as $value11) 
			    			{
			    				$center_pictures[]=array('image_id'=>$value11['image_id'],
			    					'image'=>base_url."/dashboard/".$value11['image']
			    				);
			    			}
			    			
			    		}

			    		$is_favorite=0;
			    		$chack_favorite=$db->select("center_like","*",array("center_id"=>$center_id,"user_id"=>$user_id));
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
					      'services'=> $v4,
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

$app->get('/bannerOffer',function(){
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
				$baneer_query = $db->select("banner_offer","*",array());
				if($baneer_query["status"] == "success")
				{
					foreach ($baneer_query['data'] as $key) 
					{
						$a[] = array(
								'banner_id'=>$key['banner_id'],
								'banner'=>base_url."/dashboard/images/banner_feedback/".$key['banner']
							);
					}
					$baneer_query['message'] = "successfully";
					$baneer_query['data'] = $a;
					unset($baneer_query['status']);
					unset($baneer_query['row']);
					echoResponse(200,$baneer_query);
					
				}else
				{
					$baneer_query['message'] = "No Nanner";
					unset($baneer_query['status']);
					unset($baneer_query['data']);
					unset($baneer_query['row']);
					echoResponse(200,$baneer_query);
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

$app->get('/bannerFeedback',function(){
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
				$baneer_query = $db->select("banner_feedback","*",array());
				if($baneer_query["status"] == "success")
				{
					foreach ($baneer_query['data'] as $key) 
					{
						$a[] = array(
								'banner_id'=>$key['banner_id'],
								'banner'=>base_url."/dashboard/images/banner_feedback/".$key['banner']
							);
					}
					$baneer_query['message'] = "successfully";
					$baneer_query['data'] = $a;
					unset($baneer_query['status']);
					unset($baneer_query['row']);
					echoResponse(200,$baneer_query);
					
				}else
				{
					$baneer_query['message'] = "No Nanner";
					unset($baneer_query['status']);
					unset($baneer_query['data']);
					unset($baneer_query['row']);
					echoResponse(200,$baneer_query);
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