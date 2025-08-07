<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\TestAttempt;
use App\Models\TestInvitation;
use App\Models\Test;
use App\Models\User;
use App\Http\Resources\TestAttemptResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestAttemptController extends BaseController
{
    protected $model = TestAttempt::class;
    protected $resource = TestAttemptResource::class;
    protected $collection = TestAttemptResource::class; // Assuming TestAttemptResource handles collections
    protected $withRelations = ['test', 'user', 'responses', 'responses.question'];

    /**
     * Display a listing of the resource.
     * - Individuals see their own attempts.
     * - Recruiters see attempts for tests they created.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = TestAttempt::with($this->withRelations);

        if ($user->userType->name === 'recruiter') {
            // Scope to attempts on tests created by the recruiter
            $query->whereHas('test', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        } else {
            // Scope to the individual's own attempts
            $query->where('user_id', $user->id);
        }

        $perPage = $request->get('per_page', 15);
        $attempts = $query->paginate($perPage);

        return $this->collection::collection($attempts);
    }

    /**
     * Display the specified resource.
     * - Individuals can only see their own attempt.
     * - Recruiters can see any attempt for a test they created.
     */
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
        return new $this->resource($attempt);
    }

    /**
     * Not applicable for Test Attempts.
     */
    public function store(Request $request)
    {
        return response()->json(['message' => 'Cannot create attempts directly. Use startTest or startSelfAssessment.'], 405);
    }
    
    /**
     * Not applicable for Test Attempts.
     */
    public function update(Request $request, $id)
    {
        return response()->json(['message' => 'Cannot update attempts directly. Use submitTest.'], 405);
    }

    /**
     * Not applicable for Test Attempts.
     */
    public function destroy($id)
    {
        return response()->json(['message' => 'Cannot delete test attempts.'], 405);
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

        return new TestAttemptResource($attempt->load($this->withRelations));
    }

    public function submitTest(Request $request, $attemptId)
    {
        // An individual can only submit their own test.
        $attempt = Auth::user()->testAttempts()->findOrFail($attemptId);
        
        if ($attempt->submitted_at) {
            return response()->json(['message' => 'Test already submitted'], 400);
        }

        // Validation and response saving logic...
        // ... (assuming the rest of the logic is correct)

        return new TestAttemptResource($attempt->load($this->withRelations));
    }

    public function startSelfAssessment(Request $request, $testId)
    {
        $user = $request->user();
        // Any user can attempt any 'public' or 'self-assessable' test.
        // We assume the Test model has a scope for this, e.g., scopePublic()
        $test = Test::public()->findOrFail($testId);

        $attempt = TestAttempt::create([
            'test_id' => $test->id,
            'user_id' => $user->id,
            'client_id' => $user->client_id,
            'started_at' => now(),
        ]);

        return new TestAttemptResource($attempt->load($this->withRelations));
    }
}
