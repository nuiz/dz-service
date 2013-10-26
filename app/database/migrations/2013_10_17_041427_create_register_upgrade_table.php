<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRegisterUpgradeTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('register_upgrade', function(Blueprint $table)
		{
            $table->increments('id');
            $table->integer('user_id');
            $table->string('email');
            $table->string('phone_number');
            $table->string('name');

            $table->boolean('admin_read');
            $table->boolean("called");
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
		Schema::drop('register_upgrade');
	}

}
