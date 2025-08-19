<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ResponseSelectedOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResponseSelectedOptionsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $query = ResponseSelectedOption::query();
        
        if (Auth::user()->client_id) {
            $query->where('client_id', Auth::user()->client_id);
        }
        
        if ($request->has('option_id')) {
            $query->where('option_id', $request->option_id);
        }
        
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        $options = $query->orderBy('created_at', 'desc')
                        ->paginate($perPage);
        
        return response()->json($options);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'option_id' => 'required|exists:question_options,id',
            'is_correct' => 'required|boolean',
        ]);
        
        $option = ResponseSelectedOption::create(array_merge($validated, [
            'client_id' => Auth::user()->client_id,
            'user_id' => Auth::id(),
            'created_by' => Auth::id(),
        ]));
        
        return response()->json($option, 201);
    }

    public function show($id)
    {
        $option = ResponseSelectedOption::findOrFail($id);
        
        if (Auth::user()->client_id && $option->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this selected option');
        }
        
        return response()->json($option);
    }

    public function update(Request $request, $id)
    {
        $option = ResponseSelectedOption::findOrFail($id);
        
        if (Auth::user()->client_id && $option->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this selected option');
        }
        
        $validated = $request->validate([
            'is_correct' => 'sometimes|boolean',
            'active' => 'sometimes|string|in:1,0',
        ]);
        
        $option->update($validated);
        
        return response()->json($option);
    }

    public function destroy($id)
    {
        $option = ResponseSelectedOption::findOrFail($id);
        
        if (Auth::user()->client_id && $option->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this selected option');
        }
        
        $option->delete();
        
        return response()->json(['message' => 'Selected option deleted successfully']);
    }
}