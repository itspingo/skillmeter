<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Test;
use App\Models\TestInvitation;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Auth;

class TestController extends BaseController
{
    protected $model = Test::class;
    protected $withRelations = ['creator', 'questions', 'questions.type', 'questions.difficulty'];

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $tests = Auth::user()->testsCreated()->with($this->withRelations)->paginate($perPage);
        return response()->json($tests);
    }

    public function store(Request $request)
    {
        $test = Auth::user()->testsCreated()->create($request->all());
        return response()->json([
            'success' => true,
            'test' => $test,
            'message' => 'Test created successfully'
        ], 201);
    }

    public function show($id)
    {
        $test = Auth::user()->testsCreated()->with($this->withRelations)->findOrFail($id);
        return response()->json($test);
    }

    public function update(Request $request, $id)
    {
        $test = Auth::user()->testsCreated()->findOrFail($id);
        $test->update($request->all());
        return response()->json([
            'success' => true,
            'test' => $test->load($this->withRelations),
            'message' => 'Test updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $test = Auth::user()->testsCreated()->findOrFail($id);
        $test->delete();
        return response()->json(['message' => 'Test deleted successfully.']);
    }

    public function addQuestions(Request $request, $testId)
    {
        $test = Auth::user()->testsCreated()->findOrFail($testId);
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
        return response()->json($test->load($this->withRelations));
    }

    public function removeQuestion($testId, $questionId)
    {
        $test = Auth::user()->testsCreated()->findOrFail($testId);
        $test->questions()->detach($questionId);
        return response()->json($test->load($this->withRelations));
    }

    public function inviteCandidates(Request $request, $testId)
    {
        $test = Auth::user()->testsCreated()->findOrFail($testId);
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

        if (!$invitation->first_opened_at) {
            $invitation->update(['first_opened_at' => now()]);
        }

        return response()->json($invitation->test->load($this->withRelations));
    }
}