<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'projects';
    protected $primaryKey = 'id';
    public $timestamps = true;

    /**
     * The relationships for this model
     */
    public function leader()
    {
        return $this->morphTo();
    }

    public function assistant_student_leaders()
    {
        return $this->hasMany('App\Student')->where('role', 2);
    }

    public function participants()
    {
        return $this->hasMany('App\Student')->where('role', 1);
    }

    public function messages()
    {
        return $this->hasMany('App\Message');
    }
}
