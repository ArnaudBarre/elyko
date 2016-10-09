<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EvaluationStudent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluation_student', function(Blueprint $table) {
            $table->integer('evaluation_id')->unsigned();
            $table->integer('student_id')->unsigned();
            $table->string('mark')->nullable();
            $table->boolean('up_to_date');
            $table->primary(['evaluation_id','student_id']);
            $table->foreign('evaluation_id')->references('id')->on('evaluations');
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
        Schema::drop('evaluation_student');
    }
}
