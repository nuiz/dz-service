<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShowcasesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('showcases', function(Blueprint $table)
		{
            $table->engine ='InnoDB';

            $table->increments('id');
            $table->string('youtube_id');

            $table->string('name');
            $table->string('description');
            $table->string('thumb');
            $table->integer('duration');

            $table->integer('like_count');
            $table->integer('view_count');
            $table->integer('comment_count');

            $table->integer('sort_seq');

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
		Schema::drop('showcases');
	}

}
