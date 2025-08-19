<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function generate(Request $request)
    {
        // TODO: Implement AI generation logic
        return response()->json(['message' => 'AI generation endpoint is not yet implemented.'], 501);
    }
}
