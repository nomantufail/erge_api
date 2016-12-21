<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_info', function (Blueprint $table) {
            $table->increments('id');
            $table->string('card_no');
            $table->string('exp_date');
            $table->string('cvc');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('address');
            $table->string('company_name');
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
