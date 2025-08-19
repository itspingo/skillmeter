<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UniversalDataController extends Controller
{
    public function getQuestionTypes()
    {
        $types = DB::table('question_types')->get();
        return response()->json(['data' => $types]);
    }

    public function getQuestionCategories()
    {
        $categories = DB::table('question_categories')->get();
        return response()->json(['data' => $categories]);
    }

    public function getDifficultyLevels()
    {
        $levels = DB::table('difficulty_levels')->get();
        return response()->json(['data' => $levels]);
    }
}