<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheatingData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheatingDataController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $query = CheatingData::query();
        
        if (Auth::user()->client_id) {
            $query->where('client_id', Auth::user()->client_id);
        }
        
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->has('test_id')) {
            $query->where('test_id', $request->test_id);
        }
        
        if ($request->has('event_type')) {
            $query->where('event_type', $request->event_type);
        }
        
        $data = $query->orderBy('process_time', 'desc')
                     ->paginate($perPage);
        
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'test_id' => 'required|string',
            'process_time' => 'required|date',
            'event_type' => 'nullable|string|max:200',
            'confidence' => 'nullable|string|max:50',
            'duration_seconds' => 'nullable|string|max:100',
            'face_detected' => 'nullable|boolean',
        ]);
        
        $cheatingData = CheatingData::create(array_merge($validated, [
            'client_id' => Auth::user()->client_id,
            'created_by' => Auth::id(),
        ]));
        
        return response()->json($cheatingData, 201);
    }

    public function show($id)
    {
        $data = CheatingData::findOrFail($id);
        
        if (Auth::user()->client_id && $data->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this cheating data');
        }
        
        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        $data = CheatingData::findOrFail($id);
        
        if (Auth::user()->client_id && $data->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this cheating data');
        }
        
        $validated = $request->validate([
            'event_type' => 'sometimes|string|max:200',
            'confidence' => 'sometimes|string|max:50',
            'duration_seconds' => 'sometimes|string|max:100',
            'active' => 'sometimes|string|in:1,0',
        ]);
        
        $data->update($validated);
        
        return response()->json($data);
    }

    public function destroy($id)
    {
        $data = CheatingData::findOrFail($id);
        
        if (Auth::user()->client_id && $data->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this cheating data');
        }
        
        $data->delete();
        
        return response()->json(['message' => 'Cheating data deleted successfully']);
    }
}