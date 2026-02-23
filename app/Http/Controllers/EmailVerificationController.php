<?php

namespace App\Http\Controllers;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    /**
     * Send email verification link
     */
    public function sendVerificationEmail(User $user)
    {
        // Check if email is already verified
        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already verified',
            ], 422);
        }

        // Generate a temporary signed verification URL valid for 24 hours
        $signedUrl = URL::temporarySignedRoute('verification.verify', now()->addHours(24), ['id' => $user->id]);

        // Store optional token/expiry for compatibility; ignore errors
        try {
            $user->update([
                'email_verification_token' => Str::random(64),
                'email_verification_expires_at' => now()->addHours(24),
            ]);
        } catch (\Exception $inner) {
            \Log::warning('Could not store email verification token: ' . $inner->getMessage());
        }

        // Send verification email
        try {
            Mail::to($user->email)->send(new VerifyEmailMail($user, $signedUrl));

            return response()->json([
                'success' => true,
                'message' => 'Verification email sent successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify using a temporary signed URL (GET). Redirects to frontend.
     */
    public function verifySigned(Request $request, $id)
    {
        // Ensure signature is valid
        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'Invalid or expired verification link.'], 403);
        }

        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->email_verified_at) {
            // Already verified — redirect to frontend with status
            $frontend = rtrim(env('FRONTEND_URL', 'http://127.0.0.1:5173'), '/');
            $redirect = $frontend . '/verify-email?status=already_verified&email=' . urlencode($user->email);
            return redirect($redirect);
        }

        // Mark verified
        try {
            $user->email_verified_at = now();
            $user->save();
        } catch (\Exception $e) {
            \Log::error('Failed to mark email verified via signed URL: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to verify email.'], 500);
        }

        $frontend = rtrim(env('FRONTEND_URL', 'http://127.0.0.1:5173'), '/');
        $redirect = $frontend . '/verify-email?status=success&email=' . urlencode($user->email);
        return redirect($redirect);
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Find user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // If a token was supplied, attempt token verification first
        if ($request->has('token') && $request->token) {
            // Check if token is valid
            if ($user->email_verification_token !== $request->token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification token',
                ], 422);
            }

            // Check if token has expired
            if ($user->email_verification_expires_at && $user->email_verification_expires_at < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification token has expired',
                ], 422);
            }
        }

        // If already verified
        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already verified',
            ], 422);
        }

        // Mark email as verified. If token columns exist, clear them; ignore DB errors if columns missing.
        try {
            $user->update([
                'email_verified_at' => now(),
                'email_verification_token' => null,
                'email_verification_expires_at' => null,
            ]);
        } catch (\Exception $e) {
            // Fallback: only set email_verified_at if token columns are not present
            try {
                $user->update([
                    'email_verified_at' => now(),
                ]);
            } catch (\Exception $ex) {
                \Log::error('Failed to mark email as verified: ' . $ex->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to verify email. Please try again later.',
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully',
            'user' => $user,
        ], 200);
    }

    /**
     * Resend verification email
     */
    public function resendVerificationEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already verified',
            ], 422);
        }

        return $this->sendVerificationEmail($user);
    }

    /**
     * Check email verification status
     */
    public function checkVerificationStatus(User $user)
    {
        return response()->json([
            'verified' => $user->email_verified_at !== null,
            'email' => $user->email,
            'verified_at' => $user->email_verified_at,
        ], 200);
    }
}
