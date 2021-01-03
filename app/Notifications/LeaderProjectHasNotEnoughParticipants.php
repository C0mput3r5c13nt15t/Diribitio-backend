<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaderProjectHasNotEnoughParticipants extends Notification
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
                    ->subject('Projekttage Gymnasium Dorfen - Ihr Projekt findet nicht statt')
                    ->line('Es tut uns leid Ihnen mitteilen zu müssen, dass Ihr Projekt auf Grund eines Mangels an interessierten Teilnehmern nicht stattfinden kann. Aus diesem Grund wurde IhrProjekt gelöscht.')
                    ->line('Ihr Account bleibt allerdings, wenn Sie ihn nicht persönlich löschen, bis zum Ende der Projekttage erhalten.');
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
