<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    public $timestamps = false;
    protected $hidden = ['uv_id', 'locked', 'up_to_date', 'evaluation_id', 'student_id'];

    public function uv()
    {
        return $this->belongsTo('App\Models\Uv');
    }
}