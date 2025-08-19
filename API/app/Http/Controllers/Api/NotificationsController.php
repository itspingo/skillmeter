<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $query = Notification::where('user_id', Auth::id());
        
        if ($request->has('is_read')) {
            $query->where('is_read', $request->is_read);
        }
        
        if ($request->has('notification_type')) {
            $query->where('notification_type', $request->notification_type);
        }
        
        $notifications = $query->orderBy('created_at', 'desc')
                             ->paginate($perPage);
        
        return response()->json($notifications);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:100',
            'message' => 'required|string',
            'notification_type' => 'required|in:test_invite,test_completed,ai_generation,system',
            'related_id' => 'nullable|integer',
        ]);
        
        $notification = Notification::create(array_merge($validated, [
            'client_id' => Auth::user()->client_id,
            'created_by' => Auth::id(),
        ]));
        
        return response()->json($notification, 201);
    }

    public function show($id)
    {
        $notification = Notification::where('user_id', Auth::id())
                                   ->findOrFail($id);
        
        return response()->json($notification);
    }

    public function update(Request $request, $id)
    {
        $notification = Notification::where('user_id', Auth::id())
                                   ->findOrFail($id);
        
        $validated = $request->validate([
            'is_read' => 'sometimes|boolean',
        ]);
        
        if (isset($validated['is_read']) && $validated['is_read']) {
            $validated['read_at'] = now();
        }
        
        $notification->update($validated);
        
        return response()->json($notification);
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
                  ->where('is_read', false)
                  ->update([
                      'is_read' => true,
                      'read_at' => now(),
                  ]);
        
        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function destroy($id)
    {
        $notification = Notification::where('user_id', Auth::id())
                                  ->findOrFail($id);
        
        $notification->delete();
        
        return response()->json(['message' => 'Notification deleted successfully']);
    }
}