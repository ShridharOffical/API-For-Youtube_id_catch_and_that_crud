<?php

use App\Http\Controllers\ConvertImageController;
use App\Http\Controllers\CovertImageController;
use App\Http\Controllers\YoutubeVideoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('show-youtube-video', [YoutubeVideoController::class, 'showYoutubeVideos'] );


Route::post('store-youtube-video', [YoutubeVideoController::class, 'storeYoutubeVideo'] );
Route::post('update-youtube-video', [YoutubeVideoController::class, 'updateYoutubeVideo']);
Route::post('delete-youtube-video', [YoutubeVideoController::class, 'deleteRecord']);



Route::get('user-youtube-video-list', [YoutubeVideoController::class, 'userList'] );
Route::get('admin-youtube-video-list', [YoutubeVideoController::class, 'adminList']);



Route::post('convet-image-into-base64',[CovertImageController::class,'convertImageIntoBase64']);
Route::get('get-converted-base64-into-image',[CovertImageController::class,'showImageFromBase64']);