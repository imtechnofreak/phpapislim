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



/*$app->post('/search',function() use ($app){
	$headers = apache_request_headers();
	$check = token_auth($headers['secret_key']);
	if($check['status']=="true")
	{
		global $db;
		$json1     = file_get_contents('php://input');
		if(!empty($json1))
		{
		    $data      = json_decode($json1);
		    $user_id   = $check['data'][0]['user_id'];
		    $serv	   = $data->services;
		    $name	   = $data->name;
		    $latitude  = $data->latitude;
		    $longitude = $data->longitude;
		    $type = $data->type;

		    if($type==0)
		    {
		    	$type="";
		    }else
		    {
		    	$type="AND center_type='$type'";
		    }

		    $distance  = 2;
		    $a=  array();
		    $s1 = explode(',',$serv);
		    foreach ($s1 as $k) 
		    {

			    $find_all_service = $db->customQuery("SELECT service_center.*,city_location.area_name FROM service_center INNER JOIN city_location ON service_center.area_id=city_location.area_id WHERE service_center.status='0' AND services LIKE '%$k%' AND center_name LIKE '%$name%' ".$type." ");
			    if($find_all_service["status"] == "success")
			    { 
			    	foreach($find_all_service['data'] as $key)
			    	{
			    		$center_id=$key['center_id'];
			    		$find_all_station = $db->customQuery("SELECT * FROM booking_order WHERE `center_id`='".$key['center_id']."' AND (`status`!='0' OR `status`!='2') ");
			    		if($find_all_station['status']=="success")
			    		{
			    			$order = $find_all_station['row'];
			    			if($key['center_capacity']>=$order)
			    			{
			    				$available = "yes";
			    				$icon = base_url."/dashboard/images/pin_icon/320x480/images/pin_1.png";
			    			}else
			    			{
			    				$available = "No";
			    				$icon = base_url."/dashboard/images/pin_icon/320x480/images/pin_2.png";
			    			}

			    		}else
			    		{
			    			$available = "yes";
			    			$icon = base_url."/dashboard/images/pin_icon/320x480/images/pin_1.png";
			    		}

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

			    		$find_avg_rate = $db->customQuery("SELECT avg(rating) AS avg_rate FROM review WHERE `center_id`='".$key['center_id']."'");

			    		if($find_avg_rate['status']=="success")
			    		{
			    			 $avg_rate=round($find_avg_rate['data'][0]['avg_rate'],1);
			    		}
			    		if($avg_rate==null)
			    		{
			    			 $avg_rate="Not Avialable";
			    		}

			    		$center_pictures=array();
			    		$center_image = $db->select("center_image","*",array('center_id'=>$key['center_id']));
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
			    		$chack_favorite=$db->select("center_like","*",array("center_id"=>$key['center_id'],"user_id"=>$user_id));
			    		if($chack_favorite['status']=="success")
			    		{
			    			$is_favorite=1;
			    		}

			    		$can_review=0;
			    		$chack_order=$db->select("service_order","*",array("center_id"=>$key['center_id'],"user_id"=>$user_id,"status"=>"2"));
			    		if($chack_order['status']=="success")
			    		{
			    			$can_review=1;
			    		}


			    		$dis_cal = distance($latitude,$longitude, $key['center_lat'], $key['center_lng'],'K');
			    		$a[] = array('center_id'=>$key['center_id'],
			    			  'center_name'=>$key['center_name'],
						      'center_address'=>$key['center_address'],
						      'center_lat'=> $key['center_lat'],
						      'center_lng'=> $key['center_lng'],
						      'center_mobile'=>$key['center_mobile'],
						      'center_capacity'=>$key['center_capacity'],
						      'center_desc'=> $key['center_desc'],
						      'center_create_at'=> $key['center_create_at'],
						      'center_owner'=>$key['center_owner'],
						      'center_email'=>$key['center_email'],
						      'center_phone'=>$key['center_phone'],
						      'area_name'=>$key['area_name'],
						      'response_rate'=>"NA",
						      'is_favorite'=>$is_favorite,
						      'can_review'=>$can_review,
					     	  'services'=> $v4,
						      'distance'=>round($dis_cal,$distance),
						      'available'=>$available,
					      	  'icon'=>$icon,	     
					      	  'avg_rate'=>$avg_rate,
					          'center_pictures'=>$center_pictures
			    			); 
			    	} 
			    }
		   }
		 
		   if(empty($a))
		   {
		   		$find_all_service['message'] = "No station found";
		   }
		   else
		    { 
		   		$find_all_service['message'] = "successfully";
		   	}
		   	unset($find_all_service['status']);
	   		$find_all_service['data'] = array_unique($a,SORT_REGULAR);
		   	echoResponse(200,$find_all_service);
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
});*/

$app->post('/center',function() use ($app){
	$headers = apache_request_headers();
	$check = token_auth($headers['secret_key']);
	if($check['status']=="true")
	{
		global $db;
		$json1 = file_get_contents('php://input');
		if(!empty($json1))
		{
			$data1 = json_decode($json1);
		    $user_id = $check['data'][0]['user_id'];
		    $name 	= $data1->name;
		    $latitude = $data1->latitude;
		    $longitude = $data1->longitude;
		    $distance = $data1->distance; // in Kms.
		    $type = $data1->type;

		    if($type==0)
		    {
		    	$type="";
		    }else
		    {
		    	$type="AND center_type='$type'";
		    }

		    if($name=='')
		    {
		    	$sear_name="";
		    }else
		    {
		    	$sear_name=" AND `center_name` LIKE '%$name%' ";
		    }
		    $find_all_station = $db->customQuery("SELECT service_center.*,city_location.area_name FROM service_center INNER JOIN city_location ON service_center.area_id=city_location.area_id WHERE 1=1 AND service_center.status='0'  ".$sear_name." ".$type." ");
		    if($find_all_station["status"] == "success")
		    {
		    	$a = array();
		    	foreach ($find_all_station['data'] as $key) 
		    	{
		    		$center_id=$key['center_id'];
		    		$find_all_station = $db->customQuery("SELECT * FROM booking_order WHERE `center_id`='".$key['center_id']."' AND (`status`!='0' OR `status`!='2') ");
		    		if($find_all_station['status']=="success")
		    		{
		    			$order = $find_all_station['row'];
		    			if($key['center_capacity']>=$order)
		    			{
							$available = "yes";
		    				$icon = base_url."/dashboard/images/pin_icon/320x480/images/pin_1.png";
		    			}else
		    			{
		    				$available = "No";
		    				$icon = base_url."/dashboard/images/pin_icon/320x480/images/pin_2.png";
		    			}

		    		}else
		    		{
		    			$available = "yes";
		    			$icon = "/dashboard/images/pin_icon/320x480/images/pin_1.png";
		    		}

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
		    		
		    		$find_avg_rate = $db->customQuery("SELECT avg(rating) AS avg_rate FROM review WHERE `center_id`='".$key['center_id']."'");
		    		if($find_avg_rate['status']=="success")
		    		{
		    			 $avg_rate=round($find_avg_rate['data'][0]['avg_rate'],1);
		    		}
		    		if($avg_rate==null)
		    		{
		    			 $avg_rate="Not Avialable";
		    		}

		    		$center_pictures=array();
		    		$center_image = $db->select("center_image","*",array('center_id'=>$key['center_id']));
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
		    		$chack_favorite=$db->select("center_like","*",array("center_id"=>$key['center_id'],"user_id"=>$user_id));
		    		if($chack_favorite['status']=="success")
		    		{
		    			$is_favorite=1;
		    		}

		    		$can_review=0;
		    		$chack_order=$db->select("service_order","*",array("center_id"=>$key['center_id'],"user_id"=>$user_id,"status"=>"2"));
		    		if($chack_order['status']=="success")
		    		{
		    			$can_review=1;
		    		}


		    		
		    		$dis_cal = distance($latitude,$longitude, $key['center_lat'], $key['center_lng'],'K');
		    		if($dis_cal<=$distance)
		    		{
		    			$a[] = array('center_id'=>$key['center_id'],
		    			  'center_name'=>$key['center_name'],
					      'center_address'=>$key['center_address'],
					      'center_lat'=> $key['center_lat'],
					      'center_lng'=> $key['center_lng'],
					      'center_mobile'=>$key['center_mobile'],
					      'center_capacity'=>$key['center_capacity'],
					      'center_desc'=> $key['center_desc'],
					      'center_create_at'=> $key['center_create_at'],
					      'center_owner'=>$key['center_owner'],
					      'center_email'=>$key['center_email'],
					      'center_phone'=>$key['center_phone'],
					      'area_name'=>$key['area_name'],
					      'response_rate'=>"NA",
					      'is_favorite'=>$is_favorite,
					      'can_review'=>$can_review,
					      'distance'=>round($dis_cal,2),
					      'services'=> $v4,
					      'available'=>$available,
					      'icon'=>$icon,
					      'avg_rate'=>$avg_rate,
					      'center_pictures'=>$center_pictures
		    			);
		    			
		    		}
		    		
		    	}
		    	$find_all_station['data'] = $a;	
		    	$find_all_station['message'] = "successfully";
		    	unset($find_all_station['status']);
		    	unset($find_all_station['row']);
		    	echoResponse(200,$find_all_station);
		    }
		    else
		    {
		    	$find_all_station['data'] = array();
		    	unset($find_all_station['status']);
		    	$find_all_station['message'] = "No station found";

		    	echoResponse(200,$find_all_station);
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

$app->get('/services', function()
{
	$headers = apache_request_headers();
	$check = token_auth($headers['secret_key']);
	if($check['status']=="true")
	{
		global $db;
		
		   	$condition = array();
		   	$service_query = $db->select("services","*",$condition);
		   	if($service_query["status"] == "success")
		   	{
			   	foreach($service_query['data'] as $key)
			   	{
			   	$a[] = array(
			   		'service_id'=>$key['service_id'],
			   		'service_name'=>$key['service_name'],
			     	'service_desc'=>$key['service_desc'] );
			   	} 
		   	}
		   	if(empty($a))
			{
				$service_query['message'] = "No Service found";
			}
			else
			{ 
				$service_query['message'] = "successfully";
			}
			unset($service_query['status']);
			$service_query['data'] = array_unique($a,SORT_REGULAR);
			echoResponse(200,$service_query);	

	}
 	else
	{
		$msg['message'] = "Invalid Token";
		echoResponse(200,$msg);
	}	
});

$app->get('/servicesByCity', function() use ($app)
{
	$headers = apache_request_headers();
	$check = token_auth($headers['secret_key']);
	if($check['status']=="true")
	{
		global $db;
		
			$city_id = $app->request()->params('city_id');
			$service_type = $app->request()->params('service_type');
			if($service_type=='0')
			{
				$types= "";

			}else
			{
				$types= "AND `service_type`='$service_type'";
			}
		   	$condition = array();
		   	$service_query = $db->customQuery("SELECT * FROM services WHERE `city_id`='$city_id' ".$types."");
		   	if($service_query["status"] == "success")
		   	{
		   		$service_query['message'] = "successfully";
		   		

			   	foreach($service_query['data'] as $key)
			   	{
			   	$a[] = array(
			   		'service_id'=>$key['service_id'],
			   		'service_name'=>$key['service_name'],
			   		'service_type'=>$key['service_type'],
			     	'service_desc'=>$key['service_desc'] );
			   	} 

			   	unset($service_query['row']);
			   	unset($service_query['status']);

				$service_query['data'] = $a;
		   	}
		   
			echoResponse(200,$service_query);	

	}
 	else
	{
		$msg['message'] = "Invalid Token";
		echoResponse(200,$msg);
	}	
});


$app->post('/location',function() use ($app){
	$headers = apache_request_headers();
	$check = token_auth($headers['secret_key']);
	if($check['status']=="true")
	{
		global $db;
		$json1 = file_get_contents('php://input');
		if(!empty($json1))
		{
			$data = json_decode($json1);
		    $user_id = $check['data'][0]['user_id'];
		    $create_at 	= $data->create_at;
		    $latitude = $data->latitude;
		    $longitude = $data->longitude;
		  //  $distance = $data->distance; // in Kms.
		  //  $type = $data->type;

		    if($create_at==0)
		    {
		    	$create_at1="";
		    }else
		    {
		    	$create_at1="AND center_create_at > '$create_at'";
		    }


		    $find_all_station = $db->customQuery("SELECT service_center.*,city_location.area_name FROM service_center INNER JOIN city_location ON service_center.area_id=city_location.area_id WHERE 1=1 AND service_center.status='0' ".$create_at1." ");
		    //print_r($find_all_station);
		    if($find_all_station["status"] == "success")
		    {
		    	$a = array();
		    	foreach ($find_all_station['data'] as $key) 
		    	{
		    		$center_id=$key['center_id'];
		    		$find_all_station = $db->customQuery("SELECT * FROM booking_order WHERE `center_id`='".$key['center_id']."' AND (`status`!='0' OR `status`!='2') ");
		    		if($find_all_station['status']=="success")
		    		{
		    			$order = $find_all_station['row'];
		    			if($key['center_capacity']>=$order)
		    			{
							$available = "yes";
		    				$icon = base_url."/dashboard/images/pin_icon/320x480/images/pin_1.png";
		    			}else
		    			{
		    				$available = "No";
		    				$icon = base_url."/dashboard/images/pin_icon/320x480/images/pin_2.png";
		    			}

		    		}else
		    		{
		    			$available = "yes";
		    			$icon = "/dashboard/images/pin_icon/320x480/images/pin_1.png";
		    		}

		    		$sel_services= $db->customQuery("SELECT center_services.*,services.service_name FROM center_services INNER JOIN services ON center_services.service_id=services.service_id WHERE center_services.center_id='$center_id'");
			    		/*$v1 =  $key['services'];
			    		$v2 = explode(',',$v1);*/
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
		    		
		    		$find_avg_rate = $db->customQuery("SELECT avg(rating) AS avg_rate FROM review WHERE `center_id`='".$key['center_id']."'");
		    	//	print_r($find_avg_rate);exit;
		    		if($find_avg_rate['status']=="success")
		    		{
		    			 $avg_rate=round($find_avg_rate['data'][0]['avg_rate'],1);
		    		}
		    		if($avg_rate==null)
		    		{
		    			 $avg_rate="Not Avialable";
		    		}

		    		$center_pictures=array();
		    		$center_image = $db->select("center_image","*",array('center_id'=>$key['center_id']));
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
		    		$chack_favorite=$db->select("center_like","*",array("center_id"=>$key['center_id'],"user_id"=>$user_id));
		    		if($chack_favorite['status']=="success")
		    		{
		    			$is_favorite=1;
		    		}

		    		$can_review=0;
		    		$chack_order=$db->select("service_order","*",array("center_id"=>$key['center_id'],"user_id"=>$user_id,"status"=>"2"));
		    		if($chack_order['status']=="success")
		    		{
		    			$can_review=1;
		    		}


		    		
		    		$dis_cal = distance($latitude,$longitude, $key['center_lat'], $key['center_lng'],'K');
		    		if($dis_cal<=100)
		    		{
		    			$a[] = array('center_id'=>$key['center_id'],
		    			  'center_name'=>$key['center_name'],
					      'center_address'=>$key['center_address'],
					      'center_lat'=> $key['center_lat'],
					      'center_lng'=> $key['center_lng'],
					      'center_mobile'=>$key['center_mobile'],
					      'center_capacity'=>$key['center_capacity'],
					      'center_desc'=> $key['center_desc'],
					      'center_create_at'=> $key['center_create_at'],
					      'center_owner'=>$key['center_owner'],
					      'center_email'=>$key['center_email'],
					      'center_phone'=>$key['center_phone'],
						  'create_at'=>$key['center_create_at'],
						  'area_name'=>$key['area_name'],
						  'response_rate'=>"NA",
						  'is_favorite'=>$is_favorite,
						  'can_review'=>$can_review,
					      'distance'=>round($dis_cal,2),
					      'services'=> $v4,
					      'available'=>$available,
					      'icon'=>$icon,
					      'avg_rate'=>$avg_rate,
					      'center_pictures'=>$center_pictures
		    			);
		    			
		    		}
		    		
		    	}
		    	$find_all_station['data'] = $a;	
		    	$find_all_station['message'] = "successfully";
		    	unset($find_all_station['status']);
		    	unset($find_all_station['row']);
		    	echoResponse(200,$find_all_station);
		    }
		    else
		    {
		    	$find_all_station['data'] = array();
		    	unset($find_all_station['status']);
		    	$find_all_station['message'] = "No station found";

		    	echoResponse(200,$find_all_station);
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

$app->post('/like',function() use ($app){

	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$center_id = $app->request()->params('center_id');
			$status = $app->request()->params('status');

			if(!empty($center_id))
			{
				global $db;
				$user_id=$check['data'][0]['user_id'];
				if(ctype_digit($user_id))
				{
					$ins_data = array(
						'user_id'=>$user_id,
						'center_id'=>$center_id,
						'create_at'=>militime
						);

					if($status=='1')
					{
						$check_likes = $db->select("center_like","*",array('center_id'=>$center_id,'user_id'=>$user_id));
						if($check_likes["status"] == "success")
						{
							$check_likes['message'] ="Already Liked";
							unset($check_likes['status']);
							unset($check_likes['data']);
							echoResponse(200,$check_likes);
						}else
						{
							$insert_likes = $db->insert("center_like",$ins_data,array());
							if($insert_likes["status"] == "success")
							{
								$insert_likes['message'] ="successfully";
								unset($insert_likes['status']);
								unset($insert_likes['data']);
								echoResponse(200,$insert_likes);
							}
						}

					}else
					{
						$check_likes = $db->select("center_like","*",array('center_id'=>$center_id,'user_id'=>$user_id));
						if($check_likes["status"] == "success")
						{
							$row =$db->delete("center_like",array('center_id'=>$center_id,'user_id'=>$user_id));

							$check_likes['message'] ="successfully";
							unset($check_likes['status']);
							unset($check_likes['data']);
							echoResponse(200,$check_likes);
						}else
						{
							$check_likes['message'] ="Already unliked";
							unset($check_likes['status']);
							unset($check_likes['data']);
							echoResponse(200,$check_likes);
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