<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecruiterProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecruiterProfilesController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $query = RecruiterProfile::query();
        
        if (Auth::user()->client_id) {
            $query->where('client_id', Auth::user()->client_id);
        }
        
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        
        $profiles = $query->orderBy('created_at', 'desc')
                         ->paginate($perPage);
        
        return response()->json($profiles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'company_id' => 'nullable|exists:companies,id',
            'job_title' => 'nullable|string|max:50',
            'department' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
        ]);
        
        $profile = RecruiterProfile::create(array_merge($validated, [
            'client_id' => Auth::user()->client_id,
            'created_by' => Auth::id(),
        ]));
        
        return response()->json($profile, 201);
    }

    public function show($id)
    {
        $profile = RecruiterProfile::findOrFail($id);
        
        if (Auth::user()->client_id && $profile->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this recruiter profile');
        }
        
        return response()->json($profile);
    }

    public function update(Request $request, $id)
    {
        $profile = RecruiterProfile::findOrFail($id);
        
        if (Auth::user()->client_id && $profile->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this recruiter profile');
        }
        
        $validated = $request->validate([
            'company_id' => 'sometimes|exists:companies,id',
            'job_title' => 'sometimes|string|max:50',
            'department' => 'sometimes|string|max:50',
            'phone' => 'sometimes|string|max:20',
            'active' => 'sometimes|string|in:1,0',
        ]);
        
        $profile->update($validated);
        
        return response()->json($profile);
    }

    public function destroy($id)
    {
        $profile = RecruiterProfile::findOrFail($id);
        
        if (Auth::user()->client_id && $profile->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this recruiter profile');
        }
        
        $profile->delete();
        
        return response()->json(['message' => 'Recruiter profile deleted successfully']);
    }
}