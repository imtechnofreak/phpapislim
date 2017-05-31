<?php
define( 'API_ACCESS_KEY', 'AIzaSyB-HfCc_2x6mVHBHswPbw9pPo5bqsrrcT0' );
function AndroidNotification($device_token,$message)
{
  $registrationIds = array($device_token);

  $fields = array
  (
  'registration_ids' => $registrationIds,
  'data' => array( "message" => $message),

  );
  $headers = array
  (
  'Authorization: key=' . API_ACCESS_KEY,
  'Content-Type: application/json'
  );
  $ch = curl_init();
  curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
  curl_setopt( $ch,CURLOPT_POST, true );
  curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
  curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
  curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
  curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($fields));
  $result = curl_exec($ch);
  curl_close( $ch );
 //echo $result;exit;
}

function iOSPushNotification($deviceToken, $message ,$msg)
{ 
  $passphrase = "";
  $payload['aps'] = array(
    'alert' => $msg,
    'badge' => 1, 
    'type' => $message,
    'sound' => 'default'
  );   
  $payload = json_encode($payload);
  $apnsHost = 'gateway.push.apple.com';    
  $apnsPort = 2195;
  //$apnsCert = 'taxiappProcert.pem';
  $apnsCert = 'pushcert.pem';
  $streamContext = stream_context_create();
  stream_context_set_option($streamContext, 'ssl', 'local_cert', $apnsCert);
  stream_context_set_option($streamContext, 'ssl', 'passphrase', $passphrase);
  $apns = stream_socket_client('ssl://' . $apnsHost . ':' . $apnsPort,$error,$errorString,60,STREAM_CLIENT_CONNECT,$streamContext); 
  $apnsMessage = chr(0) . chr(0) . chr(32) . pack('H*', $deviceToken) . chr(0) . chr(strlen($payload)) . $payload;
  fwrite($apns, $apnsMessage);
  @socket_close($apns);
  print_r($apns);exit;
  fclose($apns);
}

?>