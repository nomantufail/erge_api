<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;


class Headersmid {

 /**
  * Handle an incoming request.
  *
  * @param  \Illuminate\Http\Request  $request
  * @param  \Closure  $next
  * @return mixed
  */
//  public function handle($request, Closure $next)
// {  
//  $content = $next($request);
//  return ( new Response($content) )->header("Content-Type: application/json", true)
//                                    ->header('Access-Control-Allow-Credentials: true')
//                                     ->header("Access-Control-Allow-Origin: *")
//                                     ->header("Access-Control-Allow-Headers: x-prototype-version,X-Requested-With")
//                                     ->header("Access-Control-Request-Method: GET,POST");
// }
// 
 
// public function handle($request, Closure $next)
//    {
//     
//   //  echo "dadsa";
//        $response = $next($request);
////        $response->headers->set('Cache-Control','nocache, no-store, max-age=0, must-revalidate'); 
////        $response->headers->set('Pragma','no-cache'); 
////        $response->headers->set('Expires','Fri, 01 Jan 1990 00:00:00 GMT');
////        $response->headers->set("Content-Type: application/json:", true);
////        $response->headers->set('Access-Control-Allow-Credentials', true);
//        $response->headers->set("Access-Control-Allow-Origin", '*');
//        $response->headers->set("Access-Control-Allow-Headers" ,"x-prototype-version,X-Requested-With");
//        $response->headers->set("Access-Control-Request-Method", "GET,POST","OPTIONS);
//        return $response;
//    }
    public function handle($request, Closure $next)
 { 

  // ALLOW OPTIONS METHOD
  $headers = [
         'Access-Control-Allow-Methods'=> 'POST, GET, OPTIONS, PUT, DELETE',
         'Access-Control-Allow-Headers'=> 'Content-Type, X-Auth-Token, Origin'
     ];
  if($request->getMethod() == "OPTIONS") {
         // The client-side application can set only headers allowed in Access-Control-Allow-Headers
     
       response.setHeader("Access-Control-Allow-Origin","*");
       response.setHeader("Access-Control-Allow-Methods", "GET,POST,PUT,DELETE,OPTIONS");
       response.setHeader("Access-Control-Allow-Headers", "Content-Type");
    
         return Response::make('OK', 200, $headers);
     }

     $response = $next($request);
     foreach($headers as $key => $value) 
      $response->header($key, $value);
  return $response;
 }
}