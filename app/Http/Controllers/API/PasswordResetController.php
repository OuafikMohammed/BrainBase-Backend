<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\PasswordResetRequest;
use App\Notifications\PasswordResetNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $validated = $request->validate(['email' => 'required|email']);
        
        // Rate limiting: 3 attempts per hour per email
        $key = 'password-reset:' . $validated['email'];
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'status' => 'error',
                'errors' => ['email' => ['Too many reset attempts. Please try again in ' . $seconds . ' seconds.']]
            ], 429);
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            // Use consistent response time to prevent user enumeration
            sleep(1);
            return response()->json([
                'status' => 'success',
                'message' => 'If an account exists with this email, a reset code will be sent.'
            ]);
        }

        try {
            // Generate a secure 6-digit code
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            DB::beginTransaction();
            
            // Store the reset token
            DB::table('password_resets')->updateOrInsert(
                ['email' => $validated['email']],
                [
                    'token' => Hash::make($code),
                    'created_at' => now()
                ]
            );
            
            // Send notification
            $user->notify(new PasswordResetNotification($code));
            
            DB::commit();
            RateLimiter::hit($key);
            
            Log::info('Password reset code sent', [
                'user_id' => $user->id_profile,
                'email' => $validated['email']
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'If an account exists with this email, a reset code will be sent.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send password reset code', [
                'error' => $e->getMessage(),
                'user_id' => $user->id_profile ?? null
            ]);
            
            return response()->json([
                'status' => 'error',
                'errors' => ['email' => ['Failed to send reset code. Please try again later.']]
            ], 500);
        }
    }

    public function verifyCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6|regex:/^[0-9]+$/'
        ]);

        // Rate limiting: 5 attempts per minute for verification
        $key = 'verify-code:' . $validated['email'];
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'status' => 'error',
                'errors' => ['code' => ['Too many verification attempts. Please try again in ' . $seconds . ' seconds.']]
            ], 429);
        }

        try {
            $reset = DB::table('password_resets')
                ->where('email', $validated['email'])
                ->first();

            if (!$reset || !Hash::check($validated['code'], $reset->token)) {
                RateLimiter::hit($key);
                return response()->json([
                    'status' => 'error',
                    'errors' => ['code' => ['Invalid code']]
                ], 400);
            }

            if (now()->diffInMinutes($reset->created_at) > 60) {
                return response()->json([
                    'status' => 'error',
                    'errors' => ['code' => ['Code has expired. Please request a new one.']]
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Code verified successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Code verification failed', [
                'error' => $e->getMessage(),
                'email' => $validated['email']
            ]);
            
            return response()->json([
                'status' => 'error',
                'errors' => ['general' => ['Failed to verify code. Please try again.']]
            ], 500);
        }
    }

    public function reset(PasswordResetRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();
            
            $reset = DB::table('password_resets')
                ->where('email', $validated['email'])
                ->first();

            if (!$reset || !Hash::check($validated['code'], $reset->token)) {
                return response()->json([
                    'status' => 'error',
                    'errors' => ['code' => ['Invalid code']]
                ], 400);
            }

            if (now()->diffInMinutes($reset->created_at) > 60) {
                return response()->json([
                    'status' => 'error',
                    'errors' => ['code' => ['Code has expired. Please request a new one.']]
                ], 400);
            }

            $user = User::where('email', $validated['email'])->first();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'errors' => ['email' => ['User not found']]
                ], 404);
            }

            $user->forceFill([
                'password' => Hash::make($validated['password'])
            ])->save();

            // Delete all password reset tokens for this user
            DB::table('password_resets')->where('email', $validated['email'])->delete();
            
            // Log password reset for security audit
            Log::info('Password reset completed', [
                'user_id' => $user->id_profile,
                'email' => $validated['email']
            ]);
            
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Password has been reset successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Password reset failed', [
                'error' => $e->getMessage(),
                'email' => $validated['email']
            ]);
            
            return response()->json([
                'status' => 'error',
                'errors' => ['general' => ['Failed to reset password. Please try again.']]
            ], 500);
        }
    }
}