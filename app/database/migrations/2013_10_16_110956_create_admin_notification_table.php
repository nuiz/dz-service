<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminNotificationTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('admin_notification', function(Blueprint $table)
		{
			$table->increments('id');
            $table->string("type");
            $table->integer("user_id");
            $table->integer("object_id");
            $table->text("message");
            $table->text("message_html");
            $table->boolean("opened");
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
		Schema::drop('admin_notification');
	}

}
