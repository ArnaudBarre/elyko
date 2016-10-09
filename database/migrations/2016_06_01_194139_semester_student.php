<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SemesterStudent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('semester_student', function(Blueprint $table) {
            $table->integer('semester_id')->unsigned();
            $table->integer('student_id')->unsigned();
            $table->boolean('up_to_date');
            $table->primary(['semester_id','student_id']);
            $table->foreign('semester_id')->references('id')->on('semesters');
            $table->foreign('student_id')->references('id')->on('students');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('semester_student');
    }
}
