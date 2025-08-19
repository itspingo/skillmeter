<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TestResult; // Assuming you have an Eloquent model for test_results

class TestResultController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get all test results. You may want to add pagination for large datasets.
        $testResults = TestResult::all();
        return response()->json($testResults);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate the incoming request data.
        $validatedData = $request->validate([
            'client_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'created_by' => 'nullable|integer',
            'test_id' => 'required|integer',
            'score' => 'nullable|integer',
            'total_questions' => 'nullable|integer',
            'answers' => 'nullable|string',
            'duration' => 'nullable|integer',
            'completed_at' => 'nullable|date',
            'base_lang' => 'nullable|string|max:5',
            'active' => 'nullable|string|max:5',
        ]);

        // Create a new TestResult record.
        $testResult = TestResult::create($validatedData);

        return response()->json([
            'message' => 'Test result created successfully.',
            'test_result' => $testResult
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Find the test result by its ID.
        $testResult = TestResult::find($id);

        if (!$testResult) {
            return response()->json(['message' => 'Test result not found.'], 404);
        }

        return response()->json($testResult);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Find the test result to update.
        $testResult = TestResult::find($id);

        if (!$testResult) {
            return response()->json(['message' => 'Test result not found.'], 404);
        }

        // Validate the incoming request data for the update.
        $validatedData = $request->validate([
            'client_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'created_by' => 'nullable|integer',
            'test_id' => 'required|integer',
            'score' => 'nullable|integer',
            'total_questions' => 'nullable|integer',
            'answers' => 'nullable|string',
            'duration' => 'nullable|integer',
            'completed_at' => 'nullable|date',
            'base_lang' => 'nullable|string|max:5',
            'active' => 'nullable|string|max:5',
        ]);

        // Update the record with the validated data.
        $testResult->update($validatedData);

        return response()->json([
            'message' => 'Test result updated successfully.',
            'test_result' => $testResult
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Find the test result to delete.
        $testResult = TestResult::find($id);

        if (!$testResult) {
            return response()->json(['message' => 'Test result not found.'], 404);
        }

        // Delete the record. Since the table has `deleted_at`, this will be a soft delete.
        $testResult->delete();

        return response()->json(['message' => 'Test result deleted successfully.']);
    }
}
