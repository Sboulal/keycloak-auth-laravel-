<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserLoggedIn extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;
    protected $loginTime;

    public function __construct($user)
    {
        $this->user = $user;
        $this->loginTime = now();
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Login Alert')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new login to your account was detected.')
            ->line('Login Time: ' . $this->loginTime->format('Y-m-d H:i:s'))
            ->line('If this wasn\'t you, please secure your account immediately.')
            ->action('View Account', url('/dashboard'))
            ->line('Thank you for using our application!');
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'login',
            'message' => 'You logged in successfully',
            'user_id' => $notifiable->id,
            'login_time' => $this->loginTime,
            'email' => $notifiable->email,
        ];
    }
}