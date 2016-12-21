<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Response;
class SessionCheck
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
       $sessiondata = (json_decode(file_get_contents('php://input')));
        if(isset($sessiondata->sessionToken)&& isset($sessiondata->user_id )){ 
            $user= \App\User::where(array('id'=>$sessiondata->user_id,'sessiontoken'=>$sessiondata->sessionToken))->first();
            if( ! $user){
                return Response::json([ 'status' => 'error', 'serviceName' => '', 'ErrorMessage' => env('ERROR_1000'), 'ErrorCode' => '1000']);
            }
        }
        elseif($_POST && isset($_POST['user_id']) && isset ($_POST['sessionToken'])){
            $user= \App\User::where(array('id'=>$_POST['user_id'],'sessiontoken'=>$_POST['sessionToken']))->first();
            if( ! $user){
                return Response::json([ 'status' => 'error', 'serviceName' => '', 'ErrorMessage' => env('ERROR_1000'), 'ErrorCode' => '1000']);
            }  
       }
       else{
          return Response::json([ 'status' => 'error', 'serviceName' => '', 'ErrorMessage' => env('ERROR_1000'), 'ErrorCode' => '1000']);
       }
        return $next($request);
    }
}
