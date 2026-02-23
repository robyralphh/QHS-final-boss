<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Mail\VerifyEmailMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;


class AuthController extends Controller
{
    
    public function login(LoginRequest $request)
    {

        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($data)) {
            return response()->json([
                'message' => 'Email or password is incorrect.',
            ], 401); 
        }

        $user = Auth::user();

        if (!$user->isActive) {
            Auth::logout(); 
            return response()->json([
                'message' => 'Your account is inactive. Please contact the administrator.',
            ], 403);
        }

        // If email verification is enabled and user hasn't verified, return a clear response
        if (empty($user->email_verified_at)) {
            Auth::logout();
            return response()->json([
                'message' => 'Your email address is not verified. Please check your inbox for the verification link or request a new one.',
                'email_verified' => false,
                'needs_verification' => true,
            ], 403);
        }

        $token = $user->createToken('main')->plainTextToken;
        $redirectUrl = $this->getRedirectUrlBasedOnRole($user->role);

        return response()->json([
            'user' => $user,
            'token' => $token,
            'redirectUrl' => $redirectUrl,
        ]);
    }

    private function getRedirectUrlBasedOnRole($role)
    {
        switch ($role) {
            case 'admin':
                return '/admin';
            case 'custodian':
                return '/custodian';
            case 'user':
                return '/';
            default:
                return '/'; // Default landing page
        }
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'isActive' => true,
            'role' => 'user',
        ]);

        // Create a verification token and send verification email
        $emailSent = false;
        $emailError = null;
        try {
            // Create a temporary signed verification URL valid for 24 hours
            $signedUrl = URL::temporarySignedRoute('verification.verify', now()->addHours(24), ['id' => $user->id]);

            // Attempt to store token and expiration (optional) for backwards-compat; ignore failures
            try {
                $user->update([
                    'email_verification_token' => Str::random(64),
                    'email_verification_expires_at' => now()->addHours(24),
                ]);
            } catch (\Exception $inner) {
                \Log::warning('Could not store email verification token: ' . $inner->getMessage());
            }

            Mail::to($user->email)->send(new \App\Mail\VerifyEmailMail($user, $signedUrl));
            $emailSent = true;
            \Log::info('Verification email sent to: ' . $user->email);
        } catch (\Exception $e) {
            $emailSent = false;
            $emailError = $e->getMessage();
            \Log::error('Failed to send verification email: ' . $emailError);
        }

        $response = [
            'success' => true,
            'message' => 'Registration successful! You can now login.',
            'redirectUrl' => '/auth',
            'email_sent' => $emailSent,
        ];

        if ($emailError) {
            // Provide a user-friendly error message for common misconfigurations
            $friendly = 'We could not send the confirmation email.';
            if (stripos($emailError, 'scheme') !== false || stripos($emailError, 'supported schemes') !== false) {
                $friendly = 'Mail configuration error: unsupported mail scheme. Ensure your .env uses MAIL_SCHEME=smtp or MAIL_SCHEME=smtps and set MAIL_ENCRYPTION=tls for STARTTLS (port 587) or ssl for port 465.';
            }

            $response['email_error'] = $friendly;
            // Also include the raw error for admins in logs only
            \Log::debug('Raw mail error returned to client (redacted): ' . $emailError);
        }

        return response()->json($response, 201);
    }


    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        return response('',204);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            \Log::warning('Password reset requested for non-existent email: ' . $request->email);
            return response()->json(['message' => 'If this email exists in our system, you will receive a password reset link.']);
        }

        try {
            // Generate a unique reset token
            $resetToken = Str::random(64);
            $expiresAt = now()->addHours(1);

            // Store token and expiration in database
            $user->update([
                'reset_token' => $resetToken,
                'reset_token_expires_at' => $expiresAt,
            ]);

            // Send reset email
            Mail::to($user->email)->send(new \App\Mail\PasswordResetMail($user, $resetToken));
            
            \Log::info('Password reset link sent to: ' . $user->email);
            return response()->json(['message' => 'If this email exists in our system, you will receive a password reset link.']);
        } catch (\Exception $e) {
            \Log::error('Failed to send password reset email to ' . $user->email . ': ' . $e->getMessage());
            return response()->json(['message' => 'If this email exists in our system, you will receive a password reset link.']);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = User::where('email', $request->email)
            ->where('reset_token', $request->token)
            ->first();

        // Check if user exists and token is valid
        if (!$user) {
            return response()->json(['message' => 'Invalid token or email.'], 422);
        }

        // Check if token has expired
        if ($user->reset_token_expires_at < now()) {
            return response()->json(['message' => 'Password reset link has expired.'], 422);
        }

        // Update password and clear reset token
        $user->update([
            'password' => Hash::make($request->password),
            'reset_token' => null,
            'reset_token_expires_at' => null,
        ]);

        return response()->json(['message' => 'Password has been reset.']);
    }
}