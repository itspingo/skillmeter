<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuestionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionTypesController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $query = QuestionType::query();
        
        // System types are available to all, client-specific types only to their owners
        if (Auth::user()->client_id) {
            $query->where(function($q) {
                $q->whereNull('client_id')
                  ->orWhere('client_id', Auth::user()->client_id);
            });
        }
        
        $types = $query->orderBy('name')
                      ->paginate($perPage);
        
        return response()->json($types);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:30|unique:question_types,name',
            'description' => 'nullable|string|max:100',
            'has_options' => 'required|boolean',
            'has_text_answer' => 'required|boolean',
            'is_scorable' => 'required|boolean',
        ]);
        
        $type = QuestionType::create(array_merge($validated, [
            'client_id' => Auth::user()->client_id,
            'created_by' => Auth::id(),
        ]));
        
        return response()->json($type, 201);
    }

    public function show($id)
    {
        $type = QuestionType::findOrFail($id);
        
        if (Auth::user()->client_id && $type->client_id && $type->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this question type');
        }
        
        return response()->json($type);
    }

    public function update(Request $request, $id)
    {
        $type = QuestionType::findOrFail($id);
        
        if (Auth::user()->client_id && $type->client_id && $type->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this question type');
        }
        
        // Prevent modification of system types
        if (!$type->client_id && Auth::user()->client_id) {
            abort(403, 'System question types cannot be modified');
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:30|unique:question_types,name,'.$id,
            'description' => 'sometimes|string|max:100',
            'has_options' => 'sometimes|boolean',
            'has_text_answer' => 'sometimes|boolean',
            'is_scorable' => 'sometimes|boolean',
            'active' => 'sometimes|string|in:1,0',
        ]);
        
        $type->update($validated);
        
        return response()->json($type);
    }

    public function destroy($id)
    {
        $type = QuestionType::findOrFail($id);
        
        if (Auth::user()->client_id && $type->client_id && $type->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this question type');
        }
        
        // Prevent deletion of system types
        if (!$type->client_id) {
            abort(403, 'System question types cannot be deleted');
        }
        
        $type->delete();
        
        return response()->json(['message' => 'Question type deleted successfully']);
    }
}