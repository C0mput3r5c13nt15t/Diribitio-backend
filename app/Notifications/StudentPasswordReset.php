<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class StudentPasswordReset extends Notification
{
    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * The callback that should be used to create the reset password URL.
     *
     * @var \Closure|null
     */
    public static $createUrlCallback;

    /**
     * The callback that should be used to build the mail message.
     *
     * @var \Closure|null
     */
    public static $toMailCallback;

    /**
     * Create a notification instance.
     *
     * @param  string  $token
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        $url = url('http://localhost:8100/Projekttage/Anmeldung/Passwort%20zurücksetzen/' . $this->token, [
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false);

        return (new MailMessage)
            ->subject(Lang::get('Ein Schüler will sein Passwort zurücksetzen'))
            ->line(Lang::get('Sie bekommen diese E-Mail, da einer von ihren Schülern eine Passwort-Zurücksetzen-Anfrage versendet hat.'))
            ->line(Lang::get('Das folgende Token ermöglicht ihm sein Passwort zurückzusetzen:'))
            ->line(Lang::get($this->token))
            ->line(Lang::get('Dieses Token verliert seine Gültigkeit in :count Minuten.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
            ->line(Lang::get('Falls sie ihr Passwort nicht zurücksetzten wollten, geben sie bitte dem Administrator bescheid.'));
    }

    /**
     * Set a callback that should be used when creating the reset password button URL.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function createUrlUsing($callback)
    {
        static::$createUrlCallback = $callback;
    }

    /**
     * Set a callback that should be used when building the notification mail message.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function toMailUsing($callback)
    {
        static::$toMailCallback = $callback;
    }
}
