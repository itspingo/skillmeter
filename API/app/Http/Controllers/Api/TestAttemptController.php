<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\TestAttempt;
use App\Models\TestInvitation;
use App\Models\Test;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestAttemptController extends BaseController
{
    protected $model = TestAttempt::class;
    protected $withRelations = ['test', 'user', 'responses', 'responses.question'];

    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['startTest', 'submitTest']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = TestAttempt::with($this->withRelations);

        if ($user->userType->name === 'recruiter') {
            $query->whereHas('test', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        } else {
            $query->where('user_id', $user->id);
        }

        $perPage = $request->get('per_page', 15);
        $attempts = $query->paginate($perPage);

        return response()->json($attempts);
    }

    public function show($id)
    {
        $user = Auth::user();
        $query = TestAttempt::with($this->withRelations);

        if ($user->userType->name === 'recruiter') {
            $query->whereHas('test', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        } else {
            $query->where('user_id', $user->id);
        }

        $attempt = $query->findOrFail($id);
        return response()->json($attempt);
    }

    public function startTest(Request $request, $invitationToken)
    {
        $invitation = TestInvitation::where('token', $invitationToken)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        if ($invitation->status === 'completed') {
            return response()->json(['message' => 'Test already completed'], 400);
        }

        $user = $request->user() ?? User::firstOrCreate(['email' => $invitation->invitee_email], ['name' => 'Guest']);

        $attempt = TestAttempt::create([
            'test_id' => $invitation->test_id,
            'user_id' => $user->id,
            'invitation_id' => $invitation->id,
            'client_id' => $invitation->client_id,
            'started_at' => now(),
        ]);

        $invitation->update([
            'status' => 'started',
            'started_at' => now(),
        ]);

        return response()->json($attempt->load($this->withRelations));
    }

    public function submitTest(Request $request, $attemptId)
    {
        $user = Auth::user();
        $attempt = $user->testAttempts()->findOrFail($attemptId);
        
        if ($attempt->submitted_at) {
            return response()->json(['message' => 'Test already submitted'], 400);
        }

        $attempt->update(['submitted_at' => now()]);

        return response()->json($attempt->load($this->withRelations));
    }

    public function startSelfAssessment(Request $request, $testId)
    {
        $user = $request->user();
        $test = Test::public()->findOrFail($testId);

        $attempt = TestAttempt::create([
            'test_id' => $test->id,
            'user_id' => $user->id,
            'client_id' => $user->client_id,
            'started_at' => now(),
        ]);

        return response()->json($attempt->load($this->withRelations));
    }
}