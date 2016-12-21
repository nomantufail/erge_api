<?php
/**
 * Created by PhpStorm.
 * User: Shakeel Latif
 * Date: 6/1/2015
 * Time: 10:44 AM
 */

use Carbon\Carbon;

   function missingparameters($service) {
    return Response::json([ 'status' => 'error', 'serviceName' =>$service, 'ErrorCode' => '1003', 'ErrorMessage' => env('ERROR_1003')]);
   }
    function profilePicPath($pic_name) {
        if($pic_name == "")
           $pic_name = "avatar.jpg";     
        return env('BASE_PATH') . env('FOLDER_PROFILE_PIC') .'/'. $pic_name;
    }
    function filePath($pic_name2) {
        if($pic_name2 == "")
           return env('BASE_PATH') . env('FOLDER_JOB_IMAGE') .'/'."no_image.jpg";     
        return env('BASE_PATH') . env('FOLDER_JOB_IMAGE') .'/'. $pic_name2;
    }
    /*function pushNotification($registatoin_ids,$message)
    { 
            $gcmKey="AIzaSyCQOLX4Gxcf-9BkTeyPXKKmDwQ7ulyWSoo";
            
        // Set POST variables
        $url = 'https://android.googleapis.com/gcm/send';
         
        $fields = array(
            'registration_ids' => $registatoin_ids,
            'data' => $message,
        );
 
        $headers = array(
            'Authorization: key=' . $gcmKey,
            'Content-Type: application/json'
        );
        // Open connection
        $ch = curl_init();
 
        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
 
        // Execute post
        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }
 
        // Close connection
        curl_close($ch);
        return $result;
    }*/
    function pushNotification($device_type,$deviceToken,$message,$data,$user_id){
        if($deviceToken != ""){
           if($device_type == "ios"){
                iosPushNotification($deviceToken,$message,$data);
            }elseif($device_type == "android"){
                androidPushNotification($deviceToken,$message,$data);
            } 
        }
    }
    function iosPushNotification($deviceToken,$message,$data){
       
        // Put your private key's passphrase here:
        $passphrase = '12345678';

        ////////////////////////////////////////////////////////////////////////////////

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', 'pushcert.pem');
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

        // Open a connection to the APNS server
        $fp = stream_socket_client(
                'ssl://gateway.push.apple.com:2195', $err,
                $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp)
                exit("Failed to connect: $err $errstr" . PHP_EOL);
     
        // Create the payload body
        $body['aps'] = array(
                'alert' => $message,
                'sound' => 'default',
                'data' => $data,
                'badge' => 1
                );

        // Encode the payload as JSON
        $payload = json_encode($body);

        // Build the binary notification
        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

        // Send it to the server
        $result = fwrite($fp, $msg, strlen($msg));

        fclose($fp);

    }
    function androidPushNotification($deviceToken,$message,$data)
    { 
        $gcmKey="AIzaSyCQOLX4Gxcf-9BkTeyPXKKmDwQ7ulyWSoo";
            
        // Set POST variables
        $url = 'https://android.googleapis.com/gcm/send';
        $registatoin_ids = array($deviceToken);
        
        $data=array('title'=>"Erge Notification", 'message'=> $message, 'sound' => 'sound Name','payload'=>$data);
        //$data=array('title'=>"Erge Notification", 'message'=> $message, 'payload'=>array('dataobj'=>$data,'badge'=>true,'sound'=>true,'alert'=>true));
        $fields = array(
            'registration_ids' => $registatoin_ids,
            'data' => $data,
            //'badge' => $seen_count
        );
 
        $headers = array(
            'Authorization: key=' . $gcmKey,
            'Content-Type: application/json'
        );
        // Open connection
        $ch = curl_init();
 
        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
 
        // Execute post
        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }
  
        // Close connection
        curl_close($ch);
       
        return $result;
    }
    
    // get time in x min ago format
    function timeago($ptime) {
        $difference = time() - $ptime;
        $periods = array("second", "minute", "hour", "day", "week", "month", "years", "decade");
        $lengths = array("60", "60", "24", "7", "4.35", "12", "10");
        for ($j = 0; $difference >= $lengths[$j]; $j++)
            $difference /= $lengths[$j];
        
        $difference = round($difference);
        if ($difference != 1)
            $periods[$j].= "s";
        
        $text = "$difference $periods[$j] ago";
        
        return $text;
    }
    
    //set time zone for user
    function timezoneobj($min){
        $seconds = -1*$min*60;
        // Get timezone name from seconds
        $tz = timezone_name_from_abbr('', $seconds, 1);
        // Workaround for bug #44780
        if($tz === false) $tz = timezone_name_from_abbr('', $seconds, 0);
        return $tz;
    }
?>