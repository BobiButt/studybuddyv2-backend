<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SocialAuthController extends Controller
{
    public function redirect($provider)
    {
        return response()->json([
            'url' => Socialite::driver($provider)
                ->stateless()
                ->redirect()
                ->getTargetUrl()
        ]);
    }

    public function callback($provider)
    {
        try {
            Log::info('Social login callback received for provider: ' . $provider);
            
            // Get user data from Google
            $socialUser = Socialite::driver($provider)->stateless()->user();
            
            Log::info('Social user data received:', [
                'id' => $socialUser->getId(),
                'email' => $socialUser->getEmail(),
                'name' => $socialUser->getName()
            ]);

            // Find or create user
            $user = User::where('email', $socialUser->getEmail())->first();
            
            if (!$user) {
                // Create new user for social login
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'google_id' => $provider === 'google' ? $socialUser->getId() : null,
                    'email_verified_at' => now(), // Social users are auto-verified
                    'password' => bcrypt(Str::random(24)),
                ]);
                
                Log::info('New user created via social login:', ['user_id' => $user->id]);
            } else {
                // Update existing user with social ID
                if ($provider === 'google' && !$user->google_id) {
                    $user->google_id = $socialUser->getId();
                    $user->save();
                }
                Log::info('Existing user found:', ['user_id' => $user->id]);
            }

            // Create API token
            $token = $user->createToken('auth_token')->plainTextToken;
            
            Log::info('Token created for user:', ['user_id' => $user->id]);

            // Build frontend URL with token as query parameter
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $redirectUrl = $frontendUrl . '/auth/social/callback?' . http_build_query([
                'token' => $token,
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'email_verified' => $user->email_verified_at ? 'true' : 'false'
            ]);
            
            Log::info('Redirecting to frontend:', ['url' => $redirectUrl]);

            // Redirect to frontend with token
            return redirect($redirectUrl);

        } catch (\Exception $e) {
            Log::error('Social authentication failed:', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Redirect to frontend with error
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $errorUrl = $frontendUrl . '/login?error=' . urlencode('Social authentication failed: ' . $e->getMessage());
            
            return redirect($errorUrl);
        }
    }
}