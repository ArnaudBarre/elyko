<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class StudentUv extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_uv', function(Blueprint $table) {
            $table->integer('student_id')->unsigned();
            $table->integer('uv_id')->unsigned();
            $table->string('grade')->nullable();
            $table->float('forced_credits')->nullable();
            $table->boolean('up_to_date');
            $table->primary(['student_id','uv_id']);
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
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
        Schema::drop('student_uv');
    }
}
