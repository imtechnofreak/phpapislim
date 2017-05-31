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
$image_url = "http://dashboard.motorbabu.net/"; 
$dateTime = date("Y-m-d H:i:s", time()); 
$militime=round(microtime(true) * 1000);
define('dateTime', $dateTime);
define('base_url', $base_url);
define('image_url', $image_url);
define('militime', $militime);

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
					$name=$check['data'][0]['name'];

					$phone_no=$check['data'][0]['phone_no'];
					$vehicle_id=$data->vehicle_id;
					$is_pickup=$data->is_pickup;
					$pickup_address_id=$data->pickup_address_id;

					$device_token=$check['data'][0]['device_token'];

					$serv = $data->services;

					$center_id="0";
					if(!empty($data->center_code))
					{
					   	$service_query = $db->select("service_center","*",array("center_code"=>$data->center_code));
					   	if($service_query["status"] == "success")
					   	{
						   	$center_id=$service_query['data'][0]['center_id'];
					   	}
					}
					$order_data = array(
						'user_id'=>$user_id,
						'order_type'=>2,
						'order_date'=>$data->order_date,
						'order_time'=>$data->time_slot,
						'km'=>$data->km,
						'pickup_address_id'=>$data->pickup_address_id,
						'drop_address_id'=>$data->drop_address_id,
						'vehicle_id'=>$data->vehicle_id,
						'center_code'=>$data->center_code,
						'is_pickup'=>$data->is_pickup,
						'center_id'=>$center_id,
						'create_at'=>militime,
						'update_at'=>militime
						);

					$insert_order = $db->insert('service_order',$order_data,array());
					if($insert_order["status"] == "success")
					{

						$order_id= $insert_order["data"];
						$insert_order_log = $db->insert('service_order_logs',array("order_id"=>$order_id,"status"=>0,"create_at"=>militime),array());
						if($serv!="")
						{
							
							$services_arr=explode(',',$serv);
							foreach ($services_arr as $key) 
							{
								$insert_ser = $db->insert('service_order_serviceDetail',array('order_id'=>$order_id,'service_id'=>$key,'create_at'=>militime),array());
							}
						}

						$message= array('title'=>'Motorbabu','status'=>'order_book','order_id'=>$order_id,'message'=>'Your Order placed successfully');
						AndroidNotification($device_token,$message);
						$notif_insert = $db->insert("app_notification",array('user_id'=>$user_id,'order_id'=>$order_id,'title'=>'Motorbabu','status'=>'order_book','create_at'=>militime,'message'=>'Your Order placed successfully'),array());
						
						

						$address="";
						if($is_pickup =='1')
						{
							$sel_add = $db->select("user_address","*",array("address_id"=>$pickup_address_id));
							$address= "Address: ". $sel_add['data'][0]['street']." ".$sel_add['data'][0]['location']." ".$sel_add['data'][0]['zip_code']." ".$sel_add['data'][0]['city'];
						
						}

						$veh_info = $db->customQuery("SELECT user_vehicle.vehicle_reg,model_variant.variant_name,model.model_name,make.make_name FROM user_vehicle LEFT JOIN model_variant ON model_variant.variant_id=user_vehicle.variant_id LEFT JOIN model ON model_variant.model_id=model.model_id LEFT JOIN make ON make.make_id=model.make_id where user_vehicle.vehicle_id='$vehicle_id'");
						$vehi_reg = $veh_info['data'][0]['vehicle_reg'];
						$vehicle_name = $veh_info['data'][0]['make_name']." ".$veh_info['data'][0]['model_name']." ".$veh_info['data'][0]['variant_name'];

						$sel_mob = $db->customQuery("SELECT * FROM `MB_setting`");
					 	$center_mobile=$sel_mob['data'][0]['mobile'];

						$msg1 = "New order No. ".$order_id." on ".dateTime." via App- ".$name.", ".$phone_no.", ".$vehicle_name." (".$vehi_reg.")";
						$msg3 = "NEW BOOKING ORDER ID:".$order_id." ".$name." ".$phone_no." ".$vehicle_name."(".$vehi_reg.")".$address." ";

						$msg2 = "Your booking order No. ".$order_id."  for ".$vehicle_name." (".$vehi_reg.") has been placed. We will get in touch with you shortly. For any help call ".$center_mobile;

						if(!empty($data->center_code))
						{
							$center_device= $db->select("service_center_user","*",array("center_id"=>$center_id));
							foreach ($center_device['data'] as $key) 
							{
								$center_device_token = $key['device_token'];
								$mobile = $key['mobile'];
								$message11= array('title'=>'Motorbabu','status'=>'order_assign','message'=>'Your have New Order!!',"order_id"=>$order_id);
								AndroidNotification($center_device_token,$message11);
								$notif_insert1 = $db->insert("app_notification",array('center_id'=>$center_id,'order_id'=>$order_id,'title'=>'Motorbabu','status'=>'order_assign','create_at'=>militime,'message'=>'Your have New Order!!'),array());
								sms_send($mobile,$msg3);
							}
						}

						sms_send($center_mobile,$msg1);

						sms_send($phone_no,$msg2);

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

$app->post('/CancelBooking/:id',function($order_id) use ($app){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			global $db;
			$user_id=$check['data'][0]['user_id'];
			$name=$check['data'][0]['name'];
			$phone_no=$check['data'][0]['phone_no'];
			$device_token=$check['data'][0]['device_token'];

			$sel_order = $db->customQuery("SELECT * FROM service_order WHERE `user_id`='$user_id' AND `order_id`='$order_id' AND (`status`='0' OR `status`='1')");
			if($sel_order["status"] == "success")
			{
				$upd_order = $db->update("service_order",array('status'=>8),array('user_id'=>$user_id,"order_id"=>$order_id),array());
				if($upd_order["status"] == "success")
				{

					$insert_order_log = $db->insert('service_order_logs',array("order_id"=>$order_id,"status"=>8,"create_at"=>militime),array());

					$message= array('title'=>'Motorbabu','status'=>'order_cancel','order_id'=>$order_id,'message'=>'Your Order cancelled successfully');
					AndroidNotification($device_token,$message);
					$notif_insert = $db->insert("app_notification",array('user_id'=>$user_id,'order_id'=>$order_id,'title'=>'Motorbabu','status'=>'order_cancel','create_at'=>militime,'message'=>'Your Order cancelled successfully'),array());



					$sel_vehi = $db->customQuery("SELECT service_order.order_id,service_order.center_id,user_vehicle.vehicle_reg,model_variant.variant_name,model.model_name,make.make_name,make.make_type FROM service_order LEFT JOIN user_vehicle ON service_order.vehicle_id=user_vehicle.vehicle_id LEFT JOIN model_variant ON model_variant.variant_id=user_vehicle.variant_id LEFT JOIN model ON model_variant.model_id=model.model_id LEFT JOIN make ON make.make_id=model.make_id WHERE service_order.order_id='$order_id'");
					$vehicle_reg=$sel_vehi['data'][0]['vehicle_reg'];
				    $center_id=$sel_vehi['data'][0]['center_id'];
				    $make_type=$sel_vehi['data'][0]['make_type'];
				    $order_id=$sel_vehi['data'][0]['order_id'];

					$vehicle_name = $sel_vehi['data'][0]['make_name']." ".$sel_vehi['data'][0]['model_name']." ".$sel_vehi['data'][0]['variant_name'];

					

					$sel_mob = $db->customQuery("SELECT * FROM `MB_setting`");
				 	$center_mobile=$sel_mob['data'][0]['mobile'];

					$msg1 = "Order Cancel for order No. ".$order_id." on ".dateTime." via App- ".$name.", ".$phone_no.", ".$vehicle_name." (".$vehicle_reg.").";
					$msg2 = "Your booking order No. ".$order_id."  for ".$vehicle_name." (".$vehicle_reg.") has been cancelled by you.";

					if($center_id!="0")
					{
						$center_device= $db->select("service_center_user","*",array("center_id"=>$center_id));
						foreach ($center_device['data'] as $key) 
						{
							$center_device_token = $key['device_token'];
							$mobile = $key['mobile'];
							$message11= array('title'=>'Motorbabu','status'=>'order_cancel','message'=>'Your Order cancelled by customer!!',"make_type"=>$make_type,"order_id"=>$order_id);
							AndroidNotification($center_device_token,$message11);
							$notif_insert1 = $db->insert("app_notification",array('center_id'=>$center_id,'title'=>'Motorbabu','status'=>'order_cancel','create_at'=>militime,'message'=>'Your Order cancelled by customer!!'),array());
							sms_send($mobile,$msg1);
						}
					}

					sms_send($center_mobile,$msg1);

					sms_send($phone_no,$msg2);


					$upd_order['message'] ="Order cancel successfully";
					unset($upd_order['status']);
					echoResponse(200,$upd_order);
				}else
				{
					$upd_order['message'] ="Order not cancel!!";
					unset($upd_order['status']);
					echoResponse(200,$upd_order);
				}
			}else
			{
				$sel_order['message'] ="Somthing wrong order not cancel!!";
				unset($sel_order['status']);
				unset($sel_order['data']);
				echoResponse(200,$sel_order);
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

//need to update for services
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
				$vehicle_query = $db->customQuery("SELECT service_order.order_id,service_order.center_id,service_order.vehicle_id,service_order.pickup_address_id,service_order.drop_address_id,service_order.create_at,service_order.update_at,model_variant.variant_name,model.model_name,make.make_name,service_center.center_name,service_center.center_address,user_address.street,user_address.location,user_address.zip_code,user_address.city,a2.street AS drop_street,a2.location AS drop_location,a2.zip_code AS drop_zip_code,a2.city AS drop_city,user_vehicle.vehicle_reg FROM service_order LEFT JOIN user_vehicle ON service_order.vehicle_id=user_vehicle.vehicle_id LEFT JOIN model_variant ON user_vehicle.variant_id=model_variant.variant_id LEFT JOIN model ON model_variant.model_id=model.model_id LEFT JOIN make ON model.make_id=make.make_id  LEFT JOIN service_center ON service_order.center_id=service_center.center_id LEFT JOIN user_address ON service_order.pickup_address_id=user_address.address_id  LEFT JOIN user_address as a2 ON service_order.drop_address_id=a2.address_id WHERE service_order.user_id='$user_id'");
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
								$v4[] = array('service_id'=>$serv['service_id'], 
		    					'service_name'=>$serv['service_name']
		    					);
							}
						}

						$aa[] =array(
							"order_id"=>$key['order_id'],
							"center_id"=>$key['center_id'],
							"center_name"=>$key['center_name'],
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
});
//need to update for services
$app->get('/Timeline',function(){
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
				$vehicle_query = $db->customQuery("SELECT service_order.order_id,service_order.center_id,service_order.vehicle_id,service_order.pickup_address_id,service_order.drop_address_id,service_order.create_at,service_order.update_at,model_variant.variant_name,model.model_name,make.make_name,service_center.center_name,service_center.center_address,service_center.rating,user_address.street,user_address.location,user_address.zip_code,user_address.city,a2.street AS drop_street,a2.location AS drop_location,a2.zip_code AS drop_zip_code,a2.city AS drop_city,user_vehicle.vehicle_reg,service_order.status,model.model_pic,make.make_logo,service_order.km,service_order.order_date,service_order.amount,service_order.service_time,service_order.is_pickup FROM service_order LEFT JOIN user_vehicle ON service_order.vehicle_id=user_vehicle.vehicle_id LEFT JOIN model_variant ON user_vehicle.variant_id=model_variant.variant_id LEFT JOIN model ON model_variant.model_id=model.model_id LEFT JOIN make ON model.make_id=make.make_id  LEFT JOIN service_center ON service_order.center_id=service_center.center_id LEFT JOIN user_address ON service_order.pickup_address_id=user_address.address_id  LEFT JOIN user_address as a2 ON service_order.drop_address_id=a2.address_id WHERE service_order.user_id='$user_id'  ORDER BY service_order.order_id DESC");
				if($vehicle_query["status"] == "success")
				{
					$bb= array();
					$aa= array();
					foreach ($vehicle_query['data'] AS  $key) 
					{
						$order_id= $key['order_id'];

						if($key['model_pic'])
						{
							$model_pic=base_url.$key['model_pic'];
						}else
						{
							$model_pic="";
						}
						if($key['make_logo'])
						{
							$make_logo=base_url."/images/make/640x960/images/".$key['make_logo'];
						}else
						{
							$make_logo="";
						}


						$remaining_time="NA";
						if($key['service_time']!="")
						{
							$remaining_time=$key['service_time'];
						}

						$arr=array();
						$sel_static_status = $db->select("order_status_master","*",array());
						if($sel_static_status['status']=="success")
						{
							if($key['status']=="2" || $key['status']=="4" || $key['status']=="8")
							{
								$check_status=$db->customQuery("SELECT service_order_logs.*,order_status_master.msg_status_to_user,order_status_master.msg_user_short_code FROM service_order_logs LEFT JOIN order_status_master ON service_order_logs.status=order_status_master.status_id WHERE order_id='$order_id'");
								if($check_status['status']=="success")
								{
									foreach ($check_status['data'] as $check_status1) 
									{
										$arr[]=array(
												//"status_id"=>$check_status1['status'],
												"status"=>"Complete",
												"create_at"=>date("d M Y", ($check_status1['create_at']/1000)),
												"details"=>$check_status1['msg_status_to_user'],
												"status_name"=>$check_status1['msg_user_short_code']
											);
									}
								}
								

							}else 
							{
								$arr1="";
								$arr2="";$arr3="";$arr4="";$arr5="";
								$check_status1=$db->select("order_status_master","msg_status_to_user,msg_user_short_code",array("status_id"=>1));
								$status="In Progress";
								$status_create_at="";
								$check_order1=$db->select("service_order_logs","*",array("order_id"=>$order_id,"status"=>1));
								if($check_order1['status']=="success")
								{
									$status="Complete";
									$status_create_at=date("d M Y", ($check_order1['data'][0]['create_at']/1000));
								}
								$arr1=array(
												"status"=>$status,
												"create_at"=>$status_create_at,
												"details"=>$check_status1['data'][0]['msg_status_to_user'],
												"status_name"=>$check_status1['data'][0]['msg_user_short_code']
											);
								

								$check_status3=$db->select("order_status_master","msg_status_to_user,msg_user_short_code",array("status_id"=>3));
								$status="In Progress";
								$status_create_at="";
								$check_order3=$db->select("service_order_logs","*",array("order_id"=>$order_id,"status"=>3));
								if($check_order3['status']=="success")
								{
									$status="Complete";
									$status_create_at=date("d M Y", ($check_order3['data'][0]['create_at']/1000));
								}
								$arr3=array(
												"status"=>$status,
												"create_at"=>$status_create_at,
												"details"=>$check_status3['data'][0]['msg_status_to_user'],
												"status_name"=>$check_status3['data'][0]['msg_user_short_code']
											);

								$check_status4=$db->select("order_status_master","msg_status_to_user,msg_user_short_code",array("status_id"=>6));
								$status="In Progress";
								$status_create_at="";
								$check_order4=$db->select("service_order_logs","*",array("order_id"=>$order_id,"status"=>6));
								if($check_order4['status']=="success")
								{
									$status="Complete";
									$status_create_at=date("d M Y", ($check_order4['data'][0]['create_at']/1000));
								}
								$arr4=array(
												"status"=>$status,
												"create_at"=>$status_create_at,
												"details"=>$check_status4['data'][0]['msg_status_to_user'],
												"status_name"=>$check_status4['data'][0]['msg_user_short_code']
											);
								$check_status5=$db->select("order_status_master","msg_status_to_user,msg_user_short_code",array("status_id"=>7));
								$status="In Progress";
								$status_create_at="";
								$check_order5=$db->select("service_order_logs","*",array("order_id"=>$order_id,"status"=>7));
								if($check_order5['status']=="success")
								{
									$status="Complete";
									$status_create_at=date("d M Y", ($check_order5['data'][0]['create_at']/1000));
								}
								$arr5=array(
												"status"=>$status,
												"create_at"=>$status_create_at,
												"details"=>$check_status5['data'][0]['msg_status_to_user'],
												"status_name"=>$check_status5['data'][0]['msg_user_short_code']
											);
								if($key['is_pickup']=="1")
								{	
									$check_status2=$db->select("order_status_master","msg_status_to_user,msg_user_short_code",array("status_id"=>5));
									$status="In Progress";
									$status_create_at="";
									$check_order2=$db->select("service_order_logs","*",array("order_id"=>$order_id,"status"=>5));
									if($check_order2['status']=="success")
									{
										$status="Complete";
										$status_create_at=date("d M Y", ($check_order2['data'][0]['create_at']/1000));
									}
									$arr2=array(
													"status"=>$status,
													"create_at"=>$status_create_at,
													"details"=>$check_status2['data'][0]['msg_status_to_user'],
													"status_name"=>$check_status2['data'][0]['msg_user_short_code']
												);
									$arr= array($arr1,$arr2,$arr3,$arr4,$arr5);
								}else
								{
									$arr= array($arr1,$arr3,$arr4,$arr5);
								}
								
								
							}
						}


						if($key['status']!=7)
						{
							$is_cancel=0;
							if($key['status']=="0" || $key['status']=="1")
							{
								$is_cancel=1;
							}

							$aa[] =array(
								"order_id"=>$key['order_id'],
								"center_id"=>$key['center_id'],
								"center_name"=>$key['center_name'],
								"center_address"=>$key['center_address'],
								"rating"=>$key['rating'],
								"vehicle_id"=>$key['vehicle_id'],
								"vehicle_reg"=>$key['vehicle_reg'],
								"vehicle"=>$key['make_name']." ".$key['model_name']." ".$key['variant_name'],
								"make_name"=>$key['make_name'],
								"model_name"=>$key['model_name'],
								"variant_name"=>$key['variant_name'],
								"is_pickup"=>$key['is_pickup'],
								"pickup_address_id"=>$key['pickup_address_id'],
								"pickup_address"=>$key['street']." ".$key['location']." ".$key['zip_code']." ".$key['city'],
								"drop_address_id"=>$key['drop_address_id'],
								"km"=>$key['km'],
								"drop_address"=>$key['drop_street']." ".$key['drop_location']." ".$key['drop_zip_code']." ".$key['drop_city'],
								"vehicle_image"=>$model_pic,
								"remaining_time"=>$remaining_time,
								"estimated"=>$key['amount'],
								"rating"=>"4",
								"is_cancel"=>$is_cancel,
								"make_logo"=>$make_logo,
								"list_status"=>$arr,
								//"last_service"=>date("d M Y", ($key['create_at']/1000)),
								//"order_date"=>king_time(date("Y-m-d H:i:s", ($key['order_date'] / 1000))),
								"order_date"=>$key['order_date'],
								"create_at"=>date("d M Y", ($key['create_at']/1000))
								);
						}else
						{
							
							$bb[] =array(
								"order_id"=>$key['order_id'],
								"center_id"=>$key['center_id'],
								"center_name"=>$key['center_name'],
								"center_address"=>$key['center_address'],
								"rating"=>$key['rating'],
								"vehicle_id"=>$key['vehicle_id'],
								"vehicle_reg"=>$key['vehicle_reg'],
								"vehicle"=>$key['make_name']." ".$key['model_name']." ".$key['variant_name'],
								"pickup_address_id"=>$key['pickup_address_id'],
								"pickup_address"=>$key['street']." ".$key['location']." ".$key['zip_code']." ".$key['city'],
								"drop_address_id"=>$key['drop_address_id'],
								"km"=>$key['km'],
								"drop_address"=>$key['drop_street']." ".$key['drop_location']." ".$key['drop_zip_code']." ".$key['drop_city'],
								"vehicle_image"=>$model_pic,
								"remaining_time"=>$remaining_time,
								"estimated"=>$key['amount'],
								"rating"=>"4",
								"make_logo"=>$make_logo,
								"status_arr"=>$arr,
								//"last_service"=>date("d M Y", ($key['create_at']/1000)),
								//"order_date"=>king_time(date("Y-m-d H:i:s", ($key['order_date'] / 1000))),
								"order_date"=>$key['order_date'],
								"create_at"=>date("d M Y", ($key['create_at']/1000))
								);
						}
					}
					$vehicle_query['message'] = "successfully";
					unset($vehicle_query['status']);
					unset($vehicle_query['data']);

					$vehicle_query['running']=$aa;
					$vehicle_query['complete']=$bb;

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

$app->get('/TimelineDetail/:id',function($order_id)
{
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

			$vehicle_query = $db->customQuery("SELECT service_order.order_id FROM service_order  WHERE service_order.order_id='$order_id'");
			if($vehicle_query["status"] == "success")
			{
				$is_complete1="In Progress";
				$sel1 = $db->select("service_order_logs","*",array('order_id'=>$order_id,'status'=>1));
				if($sel1["status"] == "success")
				{
					$is_complete1= "Completed";
				}
				$content1=array();
				$sel_file1 = $db->select("service_order_files","*",array('order_id'=>$order_id,'status'=>1));
				if($sel_file1["status"] == "success")
				{
					foreach ($sel_file1 as $key1) 
					{
						$content1= array(
							"media_type"=>$key1['media_type'],
							"media"=>$key1['media']
							);
					}
				}
				$a1= array(
					'status'=>$is_complete1,
					'status_name'=>'Accepted by Service Centre',
					'content'=>$content1,
					);

				$is_complete2="In Progress";
				$sel2 = $db->select("service_order_logs","*",array('order_id'=>$order_id,'status'=>3));
				if($sel2["status"] == "success")
				{
					$is_complete2= "Completed";
				}
				$content2=array();
				$sel_file2 = $db->select("service_order_files","*",array('order_id'=>$order_id,'status'=>3));
				if($sel_file2["status"] == "success")
				{
					foreach ($sel_file2 as $key2) 
					{
						$content2= array(
							"media_type"=>$key2['media_type'],
							"media"=>$key2['media']
							);
					}
				}
				$a2= array(
					'status'=>$is_complete2,
					'status_name'=>'Servicing Started',
					'content'=>$content2,
					);

				$is_complete3="In Progress";
				$sel3 = $db->select("service_order_logs","*",array('order_id'=>$order_id,'status'=>6));
				if($sel3["status"] == "success")
				{
					$is_complete3= "Completed";
				}
				$content3=array();
				$sel_file3 = $db->select("service_order_files","*",array('order_id'=>$order_id,'status'=>6));
				if($sel_file3["status"] == "success")
				{
					foreach ($sel_file3 as $key3) 
					{
						$content3= array(
							"media_type"=>$key3['media_type'],
							"media"=>$key3['media']
							);
					}
				}
				$a3= array(
					'status'=>$is_complete3,
					'status_name'=>'Servicing Completed',
					'content'=>$content3,
					);

				$is_complete4="In Progress";
				$sel4 = $db->select("service_order_logs","*",array('order_id'=>$order_id,'status'=>7));
				if($sel4["status"] == "success")
				{
					$is_complete4= "Completed";
				}
				$content4=array();
				$sel_file4 = $db->select("service_order_files","*",array('order_id'=>$order_id,'status'=>7));
				if($sel_file4["status"] == "success")
				{
					foreach ($sel_file4 as $key4) 
					{
						$content4= array(
							"media_type"=>$key4['media_type'],
							"media"=>$key4['media']
							);
					}
				}
				$a4= array(
					'status'=>$is_complete4,
					'status_name'=>'Delivered Successfully',
					'content'=>$content4,
					);

				$aa =array($a1,$a2,$a3,$a4);

				$vehicle_query['message'] = "successfully";
				unset($vehicle_query['status']);
				unset($vehicle_query['row']);
				$vehicle_query['data']=$aa;
				echoResponse(200,$vehicle_query);
			}else
			{
				$vehicle_query['message'] = "Order Detail Not Found!!";
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

$app->get('/OrderDetail/:id',function($order_id)
{
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

			$vehicle_query = $db->customQuery("SELECT service_order.*,service_center.center_name,service_center.center_address,service_center.center_phone,service_center.center_mobile,service_center.rating,user_address.street,user_address.location,user_address.zip_code,user_address.city,a2.street AS street1,a2.location AS location1,a2.zip_code AS zip_code1,a2.city AS city1,order_timeslot_master.slot_name FROM service_order LEFT JOIN service_center ON service_order.center_id=service_center.center_id LEFT JOIN user_address ON service_order.pickup_address_id=user_address.address_id  LEFT JOIN user_address as a2 ON service_order.drop_address_id=a2.address_id LEFT JOIN order_timeslot_master ON service_order.order_time=order_timeslot_master.slot_id WHERE service_order.order_id='$order_id'");
			if($vehicle_query["status"] == "success")
			{
				
				$order_id= $vehicle_query['data'][0]['order_id'];
				$v4 = "";
				$servvv="";
				$sel_ser= $db->customQuery("SELECT service_order_serviceDetail.service_id,services.service_name FROM service_order_serviceDetail LEFT JOIN services ON service_order_serviceDetail.service_id=services.service_id WHERE service_order_serviceDetail.order_id='$order_id'");
				if($sel_ser["status"] == "success")
				{
					$i=1;
					foreach ($sel_ser["data"] as $serv) 
					{
						$v4[] = array('service_id'=>$serv['service_id'], 
    					'service_name'=>$serv['service_name']
    					);

    					if($i==1)
    					{
    						$servvv=$serv['service_name'];
    					}else
    					{
    						$servvv=$servvv.", ".$serv['service_name'];
    					}
    					$i++;
					}
				}

				$is_cancel=0;
				if($vehicle_query['data'][0]['status']==0 || $vehicle_query['data'][0]['status']==1)
				{
					$is_cancel=1;
				}

				$is_service_edit=0;
				if($vehicle_query['data'][0]['status']==0)
				{
					$is_service_edit=1;
				}

				$arr = array(
						"order_id"=>$vehicle_query['data'][0]['order_id'],
						"order_date"=>$vehicle_query['data'][0]['order_date'],
						"order_time"=>$vehicle_query['data'][0]['slot_name'],
						"services_arr"=>$v4,
						"services"=>$servvv,
						"is_service_edit"=>$is_service_edit,
						"is_cancel"=>$is_cancel,
						"offer"=>"",
						"center_name"=>$vehicle_query['data'][0]['center_name'],
						"center_address"=>$vehicle_query['data'][0]['center_address'],
						"rating"=>$vehicle_query['data'][0]['rating'],
						"is_pickup"=>$vehicle_query['data'][0]['is_pickup'],
						"estimated_time"=>$vehicle_query['data'][0]['service_time'],
						"pickup_address"=>$vehicle_query['data'][0]['street']." ".$vehicle_query['data'][0]['location']." ".$vehicle_query['data'][0]['zip_code']." ".$vehicle_query['data'][0]['city'],
						"drop_address"=>$vehicle_query['data'][0]['street1']." ".$vehicle_query['data'][0]['location1']." ".$vehicle_query['data'][0]['zip_code1']." ".$vehicle_query['data'][0]['city1'],
						"center_phone"=>$vehicle_query['data'][0]['center_phone'],
						"center_mobile"=>$vehicle_query['data'][0]['center_mobile'],
						"create_at"=>date("d M Y", ($vehicle_query['data'][0]['create_at']/1000)),

					);

				$vehicle_query['message'] = "successfully";
				unset($vehicle_query['status']);
				unset($vehicle_query['row']);
				$vehicle_query['data']=$arr;
				echoResponse(200,$vehicle_query);
			}else
			{
				$vehicle_query['message'] = "Order Detail Not Found!!";
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

$app->get('/order_slot',function()use ($app){
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
				$version_query = $db->select("order_timeslot_master","slot_id,slot_name",array('status'=>1));
				if($version_query["status"] == "success")
				{
					$version_query['message'] = "successfully";
					unset($version_query['status']);
					//unset($version_query['data']);
					echoResponse(200,$version_query);
					
				}else
				{
					$version_query['message']= "No slot!!";
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
			$msg['message'] = "Invalid Token";
			echoResponse(200,$msg);
		}
	}else
		{
			$msg['message'] = "Unauthorised access";
			echoResponse(200,$msg);
		}
});

$app->get('/pushTest',function(){
	global $db;
	$version_query = $db->select("service_center_user","*",array());
	foreach ($version_query['data'] as $key) 
	{
		$message11= array('title'=>'Motorbabu','status'=>'order_cancel','message'=>'Your Order cancelled by customer!!');
		AndroidNotification($key['device_token'],$message11);
		$msg['message'] = "success";
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
