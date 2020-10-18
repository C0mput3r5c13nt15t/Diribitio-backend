<?php

namespace App;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Leader extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use Notifiable;

    protected $table = 'leaders';
    protected $primaryKey = 'id';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The relationships for this model
     */
    public function leaded_project()
    {
        return $this->morphOne('App\Project', 'leader');
    }

    public function getJWTidentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'type' => 'leaders'
        ];
    }

    /**
     * The Notification for password resets.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new Notifications\PasswordReset($token));
    }

    /**
     * The Notification for verifying the email.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new Notifications\EmailVerify('leaders'));
    }
}
