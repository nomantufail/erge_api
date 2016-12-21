<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('description');
            $table->string('start_loc');
            $table->string('dest_loc');
            $table->string('start_lat');
            $table->string('start_long');
            $table->string('dest_lat');
            $table->string('dest_long');
            $table->string('visibility_radius');
            $table->string('price');
            $table->string('bonus');
            $table->dateTime('completion_date');
            $table->integer('status');
            $table->string('filename');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
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
