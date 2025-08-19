<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BrowserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrowserActivityController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $query = BrowserActivity::query();
        
        // Filter by client if user is associated with a client
        if (Auth::user()->client_id) {
            $query->where('client_id', Auth::user()->client_id);
        }
        
        // Apply filters
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->has('test_id')) {
            $query->where('test_id', $request->test_id);
        }
        
        if ($request->has('date_from')) {
            $query->where('timestamp', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->where('timestamp', '<=', $request->date_to);
        }
        
        $activities = $query->orderBy('timestamp', 'desc')
                           ->paginate($perPage);
        
        return response()->json($activities);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'test_id' => 'nullable|exists:tests,id',
            'timestamp' => 'required|date',
            'browser_title' => 'required|string|max:255',
            'duration' => 'required|numeric',
        ]);
        
        $activity = BrowserActivity::create(array_merge($validated, [
            'client_id' => Auth::user()->client_id,
            'created_by' => Auth::id(),
        ]));
        
        return response()->json($activity, 201);
    }

    public function show($id)
    {
        $activity = BrowserActivity::findOrFail($id);
        
        // Verify access
        if (Auth::user()->client_id && $activity->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this browser activity');
        }
        
        return response()->json($activity);
    }

    public function update(Request $request, $id)
    {
        $activity = BrowserActivity::findOrFail($id);
        
        // Verify access
        if (Auth::user()->client_id && $activity->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this browser activity');
        }
        
        $validated = $request->validate([
            'browser_title' => 'sometimes|string|max:255',
            'duration' => 'sometimes|numeric',
            'active' => 'sometimes|string|in:1,0',
        ]);
        
        $activity->update($validated);
        
        return response()->json($activity);
    }

    public function destroy($id)
    {
        $activity = BrowserActivity::findOrFail($id);
        
        // Verify access
        if (Auth::user()->client_id && $activity->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this browser activity');
        }
        
        $activity->delete();
        
        return response()->json(['message' => 'Browser activity deleted successfully']);
    }
}