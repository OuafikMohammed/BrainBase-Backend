<?php
namespace App\Trait;  // Defines where this code lives in the project structure
use Illuminate\Http\Request;  // Imports the Request class we need for handling file uploads

// In PHP, a trait is a special programming concept that allows you to reuse code across different classes. Here's why we use traits:

    // Why Use Traits?
    // Code Reusability:
    
    // Traits allow you to share methods between multiple classes
    // You can mix multiple traits into a single class
    // Avoiding Multiple Inheritance:
    
    // PHP doesn't support multiple inheritance (a class can't extend multiple classes)
    // Traits provide a way to use methods from multiple sources without inheritance
    // DRY Principle:
    
    // Follows "Don't Repeat Yourself" principle
    // Write the code once, use it in many classes


trait FileuploadTrait{  // Creates a reusable piece of code that can be added to other classes
    // Main function that handles file upload
    function uploadImage(Request $request, $inputName, $path='/uploads'){
        // Checks if a file was actually uploaded with the given input name
        if ($request->hasFile($inputName)){
            // Gets the uploaded file
            $image = $request->file($inputName);
            // Gets the file extens  ion (like .jpg, .png)
            $ext = $image->getClientOriginalExtension();
            // Creates a unique filename using current time and random numbers
            $imageName = 'media_'.uniqid().'.'.$ext;
            // Moves the file to the public folder
            $image->move(public_path($path),$imageName);
            // Returns the path where the file was saved
            return $path.'/'.$imageName;
        }   
    }
    function deleteImage($path){
        // Checks if the file exists at the given path
        if (file_exists(public_path($path))){
            // Deletes the file
            unlink(public_path($path));
        }
        // Returns a success message
        return response()->json(data: [
            'status' => 'success',
            'message' => 'File deleted successfully'
        ], status: 200);
    }
    
}