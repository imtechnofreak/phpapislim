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
define('militime', $militime);

$app->post('/login',function() use ($app){
	$email = $app->request()->params('email');
	$password = $app->request()->params('password');
	$device_token = $app->request()->params('device_token');

	if(!empty($email) && !empty($password))
	{		
		global $db;
		$query_login = $db->select("admin","*",array("email"=>$email,"password"=>md5($password)));
		if($query_login["status"] == "success")
		{	
			$user_id=$query_login['data'][0]['id'];
			$update_order_status = $db->update("admin",array("device_token"=>$device_token),array("id"=>$user_id),array());
			$a=array(
				 "user_id"=>$query_login['data'][0]['id'],
				 "email"=>$query_login['data'][0]['email'],
				 "name"=>$query_login['data'][0]['name']
				);
			$query_login['data']=$a;
			$query_login['message'] ="Login successfully";
			unset($query_login['status']);
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

$app->post('/assignBooking',function() use ($app){

	$user_id=$app->request()->params('user_id');
	$status=$app->request()->params('status');
	$create_at=$app->request()->params('create_at');

	global $db;
	if(!empty($user_id) && ctype_digit($user_id))
	{
		if($status=="")
		{
			$stst="AND service_order.status !='0' AND service_order.status !='7'";
		}else
		{
			$stst="AND service_order.status='$status'";
		}

		if($create_at=="0")
		{
			$create_at11="";
		}else
		{
			$create_at11="AND service_order.update_at > '$create_at'";
		}

		$vehicle_query = $db->customQuery("SELECT service_order.*,model_variant.variant_name,model.model_name,model.model_pic,make.make_name,user.phone_no,userProfile.name,vehicle.vehicle_reg,address.street,address.location,address.zip_code,address.city,address.lat,address.lng,a2.street as street1,a2.location as location1,a2.zip_code as zip_code1,a2.city as city1,a2.lat as lat1,a2.lng as lng1 FROM service_order LEFT JOIN vehicle ON service_order.vehicle_id=vehicle.vehicle_id LEFT JOIN model_variant ON vehicle.variant_id=model_variant.variant_id LEFT JOIN model ON model_variant.model_id=model.model_id LEFT JOIN make ON model.make_id=make.make_id LEFT JOIN user ON service_order.user_id=user.user_id LEFT JOIN userProfile ON service_order.user_id=userProfile.user_id LEFT JOIN address ON service_order.pickup_address_id=address.address_id INNER JOIN address as a2 ON service_order.drop_address_id=a2.address_id  WHERE service_order.assign_to='$user_id' ".$stst." ".$create_at11." ORDER BY service_order.update_at DESC LIMIT 10");
		if($vehicle_query["status"] == "success")
		{
			foreach ($vehicle_query['data'] AS  $key) 
			{
				if($key['model_pic'])
				{
					$vehicle_image=base_url."/dashboard/".$key['model_pic'];
				}else
				{
					$vehicle_image=base_url."/dashboard/images/model/car.jpg";
				}
				
				$aa[] =array(
					"order_id"=>$key['order_id'],
					"mobile"=>$key['phone_no'],
					"name"=>$key['name'],
					"status"=>$key['status'],
					"vehicle_reg"=>$key['vehicle_reg'],
					"vehicle_image"=>$vehicle_image,
					"pickup_address_id"=>$key['street']." ".$key['location']." ".$key['zip_code']." ".$key['city'],
					"pick_lat"=>$key['lat'],
					"pick_lng"=>$key['lng'],
					"drop_address_id"=>$key['street1']." ".$key['location1']." ".$key['zip_code1']." ".$key['city1'],
					"drop_lat"=>$key['lat1'],
					"drop_lng"=>$key['lng1'],
					"vehicle"=>$key['make_name']." ".$key['model_name']." ".$key['variant_name'],
					"order_date"=>$key['order_date'],
					"create_at"=>$key['update_at']
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
	}
	else
	{
		$check_otp['message']= "Empty request parameter";
		echoResponse(200,$check_otp);
	}
});
$app->post('/PullToAssignBooking',function() use ($app){

	$user_id=$app->request()->params('user_id');
	$status=$app->request()->params('status');
	$create_at=$app->request()->params('create_at');

	global $db;
	if(!empty($user_id) && ctype_digit($user_id))
	{
		if($status=="")
		{
			$stst="AND service_order.status !='0' AND service_order.status !='7'";
		}else
		{
			$stst="AND service_order.status='$status'";
		}

		if($create_at=="0")
		{
			$create_at11="";
		}else
		{
			$create_at11="AND service_order.update_at < '$create_at'";
		}

		$vehicle_query = $db->customQuery("SELECT service_order.*,model_variant.variant_name,model.model_name,model.model_pic,make.make_name,user.phone_no,userProfile.name,vehicle.vehicle_reg,address.street,address.location,address.zip_code,address.city,address.lat,address.lng,a2.street as street1,a2.location as location1,a2.zip_code as zip_code1,a2.city as city1,a2.lat as lat1,a2.lng as lng1 FROM service_order LEFT JOIN vehicle ON service_order.vehicle_id=vehicle.vehicle_id LEFT JOIN model_variant ON vehicle.variant_id=model_variant.variant_id LEFT JOIN model ON model_variant.model_id=model.model_id LEFT JOIN make ON model.make_id=make.make_id LEFT JOIN user ON service_order.user_id=user.user_id LEFT JOIN userProfile ON service_order.user_id=userProfile.user_id LEFT JOIN address ON service_order.pickup_address_id=address.address_id INNER JOIN address as a2 ON service_order.drop_address_id=a2.address_id  WHERE service_order.assign_to='$user_id' ".$stst." ".$create_at11." ORDER BY service_order.update_at DESC LIMIT 10");
		if($vehicle_query["status"] == "success")
		{
			foreach ($vehicle_query['data'] AS  $key) 
			{
				if($key['model_pic'])
				{
					$vehicle_image=base_url."/dashboard/".$key['model_pic'];
				}else
				{
					$vehicle_image=base_url."/dashboard/images/model/car.jpg";
				}
				
				$aa[] =array(
					"order_id"=>$key['order_id'],
					"mobile"=>$key['phone_no'],
					"name"=>$key['name'],
					"status"=>$key['status'],
					"vehicle_reg"=>$key['vehicle_reg'],
					"vehicle_image"=>$vehicle_image,
					"pickup_address_id"=>$key['street']." ".$key['location']." ".$key['zip_code']." ".$key['city'],
					"pick_lat"=>$key['lat'],
					"pick_lng"=>$key['lng'],
					"drop_address_id"=>$key['street1']." ".$key['location1']." ".$key['zip_code1']." ".$key['city1'],
					"drop_lat"=>$key['lat1'],
					"drop_lng"=>$key['lng1'],
					"vehicle"=>$key['make_name']." ".$key['model_name']." ".$key['variant_name'],
					"order_date"=>$key['order_date'],
					"create_at"=>$key['update_at']
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
	}
	else
	{
		$check_otp['message']= "Empty request parameter";
		echoResponse(200,$check_otp);
	}
});


$app->post('/take_location',function() use ($app)
{
	$user_id=$app->request()->params('user_id');
	$lat=round($app->request()->params('lat'),4);
	$lng=round($app->request()->params('lng'),4);
	if(!empty($user_id) && !empty($lat) && !empty($lng))
	{
		global $db;
		if(ctype_digit($user_id))
		{
			$location_query = $db->select("admin","*",array("id"=>$user_id));
			if($location_query["status"] == "success")
			{
				$check_loc= $db->customQuery("SELECT * FROM `location` where lng='$lng' AND user_id='$user_id' AND lat='$lat'");
				if($check_loc['status']=="success")
				{
					$check_loc['message'] = "Location taken";
					unset($check_loc['status']);
					unset($check_loc['data']);
					echoResponse(200,$check_loc);

				}else
				{
					$update_location = $db->insert("location",array("user_id"=>$user_id,"lat"=>$lat,"lng"=>$lng,"create_at"=>militime),array());
					if($update_location['status']=="success")
					{
						$update_location['message'] = "Location taken";
						unset($update_location['status']);
						unset($update_location['data']);
						echoResponse(200,$update_location);
					}
				}
			}else
			{
				$check_otp['message']= "Invalid User";
				echoResponse(200,$check_otp);
			}
		}
		else
		{
			$check_otp['message']= "Request parameter not valid";
			echoResponse(200,$check_otp);
		}
	}else
	{
		$check_otp['message']= "Empty request parameter";
		echoResponse(200,$check_otp);
	}
});

$app->post('/order_status',function() use ($app)
{
	$user_id=$app->request()->params('user_id');
	$lat=round($app->request()->params('lat'),4);
	$lng=round($app->request()->params('lng'),4);
	$order_id=$app->request()->params('order_id');
	$status=$app->request()->params('status');

	if(!empty($user_id) && !empty($order_id) && !empty($status))
	{
		global $db;
		if(ctype_digit($user_id))
		{
			$location_query = $db->select("admin","*",array("id"=>$user_id));
			if($location_query["status"] == "success")
			{
				$check_status= $db->customQuery("SELECT * FROM `order_logs` where order_id='$order_id' AND user_id='$user_id' AND status='$status'");
				if($check_status['status']=="success")
				{
					$check_status['message'] = "successfully";
					unset($check_status['status']);
					unset($check_status['data']);
					unset($check_status['row']);
					echoResponse(200,$check_status);

				}else
				{
					$update_status = $db->insert("order_logs",array("user_id"=>$user_id,"order_id"=>$order_id,"status"=>$status,"lat"=>$lat,"lng"=>$lng,"create_at"=>militime),array());
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
				$check_otp['message']= "Invalid User";
				echoResponse(200,$check_otp);
			}
		}
		else
		{
			$check_otp['message']= "Request parameter not valid";
			echoResponse(200,$check_otp);
		}
	}else
	{
		$check_otp['message']= "Empty request parameter";
		echoResponse(200,$check_otp);
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