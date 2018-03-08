<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSemestersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->boolean('is_automn')->default(1);
            $table->char('year', 2);
            $table->timestamp('begining_at')->unique()->useCurrent(); // Le début du semestre
            $table->timestamp('ending_at')->unique()->useCurrent(); //La fin du semestre
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
        Schema::dropIfExists('semesters');
    }
}
