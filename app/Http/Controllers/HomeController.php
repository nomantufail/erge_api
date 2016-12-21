<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
class HomeController extends Controller
{
    public function conciergeHomePage(){
        $alldata = (json_decode(file_get_contents('php://input')));
        $user_id = $alldata->user_id;
        $filter = "all";
        if(isset($alldata->filter)){
            if($alldata->filter == "pasthour"){
                $filter = "pasthour";
                $cmpdate = Carbon::now()->subMinutes(60);
                $interval = 60;
            } elseif($alldata->filter == "past6hours") {
                $filter = "past6hours";
                $cmpdate = Carbon::now()->subMinutes(360);
            }elseif ($alldata->filter == "today") {
                $filter = "today";
                $cmpdate = Carbon::today();
            }
            
        }
        $user = \App\User::find($user_id);
        $point1_lat = $user->lat;
        $point1_lng = $user->long;
        if($filter == "all"){
            $jobs = \App\Job::select('users.f_name','users.current_balance','users.profile_pic','users.mobile_no','email','role','lat','long','bio','job_applications.user_id as application','jobs.*',\Illuminate\Support\Facades\DB::raw('AVG(ratings.rating) as rating'))
                ->join('users', 'users.id', '=', 'jobs.user_id','left')
                ->join('ratings', 'ratings.to_user_id', '=', 'jobs.user_id','left')
                ->join('job_applications','jobs.id','=','job_applications.job_id','left')
                ->where('jobs.status',0)
                ->groupBy('jobs.id')
                ->get();
        }  else {
            $jobs = \App\Job::select('users.f_name','users.current_balance','users.profile_pic','users.mobile_no','email','role','lat','long','bio','job_applications.user_id as application','jobs.*',\Illuminate\Support\Facades\DB::raw('AVG(ratings.rating) as rating'))
                ->join('users', 'users.id', '=', 'jobs.user_id','left')
                ->join('ratings', 'ratings.to_user_id', '=', 'jobs.user_id','left')
                ->join('job_applications','jobs.id','=','job_applications.job_id','left')
                ->where('jobs.status',0)
                ->where('jobs.created_at', '>=', $cmpdate)
                ->groupBy('jobs.id')
                ->get();
        }
        
        
        $jobscollection = array();
        foreach ($jobs as $jobdata) {
          /*  $range = $jobdata->visibility_radius;  
           // Find Max - Min Lat / Long for Radius and zero point and query  
           $latitude = $point1_lat;
           $longitude = $point1_lng;
           $lat_range = $range/69.172;  
           $lon_range = abs($range/(cos($latitude) * 69.172));  
           $min_lat = number_format($latitude - $lat_range, "4", ".", "");  
           $max_lat = number_format($latitude + $lat_range, "4", ".", "");  
           $min_lon = number_format($longitude - $lon_range, "4", ".", "");  
           $max_lon = number_format($longitude + $lon_range, "4", ".", "");
            $point2_lat = $jobdata->start_lat;
            $point2_lng = $jobdata->start_long;
           if(($min_lat < $point2_lat) && ($point2_lat < $max_lat) && $min_lon < $point2_lng && $point2_lng < $max_lon){
               $jobscollection[] = $jobdata;
           }*/
            
            $point2_lat = $jobdata->start_lat;
            $point2_lng = $jobdata->start_long;
            $theta = $point1_lng - $point2_lng;
            $miles = (sin(deg2rad($point1_lat)) * sin(deg2rad($point2_lat))) + (cos(deg2rad($point1_lat)) * cos(deg2rad($point2_lat)) * cos(deg2rad($theta)));
            $miles = acos($miles);
            $miles = rad2deg($miles);
            $miles = $miles * 60 * 1.1515;
            if ($miles <= $jobdata->visibility_radius) {
                $jobdata->profile_pic = profilePicPath($jobdata->profile_pic);
                $jobdata->attachment = filePath($jobdata->filename);
                $date = new \DateTime();
                $date->setTimestamp($jobdata->completion_date);
                $date->setTimezone(new \DateTimeZone(timezoneobj($user->timezone)));
                $jobdata->completion_date = $date->format('F-d-Y');
                $jobdata->completion_time = $date->format('h:i A');
                $jobdata->applied = 0;
                $jobdata->rating = floatval( $jobdata->rating);
                if($jobdata->application == $user_id) 
                    $jobdata->applied = 1;
                $jobscollection[] = $jobdata;
            }
            
            
        }
        $data["jobs"] = $jobscollection;
        $request_payment = \App\RequestPayment::where("user_id",$user_id)
                ->where("status",0)
                ->orderBy('created_at', 'DESC')
                ->first();
        $data["current_balance"] = floatval( $user->current_balance);
        if($request_payment){
            $data["requested_amount"] = floatval($request_payment->requested_amount);
        }else
        {
            $data["requested_amount"] = 0;
        }
        return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_102'),'Data' => $data]);
    }
    public function bossHomePage(){
        $alldata = (json_decode(file_get_contents('php://input')));
        $user_id = $alldata->user_id;
        $user = \App\User::find($user_id);
        $unassignedjobs = \App\Job::select('jobs.id','jobs.start_lat','jobs.start_long','jobs.dest_lat','jobs.dest_long','jobs.start_loc','jobs.dest_loc','jobs.visibility_radius','jobs.description','jobs.completion_date','jobs.price','jobs.filename','jobs.created_at')
                ->where(array('jobs.user_id' => $user_id, 'jobs.status' => 0))
                ->orderBy('jobs.created_at', 'DESC')
                ->get();
        foreach ($unassignedjobs as $job){
            $job->attachment = filePath($job->filename);
            $job->type = "boss_assign_job";
            $date = new \DateTime();
            $date->setTimestamp($job->completion_date);
            $date->setTimezone(new \DateTimeZone(timezoneobj($user->timezone)));
            $job->completion_date = $date->format('F-d-Y');
            $job->completion_time = $date->format('h:i A');
        }
        
        return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_113'),'Data' => $unassignedjobs]);
    }
    public function changeLocation(){
        $alldata = (json_decode(file_get_contents('php://input')));
        if(isset($alldata->lat) && isset($alldata->long)){
            $user_id = $alldata->user_id;
            $user = \App\User::find($user_id);
            $user->lat = $alldata->lat;
            $user->long = $alldata->long;
            $user->save();
            return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_103'),'Data' => '']);
        } else {
            return missingparameters('changeLocation');
        }
        
    }
 }
