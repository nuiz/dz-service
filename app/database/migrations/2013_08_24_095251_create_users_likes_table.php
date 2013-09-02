<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersLikesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users_likes', function(Blueprint $table)
		{
			$table->increments('id');
            $table->integer('user_id');
            $table->integer('object_id');
			$table->timestamps();

            $table->index(array('user_id', 'object_id'));
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('users_likes');
	}

}
