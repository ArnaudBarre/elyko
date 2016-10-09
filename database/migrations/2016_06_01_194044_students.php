<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Students extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('students', function(Blueprint $table) {
            $table->integer('id')->unsigned()->primary();
            $table->string('name');
            $table->string('last_name');
            $table->string('login');
            $table->string('email')->nullable();
            $table->boolean('up_to_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('students');
    }
}
