<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuestionResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionResponsesController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $query = QuestionResponse::query();
        
        if (Auth::user()->client_id) {
            $query->where('client_id', Auth::user()->client_id);
        }
        
        if ($request->has('attempt_id')) {
            $query->where('attempt_id', $request->attempt_id);
        }
        
        if ($request->has('question_id')) {
            $query->where('question_id', $request->question_id);
        }
        
        $responses = $query->orderBy('created_at', 'desc')
                          ->paginate($perPage);
        
        return response()->json($responses);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'attempt_id' => 'required|exists:test_attempts,id',
            'question_id' => 'required|exists:questions,id',
            'question_type_id' => 'required|exists:question_types,id',
            'response_text' => 'nullable|string',
            'response_options' => 'nullable|json',
            'time_spent_seconds' => 'nullable|integer',
        ]);
        
        $response = QuestionResponse::create(array_merge($validated, [
            'client_id' => Auth::user()->client_id,
            'created_by' => Auth::id(),
            'max_score' => $request->max_score ?? 1,
        ]));
        
        return response()->json($response, 201);
    }

    public function show($id)
    {
        $response = QuestionResponse::findOrFail($id);
        
        if (Auth::user()->client_id && $response->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this response');
        }
        
        return response()->json($response);
    }

    public function update(Request $request, $id)
    {
        $response = QuestionResponse::findOrFail($id);
        
        if (Auth::user()->client_id && $response->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this response');
        }
        
        $validated = $request->validate([
            'response_text' => 'sometimes|string',
            'response_options' => 'sometimes|json',
            'is_correct' => 'sometimes|boolean',
            'score' => 'sometimes|numeric',
            'feedback' => 'sometimes|string',
            'graded_by' => 'sometimes|exists:users,id',
        ]);
        
        if (isset($validated['graded_by'])) {
            $validated['graded_at'] = now();
        }
        
        $response->update($validated);
        
        return response()->json($response);
    }

    public function destroy($id)
    {
        $response = QuestionResponse::findOrFail($id);
        
        if (Auth::user()->client_id && $response->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this response');
        }
        
        $response->delete();
        
        return response()->json(['message' => 'Response deleted successfully']);
    }
}