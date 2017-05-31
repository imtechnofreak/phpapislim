<?php
require '.././libs/Slim/Slim.php';
require_once 'dbHelper.php';
require_once 'auth.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app = \Slim\Slim::getInstance();
$db = new dbHelper();

date_default_timezone_set("Asia/Kolkata");
$base_url = "https://mbuapp2017.motorbabu.net:8443/"; 
$image_url = "http://dashboard.motorbabu.net/"; 
$dateTime = date("Y-m-d H:i:s", time()); 
$militime=round(microtime(true) * 1000);
define('dateTime', $dateTime);
define('base_url', $base_url);
define('image_url', $image_url);
define('militime', $militime);

$app->post('/vehicle',function() use ($app){

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
					$condition = array(
						'user_id'=>$check['data'][0]['user_id'],
						'vehicle_reg'=>$data->vehicle_reg
						);

					$data1 = array(
						'user_id'=>$check['data'][0]['user_id'],
						'vehicle_reg'=>$data->vehicle_reg,
						'vehicle_desc'=>$data->vehicle_desc,
						'variant_id'=>$data->variant_id,
						'create_at'=>militime,
						'update_at'=>militime,
						);
					//print_r($data1);
					
					$vehicle_query = $db->select("user_vehicle","*",$condition);
					if($vehicle_query["status"] == "success")
					{

						if($vehicle_query["data"][0]['status']== 1 )
						{
							$vehicle_query_upd = $db->update("user_vehicle",array("status"=>0),$condition,array());
							if($vehicle_query_upd["status"] == "success")
							{
								$vehicle_query['message'] ="vehicle registered successfully";
							}

						}else
						{
							$vehicle_query['message'] = "vehicle already registered";
						}
						
						unset($vehicle_query['status']);
						unset($vehicle_query['data']);
						echoResponse(208,$vehicle_query);
						
					}else
					{
						//die('ddk');
						$insert_vehicle = $db->insert("user_vehicle",$data1,array());
						if($insert_vehicle["status"] == "success")
						{
							$insert_vehicle['message'] ="vehicle registered successfully";
							unset($insert_vehicle['status']);
							//unset($insert_vehicle['data']);
							echoResponse(200,$insert_vehicle);
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
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});

$app->get('/vehicle',function(){
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
				//$vehicle_query = $db->select("vehicle","*",array('user_id'=>$user_id));
				$vehicle_query = $db->customQuery("SELECT user_vehicle.vehicle_id,user_vehicle.variant_id,user_vehicle.vehicle_reg,model_variant.variant_name,model.model_name,make.make_name,make.make_type FROM user_vehicle left join model_variant on user_vehicle.variant_id=model_variant.variant_id left join model on model_variant.model_id=model.model_id left join make on model.make_id=make.make_id WHERE user_vehicle.user_id='$user_id' AND user_vehicle.status='0' ORDER BY user_vehicle.vehicle_id DESC");
				if($vehicle_query["status"] == "success")
				{
					$vehicle_query['message'] = "successfully";
					unset($vehicle_query['status']);
					echoResponse(200,$vehicle_query);
					
				}else
				{
					$vehicle_query['message'] = "No vehicle";
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
$app->get('/timeline',function() use ($app){
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
				$vehicle_query = $db->customQuery("SELECT vehicle.vehicle_id,vehicle.variant_id,vehicle.vehicle_reg,model_variant.variant_name,model.model_name,make.make_name,make.make_type FROM vehicle left join model_variant on vehicle.variant_id=model_variant.variant_id left join model on model_variant.model_id=model.model_id left join make on model.make_id=make.make_id WHERE service_order.user_id='$user_id'");
				if($vehicle_query["status"] == "success")
				{
					$vehicle_query['message'] = "successfully";
					unset($vehicle_query['status']);
					echoResponse(200,$vehicle_query);
					
				}else
				{
					$vehicle_query['message'] = "No vehicle";
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

$app->put('/vehicle',function() use ($app){

	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$upd_data = json_decode($app->request->getBody());
			$vehicle_id=$upd_data->vehicle_id;
			$user_id=$check['data'][0]['user_id'];
			if(!empty($vehicle_id))
			{
				global $db;
				if(ctype_digit($check['data'][0]['user_id']))
				{
					$vehicle_query = $db->select("user_vehicle","*",array('user_id'=>$user_id,'vehicle_id'=>$vehicle_id));
					if($vehicle_query["status"] == "success")
					{
						$update_vehicle = $db->update("user_vehicle", $upd_data, array('user_id'=>$user_id,'vehicle_id'=>$vehicle_id),array());
						if($update_vehicle["status"] == "success")
						{
							$update_vehicle['message'] ="vehicle updated successfully";

							$sel_det = $db->select("user_vehicle","*",array('user_id'=>$user_id,'vehicle_id'=>$vehicle_id));
							unset($update_vehicle['status']);
							foreach ($sel_det['data'] as $key) 
							{
								$aa=$key;
							}
							$update_vehicle['data']=$aa;
							echoResponse(200,$update_vehicle);
						}else
						{
							$update_vehicle['message'] ="vehicle already updated";
							$sel_det = $db->select("user_vehicle","*",array('user_id'=>$user_id,'vehicle_id'=>$vehicle_id));
							foreach ($sel_det['data'] as $key) 
							{
								$aa=$key;
							}
							$update_vehicle['data']=$aa;
							unset($update_vehicle['status']);
							echoResponse(200,$update_vehicle);
						}
					}else
					{
						$vehicle_query['message'] ="vehicle not exist!!";
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

$app->get('/search',function(){
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
				$vehicle_query = $db->customQuery("SELECT model_variant.variant_id,model_variant.variant_name,model.model_name,model.model_pic,make.make_name,make.make_logo,make.make_type FROM model_variant LEFT JOIN model ON model_variant.model_id=model.model_id LEFT JOIN make ON make.make_id=model.make_id WHERE variant_status='0'");
				if($vehicle_query["status"] == "success")
				{
					$vehicle_query['message'] = "successfully";
					unset($vehicle_query['status']);
					unset($vehicle_query['row']);

					$aa="";
					foreach ($vehicle_query['data'] as $key) 
					{
						if($key['model_pic'])
						{
							$model_pic=base_url.$key['model_pic'];
						}else
						{
							$model_pic="";
						}
						$aa[]=array(
						"variant_id"=>$key['variant_id'],
						"make_type"=>$key['make_type'],
						"make_logo"=>base_url."/images/make/640x960/images/".$key['make_logo'],
						"vehicle_image"=>$model_pic,
						"vehicle"=>$key['make_name']." ".$key['model_name']." ".$key['variant_name'],
						"model_name"=>$key['model_name']
						);
					}
					
					$vehicle_query['data']=$aa;
					echoResponse(200,$vehicle_query);
					
				}else
				{
					$vehicle_query['message'] = "No vehicle";
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

$app->get('/History',function(){
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
				$vehicle_query = $db->customQuery("SELECT user_vehicle.*,model_variant.variant_name,model.model_name,model.model_pic,make.make_name,make.make_type,make.make_logo FROM user_vehicle  LEFT JOIN model_variant ON user_vehicle.variant_id=model_variant.variant_id LEFT JOIN model ON model_variant.model_id=model.model_id LEFT JOIN make ON model.make_id=make.make_id WHERE user_vehicle.user_id='$user_id' AND user_vehicle.status='0' ");
				if($vehicle_query["status"] == "success")
				{
					foreach ($vehicle_query['data'] AS  $key) 
					{
						$bb=array();
						$last_service="NA";
						$service_date="NA";
						$center_name="NA";
						$center_rating="NA";
						$center_address="NA";
						
						$vehicle_order = $db->customQuery("SELECT service_order.order_id,service_order.center_id,service_order.vehicle_id,service_order.pickup_address_id,service_order.drop_address_id,service_order.services,service_order.create_at,service_order.update_at,service_center.center_name,service_center.center_address,user_address.street,user_address.location,user_address.zip_code,user_address.city,a2.street AS drop_street,a2.location AS drop_location,a2.zip_code AS drop_zip_code,a2.city AS drop_city FROM service_order LEFT JOIN service_center ON service_order.center_id=service_center.center_id LEFT JOIN user_address ON service_order.pickup_address_id=user_address.address_id  LEFT JOIN user_address as a2 ON service_order.drop_address_id=a2.address_id WHERE service_order.vehicle_id='".$key['vehicle_id']."'");
						if($vehicle_order["status"] == "success")
						{
							foreach ($vehicle_order['data'] AS  $key1) 
							{

								$serv= explode(",", $key1['services']);
								$v4 = []; 
								foreach ($serv as $serv) 
								{
									$v3 = $db->select("services","*",array('service_id'=>$serv));
				    				if($v3['status']=="success")
				    				{
										$v4[] = array(
										//'service_id'=>$serv, 
				    					'service_name'=>$v3['data'][0]['service_name'],
				    					);

				    				}
								}

								$get_rate = $db->customQuery("SELECT avg(rating) as rate FROM review WHERE center_id='".$key1['center_id']."'");
								
								$bb[] =array(
									"order_id"=>$key1['order_id'],
									//"center_id"=>$key1['center_id'],
									"center_name"=>$key1['center_name'],
									"center_rating"=>round($get_rate['data'][0]['rate']),
									"center_address"=>$key1['center_address'],
									//"pickup_address_id"=>$key1['pickup_address_id'],
									"pickup_address"=>$key1['street']." ".$key1['location']." ".$key1['zip_code']." ".$key1['city'],
									//"drop_address_id"=>$key1['drop_address_id'],
									"drop_address"=>$key1['drop_street']." ".$key1['drop_location']." ".$key1['drop_zip_code']." ".$key1['drop_city'],
									"services"=>$v4,
									"last_service"=>date("d M Y", ($key1['create_at']/1000)),
									"service_date"=>king_time(date("Y-m-d H:i:s", ($key1['create_at'] / 1000)))
									);
								$last_service=date("d M Y", ($key1['create_at']/1000));
								$service_date=king_time(date("Y-m-d H:i:s", ($key1['create_at'] / 1000)));
								$center_name=$key1['center_name'];
								$center_rating=round($get_rate['data'][0]['rate']);
								$center_address=$key1['center_address'];

							}
						}

						if($key['model_pic'])
						{
							$vehicle_image=base_url.$key['model_pic'];
						}else
						{
							$vehicle_image="";
						}
						
						$aa[] =array(
							
							"vehicle_id"=>$key['vehicle_id'],
							"vehicle_reg"=>$key['vehicle_reg'],
							"make_type"=>$key['make_type'],
							"make_logo"=>base_url."/images/make/640x960/images/".$key['make_logo'],
							"vehicle_image"=>$vehicle_image,
							"vehicle"=>$key['make_name']." ".$key['model_name']." ".$key['variant_name'],
							"model_name"=>$key['model_name'],
							"last_service"=>$last_service,
							"service_date"=>$service_date,
							"center_name"=>$center_name,
							"center_rating"=>$center_rating,
							"center_address"=>$center_address,
							"history"=>$bb							
							);
						
					}
					$vehicle_query['message'] = "successfully";
					unset($vehicle_query['status']);
					unset($vehicle_query['row']);
					$vehicle_query['data']=$aa;
					echoResponse(200,$vehicle_query);
					
				}else
				{
					$vehicle_query['message'] = "No Vehicle";
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
$app->delete('/vehicle/:id',function($vehicle_id) use ($app){

	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$user_id=$check['data'][0]['user_id'];
			if(!empty($vehicle_id))
			{
				//$data = json_decode($json1);
				global $db;
				if(ctype_digit($check['data'][0]['user_id']))
				{
					//$address_id=$data->address_id;
					$vehicle_query = $db->select("user_vehicle","*",array('user_id'=>$user_id,'vehicle_id'=>$vehicle_id));
					if($vehicle_query["status"] == "success")
					{
						$delete_address = $db->update("user_vehicle",array('status'=>1),array('vehicle_id'=>$vehicle_id),array());
						if($delete_address["status"] == "success")
						{
							$delete_address['message'] ="vehicle remove successfully";
							unset($delete_address['status']);
							unset($delete_address['data']);
							echoResponse(200,$delete_address);
						}
					}else
					{
						$vehicle_query['message'] ="vehicle already removed OR not exist!!";
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


function echoResponse($status_code, $response) {
    global $app;
    $app->status($status_code);
    $app->contentType('application/json');
    echo json_encode($response,JSON_NUMERIC_CHECK);
}
$app->run();
?>