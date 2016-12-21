<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/
Route::get('/', function () {
    return Response::json([ 'status' => 'Success', 'serviceName' => '', 'SuccessMessage' => "Bingo Working", 'SuccessCode' => '1100']);
});


  Route::group(['middleware' => ['apptoken']], function () {
     // Route::group(['middleware' => ['Headersmid']], function () {
        Route::resource('users', 'AuthController');
        Route::post('verifyEmail', 'AuthController@verifyEmail');
        Route::post('register', 'AuthController@register');
        Route::post('forgetPass', 'AuthController@forgetPass');
        Route::post('savePayment', 'AuthController@savePayment');
        Route::post('login', 'AuthController@login');

      //});

});
Route::group(['middleware' => ['sessionToken']], function () {
    //Route::group(['middleware' => ['Headersmid']], function () {
        Route::post('createJob', 'JobController@createJob');

        Route::post('conciergeHomePage', 'HomeController@conciergeHomePage');
        Route::post('bossHomePage', 'HomeController@bossHomePage');
        Route::post('changeLocation', 'HomeController@changeLocation');
        Route::post('conciergeJobHistory', 'JobController@conciergeJobHistory');
        Route::post('bossJobHistory', 'JobController@bossJobHistory');
        Route::post('editProfile', 'UserController@editProfile');
        Route::post('viewProfile', 'UserController@viewProfile');
        Route::post('editProfilePic', 'UserController@editProfilePic');
        Route::post('sendMessage', 'ChatController@sendMessage');
        Route::post('viewMessages', 'ChatController@viewMessages');
        Route::post('viewBuddyList', 'ChatController@viewBuddyList');
        Route::post('deleteMessages', 'ChatController@deleteMessages');
        Route::post('deleteChatConversation', 'ChatController@deleteChatConversation');
        Route::post('changePassword', 'UserController@changePassword');
        Route::post('fetchApplicants', 'UserController@fetchApplicants');
        Route::post('requestPayment', 'UserController@requestPayment');

        Route::post('sendApplication', 'JobController@sendApplication');
        Route::post('assignJob', 'JobController@assignJob');
        Route::post('conciergeCompleteJob', 'JobController@conciergeCompleteJob');
        Route::post('bossCompleteJob', 'JobController@bossCompleteJob');
        Route::post('consciergeRating', 'JobController@consciergeRating');
        Route::post('updateDeviceId', 'AuthController@updateDeviceId');


        Route::post('logout', 'AuthController@logout');
    //});
});


Route::post('sendNotification', 'JobController@sendNotification');
Route::get('creditcard', function () {
    return view('creditcarddetails');
});
Route::post('postpayment', 'TestController@postpayment');
Route::get('users', 'TestController@users');
Route::get('sendPushNotification/{id}', 'TestController@sendPushNotification');
Route::get('assignJobPushNotification/{id}', 'TestController@assignJobPushNotification');
Route::get('testPushNotification/{id}', 'TestController@testPushNotification');


//***************************** Validation section******************//
Route::get('sessionFailur', function() {
    return Response::json([ 'status' => 'error', 'serviceName' => '', 'ErrorMessage' => env('ERROR_1000'), 'ErrorCode' => '1000']);
});
Route::get('accessdenied', function() {
    return Response::json([ 'status' => 'error', 'serviceName' => '', 'ErrorMessage' => env('ERROR_1002'), 'ErrorCode' => '1002']);
});
Route::get('apptokenFailur', function() {
    return Response::json([ 'status' => 'error', 'serviceName' => '', 'ErrorMessage' =>env('ERROR_1001'), 'ErrorCode' => '1001']);
});
Route::post('savepaypalinfo', 'UserController@savepaypalinfo');
