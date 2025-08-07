<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Test;
use App\Models\TestInvitation;
use App\Http\Resources\TestResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestController extends BaseController
{
    protected $model = Test::class;
    protected $resource = TestResource::class;
    protected $collection = TestResource::class; // Assuming TestResource handles collections
    protected $withRelations = ['creator', 'questions', 'questions.type', 'questions.category'];

    /**
     * Display a listing of the resource scoped to the authenticated user.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $tests = Auth::user()->testsCreated()->with($this->withRelations)->paginate($perPage);
        return $this->collection::collection($tests);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate(Test::$rules);
        $test = Auth::user()->testsCreated()->create($validatedData);
        return new $this->resource($test->load($this->withRelations));
    }

    /**
     * Display the specified resource scoped to the authenticated user.
     */
    public function show($id)
    {
        $test = Auth::user()->testsCreated()->with($this->withRelations)->findOrFail($id);
        return new $this->resource($test);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $test = Auth::user()->testsCreated()->findOrFail($id);
        $validatedData = $request->validate(Test::$rules);
        $test->update($validatedData);
        return new $this->resource($test->load($this->withRelations));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $test = Auth::user()->testsCreated()->findOrFail($id);
        $test->delete();
        return response()->json(['message' => 'Test deleted successfully.']);
    }

    public function addQuestions(Request $request, $testId)
    {
        $test = Auth::user()->testsCreated()->findOrFail($testId);
        
        $request->validate([
            'questions' => 'required|array',
            'questions.*.question_id' => 'required|exists:questions,id',
            'questions.*.section_name' => 'nullable|string|max:50',
            'questions.*.weight' => 'nullable|integer|min:1',
        ]);

        $questionsToAdd = collect($request->questions)
            ->mapWithKeys(function ($item) {
                return [
                    $item['question_id'] => [
                        'section_name' => $item['section_name'] ?? null,
                        'weight' => $item['weight'] ?? 1,
                    ]
                ];
            });

        $test->questions()->syncWithoutDetaching($questionsToAdd);

        return new $this->resource($test->load($this->withRelations));
    }

    public function removeQuestion($testId, $questionId)
    {
        $test = Auth::user()->testsCreated()->findOrFail($testId);
        $test->questions()->detach($questionId);

        return new $this->resource($test->load($this->withRelations));
    }

    public function inviteCandidates(Request $request, $testId)
    {
        $test = Auth::user()->testsCreated()->findOrFail($testId);
        
        $request->validate([
            'emails' => 'required|array',
            'emails.*' => 'required|email',
            'expires_at' => 'required|date|after:now',
            'custom_message' => 'nullable|string',
        ]);

        $invitations = collect($request->emails)->map(function ($email) use ($test, $request) {
            return $test->invitations()->create([
                'invited_by' => Auth::id(),
                'invitee_email' => $email,
                'token' => \Str::random(64),
                'expires_at' => $request->expires_at,
                'custom_message' => $request->custom_message,
                'client_id' => Auth::user()->client_id,
            ]);
        });

        // Dispatch job to send emails here

        return response()->json([
            'message' => 'Invitations sent successfully',
            'data' => $invitations,
        ]);
    }

    public function getByInvitation($token)
    {
        $invitation = TestInvitation::where('token', $token)->firstOrFail();

        if ($invitation->expires_at && $invitation->expires_at->isPast()) {
            return response()->json(['message' => 'This invitation has expired.'], 410);
        }

        if ($invitation->completed_at) {
            return response()->json(['message' => 'This test has already been completed.'], 410);
        }

        // Optionally, mark the invitation as opened
        if (!$invitation->first_opened_at) {
            $invitation->update(['first_opened_at' => now()]);
        }

        return new $this->resource($invitation->test->load($this->withRelations));
    }
}
