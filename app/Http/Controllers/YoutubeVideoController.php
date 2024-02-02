<?php

namespace App\Http\Controllers;

use App\Models\YoutubeVideo;
use Exception;
use Faker\Core\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class YoutubeVideoController extends Controller
{
    public function showYoutubeVideos()
    {

        try {
            $views = YoutubeVideo::where('is_active', '=', 1)->first();

            if (!$views) {
                $responceArray = [
                    'status' => false,
                    'message' => 'Record not found',
                    'data' => []
                ];
            } else {

                $responceArray = [
                    'status' => true,
                    'message' => 'Record Found',
                    'data' => ['record_details' => $views]
                ];
            }
            return response()->json($responceArray);
        } catch (Exception $e) {
            $responceArray = [
                'status' => false,
                'message' => 'Exception',
                'data' => $e->getMessage()
            ];
        }
        return response()->json($responceArray);
    }

    public function storeYoutubeVideo(Request $request)
    {

        try {

            $rules = [
                'video_id' => 'sometimes|url',
                'thumbnail' => 'sometimes|image|mimes:jpg,jpeg,webp,png|max:2048',
            ];

            $errorMessage = [];

            $validator = Validator::make($request->all(), $rules, $errorMessage);

            if ($validator->fails()) {

                $responseArray = [
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->messages()
                ];

                return response()->json($responseArray);
            } else {

                $videoId = $this->findurlid($request->video_id);

                $image = $request->file('thumbnail');

                if (!empty($image)) {

                    $folder_path = public_path() . '/youtube_videos_thumbnails';

                    if (!is_dir($folder_path)) {

                        mkdir($folder_path, 0777, true);
                    }

                    $extension = $image->getClientOriginalExtension();

                    $filename = 'yt_thumbnail' . '_' . ($videoId) . '_' . random_int(10000, 99999) . '.' . $extension;

                    $image->move(public_path('youtube_videos_thumbnails'), $filename);
                }

                $isCreated = YoutubeVideo::create([
                    'video_id' => $videoId,
                    'thumbnail' => $filename
                ]);

                if ($isCreated) {

                    $responseArray = [
                        'status' => true,
                        'message' => 'Data inserted',
                        'data' => ['youtube_video', $isCreated]
                    ];

                    return response()->json($responseArray);
                } else {

                    $responseArray = [
                        'status' => false,
                        'message' => 'Data is not inserted',
                        'data' => []
                    ];

                    return response()->json($responseArray);
                }
            }
        } catch (Exception $e) {

            $responseArray = [
                'status' => false,
                'message' => 'Exception',
                'data' => $e->getMessage()
            ];

            return response()->json($responseArray);
        }
    }



    public function updateYoutubeVideo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => ['required', Rule::exists('youtube_videos', 'id')],
                'video_id' => 'sometimes|url',
                'thumbnail' => 'sometimes|image|mimes:jpg,jpeg,webp,png|max:2048',
                'is_active' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {

                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->messages(),
                ]);
            }

            if ($request->video_id == null && $request->thumbnail == null && $request->is_active == null) {

                throw new Exception('Please give at least one value to perform update');
            }

            // At least one value is not null, perform the update
            $data = [];

            if (isset($request->video_id)) {

                $videoId = $this->findurlid($request->video_id);

                $data['video_id'] = $videoId;
            }

            if (isset($request->is_active)) {

                $is_active = $request->is_active;

                $data['is_active'] = $is_active;
            }


            if (isset($request->thumbnail)) {

                $youtubeVideo = YoutubeVideo::where('id', $request->id)->first();

                if (!$youtubeVideo) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Youtube video thumbnail is not found',
                        'data' => [],
                    ]);
                    
                }

                $fullpath = public_path() . '/youtube_videos_thumbnails/' . $youtubeVideo->thumbnail;

                // delete file
                if (file_exists($fullpath)) {

                    unlink($fullpath);
                }

                $image = $request->file('thumbnail');

                //rename file 
                $filename = 'yt_thumbnail' . '_' . ($videoId ?? $youtubeVideo->video_id) . '_' . random_int(10000, 99999) . '.' . $image->getClientOriginalExtension();

                $folder_path = public_path('youtube_videos_thumbnails');

                if (!is_dir($folder_path)) {

                    mkdir($folder_path, 0777, true);
                }

                //Move file to storage
                $image->move($folder_path, $filename);

                $data['thumbnail'] = $filename;
            }

            $isUpdated = YoutubeVideo::where('id', $request->id)->update($data);

            if (!$isUpdated) {

                $responseArray = [
                    'status' => true,
                    'message' => 'Record has been updated ',
                    'data' => []
                ];

                return response()->json($responseArray);
            } else {

                $responseArray = [
                    'status' => true,
                    'message' => 'Record has been updated ',
                    'data' => []
                ];

                return response()->json($responseArray);
            }
        } catch (Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ]);
        }
    }


    protected function findurlid($url)
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('YouTube URL is required.');
        }

        $urlComponents = parse_url($url);

        // Check if URL is valid
        if (!$urlComponents || !isset($urlComponents['query'])) {
            throw new \InvalidArgumentException('Invalid YouTube URL. Please provide a valid URL.');
        }

        // Extract the query parameters
        parse_str($urlComponents['query'], $queryParams);

        // Extract the video ID
        $videoId = $queryParams['v'] ?? null;


        //check the videoid is not empty or length must have 11 characters

        throw_if(empty($videoId) || Str::length($videoId) !== 11, new \Exception('YouTube video ID not found or not the correct  given URL. Please check the URL.'));

        return $videoId;
    }





    public function userList()
    {
        try{

            $userList = YoutubeVideo::select('video_id', 'thumbnail')->where('is_active', '=', 1)->get();
            
            if ($userList->isEmpty()) {

                return response()->json([
                    'status' => false,
                    'message' => 'Data not Found',
                    'data' => []
                ]);

            } else {

                return response()->json([
                    'status' => true,
                    'message' => 'Youtube User list',
                    'data' => [$userList]
                ]);

            }
        }
        catch (Exception $e) {

            return response()->json([
                'status'=> false,
                'message'=> $e->getMessage(),
                'data'=> []
            ]);

        }
    }
    public function adminList()
    {

        try{
            $adminList = YoutubeVideo::all();
    
            if ($adminList->isEmpty()) {

                return response()->json([
                    'status' => false,
                    'message' => 'data not found',
                    'data' => []
                ]);
                
            } else {
                
                return response()->json([
                    'status' => true,
                    'message' => 'Data Foun Youtube admin list',
                    'data' => [$adminList]
                ]);
            }
        }
        catch (Exception $e) {

            return response()->json([
                'status'=> false,
                'message'=> $e->getMessage(),
                'data'=> []
            ]);

        }
    }

    public function deleteRecord(Request $request)
    {

        try{
            $delete = YoutubeVideo::where('id', $request->id)->delete();
            if (!$delete){

                return response()->json([
                    'status' => false,
                    'message' => 'Record is not found ',
                    'data' => []
                ]);
            } 
            else {

                return response()->json([
                    'status' => true,
                    'message' => 'Youtube Record  has been deleted ',
                ]);
            }
        }
        catch (Exception $e) {

            return response()->json([
                'status'=> false,
                'message'=> $e->getMessage(),
                'data'=> []
            ]);
        }
    }
}
