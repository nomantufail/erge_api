<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use \Response;
use \Hash;
use Carbon\Carbon;
use \DB;
use \Input;
use App\User;
use App\RegistrationCode;

class AuthController extends Controller {

    public function register() {
        $registerdata = (json_decode(file_get_contents('php://input')));
        if (isset($registerdata->email) && isset($registerdata->f_name) && isset($registerdata->l_name) && isset($registerdata->mobile) && isset($registerdata->password) && isset($registerdata->code) && isset($registerdata->role) && isset($registerdata->lat) && isset($registerdata->long) && isset($registerdata->plateform) && isset($registerdata->device_id)) {
//            $confirmcode = RegistrationCode::where(array('email' => $registerdata->email, 'code' => $registerdata->code))->first();
//            if (!$confirmcode) {
//                return Response::json([ 'status' => 'error', 'serviceName' => 'register', 'ErrorMessage' => env('ERROR_1005'), 'ErrorCode' => '1005']);
//            } else {
                RegistrationCode::where('email',$registerdata->email)->delete();
                $olduser = User::where('email',$registerdata->email)->first();
                if(!$olduser){
                    $user = new User;
                    $user->f_name = $registerdata->f_name;
                    $user->l_name = $registerdata->l_name;
                    $user->mobile_no = $registerdata->mobile;
                    $user->email = $registerdata->email;
                    $user->role = $registerdata->role;
                    $user->lat = $registerdata->lat;
                    $user->long = $registerdata->long;
                    $user->plateform = $registerdata->plateform;
                    $user->device_id = $registerdata->device_id;
                    $user->password = bcrypt($registerdata->password);
                    $user->sessiontoken = md5(Hash::make($registerdata->email) . Hash::make(time()));
                    $user->save();
                    $user = User::select('users.id','f_name','l_name','mobile_no','email','role','lat','long','plateform','device_id','profile_pic','bio','sessiontoken',DB::raw('AVG(ratings.rating) as rating'))
                            ->join('ratings', 'ratings.to_user_id', '=', 'users.id','left') 
                            ->where('users.id',$user->id)
                            ->first();
                    $user->profile_pic = profilePicPath($user->profile_pic); 
                    $user->rating = floatval( $user->rating); 
                    return Response::json([ 'status' => 'success', 'SuccessMessage' => 'User Created Successfully', 'Data' => $user ]);
 
                }  else {
                    return Response::json([ 'status' => 'error', 'serviceName' => 'verifyEmail', 'ErrorMessage' => env('ERROR_1004'), 'ErrorCode' => '1004']);
                }
                
            }
        return missingparameters('register');
//        } else {
//            return missingparameters('register');
//        }
    }

    public function verifyEmail() {
        try{
            set_time_limit(60);
            $alldata = (json_decode(file_get_contents('php://input')));

            if (isset($alldata->email)) {
                $user = User::where('email', $alldata->email)->first();
                if (!$user) {
                    $token = \Illuminate\Support\Str::random(4);
                    $savecode = RegistrationCode::find($alldata->email);
                    if(!$savecode){
                        $savecode = new RegistrationCode;
                    }

                    $savecode->email = $alldata->email;
                    $savecode->code = $token;
                    $savecode->save();

                    $data['token'] = $token;
                    $emaildata = array('to' => $alldata->email, 'to_name' => 'Dear User');

                    \Illuminate\Support\Facades\Mail::send('emailsendcode', $data, function($message) use ($emaildata) {
                        $message->to($emaildata['to'], $emaildata['to_name'])
                            ->from('no-reply@erge.com', 'Erge')
                            ->subject('Your Code');
                    });

                    return Response::json([ 'status' => 'success', 'SuccessMessage' => 'Code Emailed SuccessFully', 'code'=>$token]);
                } else {
                    return Response::json([ 'status' => 'error', 'serviceName' => 'verifyEmail', 'ErrorMessage' => env('ERROR_1004'), 'ErrorCode' => '1004']);
                }
            } else {
                return missingparameters('verifyEmail');
            }
        }catch (\Exception $e){
            return Response::json($e->getMessage());
        }
    }

    public function forgetPass() {
        $email = (json_decode(file_get_contents('php://input')));
        if (isset($email->email)) {
            $user = User::where('email', $email->email)->first();
            if ($user) {
                $token = \Illuminate\Support\Str::random(8);
                $user->password = bcrypt($token);
                $user->save();
                $data['token'] = $token;
                $alldata = array('to' => $email->email, 'to_name' => 'Dear User');
                \Illuminate\Support\Facades\Mail::send('emailsendpass', $data, function($message) use ($alldata) {
                    $message->to($alldata['to'], $alldata['to_name'])
                            ->from('no-reply@erge.com', 'Erge')
                            ->subject('Your Code');
                });
                return Response::json([ 'status' => 'success', 'SuccessMessage' => 'New Password Emailed']);
            } else {
                return Response::json([ 'status' => 'error', 'serviceName' => 'verifyEmail', 'ErrorMessage' => env('ERROR_1006'), 'ErrorCode' => '1006']);
            }
        } else {
            return missingparameters('verifyEmail');
        }
    }

    public function savePayment() {
        $carddata = (json_decode(file_get_contents('php://input')));
        if (isset($carddata->user_id) && isset($carddata->card_number) && isset($carddata->exp_date) && isset($carddata->cvc) && isset($carddata->payment_method) && isset($carddata->stripe_token) && isset($carddata->plateform) &&isset($carddata->device_id) && isset($carddata->lat) && isset($carddata->long)) {
            $saveCardData = new \App\Paymentinfo;
            $saveCardData->user_id = $carddata->user_id;
            $saveCardData->card_no = $carddata->card_number;
            $saveCardData->exp_date = $carddata->exp_date;
            $saveCardData->cvc = $carddata->cvc;
            $saveCardData->payment_method = $carddata->payment_method;
            $saveCardData->save();
            $subscribeuser = User::find($carddata->user_id);
            $subscribeuser->lat = $carddata->lat;
            $subscribeuser->long = $carddata->long;
            $subscribeuser->plateform = $carddata->plateform;
            $subscribeuser->device_id = $carddata->device_id;
            $subscribeuser->sessiontoken = md5(Hash::make($subscribeuser->id) . Hash::make(time()));
            
            
            
            \Stripe\Stripe::setApiKey(env('APIKEY'));
            $token = $carddata->stripe_token;
            
           
            //$user->newSubscription('main', 'main')->create($saveCardData->stripe_token);
            if ($subscribeuser->subscribed('main')) {
                $subscribeuser->subscription('main')->cancel();
                $subscribeuser->newSubscription('main', 'main')->create($token);
            }else{
                $subscribeuser->newSubscription('main', 'main')->create($token);
            }
            $subscribeuser->save();
            $user = User::select('id','f_name','l_name','mobile_no','email','role','lat','long','plateform','device_id','profile_pic','bio','sessiontoken')->where('id',$carddata->user_id)->first();
            $user->profile_pic = profilePicPath($user->profile_pic); 
            $rating_num = 0;
            $ratings = \App\Rating::where("to_user_id",$user->id)->get();
            foreach ($ratings as $rating){
                $rating_num += $rating->rating;
            }
            $ratingcount = count($ratings);
            if($ratingcount > 0)
            $rating_num = $rating_num / $ratingcount;
            $user->rating = $rating_num;  
            return Response::json([ 'status' => 'success', 'SuccessMessage' => 'Payment Info Has been saved','Data'=> $user]); 
        } else {
            return missingparameters('savePayment');
        }
    }
    public function updateDeviceId(){
        $alldata = (json_decode(file_get_contents('php://input')));
        
        if (isset($alldata->device_id)) {
            $user = User::find($alldata->user_id);
            $user->device_id = $alldata->device_id;
            $user->save();
            return Response::json([ 'status' => 'success', 'SuccessMessage' => 'Device id updated successfully','Data' => '']);
        }  else {
            return missingparameters('updateDeviceId');
        }
    }
    public function login(){
         $loginData = (json_decode(file_get_contents('php://input')));
        if (isset($loginData->email) && isset($loginData->password) && isset($loginData->lat) && isset($loginData->long) && isset($loginData->plateform) && isset($loginData->device_id) && isset($loginData->timezone)) {
            $isblocked = User::where(array('email' => $loginData->email,'block' => 1))->first();
            if($isblocked){
                return Response::json([ 'status' => 'error', 'serviceName' => 'login', 'ErrorMessage' => env('ERROR_1023'), 'ErrorCode' => '1023']);  
            }  else {
                if (Auth::attempt(['password' => $loginData->password, 'email' => $loginData->email ])) {
                    $user = User::find(Auth::user()->id);
                    $user->sessiontoken = md5(Hash::make(Auth::user()->id) . Hash::make(time()));
                    $user->plateform = $loginData->plateform;
                    $user->device_id = $loginData->device_id;
                    $user->timezone = $loginData->timezone;
                    $user->lat = $loginData->lat;
                    $user->long = $loginData->long;
                    $user->save();
                    $newuser = User::select('users.id','f_name','l_name','mobile_no','current_balance','email','role','lat','long','plateform','device_id','profile_pic','bio','sessiontoken',DB::raw('AVG(ratings.rating) as rating'))
                            ->join('ratings', 'ratings.to_user_id', '=', 'users.id','left') 
                            ->where('users.id',Auth::user()->id)
                            ->first();
                    $newuser->profile_pic = profilePicPath($newuser->profile_pic);
                    $newuser->rating = floatval( $newuser->rating);  
                    $newuser->current_balance = floatval( $newuser->current_balance);  

                    return Response::json([ 'status' => 'success', 'SuccessMessage' => 'Login Successfull','Data' => $newuser]);       

                }else{
                  return Response::json([ 'status' => 'error', 'serviceName' => 'login', 'ErrorMessage' => env('ERROR_1007'), 'ErrorCode' => '1007']);  
                }
            }
            
        } else {
            return missingparameters('login');
        }  
    }
    public function logout() {
        $alldata = (json_decode(file_get_contents('php://input')));
        if (isset($alldata->user_id)) {
                $user = User::find($alldata->user_id);
                $user->sessiontoken ="";
                $user->device_id ="";
                $user->save();
                Auth::logout();
                return Response::json([ 'status' => 'success', 'successMessage' => env('SUCCESS_101'), 'Success_Token' => '101']);
            
        } else {
            return missingparameters('logout');
        }
    }

}
