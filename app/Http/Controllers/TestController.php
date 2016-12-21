<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class TestController extends Controller
{
    public function postpayment(){
        $user = User::find(35);
        $creditCardToken = $_POST['stripeToken'];
//        print_r($creditCardToken);exit;
        \Stripe\Stripe::setApiKey(env('APIKEY'));
        //$user->newSubscription('main', 'monthly')->create($creditCardToken);
 $token = $_POST['stripeToken'];
//$customer = \Stripe\Customer::create(array(
//  "source" => $token,
//  "description" => "Example customer")
//);
//
//// Charge the Customer instead of the card
//\Stripe\Charge::create(array(
//  "amount" => 1000, // amount in cents, again
//  "currency" => "usd",
//  "customer" => $customer->id)
//);
  
// Charge the Customer instead of the card
//\Stripe\Charge::create(array(
//  "amount" => 1000, // amount in cents, again
//  "currency" => "usd",
//  "customer" => $customer->id)
//);
   
$user->newSubscription('main', 'main')->create($token);
$user->save();
//$customer = \Stripe\Customer::create(array(
//  "description" => "Customer for test@example.com",
//  "source" => $token // obtained with Stripe.js
//));
//print_r($customer);
//$status = $user->charge(800);
//       $status = $user->charge(1000, [
//            'source' => $user->id,
//            'receipt_email' => $user->email,
//        ]);
//       echo $status;
    }
    public function users(){
        $offset = '+5:00';

// Calculate seconds from offset
list($hours, $minutes) = explode(':', $offset);
$seconds = +300* 60;
// Get timezone name from seconds
$tz = timezone_name_from_abbr('', $seconds, 1);
// Workaround for bug #44780
if($tz === false) $tz = timezone_name_from_abbr('', $seconds, 0);
// Set timezone
date_default_timezone_set($tz);

echo $tz . ': ' . date('r');exit;
        $users = User::all();
        echo '<pre>';
        print_r("$users");
        echo '</pre>';
    }
    public function testPushNotification($id){
       
       //Send Notification to Mobile App
        $user = User::find(37);
        $message= "Concierge Completed this job";
        $payload_data=array('user_details' => "Some text or obj");
        $result = $this->androidtestnoti($id,$message,$payload_data);
        echo $result;
    }
    public function sendPushNotification($id){
        

            $applicant = User::select('f_name','l_name','profile_pic','bio')->where('id', 19)->first();
            if($applicant){
                $applicant->profile_pic = profilePicPath($applicant->profile_pic);
                $applicant->application_description = "application description";
                $applicant->type = "cons_app";
                $ids =array($id);
                //print_r($ids);
                $payload_data=array('user_details' => $applicant);
                $message=array('title'=>"Job Appliucation Notification", 'message'=>"Someone applied to your job", 'payload'=>$payload_data);
                $result = pushNotification($ids,$message);
                print_r($result);exit;
                return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_200'),'Data' => $message]);
            } 
    }
    public function assignJobPushNotification($id){
        
        $assignedtouser = User::select('f_name','l_name','profile_pic','bio')->where('id', 17)->first();
        if($assignedtouser){
            $assignedtouser->profile_pic = profilePicPath($assignedtouser->profile_pic);
            $assignedtouser->rating = 5;
            $assignedtouser->start_lat = 33.628661;
            $assignedtouser->start_long = 73.092175;
            $assignedtouser->dest_lat = 31.533151;
            $assignedtouser->dest_long = 31.533151;
            $assignedtouser->start_loc = "ichra Lahore";
            $assignedtouser->dest_loc = "Mazang Lahore";
            $assignedtouser->description = "job description";
            $assignedtouser->job_id = 1;
            $assignedtouser->canidate_id = 20;
            $assignedtouser->price = 10;
            $assignedtouser->attachment =  "http://vengiledevs.cloudapp.net/erge/ergeapi/public/jobs/56cb27436527f56cd45891e617e54d94b0111f517367e036af9fc.jpg";
            $assignedtouser->type = "boss_assign_job";
            $ids =array($id);
            $payload_data=array('user_details' => $assignedtouser);
            $message=array('title'=>"Job Assigned Notification", 'message'=>"Boss assigned job to you", 'payload'=>$payload_data);
            $result = pushNotification($ids,$message); 
            return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_201'),'Data' => $message]);
        }  
    }
    public function savepaypalinfo(){
        //$data = $_POST;
        $data = json_encode( $_POST);
        Storage::disk('local')->put('file.txt', $data);
    }
    function androidtestnoti($deviceToken,$message,$data)
    { 
        $gcmKey="AIzaSyCQOLX4Gxcf-9BkTeyPXKKmDwQ7ulyWSoo";
            
        // Set POST variables
        $url = 'https://android.googleapis.com/gcm/send';
        $registatoin_ids = array($deviceToken);
        $data=array('title'=>"Erge Notification", 'message'=> $message, 'sound' => 'Bell.caf','payload'=>$data);
        //$data=array('title'=>"TalentedHuman Notification", 'message'=> $message, 'badge'=>true, 'sound'=>true, 'alert'=>true, 'payload'=>$data); 
//'vibrate' => false,
        
        
        $fields = array(
            'registration_ids' => $registatoin_ids,
            'data' => $data
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
}
