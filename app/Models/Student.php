<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    public $timestamps = false;
    protected $hidden = ['id', 'up_to_date'];

    public function semesters()
    {
        return $this->belongsToMany('App\Models\Semester');
    }

    public function uvs()
    {
        return $this->belongsToMany('App\Models\Uv');
    }

    public function evaluations()
    {
        return $this->belongsToMany('App\Models\Evaluation');
    }
}