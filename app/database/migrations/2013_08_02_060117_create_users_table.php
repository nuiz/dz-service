<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function(Blueprint $table)
		{
            $table->engine ='InnoDB';

            $table->increments('id');
            $table->string('email');
            $table->string('username')->nullable();

            $table->string('facebook_id')->nullable();
            $table->string('password');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_number');
            $table->string('birth_date');
            $table->string('gender');
            $table->string('type');

            $table->string('email_show');
            $table->string('phone_show');

            $table->timestamps();

            $table->unique('email');
            $table->unique('username');
            $table->unique('facebook_id');
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