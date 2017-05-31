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

$app->post('/booking',function() use ($app){

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
					$user_id=$check['data'][0]['user_id'];

					$order_data = array(
						'user_id'=>$user_id,
						'center_id'=>$data->center_id,
						'vehicle_id'=>$data->vehicle_id,
						'pickup_address_id'=>$data->pickup_address_id,
						'drop_address_id'=>$data->drop_address_id,
						'services'=>$data->services,
						'order_type'=>$data->order_type,
						'order_date'=>$data->order_date,
						'order_time'=>$data->order_time,
						'create_at'=>militime,
						'update_at'=>militime
						);

					$insert_order = $db->insert('service_order',$order_data,array());
					if($insert_order["status"] == "success")
					{
						$insert_order['message'] ="Order placed successfully";
						unset($insert_order['status']);
						echoResponse(200,$insert_order);
					}else
					{

						$insert_order['message'] ="Order not placed";
						unset($insert_order['status']);
						unset($insert_order['data']);
						echoResponse(200,$insert_order);
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

$app->get('/booking',function(){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$user_id=$check['data'][0]['user_id'];
			global $db;
			if(ctype_digit($user_id))
			{
				$vehicle_query = $db->customQuery("SELECT service_order.order_id,service_order.center_id,service_order.vehicle_id,service_order.pickup_address_id,service_order.drop_address_id,service_order.services,service_order.create_at,service_order.update_at,model_variant.variant_name,model.model_name,model.model_pic,make.make_name FROM service_order LEFT JOIN vehicle ON service_order.vehicle_id=vehicle.vehicle_id LEFT JOIN model_variant ON vehicle.variant_id=model_variant.variant_id LEFT JOIN model ON model_variant.model_id=model.model_id LEFT JOIN make ON model.make_id=make.make_id  WHERE service_order.user_id='$user_id' ORDER BY service_order.order_id DESC ");
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
							"vehicle_id"=>$key['vehicle_id'],
							"vehicle_image"=>$vehicle_image,
							"vehicle"=>$key['make_name']." ".$key['model_name']." ".$key['variant_name'],
							"last_service"=>date("d M Y", ($key['create_at']/1000)),
							"status"=>"In Progress",
							"left"=>"NA"
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

$app->post('/QuickBooking',function() use ($app){

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
					$user_id=$check['data'][0]['user_id'];

					$order_data = array(
						'user_id'=>$user_id,
						'order_type'=>2,
						'order_date'=>date("Y-m-d", time()),
						'pickup_address_id'=>$data->pickup_address_id,
						'drop_address_id'=>$data->drop_address_id,
						'vehicle_id'=>$data->vehicle_id,
						'services'=>$data->services,
						'create_at'=>militime,
						'update_at'=>militime
						);

					$insert_order = $db->insert('service_order',$order_data,array());
					if($insert_order["status"] == "success")
					{
						$insert_order['message'] ="Order placed successfully";
						unset($insert_order['status']);
						echoResponse(200,$insert_order);
					}else
					{

						$insert_order['message'] ="Order not placed";
						unset($insert_order['status']);
						unset($insert_order['data']);
						echoResponse(200,$insert_order);
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



/*$app->get('/booking',function(){
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
				$vehicle_query = $db->customQuery("SELECT service_order.order_id,service_order.center_id,service_order.vehicle_id,service_order.pickup_address_id,service_order.drop_address_id,service_order.services,service_order.create_at,service_order.update_at,model_variant.variant_name,model.model_name,make.make_name,service_center.center_name,service_center.center_address,address.street,address.location,address.zip_code,address.city,a2.street AS drop_street,a2.location AS drop_location,a2.zip_code AS drop_zip_code,a2.city AS drop_city,vehicle.vehicle_reg FROM service_order LEFT JOIN vehicle ON service_order.vehicle_id=vehicle.vehicle_id LEFT JOIN model_variant ON vehicle.variant_id=model_variant.variant_id LEFT JOIN model ON model_variant.model_id=model.model_id LEFT JOIN make ON model.make_id=make.make_id  LEFT JOIN service_center ON service_order.center_id=service_center.center_id LEFT JOIN address ON service_order.pickup_address_id=address.address_id  LEFT JOIN address as a2 ON service_order.drop_address_id=a2.address_id WHERE service_order.user_id='$user_id'");
				if($vehicle_query["status"] == "success")
				{
					foreach ($vehicle_query['data'] AS  $key) 
					{
						$serv= explode(",", $key['services']);
						$v4 = ""; 
						foreach ($serv as $serv) 
						{
							$v3 = $db->select("services","*",array('service_id'=>$serv));
		    				if($v3['status']=="success")
		    				{
								$v4[] = array('service_id'=>$serv, 
		    					'service_name'=>$v3['data'][0]['service_name'],
		    					//'service_desc'=>$v3['data'][0]['service_desc']
		    					);

		    				}
						}

						$get_rate = $db->customQuery("SELECT avg(rating) as rate FROM review WHERE center_id='".$key['center_id']."'");

						$aa[] =array(
							"order_id"=>$key['order_id'],
							"center_id"=>$key['center_id'],
							"center_name"=>$key['center_name'],
							"center_rating"=>round($get_rate['data'][0]['rate']),
							"center_address"=>$key['center_address'],
							"vehicle_id"=>$key['vehicle_id'],
							"vehicle_reg"=>$key['vehicle_reg'],
							"vehicle"=>$key['make_name']." ".$key['model_name']." ".$key['variant_name'],
							"pickup_address_id"=>$key['pickup_address_id'],
							"pickup_address"=>$key['street']." ".$key['location']." ".$key['zip_code']." ".$key['city'],
							"drop_address_id"=>$key['drop_address_id'],
							"drop_address"=>$key['drop_street']." ".$key['drop_location']." ".$key['drop_zip_code']." ".$key['drop_city'],
							"services"=>$v4,
							"last_service"=>date("d M Y", ($key['create_at']/1000)),
							"service_date"=>king_time(date("Y-m-d H:i:s", ($key['create_at'] / 1000)))
							);
					}
					$vehicle_query['message'] = "successfully";
					unset($vehicle_query['status']);
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
});*/

function echoResponse($status_code, $response) {
    global $app;
    $app->status($status_code);
    $app->contentType('application/json');
    echo json_encode($response,JSON_NUMERIC_CHECK);
}
$app->run();
?>