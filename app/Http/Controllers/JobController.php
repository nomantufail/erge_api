<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Job_Applications;
use Carbon\Carbon;
use App\User;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
class JobController extends Controller
{
    public function createJob(){
        $alldata = $_POST;
        if(isset($alldata["user_id"]) && isset($alldata["start_loc"]) && isset($alldata["dest_loc"]) && isset($alldata["start_lat"]) && isset($alldata["start_long"]) && isset($alldata["dest_lat"]) && isset($alldata["dest_long"]) && isset($alldata["description"]) && isset($alldata["price"]) && isset($alldata["completion_date"]) && isset($alldata["visibility_radius"])){
            $fileName = "";
            
            if (isset($_FILES['file']['name'])) {
                if (Input::file('file')->isValid()) {
                    $destinationPath = public_path(env('FOLDER_JOB_IMAGE')); // upload path
                    $extension = Input::file('file')->getClientOriginalExtension(); // getting image extension
                    $extension  = strtolower($extension);
                    $fileName = uniqid() . sha1(time()) . '.' . $extension; // renameing image
                    Input::file('file')->move($destinationPath, $fileName);
                }
            }
            $job = new \App\Job;
            $job->start_loc = $alldata["start_loc"];
            $job->dest_loc = $alldata["dest_loc"];
            $job->start_lat = $alldata["start_lat"];
            $job->start_long = $alldata["start_long"];
            $job->dest_lat = $alldata["dest_lat"];
            $job->dest_long = $alldata["dest_long"];
            $job->description = $alldata["description"];
            $job->price = $alldata["price"];
//            $date = new DateTime();
//            $date->setTimestamp($alldata["completion_date"]);
//            print_r($date);exit;
//            $unixtime_to_date = $date->format('F-d-Y H:i:s');
            $job->completion_date = $alldata["completion_date"];
            $job->visibility_radius = $alldata["visibility_radius"];
            $job->filename = $fileName;
            $job->user_id = $alldata["user_id"];
            $job->save();
            
            $jobdata =  \App\Job::select('users.f_name','users.profile_pic','users.mobile_no','email','role','lat','long','bio','jobs.*',\Illuminate\Support\Facades\DB::raw('AVG(ratings.rating) as rating'))
                ->join('users', 'users.id', '=', 'jobs.user_id')
                ->join('ratings', 'ratings.to_user_id', '=', 'jobs.user_id','left')
                ->where('jobs.id',$job->id)
                ->groupBy('jobs.id')
                ->first();
            $jobdata->rating = floatval( $jobdata->rating);
            $point1_lat = $jobdata->start_lat;
            $point1_lng = $jobdata->start_long;
            $jobdata->type = "new_job";
            $jobdata->profile_pic = profilePicPath($jobdata->profile_pic);
            $jobdata->attachment = filePath($jobdata->filename);
            $date = new \DateTime();
            $date->setTimestamp($jobdata->completion_date);
            
            $jobdata->applied = 0;
            $users = User::where('device_id','!=','')->where('role','CONCIERGE')->get();
            
            foreach ($users as $user) {
                $date->setTimezone(new \DateTimeZone(timezoneobj($user->timezone)));
                $jobdata->completion_date = $date->format('F-d-Y');
                $jobdata->completion_time = $date->format('h:i A');
                $point2_lat = $user->lat;
                $point2_lng = $user->long;
                $theta = $point1_lng - $point2_lng;
                $miles = (sin(deg2rad($point1_lat)) * sin(deg2rad($point2_lat))) + (cos(deg2rad($point1_lat)) * cos(deg2rad($point2_lat)) * cos(deg2rad($theta)));
                $miles = acos($miles);
                $miles = rad2deg($miles);
                $miles = $miles * 60 * 1.1515;
                if ($miles <= $jobdata->visibility_radius) {
                    //Send Notification to Mobile App
                    $message= "New job created near your location";
                    $payload_data=array('user_details' => $jobdata);
                    pushNotification($user->plateform,$user->device_id,$message,$payload_data,$user->id);
                }

            }
            return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_100'),'Data' => '']);
        } else {
            return missingparameters('create_job');
        }
    }
    public function viewJob(){
        $alldata = (json_decode(file_get_contents('php://input')));
        $job = \App\Job::find($alldata->user_id);
        return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_100'),'Data' => $job]);
    }
    public function sendApplication(){
        $application_data = (json_decode(file_get_contents('php://input')));
        if (isset($application_data->job_id) && isset($application_data->description)) {
//            $already_applied = Job_Applications::where('user_id',$application_data->user_id)->where('job_id',$application_data->job_id)->first();
//            if($already_applied){
//                return Response::json([ 'status' => 'error', 'serviceName' => 'sendApplication', 'ErrorMessage' =>env('ERROR_1012'), 'ErrorCode' => '1012']);
//            }
            $save_application=new Job_Applications;
            $save_application->user_id=$application_data->user_id;
            $save_application->job_id=$application_data->job_id;
            $save_application->description=$application_data->description;
            $save_application->save();
            $job = \App\Job::find($application_data->job_id);
            $user = User::find($job->user_id);
            $applicant = User::select('f_name','l_name','profile_pic','bio',DB::raw('AVG(ratings.rating) as rating'))
                    ->join('ratings', 'ratings.to_user_id', '=', 'users.id','left') 
                    ->where('users.id', $application_data->user_id)
                    ->first();
            if($user && $applicant){
                $applicant->profile_pic = profilePicPath($applicant->profile_pic);
                $applicant->rating = floatval( $applicant->rating);
                $applicant->application_description = $application_data->description;
                $applicant->job_id = $application_data->job_id; 
                $applicant->canidate_id = $application_data->user_id;
                $applicant->type = "cons_app";
                
                //Send Notification to Mobile App
                $message= "Someone applied to your job";
                $payload_data=array('user_details' => $applicant);
                pushNotification($user->plateform,$user->device_id,$message,$payload_data,$user->id);
                
                return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_200'),'Data' => $applicant]);
            }
        }else {
             return missingparameters('sendApplication');
        }
    }
    public function assignJob(){
       $responce_application_data = (json_decode(file_get_contents('php://input'))); 
       if (isset($responce_application_data->job_id) && isset($responce_application_data->canidate_id)){
                $assignjob= \App\Jobs_assigned::where(array('job_id'=>$responce_application_data->job_id))->first();
                if($assignjob){
                    return Response::json([ 'status' => 'error', 'serviceName' => 'assignJob', 'ErrorMessage' =>env('ERROR_1022'), 'ErrorCode' => '1022']);
                }
                $job=  \App\Job::find($responce_application_data->job_id);
                $job->status=1;
                $job->save();
                
                $already_applied = \App\Jobs_assigned::where('user_id',$responce_application_data->canidate_id)->where('job_id',$responce_application_data->job_id)->first();
                if($already_applied){
                    return Response::json([ 'status' => 'error', 'serviceName' => 'assignJob', 'ErrorMessage' =>env('ERROR_1013'), 'ErrorCode' => '1013']);
                }
                $savejob =new \App\Jobs_assigned;
                $savejob->job_id=$responce_application_data->job_id;
                $savejob->status=1;
                $savejob->user_id=$responce_application_data->canidate_id;
                $savejob->cancierge_status=1;
                $savejob->start_date=Carbon::now(); 
                $savejob->save();
                $user = User::find($responce_application_data->canidate_id);
                $assignedtouser = User::select('f_name','l_name','mobile_no','profile_pic','bio',DB::raw('AVG(ratings.rating) as rating'))
                        ->join('ratings', 'ratings.to_user_id', '=', 'users.id','left') 
                        ->where('users.id', $responce_application_data->user_id)
                        ->first();
                if($user && $assignedtouser){
                    $assignedtouser->profile_pic = profilePicPath($assignedtouser->profile_pic);
                    $assignedtouser->rating = floatval($assignedtouser->rating);
                    $assignedtouser->start_lat = $job->start_lat;
                    $assignedtouser->start_long = $job->start_long;
                    $assignedtouser->dest_lat = $job->dest_lat;
                    $assignedtouser->dest_long = $job->dest_long;
                    $assignedtouser->start_loc = $job->start_loc;
                    $assignedtouser->dest_loc = $job->dest_loc;
                    $assignedtouser->description = $job->description;
                    $assignedtouser->id = $responce_application_data->job_id;
                    $assignedtouser->canidate_id = $responce_application_data->canidate_id;
                    $assignedtouser->price = $job->price;
                    $assignedtouser->attachment =  filePath($job->filename);
                    $assignedtouser->type = "boss_assign_job";
                    $assignedtouser->cancierge_status = "In Progress";
                     
                    //Send Notification to Mobile App
                    $message= "Boss assigned job to you";
                    $payload_data=array('user_details' => $assignedtouser);
                    pushNotification($user->plateform,$user->device_id,$message,$payload_data,$user->id);
                    
//                    $canonical_ids_count = $result->canonical_ids;
//                    if($canonical_ids_count){
//                        $user->device_id = json_encode($result);
//                    }
                }    
                
            
            return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_201'),'Data' => '']);
       }else {
            return missingparameters('assignJob');
        }
    }
    public function conciergeCompleteJob(){
       $alldata = (json_decode(file_get_contents('php://input'))); 
       if (isset($alldata->job_id)){
            $assignjob= \App\Jobs_assigned::where(array('job_id'=>$alldata->job_id,'user_id'=>$alldata->user_id))->first();
            if($assignjob){
                $assignjob->cancierge_status=2;
                $assignjob->save();
                $completedjob = \App\Job::select('jobs.id','jobs.start_lat','jobs.start_long','jobs.dest_lat','jobs.dest_long','jobs.start_loc','jobs.dest_loc','jobs.description','jobs.completion_date','jobs.price','jobs.filename','users.f_name','users.l_name','users.mobile_no','users.profile_pic','users.bio','jobs.user_id','jobs_assigned.start_date',DB::raw('AVG(ratings.rating) as rating'))
                ->join('jobs_assigned', 'jobs.id', '=', 'jobs_assigned.job_id')
                ->join('users', 'users.id', '=', 'jobs.user_id')
                ->join('ratings', 'ratings.to_user_id', '=', 'jobs.user_id','left')        
                ->groupBy('jobs.id')
                ->where(array('jobs.id'=>$alldata->job_id,'jobs_assigned.user_id' => $alldata->user_id, 'jobs_assigned.status' => 1))
                ->first();
                if($completedjob){
                    $user = User::find($completedjob->user_id);
                    if($user){
                        $completedjob->rating = floatval($completedjob->rating);
                        $completedjob->start_lat = $completedjob->start_lat;
                        $completedjob->start_long = $completedjob->start_long;
                        $completedjob->dest_lat = $completedjob->dest_lat;
                        $completedjob->dest_long = $completedjob->dest_long;
                        $completedjob->start_loc = $completedjob->start_loc;
                        $completedjob->dest_loc = $completedjob->dest_loc;
                        $completedjob->description = $completedjob->description;
                        $completedjob->job_id = $completedjob->id;
                        $completedjob->price = $completedjob->price;
                        $completedjob->attachment =  filePath($completedjob->filename);
                        $completedjob->type = "concierge_completed_job";
                        $completedjob->start_date = strtotime($completedjob->start_date);
                        $completedjob->profile_pic = profilePicPath($completedjob->profile_pic);
                        
                        $completedjob->cancierge_status = "Concierge Completed";
                        
                        //Send Notification to Mobile App
                        $message= "Concierge Completed this job";
                        $payload_data=array('user_details' => $completedjob);
                        pushNotification($user->plateform,$user->device_id,$message,$payload_data,$user->id);
                        
                        return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_109'),'Data' => $completedjob]);
 
                    }
                    
                }else{
                    return Response::json([ 'status' => 'error', 'serviceName' => '', 'ErrorMessage' =>env('ERROR_1016'), 'ErrorCode' => '1016']);
                }

            }else{
                return Response::json([ 'status' => 'error', 'serviceName' => '', 'ErrorMessage' =>env('ERROR_1010'), 'ErrorCode' => '1010']);
            }
            
        }else {
            return missingparameters('conciergeCompleteJob');
        }
    }
    public function conciergeJobHistory(){
        $alldata = (json_decode(file_get_contents('php://input')));
        $user_id = $alldata->user_id;
        $user = User::find($user_id);
        $jobson = \App\Job::select('jobs.id','jobs.start_lat','jobs.start_long','jobs.dest_lat','jobs.dest_long','jobs.start_loc','jobs.dest_loc','jobs.description','jobs.completion_date','jobs.price','jobs.filename','jobs.created_at','users.f_name','users.l_name','users.mobile_no','users.profile_pic','users.bio','jobs.user_id','jobs_assigned.cancierge_status',DB::raw('AVG(ratings.rating) as rating'))
                ->join('jobs_assigned', 'jobs.id', '=', 'jobs_assigned.job_id')
                ->join('users', 'users.id', '=', 'jobs.user_id')
                ->join('ratings', 'ratings.to_user_id', '=', 'jobs.user_id','left')
                ->groupBy('jobs.id')
                ->where(array('jobs_assigned.user_id' => $user_id, 'jobs_assigned.status' => 1))
                ->orderBy('jobs.created_at', 'DESC')
                ->get();
        foreach ($jobson as $job){
            
            $job->rating = floatval( $job->rating);
            $job->attachment = filePath($job->filename);
            $job->type = "concierge_jobs";
            $job->canidate_id = $job->user_id;
            $job->profile_pic = profilePicPath($job->profile_pic);
            if($job->cancierge_status == 1){
                 $job->cancierge_status = "In Progress";
            }
            else {
               $job->cancierge_status = "Completed";
            }
            
            $date = new DateTime();
            $date->setTimestamp($job->completion_date);
            $date->setTimezone(new DateTimeZone(timezoneobj($user->timezone)));
            $job->completion_date = $date->format('F-d-Y');
            $job->completion_time = $date->format('h:i A');
        }
        
        $completedjobs = \App\Job::select('jobs.id','jobs.start_lat','jobs.start_long','jobs.dest_lat','jobs.dest_long','jobs.start_loc','jobs.dest_loc','jobs.description','jobs.completion_date','jobs.price','jobs.bonus','jobs_assigned.user_id','jobs.filename','jobs.created_at','users.f_name','users.l_name','users.mobile_no','users.profile_pic','users.bio','jobs.user_id',DB::raw('AVG(ratings.rating) as rating'),'jobs_assigned.jobCompleteDuration')
                ->join('jobs_assigned', 'jobs.id', '=', 'jobs_assigned.job_id')
                ->join('users', 'users.id', '=', 'jobs.user_id')
                ->join('ratings', 'ratings.to_user_id', '=', 'jobs.user_id','left')
                ->groupBy('jobs.id')
                ->where(array('jobs_assigned.user_id' => $user_id, 'jobs_assigned.status' => 2))
                ->orderBy('jobs.created_at', 'DESC')
                ->get();
        foreach ($completedjobs as $job){
            $single_rating = \App\Rating::where(array("job_id" => $job->id,"to_user_id" => $job->user_id))->first();
            if($single_rating){
                $job->single_rating = floatval($single_rating->rating);
            }  else {
                $job->single_rating = 0;
            }
            
            $job->rating = floatval( $job->rating);
            $job->bonus = intval( $job->bonus);
            $job->attachment = filePath($job->filename);
            $job->type = "concierge_jobs";
            $job->canidate_id = $job->user_id;
            $job->profile_pic = profilePicPath($job->profile_pic);
            $date = new DateTime();
            $date->setTimestamp($job->completion_date);
            $date->setTimezone(new DateTimeZone(timezoneobj($user->timezone)));
            $job->completion_date = $date->format('F-d-Y');
            $job->completion_time = $date->format('h:i A');
        }
        
        $data["jobson"] = $jobson;
        $data["completedjobs"] = $completedjobs;
        return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_104'),'Data' => $data]);
    }
    public function bossJobHistory(){
        
        $alldata = (json_decode(file_get_contents('php://input')));
        $user_id = $alldata->user_id;
        $user = User::find($user_id);
         
        $unassignedjobs = \App\Job::select('jobs.id','jobs.start_lat','jobs.start_long','jobs.dest_lat','jobs.dest_long','jobs.start_loc','jobs.dest_loc','jobs.description','jobs.completion_date','jobs.price','jobs.filename','jobs.created_at')
                ->where(array('jobs.user_id' => $user_id, 'jobs.status' => 0))
                ->orderBy('jobs.created_at', 'DESC')
                ->get();
        foreach ($unassignedjobs as $job){
            $job->attachment = filePath($job->filename);
            $job->type = "boss_assign_job";
            $date = new DateTime();
            $date->setTimestamp($job->completion_date);
            $date->setTimezone(new DateTimeZone(timezoneobj($user->timezone)));
            $job->completion_date = $date->format('F-d-Y');
            $job->completion_time = $date->format('h:i A');
        }
        
        $jobson = \App\Job::select('jobs.id','jobs.start_lat','jobs.start_long','jobs.dest_lat','jobs.dest_long','jobs.start_loc','jobs.dest_loc','jobs.description','jobs.completion_date','jobs.price','jobs.filename','jobs_assigned.start_date','jobs_assigned.user_id as canidate_id','users.id as user_id','users.f_name','users.l_name','users.mobile_no','users.profile_pic','users.bio','jobs_assigned.cancierge_status',DB::raw('AVG(ratings.rating) as rating'))
                ->join('jobs_assigned', 'jobs.id', '=', 'jobs_assigned.job_id')
                ->join('users', 'users.id', '=', 'jobs_assigned.user_id')
                ->join('ratings', 'ratings.to_user_id', '=', 'users.id','left')
                ->groupBy('jobs.id')
                ->where(array('jobs.user_id' => $user_id, 'jobs.status' => 1))
                ->where('jobs_assigned.status' , 1)
                ->orderBy('jobs.created_at', 'DESC')
                ->get();
        
        foreach ($jobson as $job){
            $job->rating = floatval( $job->rating);
            $job->attachment = filePath($job->filename);
            $job->type = "boss_assign_job";
            $job->profile_pic = profilePicPath($job->profile_pic);
            if($job->cancierge_status == 1){
                 $job->cancierge_status = "In Progress";
            }
            else {
               $job->cancierge_status = "Completed";
            }
            $date = new DateTime();
            $date->setTimestamp($job->completion_date);
            $date->setTimezone(new DateTimeZone(timezoneobj($user->timezone)));
            $job->completion_date = $date->format('F-d-Y');
            $job->completion_time = $date->format('h:i A');
            $job->start_date = strtotime($job->start_date);
            
        }
        
        $completedjobs = \App\Job::select('jobs.id','jobs.start_lat','jobs.start_long','jobs.dest_lat','jobs.dest_long','jobs.start_loc','jobs.dest_loc','jobs.description','jobs.completion_date','jobs.price','jobs.bonus','jobs.filename','jobs_assigned.jobCompleteDuration','jobs_assigned.user_id as canidate_id','jobs.created_at','users.id as user_id','users.f_name','users.l_name','users.mobile_no','users.profile_pic','users.bio',DB::raw('AVG(ratings.rating) as rating'))
                ->join('jobs_assigned', 'jobs.id', '=', 'jobs_assigned.job_id')
                ->join('users', 'users.id', '=', 'jobs_assigned.user_id')
                ->join('ratings', 'ratings.to_user_id', '=', 'users.id','left')
                ->groupBy('jobs.id')
                ->where(array('jobs.user_id' => $user_id, 'jobs_assigned.status' => 2))
                ->orderBy('jobs.created_at', 'DESC')
                ->get();
        foreach ($completedjobs as $job){
            $single_rating = \App\Rating::where(array("job_id" => $job->id,"to_user_id" => $job->user_id))->first();
            if($single_rating){
                $job->single_rating = floatval($single_rating->rating);
            }  else {
                $job->single_rating = 0;
            }
            $job->rating = floatval( $job->rating);
            $job->bonus = intval( $job->bonus);
            $job->attachment = filePath($job->filename);
            $job->type = "boss_assign_job";
            $job->profile_pic = profilePicPath($job->profile_pic);
            $date = new DateTime();
            $date->setTimestamp($job->completion_date);
            $date->setTimezone(new DateTimeZone(timezoneobj($user->timezone)));
            $job->completion_date = $date->format('F-d-Y');
            $job->completion_time = $date->format('h:i A');
            
        }
        
        $data["unassignedjobs"] = $unassignedjobs;
        $data["jobson"] = $jobson;
        $data["completedjobs"] = $completedjobs;
        return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_104'),'Data' => $data]);
    }
    
    
    public function bossCompleteJob(){
       
       $boss_success_data = (json_decode(file_get_contents('php://input'))); 
       if (isset($boss_success_data->job_id) && isset($boss_success_data->payment_method)){
            $assignjob= \App\Jobs_assigned::where(array('job_id'=>$boss_success_data->job_id))->first();
            if($assignjob){
                if($assignjob->status != 2){
                    $cmpjob = \App\Job::find($boss_success_data->job_id);
                    if(isset($boss_success_data->bonus) && $boss_success_data->bonus >= 0){
                        $cmpjob->bonus = $boss_success_data->bonus;
                        $cmpjob->save();
                    }
                    $payable_amount = ($cmpjob->price + $cmpjob->bonus);
                    $erge_fee = $payable_amount * env('ERGE_FEE');
                    $concierge_amount = $payable_amount - $erge_fee;
                    $concierge_user = User::find($assignjob->user_id);
                    $boss_user = User::find($boss_success_data->user_id);
                    if($boss_success_data->payment_method == "creditcard"){
                        \Stripe\Stripe::setApiKey(env('APIKEY'));
                        $payment_status = $boss_user->charge($payable_amount*100, [
                            'source' => $boss_success_data->stripe_token,
                            'receipt_email' => $boss_user->email,
                        ]);
                        if($payment_status && $payment_status->status == "succeeded"){
                            $transaction = new\App\Transaction;
                            $transaction->from_user_id = $boss_success_data->user_id;
                            $transaction->to_user_id = $assignjob->user_id;
                            $transaction->job_id = $boss_success_data->job_id;
                            $transaction->payment_method = "creditCard";
                            $transaction->transaction_id = $payment_status->id;
                            $transaction->amount = $concierge_amount;
                            $transaction->erge_fee = $erge_fee;
                            $transaction->transaction_type = 1;
                            $transaction->status = 1;
                            $transaction->payment_response = json_encode($payment_status);
                            $transaction->save();
                            $concierge_user->current_balance = $concierge_user->current_balance + $concierge_amount;
                            $concierge_user->save();
                            
                            $emaildata = array('to' => $boss_user->email, 'to_name' => 'Dear User');
                            $data['f_name'] = $boss_user->f_name;
                            $data['l_name'] = $boss_user->l_name;
                            $data['amount'] = $payable_amount;
                            \Illuminate\Support\Facades\Mail::send('transaction_boss', $data, function($message) use ($emaildata) {
                                $message->to($emaildata['to'], $emaildata['to_name'])
                                        ->from('no-reply@erge.com', 'Erge')
                                        ->subject('You have paid for service on Erge using creditcard');
                            });
                            $emaildata = array('to' => $concierge_user->email, 'to_name' => 'Dear User');
                            $data['f_name'] = $concierge_user->f_name;
                            $data['l_name'] = $concierge_user->l_name;
                            $data['amount'] = $concierge_amount;
                            \Illuminate\Support\Facades\Mail::send('transaction_con', $data, function($message) use ($emaildata) {
                                $message->to($emaildata['to'], $emaildata['to_name'])
                                        ->from('no-reply@erge.com', 'Erge')
                                        ->subject('You have recieved payment for your service');
                            });
                            
                        }  else {
                            return Response::json([ 'status' => 'error', 'serviceName' => '', 'ErrorMessage' =>env('ERROR_1017'), 'ErrorCode' => '1017']);
                        }
                    }elseif ($boss_success_data->payment_method == "paypal") {
                        $transaction = new\App\Transaction;
                        $transaction->from_user_id = $boss_success_data->user_id;
                        $transaction->to_user_id = $assignjob->user_id;
                        $transaction->job_id = $boss_success_data->job_id;
                        $transaction->payment_method = "paypal";
                        $transaction->transaction_id = $boss_success_data->payPalTransectionId;
                        $transaction->amount = $concierge_amount;
                        $transaction->erge_fee = $erge_fee;
                        $transaction->transaction_type = 1;
                        $transaction->status = 0;
                        $transaction->save();
                        $concierge_user->current_balance = $concierge_user->current_balance + $concierge_amount;
                        $concierge_user->save();
                        $emaildata = array('to' => $boss_user->email, 'to_name' => 'Dear User');
                        $data['f_name'] = $boss_user->f_name;
                        $data['l_name'] = $boss_user->l_name;
                        $data['amount'] = $payable_amount;
                        \Illuminate\Support\Facades\Mail::send('transaction_boss', $data, function($message) use ($emaildata) {
                            $message->to($emaildata['to'], $emaildata['to_name'])
                                    ->from('no-reply@erge.com', 'Erge')
                                    ->subject('You have paid for service on Erge using paypal');
                        });
                        $emaildata = array('to' => $concierge_user->email, 'to_name' => 'Dear User');
                        $data['f_name'] = $concierge_user->f_name;
                        $data['l_name'] = $concierge_user->l_name;
                        $data['amount'] = $concierge_amount;
                        \Illuminate\Support\Facades\Mail::send('transaction_con', $data, function($message) use ($emaildata) {
                            $message->to($emaildata['to'], $emaildata['to_name'])
                                    ->from('no-reply@erge.com', 'Erge')
                                    ->subject('You have recieved payment for your service');
                        });
                    }else{
                        return Response::json([ 'status' => 'error', 'serviceName' => '', 'ErrorMessage' =>env('ERROR_1018'), 'ErrorCode' => '1018']);
                    }

                    $jobCompleteDuration = "";
                    if(isset($boss_success_data->jobCompleteDuration)){
                        $jobCompleteDuration = $boss_success_data->jobCompleteDuration;
                    }
                    

                    if(isset($boss_success_data->rating)){
                        $rating = new \App\Rating;
                        $rating->from_user_id = $boss_success_data->user_id;
                        $rating->to_user_id = $assignjob->user_id;
                        $rating->job_id = $boss_success_data->job_id;
                        $rating->rating = $boss_success_data->rating;
                        $rating->save();
                    }
                    

                    $assignjob->status=2;
                    $assignjob->end_date=Carbon::now();
                    $assignjob->jobCompleteDuration = $jobCompleteDuration;
                    $assignjob->save();
                    
                    $completedjob = \App\Job::select('jobs.id','jobs.start_lat','jobs.start_long','jobs.dest_lat','jobs.dest_long','jobs.start_loc','jobs.dest_loc','jobs.description','jobs.completion_date','jobs.price','jobs.bonus','jobs.filename','users.f_name','users.l_name','users.mobile_no','users.profile_pic','users.bio','jobs_assigned.user_id','jobs_assigned.jobCompleteDuration','jobs_assigned.created_at',DB::raw('AVG(ratings.rating) as rating'))
                    ->join('jobs_assigned', 'jobs.id', '=', 'jobs_assigned.job_id')
                    ->join('users', 'users.id', '=', 'jobs.user_id')
                    ->join('ratings', 'ratings.to_user_id', '=', 'jobs.user_id','left')        
                    ->groupBy('jobs.id')
                    ->where(array('jobs.id'=>$boss_success_data->job_id, 'jobs_assigned.status' => 2))
                    ->first();

                    $user = User::find($completedjob->user_id);
                    if($completedjob && $user){
                        $completedjob->rating = floatval( $completedjob->rating);
                        $completedjob->bonus = intval( $completedjob->bonus);
                        $completedjob->start_lat = $completedjob->start_lat;
                        $completedjob->start_long = $completedjob->start_long;
                        $completedjob->dest_lat = $completedjob->dest_lat;
                        $completedjob->dest_long = $completedjob->dest_long;
                        $completedjob->start_loc = $completedjob->start_loc;
                        $completedjob->dest_loc = $completedjob->dest_loc;
                        $completedjob->description = $completedjob->description;
                        $completedjob->job_id = $completedjob->id;
                        $completedjob->price = $completedjob->price;
                        $completedjob->attachment =  filePath($completedjob->filename);
                        $completedjob->type = "boss_completed_job";
                        $completedjob->profile_pic = profilePicPath($completedjob->profile_pic);
                        $date = new DateTime();
                        $date->setTimestamp($completedjob->completion_date);
                        $date->setTimezone(new DateTimeZone(timezoneobj($user->timezone)));
                        $completedjob->completion_date = $date->format('F-d-Y');
                        $completedjob->completion_time = $date->format('h:i A');
                        $start_date = new DateTime($completedjob->created_at);
                        $end_date = new DateTime(date('Y-m-d H:i:s'));

                        $dd = date_diff($end_date, $start_date);
                        $completedjob->duration = $dd->d . "Days ".$dd->h . "Hours ".$dd->i . "Minutes";
                        
                        //Send Notification to Mobile App
                        $message= "Boss Completed this job";
                        $payload_data=array('user_details' => $completedjob);
                        pushNotification($user->plateform,$user->device_id,$message,$payload_data,$user->id);
                        
                        return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_108'),'Data' => '']);

                    }
                }else{
                    return Response::json([ 'status' => 'error', 'serviceName' => '', 'ErrorMessage' =>env('ERROR_1015'), 'ErrorCode' => '1015']);
                }
                    
            }else{
                return Response::json([ 'status' => 'error', 'serviceName' => '', 'ErrorMessage' =>env('ERROR_1010'), 'ErrorCode' => '1010']);
            }
            return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_202'),'Data' => '']);
        }else {
            return missingparameters('bossCompleteJob');
        }
        
    }
    

    public function consciergeRating(){
       $alldata = (json_decode(file_get_contents('php://input'))); 
       if (isset($alldata->rating) && isset($alldata->job_id)){
           $job = \App\Job::find($alldata->job_id);
           if($job){
               $rating = new \App\Rating;
                $rating->from_user_id = $alldata->user_id;
                $rating->to_user_id = $job->user_id;
                $rating->rating = $alldata->rating;
                $rating->save();
                return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_110'),'Data' => ""]);
           }
           
       }else {
            return missingparameters('consciergeRating');
        }
        
    }
}

