<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CovertImage;

class CovertImageController extends Controller
{
    public function convertImageIntoBase64(Request $request){
        
        // Check if a file is present in the request
        if ($request->hasFile('image')) {

            // Get the file from the request
            $image = $request->file("image");
        
            // Read the contents of the file and encode it to base64
            $imageData = base64_encode(file_get_contents($image));
        
           $query = CovertImage::create([
                'image_data'=> $imageData
            ]);

            if(!$query){
                return response()->json([
                    'status'=> false,
                    'message'=> 'cant create record ',
                    'data' => []
                ]);

            }
            else{
                return response()->json([
                    'status'=> true,
                    'messate'=> 'image converted text added',
                    'data'=> []
                ]);
            }
        
    
        } else {

            return response()->json([
                'status'=> false,
                'message'=> 'Please give image ',
                'data'=> []
            ]);
        }
    }


    public function showImageFromBase64(){
        // Retrieve all records from the ConvertImage model
        $imageRecords = CovertImage::all();
    
        if (empty($imageRecords)) {
            return response()->json([
                'status' => false,
                'message' => 'No image records found',
                'data' => null,
            ]);
        }
        
        $images = [];
        
        foreach ($imageRecords as $record) {
            try {
                $decodedImageData = base64_decode($record->image_data);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Error decoding image data',
                    'data' => null,
                ]);
            }
        
            // Generate a unique filename for the image
            $imageName = uniqid() . '.png'; // You can adjust the extension as needed
        
            // Save the decoded image data to the file system or storage
            file_put_contents(public_path('youtube_videos_thumbnails') . '/' . $imageName, $decodedImageData);
        
            $images[] = $imageName;
        }
        
        return response()->json([
            'status' => true,
            'message' => 'View successfully',
            'data' => [$images],
        ]);
        
   
    }
}
