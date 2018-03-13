<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type');
            $table->string('body');
            $table->integer('visibility_id')->unsigned();
            $table->foreign('visibility_id')->references('id')->on('visibilities');
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
        Schema::dropIfExists('contacts_users');
    }
}
