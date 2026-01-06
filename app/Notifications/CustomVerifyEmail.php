<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class CustomVerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // Generate the original Laravel signed URL
        $originalUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
        
        // Parse the original URL to get all parameters
        $parsedUrl = parse_url($originalUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);
        
        // Build frontend URL with ALL parameters
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        
        $frontendVerificationUrl = $frontendUrl . '/email/verify?' . http_build_query([
            'id' => $notifiable->getKey(),
            'hash' => $queryParams['hash'] ?? sha1($notifiable->getEmailForVerification()),
            'expires' => $queryParams['expires'] ?? '',
            'signature' => $queryParams['signature'] ?? ''
        ]);
        
        \Log::info('Frontend verification URL generated for ' . $notifiable->email . ': ' . $frontendVerificationUrl);
        \Log::info('Original signed URL: ' . $originalUrl);
        \Log::info('Query params: ', $queryParams);

        return (new MailMessage)
            ->subject('Verify Email Address')
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address', $frontendVerificationUrl)
            ->line('If you did not create an account, no further action is required.');
    }
}