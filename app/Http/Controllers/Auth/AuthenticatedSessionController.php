<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();
        
        $user = $request->user();
        
        // Revoke all existing tokens
        $user->tokens()->delete();
        
        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'token' => $token,
            'user' => $user,
            'message' => 'Login successful'
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        $user = $request->user();
        
        if ($user) {
            // Revoke the current token
            $user->currentAccessToken()->delete();
        }
        
        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}