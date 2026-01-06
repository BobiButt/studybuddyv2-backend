<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
         VerifyEmail::toMailUsing(function ($notifiable, $url) {
            // Create frontend verification URL
            $frontendUrl = env('FRONTEND_URL') . '/email/verify?' . http_build_query([
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]);
            
            return (new MailMessage)
                ->subject('Verify Email Address')
                ->line('Click the button below to verify your email address.')
                ->action('Verify Email Address', $frontendUrl);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    
        {
        VerifyEmail::createUrlUsing(function ($notifiable) {
            // Generate the signed URL
            $signedUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
            
            // Parse the signed URL
            $parsedUrl = parse_url($signedUrl);
            parse_str($parsedUrl['query'] ?? '', $queryParams);
            
            // Build frontend URL
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            
            return $frontendUrl . '/email/verify?' . http_build_query([
                'id' => $notifiable->getKey(),
                'hash' => $queryParams['hash'] ?? '',
                'expires' => $queryParams['expires'] ?? '',
                'signature' => $queryParams['signature'] ?? ''
            ]);
        });
        
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            \Log::info('Email verification URL: ' . $url);
            
            return (new MailMessage)
                ->subject('Verify Email Address')
                ->line('Please click the button below to verify your email address.')
                ->action('Verify Email Address', $url)
                ->line('If you did not create an account, no further action is required.');
        });
    }
    
}
