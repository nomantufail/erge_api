<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('f_name');
            $table->string('profile_pic');
            $table->string('mobile_no');
            $table->string('email')->unique();
            $table->string('role');
            $table->string('lat');
            $table->string('long');
            $table->string('bio');
            $table->string('password', 60);
            $table->string('sessiontoken');
            $table->string('reg_code');
            $table->string('device_id');
            $table->string('plateform');
            $table->integer('current_balance');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }
}
