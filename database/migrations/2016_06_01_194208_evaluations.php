<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Evaluations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluations', function(Blueprint $table) {
            $table->integer('id')->unsigned()->primary();
            $table->string('name');
            $table->integer('uv_id')->unsigned();
            $table->double('coefficient');
            $table->boolean('locked');
            $table->boolean('up_to_date');
            $table->foreign('uv_id')->references('id')->on('uvs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('evaluations');
    }
}
