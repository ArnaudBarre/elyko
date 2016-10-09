<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    public $timestamps = false;
    protected $hidden = ['pivot', 'up_to_date'];

    public function uvs()
    {
        return $this->hasMany('App\Models\Uv');
    }
}