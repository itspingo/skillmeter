<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionsController extends Controller
{
    protected $withRelations = ['type', 'difficulty', 'categories'];

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $query = Question::with($this->withRelations);
        
        if (Auth::user()->client_id) {
            $query->where('client_id', Auth::user()->client_id);
        }
        
        if ($request->has('test_id')) {
            $query->where('test_id', $request->test_id);
        }
        
        if ($request->has('type_id')) {
            $query->where('type_id', $request->type_id);
        }
        
        if ($request->has('difficulty_id')) {
            $query->where('difficulty_id', $request->difficulty_id);
        }
        
        $questions = $query->orderBy('created_at', 'desc')
                          ->paginate($perPage);
        
        return response()->json($questions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'test_id' => 'required|exists:tests,id',
            'type_id' => 'required|exists:question_types,id',
            'difficulty_id' => 'nullable|exists:difficulty_levels,id',
            'question_data' => 'required|string',
            'explanation' => 'nullable|string',
            'time_limit' => 'nullable|integer',
            'max_score' => 'sometimes|integer|min:1',
        ]);
        
        $question = Question::create(array_merge($validated, [
            'client_id' => Auth::user()->client_id,
            'created_by' => Auth::id(),
        ]));
        
        return response()->json($question->load($this->withRelations), 201);
    }

    public function show($id)
    {
        $question = Question::with($this->withRelations)->findOrFail($id);
        
        if (Auth::user()->client_id && $question->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this question');
        }
        
        return response()->json($question);
    }

    public function update(Request $request, $id)
    {
        $question = Question::findOrFail($id);
        
        if (Auth::user()->client_id && $question->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this question');
        }
        
        $validated = $request->validate([
            'type_id' => 'sometimes|exists:question_types,id',
            'difficulty_id' => 'sometimes|exists:difficulty_levels,id',
            'question_data' => 'sometimes|string',
            'explanation' => 'sometimes|string',
            'time_limit' => 'sometimes|integer',
            'max_score' => 'sometimes|integer|min:1',
            'active' => 'sometimes|string|in:1,0',
        ]);
        
        $question->update($validated);
        
        return response()->json($question->load($this->withRelations));
    }

    public function destroy($id)
    {
        $question = Question::findOrFail($id);
        
        if (Auth::user()->client_id && $question->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this question');
        }
        
        $question->delete();
        
        return response()->json(['message' => 'Question deleted successfully']);
    }
}