<?php

namespace App\Http\Controllers\Api;

use App\Models\CheatingData;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Http\Resources\CheatingDataResource;

class CheatingDataController extends Controller
{
    public function index(Request $request)
    {
        $query = CheatingData::with(['user', 'test'])
            ->latest();
            
        if ($request->test_id) {
            $query->where('test_id', $request->test_id);
        }
        
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        } 
        
        return CheatingDataResource::collection($query->paginate(25));
    }

    // Record new cheating incident (called by proctoring system)
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'test_id' => 'required|exists:tests,id',
            'event_type' => 'required|string|max:200',
            'confidence' => 'nullable|string|max:50',
            'duration_seconds' => 'nullable|numeric',
            'face_detected' => 'boolean',
            'screenshot_path' => 'nullable|string',
            'process_time' => 'required|date'
        ]);

        $cheatingData = CheatingData::create($data);

        $cheatingData->load(['user', 'test']);

        return new CheatingDataResource($cheatingData);
    }

    // Get cheating summary for a test
    public function testSummary($testId)
    {
        $data = CheatingData::where('test_id', $testId)
            ->selectRaw('
                event_type,
                COUNT(*) as incident_count,
                AVG(confidence) as avg_confidence
            ')
            ->groupBy('event_type')
            ->get();

        return response()->json($data);
    }
}