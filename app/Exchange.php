<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Exchange extends Model
{
    protected $table = 'exchanges';
    protected $primaryKey = 'id';
    public $timestamps = true;

    /**
     * The relationships for this model
     */
    public function sender()
    {
        return $this->belongsTo('App\Student');
    }

    public function receiver()
    {
        return $this->belongsTo('App\Student');
    }
}
