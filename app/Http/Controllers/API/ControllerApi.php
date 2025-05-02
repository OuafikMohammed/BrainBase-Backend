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
        
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        
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
    // Delete a user 
    public function deleteUser( int $id)
    {
        $user = User::find($id);
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
    public function editUser(Request $request, $id)
    {   
        // Check user if exists
        $user = User::find($id);
        if ($user) {
            // $request retrieves the data from the request
            // $request->all() retrieves all the data from the request
            // $request->input('name') retrieves the name from the request
            $validator = Validator::make($request->all(), rules:[
                'name' => 'required',
                'email' => 'required',
                'password' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json(data: [
                    'status' => 'fail',
                    'message' => $validator->errors()
                ], status: 400);
            }
            $data = $request->all();
            $user->update($data);
            return response()->json(data: [
                'status' => 'success',
                'message' => 'User updated successfully'
            ], status: 200);
        } else {
            return response()->json(data: [
                'status' => 'fail',
                'message' => 'User not found'
            ], status: 404);
        }
    }
    // public function createCategory(Request $request){
    //     $validator = Validator::make(data: $request->all() , rules:[
    //         'name' => 'required'
    //     ]);
    //     if ($validator->fails()){
    //         return response()->json(data:[
    //             'status' => 'fail',
    //             'message'=> $validator->errors()
    //         ], status:400);
    //     }
    //     $data['name'] = $request->name;
    //     $data['slug'] = Str::slug($data['name']);
    //     $imagePath = $this->uploadImage($request , 'image');
    //     $data['image'] = isset($imagePath) ? $imagePath : '';
    //     ProductCategory::create($data);
    //     return response()->json(data:[
    //         'status'=> 'success',
    //        'message'=> 'Category created successfully'
    //     ],status:200);
    // }

    // public function getAllCategories(){
    //     // to retrieve all the categories from the database
    //     $categories = ProductCategory::get();
    //     // to check if the categories are empty
    //     // if the categories are empty, return a 404 error
    //     // if the categories are not empty, return a 200 success
    //     if ($categories->isEmpty()){
    //         return response()->json(data:[
    //             'status'=> 'fail',
    //             'message'=> 'No category found'
    //         ], status: 404);
    //     }
    //     return response()->json(data:[
    //         'status'=> 'success',
    //         'count' => count($categories), 
    //         'message'=> 'All categories',
    //         'data'=> $categories
    //     ], status: 200);
    // }
    // public function editCategory(int $categoryId , Request $request){
    //     $category = ProductCategory::find($categoryId);
    //     if (!$category){
    //         return response()->json(data:[
    //             'status' => 'fail',
    //             'message' => 'Category not found with this id'
    //         ], status:404);
    //     }
    //     $validator = Validator::make(data: $request->all(), rules:[
    //         'name' => 'required'
    //     ]);
    //     if ($validator->fails()){
    //         return response()->json(data: [
    //             'status' => 'fail',
    //             'message' => $validator->errors()
    //         ]);
    //     }
    //     $data['name'] = $request->name;
    //     $data['slug'] = Str::slug($data['name']);
    //     $imagePath = $this->uploadImage($request , 'image');
    //     $data['image'] = isset($imagePath) ? $imagePath : '';
    //     $category->update($data);
    //     return response()->json(data:[
    //         'status'=> 'success',
    //         'message'=> 'Category updated successfully'
    //     ], status: 200);
    // }

    // public function deleteCategory(int $categoryId , Request $request){
    //     $category = ProductCategory::find($categoryId);
    //     if (!$category){
    //         return response()->json([
    //             "status"  => "fail",
    //             "message" => "Category not founded !"
    //         ],404);
    //     }
    //     $validator = Validator::make(data: $request->all() , rules:[
    //         "name" => "required",
    //     ]);
    //     if ($validator->fails()){
    //         return response()->json([
    //             "status" => "fail",
    //             "message"=>$validator->errors(),
    //         ]);
    //     }
    // }
}