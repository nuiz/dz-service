<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVideosTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('videos', function(Blueprint $table)
		{
            $table->engine ='InnoDB';

            $table->increments('id');
            $table->integer('chapter_id');

            $table->string('name');
            $table->string('description');
            $table->string('video_link');

            $table->boolean('is_public');
			$table->timestamps();

            $table->index(array('chapter_id'));
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('videos');
	}

}
