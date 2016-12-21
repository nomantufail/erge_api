<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\RequestPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
class UserController extends Controller
{
    public function editProfile() {
        $alldata = (json_decode(file_get_contents('php://input')));
        if (isset($alldata->f_name) && isset($alldata->l_name) && isset($alldata->mobile) && isset($alldata->bio) && isset($alldata->lat) && isset($alldata->long)) {
            $user = \App\User::find($alldata->user_id);
            $user->f_name = $alldata->f_name;
            $user->l_name = $alldata->l_name;
            $user->mobile_no = $alldata->mobile;
            $user->lat = $alldata->lat;
            $user->long = $alldata->long;
            $user->bio = $alldata->bio;
            $user->save();
            
            return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_105'), 'Data' => '']);
        } else {
            return missingparameters('register');
        }
    }
    public function changePassword() {
        $alldata = (json_decode(file_get_contents('php://input')));
        if (isset($alldata->oldpassword) && isset($alldata->password)) {
            if (Auth::attempt(['password' => $alldata->oldpassword, 'id' => $alldata->user_id ])) {
                $user = \App\User::find($alldata->user_id);
                $user->password = bcrypt($alldata->password);
                $user->save();
                return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_107'), 'Data' => '']);
            }
            else {
                return Response::json([ 'status' => 'error', 'serviceName' => 'changePassword', 'ErrorMessage' =>env('ERROR_1011'), 'ErrorCode' => '1011']);
            }
            
        } else {
            return missingparameters('register');
        }
    }
    public function viewProfile() {
        $alldata = (json_decode(file_get_contents('php://input')));
        $user = \App\User::select('f_name','l_name','mobile_no','lat','long','bio','profile_pic')->find($alldata->user_id);
        $user->profile_pic = profilePicPath($user->profile_pic);
        
        return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_105'), 'Data' => $user]);
        
    }
    public function editProfilePic() {
        $alldata = $_POST;
        if (isset($_FILES['file']['name'])) {
            if (Input::file('file')->isValid()) {
                $destinationPath = public_path(env('FOLDER_PROFILE_PIC')); // upload path
                $extension = Input::file('file')->getClientOriginalExtension(); // getting image extension
                $extension  = strtolower($extension);
                $fileName = uniqid() . sha1(time()) . '.' . $extension; // renameing image
                Input::file('file')->move($destinationPath, $fileName);
                $user = \App\User::find($alldata["user_id"]);
                $user->profile_pic = $fileName;
                $user->save();
                $profile_pic_path = profilePicPath($fileName);
                return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_105'), 'Data' => $profile_pic_path]);
            }
        }
    }  
    public function updateDeviceId() {
        $alldata = (json_decode(file_get_contents('php://input')));
    }
    public function fetchApplicants(){
        $alldata = (json_decode(file_get_contents('php://input')));
        if (isset($alldata->job_id)) {
            $job = \App\Job::find($alldata->job_id);
            $user = User::find($alldata->user_id);
            $applicants = DB::select( DB::raw("SELECT users.id as canidate_id,f_name,l_name,profile_pic,bio,job_applications.job_id,job_applications.description as application_description,job_applications.created_at FROM job_applications inner join users on users.id = job_applications.user_id WHERE job_applications.id IN ( SELECT MAX(id) FROM job_applications WHERE job_id = :job_id_var AND job_applications.created_at >= :filter_date GROUP BY user_id )"), array(
                        'job_id_var' => $alldata->job_id,
                        'filter_date' => Carbon::now()->subMinutes(10)
                      ));
            
            foreach($applicants as $applicant){
                $applicant->profile_pic = profilePicPath($applicant->profile_pic);
                $date = new \DateTime($applicant->created_at);
                //$date->setTimestamp($applicant->created_at);
                $date->setTimezone(new \DateTimeZone(timezoneobj($user->timezone)));
                $applicant->created_at = $date->format('F-d-Y h:i A');
                
                $ratings = \App\Rating::where("to_user_id",$applicant->canidate_id)->get();
                $rating_num = 0;
                foreach ($ratings as $rating){
                    $rating_num += $rating->rating;
                }
                $ratingcount = count($ratings);
                if($ratingcount > 0)
                $rating_num = $rating_num / $ratingcount;
                $applicant->rating = $rating_num;
            }
            return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_111'),'Data' => $applicants]);
            
        } else {
            return missingparameters('fetchApplicants');
        }
    }
    public function requestPayment(){
        $alldata = (json_decode(file_get_contents('php://input'))); 
        $user = User::find($alldata->user_id);
        $requested_amount = ($alldata->requested_amount);
        if (Auth::attempt(['password' => $alldata->password, 'id' => $alldata->user_id ])) {
            if($alldata->paypal_email){
                if(isset($alldata->requested_amount) && ($requested_amount)>0 && ($requested_amount) < $user->current_balance){
                    $user->paypal_email = $alldata->paypal_email;
                    $user->current_balance = $user->current_balance - $requested_amount;
                    $user->save();
                    $requestPayment = new RequestPayment;
                    $requestPayment->user_id = $alldata->user_id;
                    $requestPayment->requested_amount = $requested_amount;
                    $requestPayment->status = 0;
                    $requestPayment->save();
                    $emaildata = array('to' => $user->email, 'to_name' => 'Dear User');
                    $data['f_name'] = $user->f_name;
                    $data['l_name'] = $user->l_name;
                    $data['requested_amount'] = $requested_amount;
                    $data['paypal_email'] = $user->paypal_email;
                    \Illuminate\Support\Facades\Mail::send('withdrawal_request', $data, function($message) use ($emaildata) {
                        $message->to($emaildata['to'], $emaildata['to_name'])
                                ->from('no-reply@erge.com', 'Erge')
                                ->subject('We received your withdrawal request');
                    });
                    
                    return Response::json([ 'status' => 'success', 'SuccessMessage' => env('SUCCESS_112'),'Data' => ['current_balance' => $user->current_balance,'requested_amount' => $requested_amount ]]);
                }else {
                    return Response::json([ 'status' => 'error', 'serviceName' => 'requestPayment', 'ErrorMessage' =>env('ERROR_1020'), 'ErrorCode' => '1020']);
                }
            } else {
                return Response::json([ 'status' => 'error', 'serviceName' => 'requestPayment', 'ErrorMessage' =>env('ERROR_1019'), 'ErrorCode' => '1019']);
            }
        }else{
            return Response::json([ 'status' => 'error', 'serviceName' => 'login', 'ErrorMessage' => env('ERROR_1021'), 'ErrorCode' => '1021']);  
        }
    }
    public function savepaypalinfo(){
        //$data = $_POST;
        if($_POST["payment_status"] == "Completed"){
            $data = json_encode($_POST);
            Storage::disk('local')->put('file.txt', $data);
            $transaction = \App\Transaction::where('transaction_id',$_POST["txn_id"])->first();
            if($transaction){
                $transaction->status = 1;
                $transaction->payment_response = $data;
                $transaction->save();
            }  else {
                $custom_data = json_decode($_POST["custom"]);
                $assignjob = \App\Jobs_assigned::where(array('job_id'=>$custom_data->job_id))->first();
                $assignjob->status=2;
                $assignjob->end_date=Carbon::now();
                $assignjob->jobCompleteDuration = $custom_data->jobCompleteDuration;
                $assignjob->save();
                
                $cmpjob = \App\Job::find($custom_data->job_id);
                $cmpjob->bonus = $custom_data->bonus;
                $cmpjob->save();
                
                

                $rating = new \App\Rating;
                $rating->from_user_id = $custom_data->user_id;
                $rating->to_user_id = $assignjob->user_id;
                $rating->rating = $custom_data->rating;
                $rating->save();
                
                $payable_amount = $_POST["payment_gross"];
                $erge_fee = $payable_amount * env('ERGE_FEE');
                $concierge_amount = $payable_amount - $erge_fee;
                $concierge_user = User::find($assignjob->user_id);
                $transaction = new\App\Transaction;
                $transaction->from_user_id = $custom_data->user_id;
                $transaction->to_user_id = $assignjob->user_id;
                $transaction->job_id = $custom_data->job_id;
                $transaction->payment_method = "paypal";
                $transaction->transaction_id = $_POST["txn_id"];
                $transaction->amount = $concierge_amount;
                $transaction->erge_fee = $erge_fee;
                $transaction->transaction_type = 1;
                $transaction->status = 1;
                $transaction->payment_response = $data;
                $transaction->save();
                $concierge_user->current_balance = $concierge_user->current_balance + $concierge_amount;
                $concierge_user->save();
            }
        }
        
        
    }
}
