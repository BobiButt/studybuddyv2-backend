<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        Log::info('Email verification attempt', [
            'id' => $id,
            'hash' => $hash,
            'expires' => $request->query('expires'),
            'signature' => $request->query('signature') ? substr($request->query('signature'), 0, 20) . '...' : 'none'
        ]);
        
        $user = User::find($id);
        
        if (!$user) {
            Log::error('User not found for verification', ['id' => $id]);
            return response()->json(['error' => 'User not found'], 404);
        }
        
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            Log::error('Invalid verification hash', [
                'id' => $id,
                'expected' => sha1($user->getEmailForVerification()),
                'received' => $hash
            ]);
            return response()->json(['error' => 'Invalid verification link'], 403);
        }
        
        // Check if link has expired (if we have expires parameter)
        if ($request->has('expires') && $request->has('signature')) {
            // Verify the signed URL
            if (!$request->hasValidSignature()) {
                Log::error('Invalid or expired signature');
                return response()->json(['error' => 'Verification link has expired'], 410);
            }
        }
        
        if ($user->hasVerifiedEmail()) {
            Log::info('Email already verified', ['email' => $user->email]);
            return response()->json(['message' => 'Email already verified']);
        }
        
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            Log::info('Email verified successfully', ['email' => $user->email]);
        }
        
        return response()->json([
            'message' => 'Email verified successfully',
            'verified' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at
            ]
        ]);
    }
    
    public function resend(Request $request)
{
    Log::info('Resend verification request', [
        'user_id' => $request->user()?->id,
        'auth_check' => Auth::check(),
        'has_bearer_token' => $request->bearerToken() ? 'yes' : 'no',
        'token_length' => $request->bearerToken() ? strlen($request->bearerToken()) : 0
    ]);
    
    // Try to get user from Sanctum token
    $user = $request->user();
    
    // If still no user, try to authenticate with the token
    if (!$user && $request->bearerToken()) {
        Log::info('Attempting to authenticate with token');
        
        // Try to find the token in database
        $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
        
        if ($token) {
            $user = $token->tokenable;
            Log::info('Found user via token:', ['user_id' => $user->id, 'email' => $user->email]);
            
            // Log in the user for this request
            Auth::login($user);
        } else {
            Log::error('Token not found in database');
        }
    }
    
    // If still no user, return error
    if (!$user) {
        Log::error('No authenticated user found for resend verification', [
            'available_tokens' => $request->bearerToken() ? 'Token exists but invalid' : 'No token',
            'all_headers' => $request->headers->all()
        ]);
        
        return response()->json([
            'error' => 'Authentication required',
            'message' => 'Please log in to resend verification email',
            'debug' => 'Token provided but user not authenticated'
        ], 401);
    }
    
    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified']);
    }
    
    $user->sendEmailVerificationNotification();
    Log::info('Verification email resent', [
        'email' => $user->email,
        'user_id' => $user->id
    ]);
    
    return response()->json([
        'message' => 'Verification link sent',
        'email' => $user->email
    ]);
}
}