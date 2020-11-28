<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentProjectHasNotEnoughParticipants extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->greeting('Hallo!')
                    ->subject('Projettage Gymnasium Dorfen - Dein Projekt findet nicht statt')
                    ->line('Es tut uns leid dir mitteilen zu müssen, dass dein Projekt auf Grund eines Mangels an interessierten Teilnehmern nicht stattfinden kann. Aus diesem Grund wurde dein Projekt gelöscht.')
                    ->line('Du bist nun Teilnehmer in einem deiner drei Projektwünsche und kannst natürlich auch Tauschen.')
                    ->line('Wir wünschen dir deshalb trotzdem viel Spaß in den kommenden Projekttagen!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
