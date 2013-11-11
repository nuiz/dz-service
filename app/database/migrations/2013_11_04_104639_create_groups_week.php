<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupsWeek extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('groups_week', function(Blueprint $table)
		{
			$table->increments('id');

            $table->integer("study_count");
            $table->date("date_start");
            $table->date("date_end");

            $table->boolean("sun_active");
            $table->time('sun_start');
            $table->time('sun_end');
            $table->boolean("mon_active");
            $table->time('mon_start');
            $table->time('mon_end');
            $table->boolean("tue_active");
            $table->time('tue_start');
            $table->time('tue_end');
            $table->boolean("wed_active");
            $table->time('wed_start');
            $table->time('wed_end');
            $table->boolean("thu_active");
            $table->time('thu_start');
            $table->time('thu_end');
            $table->boolean("fri_active");
            $table->time('fri_start');
            $table->time('fri_end');
            $table->boolean("sat_active");
            $table->time('sat_start');
            $table->time('sat_end');

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
		Schema::drop('groups_week');
	}

}
