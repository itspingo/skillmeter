<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CheatingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheatingEventsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $query = CheatingEvent::query();
        
        if (Auth::user()->client_id) {
            $query->where('client_id', Auth::user()->client_id);
        }
        
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->has('event_type')) {
            $query->where('event_type', $request->event_type);
        }
        
        if ($request->has('date_from')) {
            $query->where('timestamp', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->where('timestamp', '<=', $request->date_to);
        }
        
        $events = $query->orderBy('timestamp', 'desc')
                       ->paginate($perPage);
        
        return response()->json($events);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'timestamp' => 'required|date',
            'event_type' => 'required|string|max:50',
            'confidence' => 'nullable|numeric',
            'duration' => 'nullable|numeric',
            'face_detected' => 'nullable|boolean',
            'attention_state' => 'nullable|string|max:255',
            'voice_detected' => 'nullable|boolean',
            'forbidden_app' => 'nullable|boolean',
            'app_name' => 'nullable|string|max:255',
            'screenshot_path' => 'nullable|string|max:255',
        ]);
        
        $event = CheatingEvent::create(array_merge($validated, [
            'client_id' => Auth::user()->client_id,
            'created_by' => Auth::id(),
        ]));
        
        return response()->json($event, 201);
    }

    public function show($id)
    {
        $event = CheatingEvent::findOrFail($id);
        
        if (Auth::user()->client_id && $event->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this cheating event');
        }
        
        return response()->json($event);
    }

    public function update(Request $request, $id)
    {
        $event = CheatingEvent::findOrFail($id);
        
        if (Auth::user()->client_id && $event->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this cheating event');
        }
        
        $validated = $request->validate([
            'event_type' => 'sometimes|string|max:50',
            'confidence' => 'sometimes|numeric',
            'duration' => 'sometimes|numeric',
            'attention_state' => 'sometimes|string|max:255',
            'screenshot_path' => 'sometimes|string|max:255',
            'active' => 'sometimes|string|in:1,0',
        ]);
        
        $event->update($validated);
        
        return response()->json($event);
    }

    public function destroy($id)
    {
        $event = CheatingEvent::findOrFail($id);
        
        if (Auth::user()->client_id && $event->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this cheating event');
        }
        
        $event->delete();
        
        return response()->json(['message' => 'Cheating event deleted successfully']);
    }
}