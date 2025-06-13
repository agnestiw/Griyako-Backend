<?php

use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\API\PropertyAPI;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FavoritePropertyController;
use App\Http\Controllers\LocationController;
use App\Http\Middleware\CorsMiddleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware('auth:sanctum')->post('/user/update', [AuthController::class, 'updateProfile']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::get('property-image/{filename}', 'PropertyController@getImage');

// property
Route::get('/properties/fetch/', [PropertyAPI::class, 'fetch'])->middleware(HandleCors::class);
Route::get('/properties/fetch/{id}', [PropertyAPI::class, 'getPropertiesFromUserId']);
Route::post('/properties/store/', [PropertyAPI::class, 'store']);

Route::get('/locations', [LocationController::class, 'index']);


/*
 * ===========================
 * Protected Routes
 * ===========================
 */
Route::middleware('auth:sanctum')->group(function () {

    // favorites
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites/store', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{property_id}/delete', [FavoriteController::class, 'destroy']);

    // property
    Route::delete('/properties/{id}', [PropertyAPI::class, 'destroy']);
    Route::get('/properties/{id}/edit', [PropertyAPI::class, 'edit']);
    Route::put('/properties/{id}/update', [PropertyAPI::class, 'update']);

    // notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);

});

// Search
Route::get('/properties/search', [PropertyAPI::class, 'search']);

// profile
Route::get('/profile', [ProfileController::class, 'show']);
Route::post('/profile/update', [ProfileController::class, 'update']);

Route::get('/image/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);

    if (!File::exists($fullPath)) {
        abort(404);
    }

    $file = File::get($fullPath);
    $type = File::mimeType($fullPath);

    return response($file, 200)->header("Content-Type", $type);
})->where('path', '.*'); // <--- allow slashes in the path