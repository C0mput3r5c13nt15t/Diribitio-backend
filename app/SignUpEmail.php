<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SignUpEmail extends Model
{
    protected $table = 'sign_up_emails';
    protected $primaryKey = 'id';
    public $timestamps = true;
}
