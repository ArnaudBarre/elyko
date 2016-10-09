<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Uv extends Model
{
    public $timestamps = false;
    protected $hidden = ['semester_id', 'up_to_date', 'student_id', 'uv_id'];

    public function semester()
    {
        return $this->belongsTo('App\Models\Semester');
    }

    public function evaluations()
    {
        return $this->hasMany('App\Models\Evaluation');
    }
}