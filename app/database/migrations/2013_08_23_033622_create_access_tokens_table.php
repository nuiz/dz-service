<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccessTokensTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('access_tokens', function(Blueprint $table)
		{
            $table->increments('id');
            $table->integer('user_id');
            $table->string('access_token');
            $table->dateTime('expire');
			$table->timestamps();

            $table->index('user_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('access_tokens');
	}

}
