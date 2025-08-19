<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CheatingDetectionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    
    public function store(Request $request)
    {
        // TODO: Implement cheating detection logic
        return response()->json(['message' => 'Cheating detection endpoint is not yet implemented.'], 501);
    }
}
