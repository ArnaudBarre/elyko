<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SkillsAssessed extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('skillsAssessed', function(Blueprint $table) {
            $table->increments('id');
            $table->string('skill_name');
            $table->integer('student_id')->unsigned();
            $table->integer('semester_id')->unsigned();
            $table->string('value');
            $table->boolean('up_to_date');
            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('semester_id')->references('id')->on('semesters');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('skillsAssessed');
    }
}
