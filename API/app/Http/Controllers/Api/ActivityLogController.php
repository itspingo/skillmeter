<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $query = ActivityLog::query();
        
        // Filter by client if user is associated with a client
        if (Auth::user()->client_id) {
            $query->where('client_id', Auth::user()->client_id);
        }
        
        // Filter by user if requested
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        // Filter by activity type if requested
        if ($request->has('activity_type')) {
            $query->where('activity_type', $request->activity_type);
        }
        
        // Filter by date range if requested
        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }
        
        $logs = $query->orderBy('created_at', 'desc')
                      ->paginate($perPage);
                      
        return response()->json($logs);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'activity_type' => 'required|string|max:50',
            'activity_details' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);
        
        $logData = array_merge($validated, [
            'client_id' => Auth::user()->client_id,
            'created_by' => Auth::id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        $log = ActivityLog::create($logData);
        
        return response()->json($log, 201);
    }

    public function show($id)
    {
        $log = ActivityLog::findOrFail($id);
        
        // Verify the user has access to this log
        if (Auth::user()->client_id && $log->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this activity log');
        }
        
        return response()->json($log);
    }

    public function update(Request $request, $id)
    {
        $log = ActivityLog::findOrFail($id);
        
        // Verify the user has access to this log
        if (Auth::user()->client_id && $log->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this activity log');
        }
        
        $validated = $request->validate([
            'activity_details' => 'nullable|string',
            'active' => 'nullable|string|in:1,0',
        ]);
        
        $log->update($validated);
        
        return response()->json($log);
    }

    public function destroy($id)
    {
        $log = ActivityLog::findOrFail($id);
        
        // Verify the user has access to this log
        if (Auth::user()->client_id && $log->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this activity log');
        }
        
        $log->delete();
        
        return response()->json(['message' => 'Activity log deleted successfully.'], 200);
    }
}