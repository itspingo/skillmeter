<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\CheatingDataController;
use App\Http\Controllers\DatabaseController;


Route::get('/', function () {
    return view('welcome'); 
});


// Route::middleware('auth:sanctum')->group(function () {
//     // Tests
//     Route::apiResource('tests', 'Api\TestController');
//     Route::post('tests/{test}/questions', 'Api\TestController@addQuestions');
//     Route::delete('tests/{test}/questions/{question}', 'Api\TestController@removeQuestion');
//     Route::post('tests/{test}/invite', 'Api\TestController@inviteCandidates');

//     // Questions
//     Route::apiResource('questions', 'Api\QuestionController');
//     Route::post('questions/generate-ai', 'Api\QuestionController@generateAiQuestions');
//     Route::get('questions/ai-generated/{requestId}', 'Api\QuestionController@getAiGeneratedQuestions');

//     // Test Attempts
//     Route::apiResource('attempts', 'Api\TestAttemptController')->except(['store']);
// });

Route::get('/test-connection', function() {
    return response()->json([
        'status' => 'success',
        'message' => 'API is working!'
    ]);
}); 

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Public routes
Route::post('attempts/start/{invitationToken}', 'Api\TestAttemptController@startTest');
Route::post('attempts/{attempt}/submit', 'Api\TestAttemptController@submitTest');

Route::get('/cheating', [CheatingDataController::class, 'index']);
Route::post('/cheating', [CheatingDataController::class, 'store']);
Route::get('/test/{testId}/summary', [CheatingDataController::class, 'testSummary']);

// Route::prefix('cheating')->group(function () {
//     Route::get('/', [CheatingDataController::class, 'index']);
//     Route::post('/', [CheatingDataController::class, 'store']);
//     Route::get('/test/{testId}/summary', [CheatingDataController::class, 'testSummary']);
// });

Route::prefix('admin/database')->group(function () {
    Route::post('/execute', [DatabaseController::class, 'executeQuery'])
         ->middleware(['auth:sanctum', 'ability:admin,super-admin']);
});

Route::prefix('api')->middleware('auth:sanctum')->group(function () {
    // Universal CRUD endpoint
    Route::post('/data/{operation}', [\App\Http\Controllers\Api\UniversalDataController::class, 'handleDataOperation'])
        ->where('operation', 'create|read|update|delete');
    
    // Schema inspection
    Route::get('/schema/{table}', [\App\Http\Controllers\Api\UniversalDataController::class, 'getTableSchema']);
    
    // Specialized endpoints
    Route::post('/ai/generate', [\App\Http\Controllers\Api\AiController::class, 'generate']);
    Route::post('/cheating/detect', [\App\Http\Controllers\Api\CheatingDetectionController::class, 'store']);
});