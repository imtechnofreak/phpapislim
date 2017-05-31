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
$base_url = "https://mbuapp2017.motorbabu.net:8443"; 
$image_url = "http://dashboard.motorbabu.net/"; 
$dateTime = date("Y-m-d H:i:s", time()); 
$militime=round(microtime(true) * 1000);
define('dateTime', $dateTime);
define('base_url', $base_url);
define('image_url', $image_url);
define('militime', $militime);

$app->get('/login',function() use ($app){
	$mobile = $app->request()->params('mobile');
    $password = $app->request()->params('password');
	$device_token = $app->request()->params('device_token');

	if(!empty($mobile) && !empty($password))
	{		
		global $db;
		$query_login = $db->customQuery("SELECT service_center_user.*,service_center.center_name,service_center.center_address,service_center.center_code,service_center.center_mobile,service_center.center_phone FROM service_center_user INNER JOIN service_center ON service_center.center_id=service_center_user.center_id WHERE service_center_user.mobile='$mobile' AND service_center_user.password='".md5($password)."' ");
		if($query_login["status"] == "success")
		{	
			$token = bin2hex(openssl_random_pseudo_bytes(16));
		    $token = $token.militime;

			$user_id=$query_login['data'][0]['center_user_id'];
			$center_id=$query_login['data'][0]['center_id'];

			$update_order_status = $db->update("service_center_user",array("device_token"=>$device_token,"token"=>$token),array("center_user_id"=>$user_id),array());
			$new_order=0;
			$sel_order = $db->customQuery("SELECT count(*) AS count FROM service_order WHERE center_id='$center_id'");
			if($sel_order["status"] == "success")
			{
				$new_order=$sel_order["data"][0]['count'];
			}
			$a=array(
				 "user_id"=>$query_login['data'][0]['center_user_id'],
				 "email"=>$query_login['data'][0]['email'],
				 "name"=>$query_login['data'][0]['name'],
				 "mobile"=>$query_login['data'][0]['mobile'],
				 "center_name"=>$query_login['data'][0]['center_name'],
				 "center_address"=>$query_login['data'][0]['center_address'],
				 "center_code"=>$query_login['data'][0]['center_code'],
				 "center_contact"=>$query_login['data'][0]['center_mobile'],
				 "new_order"=>$new_order,
				 "is_online"=>1,
				 "token"=>$token
				);
			$query_login['data']=$a;
			$query_login['message'] ="Login successfully";
			unset($query_login['status']);
			unset($query_login['row']);
			echoResponse(200,$query_login);
		}
		else
		{ 
			$query_login['message'] ="Invalid credential";
			unset($query_login['status']);
			unset($query_login['data']);
			echoResponse(200,$query_login);
		}
		
	}
	else
	{
		$check_otp['message']= "Empty request parameter";
		echoResponse(200,$check_otp);
	}
});

$app->get('/PullToAssignBooking',function() use ($app){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$center_id=$check['data'][0]['center_id'];
			$status=$app->request()->params('status');
			$create_at=$app->request()->params('create_at');
			global $db;


			$status_arr =explode(",",$status);
			$ststs = "";
			if(!empty($status_arr))
			{
				$ststs.= 'AND (';
				$i = 1;	
				foreach ($status_arr as $key) 
				{
					if($i == 1)
					{
						$ststs.=" service_order.status ='".$key."'";
					}
					else{
						$ststs.=" OR service_order.status ='".$key."'";
					}
					$i++; 
					/*$is_pickup_check=0;
					if($key==1)
					{
						$is_pickup_check=1;
					}*/
				}
				
				$ststs.= ')';
				if($status=="1")
				{
					$ststs.= "AND service_order.is_pickup='1'";
				}else if($status=="6")
				{
					$ststs.= "AND service_order.is_pickup='1'";
				}
			}
			
			if($create_at=="0")
			{
				$create_at11="";
			}else
			{
				$create_at11="AND service_order.create_at > '$create_at'";
			}


				$vehicle_query = $db->customQuery("SELECT service_order.*,model_variant.variant_name,model.model_name,model.model_pic,make.make_name,user.phone_no,user.name,user_vehicle.vehicle_reg,user_address.street,user_address.location,user_address.zip_code,user_address.city,user_address.lat,user_address.lng,a2.street as street1,a2.location as location1,a2.zip_code as zip_code1,a2.city as city1,a2.lat as lat1,a2.lng as lng1 FROM service_order LEFT JOIN user_vehicle ON service_order.vehicle_id=user_vehicle.vehicle_id LEFT JOIN model_variant ON user_vehicle.variant_id=model_variant.variant_id LEFT JOIN model ON model_variant.model_id=model.model_id LEFT JOIN make ON model.make_id=make.make_id LEFT JOIN user ON service_order.user_id=user.user_id LEFT JOIN user_address ON service_order.pickup_address_id=user_address.address_id LEFT JOIN user_address as a2 ON service_order.drop_address_id=a2.address_id  WHERE service_order.center_id='$center_id' ".$ststs." ".$create_at11." ORDER BY service_order.create_at DESC LIMIT 10");
				if($vehicle_query["status"] == "success")
				{
					foreach ($vehicle_query['data'] AS  $key) 
					{
						if($key['model_pic'])
						{
							$vehicle_image=base_url.$key['model_pic'];
						}else
						{
							$vehicle_image=base_url."images/model/car.jpg";
						}
						
						$aa[] =array(
							"order_id"=>$key['order_id'],
							"order_type"=>$key['order_type'],
							"order_date"=>$key['order_date'],
							"order_time"=>$key['order_time'],
							"name"=>$key['name'],
							"phone_no"=>$key['phone_no'],
							"vehicle_reg"=>$key['vehicle_reg'],
							"status"=>$key['status'],
							"vehicle_name"=>$key['make_name']." ".$key['model_name']." ".$key['variant_name'],
							"create_at"=>$key['create_at'],
							"pickup_address"=>$key['location'].", ".$key['city'],
							"drop_address"=>$key['location1'].", ".$key['city1'],
							"date"=>date("d-m-Y H:i:s", ($key['create_at'] / 1000))
							);
					}
					$vehicle_query['message'] = "successfully";
					unset($vehicle_query['status']);
					unset($vehicle_query['row']);
					$vehicle_query['data']=$aa;
					echoResponse(200,$vehicle_query);
					
				}else
				{
					$vehicle_query['message'] = "No booking order";
					unset($vehicle_query['status']);
					unset($vehicle_query['data']);
					echoResponse(200,$vehicle_query);
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

$app->get('/AssignBooking',function() use ($app){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$center_id=$check['data'][0]['center_id'];
			$status=$app->request()->params('status');
			$create_at=$app->request()->params('create_at');
			global $db;

			$status_arr =explode(",",$status);
			$ststs = "";
			if(!empty($status_arr))
			{
				$ststs.= 'AND (';
				$i = 1;	
				foreach ($status_arr as $key) 
				{
					if($i == 1)
					{
						$ststs.=" service_order.status ='".$key."'";
					}
					else{
						$ststs.=" OR service_order.status ='".$key."'";
					}
					$i++; 
					/*$is_pickup_check=0;
					if($key==1 || $key=6)
					{
						$is_pickup_check=1;
					}*/
				}
				
				$ststs.= ')';
				if($status=="1")
				{
					$ststs.= "AND service_order.is_pickup='1'";
				}else if($status=="6")
				{
					$ststs.= "AND service_order.is_pickup='1'";
				}
			}

			if($create_at=="0")
			{
				$create_at11="";
			}else
			{
				$create_at11="AND service_order.create_at < '$create_at'";
			}

				$vehicle_query = $db->customQuery("SELECT service_order.*,model_variant.variant_name,model.model_name,model.model_pic,make.make_name,user.phone_no,user.name,user_vehicle.vehicle_reg,user_address.street,user_address.location,user_address.zip_code,user_address.city,user_address.lat,user_address.lng,a2.street as street1,a2.location as location1,a2.zip_code as zip_code1,a2.city as city1,a2.lat as lat1,a2.lng as lng1 FROM service_order LEFT JOIN user_vehicle ON service_order.vehicle_id=user_vehicle.vehicle_id LEFT JOIN model_variant ON user_vehicle.variant_id=model_variant.variant_id LEFT JOIN model ON model_variant.model_id=model.model_id LEFT JOIN make ON model.make_id=make.make_id LEFT JOIN user ON service_order.user_id=user.user_id LEFT JOIN user_address ON service_order.pickup_address_id=user_address.address_id LEFT JOIN user_address as a2 ON service_order.drop_address_id=a2.address_id  WHERE service_order.center_id='$center_id' ".$ststs." ".$create_at11." ORDER BY service_order.create_at DESC LIMIT 10");
				if($vehicle_query["status"] == "success")
				{
					foreach ($vehicle_query['data'] AS  $key) 
					{
						if($key['model_pic'])
						{
							$vehicle_image=base_url.$key['model_pic'];
						}else
						{
							$vehicle_image=base_url."images/model/car.jpg";
						}
						
						$aa[] =array(
							"order_id"=>$key['order_id'],
							"order_type"=>$key['order_type'],
							"order_date"=>$key['order_date'],
							"order_time"=>$key['order_time'],
							"name"=>$key['name'],
							"phone_no"=>$key['phone_no'],
							"vehicle_reg"=>$key['vehicle_reg'],
							"status"=>$key['status'],
							"vehicle_name"=>$key['make_name']." ".$key['model_name']." ".$key['variant_name'],
							"create_at"=>$key['create_at'],
							"pickup_address"=>$key['location']." ".$key['city'],
							"drop_address"=>$key['location1']." ".$key['city1'],
							"date"=>date("d-m-Y H:i:s", ($key['create_at'] / 1000))
							);
					}
					$vehicle_query['message'] = "successfully";
					unset($vehicle_query['status']);
					unset($vehicle_query['row']);
					$vehicle_query['data']=$aa;
					echoResponse(200,$vehicle_query);
					
				}else
				{
					$vehicle_query['message'] = "No booking order";
					unset($vehicle_query['status']);
					unset($vehicle_query['data']);
					echoResponse(200,$vehicle_query);
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

$app->get('/BookingDetail/:id',function($order_id) use ($app){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$center_id=$check['data'][0]['center_id'];
			$status=$app->request()->params('status');
			$create_at=$app->request()->params('create_at');
			global $db;
				$vehicle_query = $db->customQuery("SELECT service_order.*,user.name,user.phone_no,user.gender,user.email,model.model_name,model.model_pic,make.make_name,model_variant.variant_name,user_address.street,user_address.location,user_address.zip_code,user_address.city,a2.street AS drop_street,a2.location AS drop_location,a2.zip_code AS drop_zip_code,a2.city AS drop_city FROM service_order LEFT JOIN user_vehicle ON service_order.vehicle_id=user_vehicle.vehicle_id LEFT JOIN model_variant ON user_vehicle.variant_id=model_variant.variant_id LEFT JOIN model ON model_variant.model_id=model.model_id LEFT JOIN make ON model.make_id=make.make_id LEFT JOIN user ON service_order.user_id=user.user_id LEFT JOIN user_address ON service_order.pickup_address_id=user_address.address_id LEFT JOIN user_address as a2 ON service_order.drop_address_id=a2.address_id  WHERE service_order.order_id='$order_id' ");
				if($vehicle_query["status"] == "success")
				{
					foreach ($vehicle_query['data'] AS  $key) 
					{
						$order_id= $key['order_id'];
						$v4 = "";
						$sel_ser= $db->customQuery("SELECT service_order_serviceDetail.service_id,services.service_name FROM service_order_serviceDetail LEFT JOIN services ON service_order_serviceDetail.service_id=services.service_id WHERE service_order_serviceDetail.order_id='$order_id'");
						if($sel_ser["status"] == "success")
						{
							foreach ($sel_ser["data"] as $serv) 
							{
								$v4[] = array($serv['service_name']);
							}
						}

						if($key['model_pic'])
						{
							$model_pic=base_url.$key['model_pic'];
						}else
						{
							$model_pic="";
						}
						$is_feedback=0;
						if($key['user_rating']!=0)
						{
							$is_feedback = 1;
						}

						$aa =array(
							"order_id"=>$key['order_id'],
							"order_type"=>$key['order_type'],
							"order_date"=>$key['order_date'],
							"order_time"=>$key['order_time'],
							"km"=>$key['km'],
							"estimation_amount"=>$key['amount'],
							"remaining_time"=>$key['service_time'],
							"name"=>$key['name'],
							"mobile"=>$key['phone_no'],
							"email"=>$key['email'],
							"gender"=>$key['gender'],
							"is_feedback"=>$is_feedback,
							"services"=>$v4,
							"status"=>$key['status'],
							"is_pickup"=>$key['is_pickup'],
							"create_at"=>$key['create_at'],
							"user_rating"=>$key['user_rating'],
							"user_comment"=>$key['user_comment'],
							"center_rating"=>$key['center_rating'],
							"center_comment"=>$key['center_comment'],
							"vehicle"=>$key['make_name']." ".$key['model_name']." ".$key['variant_name'],
							"vehicle_image"=>$model_pic,
							"pickup_address"=>$key['street']." ".$key['location']." ".$key['zip_code']." ".$key['city'],
							"drop_address"=>$key['drop_street']." ".$key['drop_location']." ".$key['drop_zip_code']." ".$key['drop_city'],
							"date"=>date("d-m-Y H:i:s", ($key['create_at'] / 1000))
							);
					}
					$vehicle_query['message'] = "successfully";
					unset($vehicle_query['status']);
					unset($vehicle_query['row']);
					$vehicle_query['data']=$aa;
					echoResponse(200,$vehicle_query);
					
				}else
				{
					$vehicle_query['message'] = "No booking order";
					unset($vehicle_query['status']);
					unset($vehicle_query['data']);
					echoResponse(200,$vehicle_query);
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

$app->put('/UpdateStatus',function() use ($app)
{
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$center_id=$check['data'][0]['center_id'];
			$order_id=$app->request()->params('order_id');
			$status=$app->request()->params('status');

			if(!empty($center_id) && !empty($order_id) && !empty($status))
			{
				global $db;
				$check_status= $db->customQuery("SELECT * FROM `service_order_logs` where order_id='$order_id' AND center_id='$center_id' AND status='$status'");
				if($check_status['status']=="success")
				{
					$check_status['message'] = "successfully";
					unset($check_status['status']);
					unset($check_status['data']);
					unset($check_status['row']);
					echoResponse(200,$check_status);

				}else
				{
					$update_status = $db->insert("service_order_logs",array("center_id"=>$center_id,"order_id"=>$order_id,"status"=>$status,"create_at"=>militime),array());
					if($update_status['status']=="success")
					{
						$update_order_status = $db->update("service_order",array("status"=>$status,"update_at"=>militime),array("order_id"=>$order_id),array());
						$update_status['message'] = "successfully";
						unset($update_status['status']);
						unset($update_status['data']);
						echoResponse(200,$update_status);
					}
				}
			}else
			{
				$check_otp['message']= "Empty request parameter";
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

$app->put('/UpdateFeedback',function() use ($app)
{
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$center_id=$check['data'][0]['center_id'];
			$order_id=$app->request()->params('order_id');
			$rating=$app->request()->params('rating');
			$comment=$app->request()->params('comment');

			if(!empty($center_id) && !empty($order_id) && !empty($rating) )
			{
				global $db;
				$check_status= $db->customQuery("SELECT * FROM `service_order` where order_id='$order_id' AND center_id='$center_id' ");
				if($check_status['status']=="success")
				{
					$update_order_status = $db->update("service_order",array("user_rating"=>$rating,"user_comment"=>$comment,"update_at"=>militime),array("order_id"=>$order_id),array());
					$update_status['message'] = "successfully";
					unset($update_status['status']);
					unset($update_status['data']);
					echoResponse(200,$update_status);

				}else
				{	
					$check_status['message']= "Something Wrong!!";
					echoResponse(200,$check_status);
				}
			}else
			{
				$check_otp['message']= "Empty request parameter";
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

$app->put('/UpdateKMATime',function() use ($app)
{
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$center_id=$check['data'][0]['center_id'];
			$order_id=$app->request()->params('order_id');
			$km=$app->request()->params('km');
			$amount=$app->request()->params('estimated_amount');
			$service_time=$app->request()->params('remaining_time');

			if(!empty($center_id) && !empty($order_id) )
			{
				global $db;
				$check_status= $db->customQuery("SELECT * FROM `service_order` where order_id='$order_id' AND center_id='$center_id' ");
				if($check_status['status']=="success")
				{
					$update_order_status = $db->update("service_order",array("amount"=>$amount,"service_time"=>$service_time,"km"=>$km,"update_at"=>militime),array("order_id"=>$order_id),array());
					$update_status['message'] = "successfully";
					unset($update_status['status']);
					unset($update_status['data']);
					echoResponse(200,$update_status);

				}else
				{	
					$check_status['message']= "Something Wrong!!";
					echoResponse(200,$check_status);
				}
			}else
			{
				$check_otp['message']= "Empty request parameter";
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

$app->get('/notification',function() use($app){

	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$center_id=$check['data'][0]['center_id'];
			if(!empty($center_id))
			{
				$create_at= $app->request->params('create_at');
				global $db;
				if($create_at==0)
				{
					$create_at1="";
				}else
				{
					$create_at1="AND `create_at` > '$create_at'";
				}
				$notifi_query = $db->customQuery("SELECT * FROM `app_notification` WHERE `center_id`='$center_id' ".$create_at1." ORDER BY `notif_id` DESC LIMIT 10");
				if($notifi_query["status"] == "success")
				{
					foreach ($notifi_query['data'] as $key) 
					{
					   $my_date = $key['create_at'];
                       $seconds = $my_date / 1000;
                       $date = date("d-m-Y h:i:s", $seconds); 
						$arr[]=array(
								"notif_id"=>$key['notif_id'],
								"order_id"=>$key['order_id'],
								"title"=>$key['title'],
								"status"=>$key['status'],
								"message"=>$key['message'],
								"create_at"=>$key['create_at'],
								"date"=>$date
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

$app->get('/GetStatusCount',function()use ($app){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			global $db;
			$center_id=$check['data'][0]['center_id'];
		
				$cel_count = $db->customQuery("SELECT count('order_id') AS count FROM service_order WHERE `center_id`='$center_id' AND `status`='0'");
				if($cel_count["status"] == "success")
				{
					$arr1=array('status_id'=>"new",'count'=>$cel_count['data'][0]['count'],);
				}

				$cel_count2 = $db->customQuery("SELECT count('order_id') AS count FROM service_order WHERE `center_id`='$center_id' AND `status`='1' AND `is_pickup`='1'");
				if($cel_count["status"] == "success")
				{
					$arr2=array('status_id'=>"pending_pickup",'count'=>$cel_count2['data'][0]['count'],);
				}

				$cel_count3 = $db->customQuery("SELECT count('order_id') AS count FROM service_order WHERE `center_id`='$center_id' AND `status`='6' AND `is_pickup`='1'");
				if($cel_count["status"] == "success")
				{
					$arr3=array('status_id'=>"pending_drop",'count'=>$cel_count3['data'][0]['count'],);
				}

				$cel_count4 = $db->customQuery("SELECT count('order_id') AS count FROM service_order WHERE `center_id`='$center_id' AND (`status`='1' OR `status`='3' OR `status`='6')");
				if($cel_count4["status"] == "success")
				{
					$arr4=array('status_id'=>"running",'count'=>$cel_count4['data'][0]['count'],);
				}
				
				$arr=array($arr1,$arr2,$arr3,$arr4);

			if($arr==null)
			{
				$cel_count['message'] = "No Count!!";
				unset($cel_count['data']);
				
			}else
			{
				$cel_count['message'] = "successfully";
				$cel_count['data']=$arr;
			}
			unset($cel_count['status']);
			unset($cel_count['row']);
			echoResponse(200,$cel_count);
		
			
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
			$version_query['apk_version'] = $version_query['data'][0]['service_app_version'];
			$version_query['apk_url'] = $version_query['data'][0]['apk_url'];
			unset($version_query['status']);
			unset($version_query['data']);
			echoResponse(200,$version_query);
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