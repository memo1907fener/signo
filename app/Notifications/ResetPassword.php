<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPassword extends Notification
{
    public function __construct(public string $token) {}

    public function via($notifiable): array { return ['mail']; }

    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->email]);
        return (new MailMessage)
            ->subject('Neues Passwort für Signo')
            ->greeting('Hallo ' . $notifiable->name . ',')
            ->line('Sie haben ein neues Passwort für Ihr Signo-Konto angefordert.')
            ->action('Neues Passwort festlegen', $url)
            ->line('Der Link ist 60 Minuten gültig. Wenn Sie nichts angefordert haben, können Sie diese E-Mail ignorieren.')
            ->salutation('Viele Grüße, Ihr Signo-Team');
    }
}
