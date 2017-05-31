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

$app->post('/user',function() use ($app){

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

					$ins_data = array(
						'user_id'=>$check['data'][0]['user_id'],
						'center_id'=>$data->center_id,
						'rating'=>$data->rating,
						'comment'=>$data->comment,
						'create_at'=>militime
						);
					

					$insert_review = $db->insert("review",$ins_data,array());
					if($insert_review["status"] == "success")
					{
						$insert_review['message'] ="Review added successfully";
						unset($insert_review['status']);
						unset($insert_review['data']);
						echoResponse(200,$insert_review);
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

$app->get('/user/:id',function($id) use ($app){
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
				//$vehicle_query = $db->select("review","*",array('center_id'=>$id));
				$vehicle_query = $db->customQuery("SELECT review.*,userProfile.name,userProfile.email,userProfile.profile_pic FROM review INNER JOIN userProfile ON review.user_id=userProfile.user_id WHERE review.center_id=$id  ORDER BY review.review_id DESC LIMIT 2");
				if($vehicle_query["status"] == "success")
				{
					foreach ($vehicle_query['data'] as $key) 
					{
						$counts=0;
						$like_count = $db->customQuery("SELECT count(*) AS like_count FROM review_likes WHERE `review_id`='".$key['review_id']."' AND `status`='1'");
						if($like_count["status"] == "success")
						{
							$counts=$like_count['data'][0]["like_count"];
						}
						
						$is_like=2;
						$like_is = $db->customQuery("SELECT * FROM review_likes WHERE `review_id`='".$key['review_id']."'  AND user_id='$user_id'");
						if($like_is["status"] == "success")
						{
							$is_like=$like_is['data'][0]['status'];
						}
						

						$counts_un=0;
						$unlike_count = $db->customQuery("SELECT count(*) AS unlike_count FROM review_likes WHERE `review_id`='".$key['review_id']."' AND `status`='0'");
						if($unlike_count["status"] == "success")
						{
							$counts_un=$unlike_count['data'][0]["unlike_count"];
						}
						


						if($key['profile_pic']!="")
					    	{
					    		$pro= base_url."/dashboard/images/user_profile/".$key['profile_pic'];
					    	}else
					    	{
					    		$pro = "";
					    	}


						$aa[]=array(
									"review_id"=>$key['review_id'],
									"user_id"=>$key['user_id'],
									"name"=>$key['name'],
									"email"=>$key['email'],
									"profile_pic"=>$key['profile_pic'],
									"rating"=>$key['rating'],
									"profile_pic"=>$pro,
									"counts"=>$counts,
									"unlike_counts"=>$counts_un,
									"is_like"=>$is_like,
									"comment"=>$key['comment'],
									"create_at"=>$key['create_at'],
									"date"=>king_time(date("Y-m-d H:i:s", ($key['create_at'] / 1000)))
									);
					}
					$vehicle_query['message'] = "Successfully";
					unset($vehicle_query['status']);
					unset($vehicle_query['row']);
					$vehicle_query['data'] = $aa;
					echoResponse(200,$vehicle_query);
					
				}else
				{
					$vehicle_query['message'] = "No review available";
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

$app->get('/user_status/:id',function($id) use ($app){
	$headers = apache_request_headers();
	if(!empty($headers['secret_key']))
	{
		$check = token_auth($headers['secret_key']);
		if($check['status']=="true")
		{
			$status = $app->request()->params('status');
			$create_at = $app->request()->params('create_at');
			if($create_at==0)
			{
				$create_at1= "";
			}else
			{
				$create_at1= "AND review.create_at > $create_at";
			}


			if($status=='1')
			{
				$connn= "AND review.rating > 2";
			}elseif($status=='2')
			{
				$connn= "AND review.rating < 2";
			}else
			{
				$connn= "";
			}
			//die($status);
			$user_id=$check['data'][0]['user_id'];
			$json1 = file_get_contents('php://input');
			$data = json_decode($json1);
			global $db;
			if(ctype_digit($user_id))
			{
				//$vehicle_query = $db->select("review","*",array('center_id'=>$id));
				//echo "SELECT review.*,userProfile.name,userProfile.email,userProfile.profile_pic FROM review INNER JOIN userProfile ON review.user_id=userProfile.user_id WHERE review.center_id=$id ".$connn." ".$create_at1." LIMIT 10";exit;
				$vehicle_query = $db->customQuery("SELECT review.*,userProfile.name,userProfile.email,userProfile.profile_pic FROM review INNER JOIN userProfile ON review.user_id=userProfile.user_id WHERE review.center_id=$id ".$connn." ".$create_at1." LIMIT 10");
				if($vehicle_query["status"] == "success")
				{
					foreach ($vehicle_query['data'] as $key) 
					{
						$rating = $key['rating'];

						$counts=0;
						$like_count = $db->customQuery("SELECT count(*) AS like_count FROM review_likes WHERE `review_id`='".$key['review_id']."' AND `status`='1'");
						if($like_count["status"] == "success")
						{
							$counts=$like_count['data'][0]["like_count"];
						}
						
						$counts_un=0;
						$unlike_count = $db->customQuery("SELECT count(*) AS unlike_count FROM review_likes WHERE `review_id`='".$key['review_id']."' AND `status`='0'");
						if($unlike_count["status"] == "success")
						{
							$counts_un=$unlike_count['data'][0]["unlike_count"];
						}
						
						$is_like=2;
						$like_is = $db->customQuery("SELECT * FROM review_likes WHERE `review_id`='".$key['review_id']."' AND user_id='$user_id'");
						if($like_is["status"] == "success")
						{
							$is_like=$like_is['data'][0]['status'];
						}
						


						if($key['profile_pic']!="")
					    	{
					    		$pro= base_url."/dashboard/images/user_profile/".$key['profile_pic'];
					    	}else
					    	{
					    		$pro = "";
					    	}

						
							$aa[]=array(
									"review_id"=>$key['review_id'],
									"user_id"=>$key['user_id'],
									"name"=>$key['name'],
									"email"=>$key['email'],
									"profile_pic"=>$pro,
									"rating"=>$key['rating'],
									"comment"=>$key['comment'],
									"create_at"=>$key['create_at'],
									"counts"=>$counts,
									"is_like"=>$is_like,
									"unlike_counts"=>$counts_un,
									"date"=>king_time(date("Y-m-d H:i:s", ($key['create_at'] / 1000)))
									);
						
						
					}
					$vehicle_query['message'] = "Successfully";
					unset($vehicle_query['status']);
					unset($vehicle_query['row']);
					$vehicle_query['data'] = $aa;
					echoResponse(200,$vehicle_query);
					
				}else
				{
					$vehicle_query['message'] = "No review available";
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

$app->post('/like',function() use ($app){

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
				$user_id=$check['data'][0]['user_id'];
				if(ctype_digit($user_id))
				{
					$status=$data->status;
					$ins_data = array(
						'user_id'=>$user_id,
						'review_id'=>$data->review_id,
						'status'=>$data->status,
						'create_at'=>militime
						);
					
				
						$select_review = $db->select("review_likes","*",array('review_id'=>$data->review_id,'user_id'=>$user_id));
						if($select_review["status"] == "success")
						{
							$row =$db->update("review_likes",array('status'=>$data->status),array('review_id'=>$data->review_id,'user_id'=>$user_id),array());

							$select_review['message'] ="successfully";
							unset($select_review['status']);
							unset($select_review['data']);
							echoResponse(200,$select_review);
						}else
						{
							$insert_review = $db->insert("review_likes",$ins_data,array());
							if($insert_review["status"] == "success")
							{
								$insert_review['message'] ="successfully";
								unset($insert_review['status']);
								unset($insert_review['data']);
								echoResponse(200,$insert_review);
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


/*$app->delete('/review/:id',function($vehicle_id) use ($app){

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
					$vehicle_query = $db->select("vehicle","*",array('user_id'=>$user_id,'vehicle_id'=>$vehicle_id));
					if($vehicle_query["status"] == "success")
					{
						$delete_address = $db->delete("vehicle", array('vehicle_id'=>$vehicle_id));
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
*/


function echoResponse($status_code, $response) {
    global $app;
    $app->status($status_code);
    $app->contentType('application/json');
    echo json_encode($response,JSON_NUMERIC_CHECK);
}
$app->run();
?>