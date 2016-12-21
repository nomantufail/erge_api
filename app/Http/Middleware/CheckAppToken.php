<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Response;
class CheckAppToken {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {

        $sessiondata = (json_decode(file_get_contents('php://input')));
//       print_r($sessiondata);
//       print_r($_POST);
//       exit;
        set_time_limit(60);
        if (isset($sessiondata->appToken)) {
            if ($sessiondata->appToken != env('APP_KEY')) {
                return Response::json([ 'status' => 'error', 'serviceName' => '', 'ErrorMessage' =>env('ERROR_1001'), 'ErrorCode' => '1001']);
            }
        } elseif (isset($_POST['appToken'])) {
            if ($_POST['appToken'] != env('APP_KEY')) {
                return Response::json([ 'status' => 'error', 'serviceName' => '', 'ErrorMessage' =>env('ERROR_1001'), 'ErrorCode' => '1001']);
            }
        } else {
            return Response::json([ 'status' => 'error', 'serviceName' => '', 'ErrorMessage' =>env('ERROR_1001'), 'ErrorCode' => '1001']);
        }
        return $next($request);
    }

}
