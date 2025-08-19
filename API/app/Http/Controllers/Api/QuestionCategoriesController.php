<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuestionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionCategoriesController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $query = QuestionCategory::query();
        
        if (Auth::user()->client_id) {
            $query->where('client_id', Auth::user()->client_id)
                 ->orWhere('is_system', true);
        }
        
        if ($request->has('parent_category_id')) {
            $query->where('parent_category_id', $request->parent_category_id);
        }
        
        $categories = $query->orderBy('name')
                           ->paginate($perPage);
        
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'parent_category_id' => 'nullable|exists:question_categories,id',
        ]);
        
        $category = QuestionCategory::create(array_merge($validated, [
            'client_id' => Auth::user()->client_id,
            'created_by' => Auth::id(),
        ]));
        
        return response()->json($category, 201);
    }

    public function show($id)
    {
        $category = QuestionCategory::findOrFail($id);
        
        if (Auth::user()->client_id && !$category->is_system && $category->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this category');
        }
        
        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $category = QuestionCategory::findOrFail($id);
        
        if (Auth::user()->client_id && !$category->is_system && $category->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this category');
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:50',
            'description' => 'sometimes|string|max:255',
            'parent_category_id' => 'sometimes|exists:question_categories,id',
            'active' => 'sometimes|string|in:1,0',
        ]);
        
        $category->update($validated);
        
        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = QuestionCategory::findOrFail($id);
        
        if (Auth::user()->client_id && !$category->is_system && $category->client_id !== Auth::user()->client_id) {
            abort(403, 'Unauthorized access to this category');
        }
        
        if ($category->is_system) {
            abort(403, 'System categories cannot be deleted');
        }
        
        $category->delete();
        
        return response()->json(['message' => 'Category deleted successfully']);
    }
}