<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Question;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionController extends BaseController
{
    protected $model = Question::class;
    protected $withRelations = ['type', 'difficulty'];

    // --- Test-Specific Question Methods ---

    public function indexForTest(Test $test)
    {
        // Ensure the user is authorized to view this test's questions
        if ($test->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $questions = $test->questions()->with($this->withRelations)->paginate(15);
        return response()->json($questions);
    }

    public function storeForTest(Request $request, Test $test)
    {
        if ($test->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $data = $request->all();
        $data['test_id'] = $test->id;
        $data['created_by'] = Auth::id();
        
        $question = Question::create($data);
        return response()->json($question->load($this->withRelations), 201);
    }

    // --- General Question Methods (can be used for updates/deletes) ---

    public function show($id)
    {
        $question = Question::with($this->withRelations)->findOrFail($id);
        // Add authorization check
        if ($question->test->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json($question);
    }

    public function update(Request $request, $id)
    {
        $question = Question::findOrFail($id);
        if ($question->test->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $question->update($request->all());
        return response()->json($question->load($this->withRelations));
    }

    public function destroy($id)
    {
        $question = Question::findOrFail($id);
        if ($question->test->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $question->delete();
        return response()->json(['message' => 'Question deleted successfully.']);
    }
}
