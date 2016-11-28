<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Uvs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('uvs', function(Blueprint $table) {
            $table->integer('id')->unsigned()->primary();
            $table->string('name');
            $table->integer('semester_id')->unsigned();
            $table->integer('credits');
            $table->boolean('up_to_date');
            $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('uvs');
    }
}
