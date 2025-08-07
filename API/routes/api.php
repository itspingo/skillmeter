<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\CheatingDataController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\TestAttemptController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\UniversalDataController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\CheatingDetectionController;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/tests/invitation/{token}', [TestController::class, 'getByInvitation']);
Route::post('/tests/invitation/{token}/start', [TestAttemptController::class, 'startTest']);
Route::post('/attempts/{attemptId}/submit', [TestAttemptController::class, 'submitTest']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    // Tests
    Route::apiResource('tests', TestController::class);
    Route::post('tests/{test_id}/start-self-assessment', [TestAttemptController::class, 'startSelfAssessment']);
    Route::post('tests/{test}/questions', [TestController::class, 'addQuestions']);
    Route::delete('tests/{test}/questions/{question}', [TestController::class, 'removeQuestion']);
    Route::post('tests/{test}/invite', [TestController::class, 'inviteCandidates']);

    // Questions
    Route::apiResource('questions', QuestionController::class);
    Route::post('questions/generate-ai', [QuestionController::class, 'generateAiQuestions']);
    Route::get('questions/ai-generated/{requestId}', [QuestionController::class, 'getAiGeneratedQuestions']);

    // Test Attempts
    Route::apiResource('attempts', TestAttemptController::class)->except(['store']);

    // Cheating Data
    Route::get('/cheating', [CheatingDataController::class, 'index']);
    Route::post('/cheating', [CheatingDataController::class, 'store']);
    Route::get('/test/{testId}/summary', [CheatingDataController::class, 'testSummary']);
});

Route::prefix('admin/database')->group(function () {
    Route::post('/execute', [DatabaseController::class, 'executeQuery'])
         ->middleware(['auth:sanctum', 'ability:admin,super-admin']);
});

Route::prefix('api')->middleware('auth:sanctum')->group(function () {
    // The UniversalDataController provides direct, un-scoped access to database tables and has been
// disabled due to major security vulnerabilities. Any user could access, modify, or delete
// any data in the allowed tables. It is highly recommended to create dedicated,
// policy-controlled endpoints for each resource instead of using this controller.
// Route::post('/data/{operation}', [UniversalDataController::class, 'handleDataOperation'])
//     ->where('operation', 'create|read|update|delete');
//
// Route::get('/schema/{table}', [UniversalDataController::class, 'getTableSchema']);
    
    // Specialized endpoints
    Route::post('/ai/generate', [AiController::class, 'generate']);
    Route::post('/cheating/detect', [CheatingDetectionController::class, 'store']);
});
