<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRegistersGroupsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('registers_groups', function(Blueprint $table)
		{
            $table->engine ='InnoDB';

			$table->increments('id');
            $table->integer('user_id');
            $table->integer('group_id');
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
		Schema::drop('registers_groups');
	}

}
