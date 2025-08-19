<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TestInvitation; // Assuming you have an Eloquent model for test_invitations
use Illuminate\Validation\Rule;

class TestInvitationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get all test invitations. You may want to add pagination for large datasets.
        $testInvitations = TestInvitation::all();
        return response()->json($testInvitations);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate the incoming request data based on the table schema.
        $validatedData = $request->validate([
            'client_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'created_by' => 'nullable|integer',
            'test_id' => 'required|integer',
            'invited_by' => 'required|integer',
            'invitee_email' => 'required|email|max:255',
            'token' => 'required|string|max:64|unique:test_invitations',
            'expires_at' => 'required|date',
            'status' => ['nullable', Rule::in(['sent', 'opened', 'started', 'completed', 'expired'])],
            'custom_message' => 'nullable|string',
            'base_lang' => 'nullable|string|max:5',
            'active' => 'nullable|string|max:5',
        ]);

        // Create a new TestInvitation record using the validated data.
        $testInvitation = TestInvitation::create($validatedData);

        return response()->json([
            'message' => 'Test invitation created successfully.',
            'test_invitation' => $testInvitation
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
        // Find the test invitation by its ID.
        $testInvitation = TestInvitation::find($id);

        if (!$testInvitation) {
            return response()->json(['message' => 'Test invitation not found.'], 404);
        }

        return response()->json($testInvitation);
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
        // Find the test invitation to update.
        $testInvitation = TestInvitation::find($id);

        if (!$testInvitation) {
            return response()->json(['message' => 'Test invitation not found.'], 404);
        }

        // Validate the incoming request data for the update.
        $validatedData = $request->validate([
            'client_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'created_by' => 'nullable|integer',
            'test_id' => 'required|integer',
            'invited_by' => 'required|integer',
            'invitee_email' => 'required|email|max:255',
            'expires_at' => 'required|date',
            'status' => ['nullable', Rule::in(['sent', 'opened', 'started', 'completed', 'expired'])],
            'custom_message' => 'nullable|string',
            'base_lang' => 'nullable|string|max:5',
            'active' => 'nullable|string|max:5',
        ]);

        // Update the record with the validated data.
        $testInvitation->update($validatedData);

        return response()->json([
            'message' => 'Test invitation updated successfully.',
            'test_invitation' => $testInvitation
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
        // Find the test invitation to delete.
        $testInvitation = TestInvitation::find($id);

        if (!$testInvitation) {
            return response()->json(['message' => 'Test invitation not found.'], 404);
        }

        // Delete the record. Since the table has `deleted_at`, this will be a soft delete.
        $testInvitation->delete();

        return response()->json(['message' => 'Test invitation deleted successfully.']);
    }
}
