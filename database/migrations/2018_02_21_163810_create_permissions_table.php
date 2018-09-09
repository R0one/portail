<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('permissions', function (Blueprint $table) {
			$table->uuid('id')->primary();
			$table->string('type', 64);
			$table->string('name', 128);
			$table->string('description');
      $table->uuid('owned_by_id')->nullable();
      $table->string('owned_by_type')->nullable();

			$table->timestamps();

			$table->unique(['type', 'owned_by_id', 'owned_by_type']);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('permissions');
	}
}
