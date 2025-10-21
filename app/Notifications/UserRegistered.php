<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Welcome to Our Application')
            ->greeting('Welcome ' . $notifiable->name . '!')
            ->line('Your account has been successfully created.')
            ->line('Email: ' . $notifiable->email)
            ->line('You can now login and start using our application.')
            ->action('Go to Dashboard', url('/dashboard'))
            ->line('If you have any questions, please contact our support team.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'registration',
            'message' => 'Your account was successfully created',
            'user_id' => $notifiable->id,
            'email' => $notifiable->email,
            'created_at' => now(),
        ];
    }
}