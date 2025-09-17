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
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DifficultyLevelsController;

use App\Http\Controllers\Api\BrowserActivityController;
use App\Http\Controllers\Api\CheatingEventsController;
use App\Http\Controllers\Api\NotificationsController;
use App\Http\Controllers\Api\QuestionCategoriesController;
use App\Http\Controllers\Api\QuestionResponsesController;
use App\Http\Controllers\Api\QuestionsController;
use App\Http\Controllers\Api\QuestionTypesController;
use App\Http\Controllers\Api\RecruiterProfilesController;
use App\Http\Controllers\Api\ResponseSelectedOptionsController;
use App\Http\Controllers\Api\TestInvitationController;
use App\Http\Controllers\Api\TestResultController;
use App\Http\Controllers\Api\UserTypeController;


// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/tests/invitation/{token}', [TestController::class, 'getByInvitation']);
Route::post('/tests/invitation/{token}/start', [TestAttemptController::class, 'startTest']);
Route::post('/attempts/{attemptId}/submit', [TestAttemptController::class, 'submitTest']);

// Protected routes
Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Tests
    // Route::apiResource('tests', TestController::class);
    Route::get('tests', [TestController::class, 'index']);
    Route::post('tests', [TestController::class, 'store']);
    Route::get('tests/{test}', [TestController::class, 'show']);
    Route::put('tests/{test}', [TestController::class, 'update']);
    Route::patch('tests/{test}', [TestController::class, 'update']);
    Route::delete('tests/{test}', [TestController::class, 'destroy']);


    Route::post('tests/{test_id}/start-self-assessment', [TestAttemptController::class, 'startSelfAssessment']);
    Route::post('tests/{test}/questions', [TestController::class, 'addQuestions']);
    Route::delete('tests/{test}/questions/{question}', [TestController::class, 'removeQuestion']);
    Route::post('tests/{test}/invite', [TestController::class, 'inviteCandidates']);

    // Users / Candidates
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::patch('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
    // Route:delete('users/{user}', [UserController::class, 'destroy']);

    // Test-Specific Questions
    Route::get('tests/{test}/questions', [QuestionController::class, 'indexForTest']);
    Route::post('tests/{test}/questions', [QuestionController::class, 'storeForTest']);
    Route::get('questions/{question}', [QuestionController::class, 'show']);
    Route::put('questions/{question}', [QuestionController::class, 'update']);
    Route::delete('questions/{question}', [QuestionController::class, 'destroy']);

    Route::get('difficulty_levels', [DifficultyLevelsController::class, 'index']);
    Route::post('difficulty_levels', [DifficultyLevelsController::class, 'store']);
    Route::get('difficulty_levels/{id}', [DifficultyLevelsController::class, 'show']);
    Route::put('difficulty_levels/{id}', [DifficultyLevelsController::class, 'update']);
    Route::patch('difficulty_levels/{id}', [DifficultyLevelsController::class, 'update']);
    Route::delete('difficulty_levels/{id}', [DifficultyLevelsController::class, 'destroy']);

    Route::get('browser_activities', [BrowserActivityController::class, 'index']);
    Route::post('browser_activities', [BrowserActivityController::class, 'store']);
    Route::get('browser_activities/{id}', [BrowserActivityController::class, 'show']);
    Route::put('browser_activities/{id}', [BrowserActivityController::class, 'update']);
    Route::patch('browser_activities/{id}', [BrowserActivityController::class, 'update']);
    Route::delete('browser_activities/{id}', [BrowserActivityController::class, 'destroy']);
    
    Route::get('cheating_data', [CheatingDataController::class, 'index']);
    Route::post('cheating_data', [CheatingDataController::class, 'store']);
    Route::get('cheating_data/{id}', [CheatingDataController::class, 'show']);
    Route::put('cheating_data/{id}', [CheatingDataController::class, 'update']);
    Route::patch('cheating_data/{id}', [CheatingDataController::class, 'update']);
    Route::delete('cheating_data/{id}', [CheatingDataController::class, 'destroy']);
    
    Route::get('cheating_detection', [CheatingDetectionController::class, 'index']);
    Route::post('cheating_detection', [CheatingDetectionController::class, 'store']);
    Route::get('cheating_detection/{id}', [CheatingDetectionController::class, 'show']);
    Route::put('cheating_detection/{id}', [CheatingDetectionController::class, 'update']);
    Route::patch('cheating_detection/{id}', [CheatingDetectionController::class, 'update']);
    Route::delete('cheating_detection/{id}', [CheatingDetectionController::class, 'destroy']);
    
    Route::get('cheating_events', [CheatingEventsController::class, 'index']);
    Route::post('cheating_events', [CheatingEventsController::class, 'store']);
    Route::get('cheating_events/{id}', [CheatingEventsController::class, 'show']);
    Route::put('cheating_events/{id}', [CheatingEventsController::class, 'update']);
    Route::patch('cheating_events/{id}', [CheatingEventsController::class, 'update']);
    Route::delete('cheating_events/{id}', [CheatingEventsController::class, 'destroy']);
    
    Route::get('difficulty_levels', [DifficultyLevelsController::class, 'index']);
    Route::post('difficulty_levels', [DifficultyLevelsController::class, 'store']);
    Route::get('difficulty_levels/{id}', [DifficultyLevelsController::class, 'show']);
    Route::put('difficulty_levels/{id}', [DifficultyLevelsController::class, 'update']);
    Route::patch('difficulty_levels/{id}', [DifficultyLevelsController::class, 'update']);
    Route::delete('difficulty_levels/{id}', [DifficultyLevelsController::class, 'destroy']);
    
    Route::get('notifications', [NotificationsController::class, 'index']);
    Route::post('notifications', [NotificationsController::class, 'store']);
    Route::get('notifications/{id}', [NotificationsController::class, 'show']);
    Route::put('notifications/{id}', [NotificationsController::class, 'update']);
    Route::patch('notifications/{id}', [NotificationsController::class, 'update']);
    Route::delete('notifications/{id}', [NotificationsController::class, 'destroy']);
    
    Route::get('question_categories', [QuestionCategoriesController::class, 'index']);
    Route::post('question_categories', [QuestionCategoriesController::class, 'store']);
    Route::get('question_categories/{id}', [QuestionCategoriesController::class, 'show']);
    Route::put('question_categories/{id}', [QuestionCategoriesController::class, 'update']);
    Route::patch('question_categories/{id}', [QuestionCategoriesController::class, 'update']);
    Route::delete('question_categories/{id}', [QuestionCategoriesController::class, 'destroy']);
    
    Route::get('questions', [QuestionsController::class, 'index']);
    Route::post('questions', [QuestionsController::class, 'store']);
    Route::get('questions/{id}', [QuestionsController::class, 'show']);
    Route::put('questions/{id}', [QuestionsController::class, 'update']);
    Route::patch('questions/{id}', [QuestionsController::class, 'update']);
    Route::delete('questions/{id}', [QuestionsController::class, 'destroy']);
    
    Route::get('question_responses', [QuestionResponsesController::class, 'index']); 
    Route::post('question_responses', [QuestionResponsesController::class, 'store']);
    Route::get('question_responses/{id}', [QuestionResponsesController::class, 'show']);
    Route::put('question_responses/{id}', [QuestionResponsesController::class, 'update']);
    Route::patch('question_responses/{id}', [QuestionResponsesController::class, 'update']);
    Route::delete('question_responses/{id}', [QuestionResponsesController::class, 'destroy']);

    Route::get('questions', [QuestionsController::class, 'index']);
    Route::post('questions', [QuestionsController::class, 'store']);
    Route::get('questions/{id}', [QuestionsController::class, 'show']);
    Route::put('questions/{id}', [QuestionsController::class, 'update']);
    Route::patch('questions/{id}', [QuestionsController::class, 'update']);
    Route::delete('questions/{id}', [QuestionsController::class, 'destroy']);

    Route::get('question_types', [QuestionTypesController::class, 'index']);
    Route::post('question_types', [QuestionTypesController::class, 'store']);
    Route::get('question_types/{id}', [QuestionTypesController::class, 'show']);
    Route::put('question_types/{id}', [QuestionTypesController::class, 'update']);
    Route::patch('question_types/{id}', [QuestionTypesController::class, 'update']);
    Route::delete('question_types/{id}', [QuestionTypesController::class, 'destroy']);

    Route::get('recruiter_profiles', [RecruiterProfilesController::class, 'index']);
    Route::post('recruiter_profiles', [RecruiterProfilesController::class, 'store']);
    Route::get('recruiter_profiles/{id}', [RecruiterProfilesController::class, 'show']);
    Route::put('recruiter_profiles/{id}', [RecruiterProfilesController::class, 'update']);
    Route::patch('recruiter_profiles/{id}', [RecruiterProfilesController::class, 'update']);
    Route::delete('recruiter_profiles/{id}', [RecruiterProfilesController::class, 'destroy']);

    Route::get('response_selected_options', [ResponseSelectedOptionsController::class, 'index']);
    Route::post('response_selected_options', [ResponseSelectedOptionsController::class, 'store']);
    Route::get('response_selected_options/{id}', [ResponseSelectedOptionsController::class, 'show']);
    Route::put('response_selected_options/{id}', [ResponseSelectedOptionsController::class, 'update']);
    Route::patch('response_selected_options/{id}', [ResponseSelectedOptionsController::class, 'update']);
    Route::delete('response_selected_options/{id}', [ResponseSelectedOptionsController::class, 'destroy']);
    
    Route::get('test_attempts', [TestAttemptController::class, 'index']);
    Route::post('test_attempts', [TestAttemptController::class, 'store']);
    Route::get('test_attempts/{id}', [TestAttemptController::class, 'show']);
    Route::put('test_attempts/{id}', [TestAttemptController::class, 'update']);
    Route::patch('test_attempts/{id}', [TestAttemptController::class, 'update']);
    Route::delete('test_attempts/{id}', [TestAttemptController::class, 'destroy']);
    
    Route::get('test_invitations', [TestInvitationController::class, 'index']);
    Route::post('test_invitations', [TestInvitationController::class, 'store']);
    Route::get('test_invitations/{id}', [TestInvitationController::class, 'show']);
    Route::put('test_invitations/{id}', [TestInvitationController::class, 'update']);
    Route::patch('test_invitations/{id}', [TestInvitationController::class, 'update']);
    Route::delete('test_invitations/{id}', [TestInvitationController::class, 'destroy']);
    
    Route::get('test_results', [TestResultController::class, 'index']);
    Route::post('test_results', [TestResultController::class, 'store']);
    Route::get('test_results/{id}', [TestResultController::class, 'show']);
    Route::put('test_results/{id}', [TestResultController::class, 'update']);
    Route::patch('test_results/{id}', [TestResultController::class, 'update']);
    Route::delete('test_results/{id}', [TestResultController::class, 'destroy']);
    
    Route::get('user_types', [UserTypeController::class, 'index']);
    Route::post('user_types', [UserTypeController::class, 'store']);
    Route::get('user_types/{id}', [UserTypeController::class, 'show']);
    Route::put('user_types/{id}', [UserTypeController::class, 'update']);
    Route::patch('user_types/{id}', [UserTypeController::class, 'update']);
    Route::delete('user_types/{id}', [UserTypeController::class, 'destroy']);



    // Universal Data Endpoints
    Route::get('question-types', [UniversalDataController::class, 'getQuestionTypes']);
    Route::get('question-categories', [UniversalDataController::class, 'getQuestionCategories']);
    Route::get('difficulty-levels', [UniversalDataController::class, 'getDifficultyLevels']);

    // Test Attempts
    Route::get('attempts', [TestAttemptController::class, 'index']);
    Route::get('attempts/{attempt}', [TestAttemptController::class, 'show']);

    // Cheating Data
    Route::get('/cheating', [CheatingDataController::class, 'index']);
    Route::post('/cheating', [CheatingDataController::class, 'store']);
    Route::get('/test/{testId}/summary', [CheatingDataController::class, 'testSummary']);

    // Specialized endpoints
    Route::post('/ai/generate', [AiController::class, 'generate']);
    Route::post('/cheating/detect', [CheatingDetectionController::class, 'store']);

    // Admin routes
    Route::prefix('admin/database')->group(function () {
        Route::post('/execute', [DatabaseController::class, 'executeQuery'])
             ->middleware('ability:admin,super-admin');
    });
});

// The UniversalDataController provides direct, un-scoped access to database tables and has been
// disabled due to major security vulnerabilities. Any user could access, modify, or delete
// any data in the allowed tables. It is highly recommended to create dedicated,
// policy-controlled endpoints for each resource instead of using this controller.
// Route::post('/data/{operation}', [UniversalDataController::class, 'handleDataOperation'])
//     ->where('operation', 'create|read|update|delete');
//
// Route::get('/schema/{table}', [UniversalDataController::class, 'getTableSchema']);
