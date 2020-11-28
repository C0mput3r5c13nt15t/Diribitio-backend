<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssistantProjectDeleted extends Notification
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
                    ->subject('Projettage Gymnasium Dorfen - Dein Projekt wurde gelöscht')
                    ->line('Es tut uns leid dir mitteilen zu müssen, dass das Projekt, in dem du mitgewirkt hast, gelöscht wurde. Für nähere Informationen, warum das Projekt gelöscht wurde, wenden dich sich bitte an den zuständigen Admin.')
                    ->line('Du kannst nun ganz normal weiter an den Projekttagen teilnehmen. Als nächstes steht hier die Wahl der Projektwünsche aus.')
                    ->line('Wir wünschen dir deshalb trotzdem viel Spaß in den kommenden Projekttagen!');;
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
