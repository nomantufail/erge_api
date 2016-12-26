<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json: true");
        header('Access-Control-Allow-Credentials: true');
        header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Origin, Authorization, Accept, Client-Security-Token, Accept-Encoding");
        header("Access-Control-Request-Method: GET,POST,PUT,PATCH,DELETE,OPTIONS");
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
