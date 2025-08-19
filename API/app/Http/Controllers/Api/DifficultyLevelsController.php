<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DifficultyLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DifficultyLevelsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $query = DifficultyLevel::query();
        
        if (Auth::user()->client_id) {
            $query->where('client_id', Auth::user()->client_id)
                 ->orWhereNull('client_id');
        }
        
        if ($request->has('active')) {
            $query->where('active', $request->active);
        }
        
        $levels = $query->orderBy('weight')
                       ->paginate($perPage);
        
        return response()->json($levels);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20',
            'description' => 'nullable|string|max:100',
            'weight' => 'required|integer|min:1|max:255',
        ]);
        
        $level = DifficultyLevel::create(array_merge($validated, [
            'client_id' => Auth::user()->client_id,
            'created_by' => Auth::id(),
        ]));
        
        return response()->json($level, 201);
    }

    public function show($id)
    {
        $level = DifficultyLevel::findOrFail($id);
        
        if (Auth::user()->client_id && $level->client_id && $level->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this difficulty level');
        }
        
        return response()->json($level);
    }

    public function update(Request $request, $id)
    {
        $level = DifficultyLevel::findOrFail($id);
        
        if (Auth::user()->client_id && $level->client_id && $level->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this difficulty level');
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:20',
            'description' => 'sometimes|string|max:100',
            'weight' => 'sometimes|integer|min:1|max:255',
            'active' => 'sometimes|string|in:1,0',
        ]);
        
        $level->update($validated);
        
        return response()->json($level);
    }

    public function destroy($id)
    {
        $level = DifficultyLevel::findOrFail($id);
        
        if (Auth::user()->client_id && $level->client_id && $level->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this difficulty level');
        }
        
        $level->delete();
        
        return response()->json(['message' => 'Difficulty level deleted successfully']);
    }
}