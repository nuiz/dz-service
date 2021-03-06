<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('groups', function(Blueprint $table)
		{
            $table->engine ='InnoDB';

            $table->increments('id');
            $table->integer('class_id');
            $table->integer('video_id');

            $table->string('name');
            $table->string('description');
            $table->integer('user_length');
            $table->integer('register_length');
			$table->timestamps();

            $table->index(array('class_id'));
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('groups');
	}

}
