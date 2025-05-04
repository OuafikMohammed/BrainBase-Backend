<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Trait\FileuploadTrait;
use Illuminate\Http\Request;
use \Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
/**
 * @method \Laravel\Sanctum\NewAccessToken createToken(string $name, array $abilities = ['*'])
 */
class ControllerApi extends Controller
{   
    use FileuploadTrait;
    public function test (): JsonResponse
    {
        return response()->json(data:[
            'status' => 'success',
            'message' => 'Hello, World!'
        ],status: 200);
    }
    // CSRF token for frontend
    public function csrf(Request $request): JsonResponse
    {
        return response()->json(data: [
            'status' => 'success',
            'message' => 'CSRF token generated successfully',
            'csrf_token' => csrf_token()
        ], status: 200);
    }
    // New user registration function
    public function register(Request $request)
{
    $request->validate([
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:6',
        'user_type' => 'required|in:ADMIN,EDITOR,VIEWER',
    ]);

    // Create the user
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'user_type' => $request->user_type
    ]);

    // Generate token for the new user
    $token = $user->createToken('authToken')->plainTextToken;

    return response()->json([
        'status' => 'success',
        'message' => 'User registered successfully',
        'user' => $user,
        'token' => $token
    ], 201);
}
    

    public function login(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Find the user by email
        $user = User::where('email', $request->email)->first();

        // Check if the user exists and the password is correct
        if ($user && Hash::check($request->password, $user->password)) {
            // Generate a new API token for the user
            $token = $user->createToken('authToken')->plainTextToken;

            // Return the token and user data
            return response()->json([
                'token' => $token,
                'user' => $user,
            ]);
        }

        // If credentials are invalid, return an error response
        return response()->json(['error' => 'Invalid credentials'], 401);
    }
    
    public function logout(Request $request)
    {
        // Get the currently authenticated user
        $user = $request->user();
    
        // Delete all tokens for the user
        $user->tokens()->delete();
    
        return response()->json([
            'status' => 'success',
            'message' => 'Logged out from all devices successfully',
        ], 200);
    }
    // Get all users
    public function allUsers()
    {
        $users = User::all();
        return response()->json(data: [
            'status' => 'success',
            'message' => 'All users',
            'data' => $users  
        ], status: 200);
    }
    // Get a specific user
    public function getUser(string $id_profile)
    {   
        $user = User::find($id_profile);
        if ($user) {
            return response()->json(data: [
                'status' => 'success',
                'message' => 'User found successfully',
                'data' => $user
            ], status: 200);
        } else {
            return response()->json(data: [
                'status' => 'fail',
                'message' => 'User not found'
            ], status: 404);
        }
    }
    // Delete a user 
    public function deleteUser(string $id_profile)
    {
        $user = User::find($id_profile);
        if ($user) {
            $user->delete();
            return response()->json(data: [
                'status' => 'success',
                'message' => 'User deleted successfully'
            ], status: 200);
        } else {
            return response()->json(data: [
                'status' => 'fail',
                'message' => 'User not found'
            ], status: 404);
        }
    }
    
    // Edit a user
    public function editUser(Request $request, string $id_profile)
    {   
        // Check user if exists
        $user = User::find($id_profile);
        if ($user) {
            // Validate request data
            $validator = Validator::make($request->all(), rules:[
                'name' => 'required',
                'email' => 'required|email|unique:users,email,'.$id_profile.',id_profile',
                'user_type' => 'sometimes|required|in:ADMIN,EDITOR,VIEWER'
            ]);

            if ($validator->fails()) {
                return response()->json(data: [
                    'status' => 'fail',
                    'message' => $validator->errors()
                ], status: 400);
            }

            $data = $request->only(['name', 'email', 'user_type']);
            $user->update($data);
            
            return response()->json(data: [
                'status' => 'success',
                'message' => 'User updated successfully',
                'user' => $user
            ], status: 200);
        } else {
            return response()->json(data: [
                'status' => 'fail',
                'message' => 'User not found'
            ], status: 404);
        }
    }

}