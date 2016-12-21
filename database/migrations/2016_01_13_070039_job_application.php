<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class JobApplication extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::create('job_applications', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id')->references('id')->on('users');
            $table->string('job_id')->references('id')->on('jobs');
            $table->text('description');
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
        //
    }
}
