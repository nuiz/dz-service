<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsStudy extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('groups_study', function(Blueprint $table)
		{
            $table->increments('id');
            $table->integer("group_id");
            $table->string("status");
            $table->dateTime("start");
            $table->dateTime("end");
            $table->dateTime("ori_start");
            $table->dateTime("ori_end");
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
		Schema::drop('groups_study');
	}

}
