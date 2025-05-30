<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Trait\FileuploadTrait;

class ProfileController extends Controller
{
    use FileuploadTrait;

    /**
     * Get the authenticated user's profile
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            // Add better error handling
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }
            
            return response()->json([
                'status' => 'success',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            // Add proper error logging
            Log::error('Profile fetch failed:', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch profile'
            ], 500);
        }
    }

    /**
     * Update the authenticated user's profile
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id_profile.',id_profile',
            'user_type' => 'sometimes|required|in:ADMIN,EDITOR,VIEWER',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $user->name = $request->name;
            $user->email = $request->email;
            $user->save();
            
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Profile update failed: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'errors' => ['general' => 'Failed to update profile. Please try again.']
            ], 500);
        }
    }

    /**
     * Update the user's avatar
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();
        
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                $this->deleteImage($user->avatar);
            }
            
            // Upload new avatar using the existing trait method
            $path = $this->uploadImage($request, 'avatar', 'avatars');
            
            $user->avatar = $path;
            $user->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Avatar updated successfully',
                'avatar_url' => asset($path) // Generates a full public URL
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No avatar file provided'
        ], 400);
    }

    /**
     * Change the user's password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed|different:current_password',
            'password_confirmation' => 'required'
        ], [
            'current_password.required' => 'Current password is required',
            'password.required' => 'New password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.confirmed' => 'Passwords do not match',
            'password.different' => 'New password must be different from current password',
            'password_confirmation.required' => 'Please confirm your new password'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'errors' => ['current_password' => ['Current password is incorrect']]
            ], 401);
        }

        try {
            DB::beginTransaction();
            
            $user->password = Hash::make($request->password);
            $user->save();
            
            // Log password change for security audit
            Log::info('Password changed successfully', ['user_id' => $user->id_profile]);
            
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Password changed successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Password change failed: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'errors' => ['general' => 'Failed to change password. Please try again.']
            ], 500);
        }
    }
}