<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SignUpToken extends Model
{
    protected $table = 'sign_up_tokens';
    protected $primaryKey = 'id';
    public $timestamps = true;
}
