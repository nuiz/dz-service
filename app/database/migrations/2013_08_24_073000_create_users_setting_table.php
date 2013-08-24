<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersSettingTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users_setting', function(Blueprint $table)
		{
            $table->increments('id');
            $table->integer('new_update');
            $table->integer('new_showcase');
            $table->integer('new_lesson');
            $table->integer('news_from_dancezone');
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
		Schema::drop('users_setting');
	}

}
