<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChaptersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('chapters', function(Blueprint $table)
		{
            $table->engine ='InnoDB';

            $table->increments('id');
            $table->integer('lesson_id');

            $table->string('name');
            $table->string('description');
            $table->string('cover_link');
            $table->integer('video_length');
			$table->timestamps();

            $table->index(array('lesson_id'));
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('chapters');
	}

}
