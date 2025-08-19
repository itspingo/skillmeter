<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserType; // Assuming you have an Eloquent model for user_types

class UserTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get all user types.
        $userTypes = UserType::all();
        return response()->json($userTypes);
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
            'type_name' => 'required|string|max:20',
            'description' => 'nullable|string|max:100',
            'base_lang' => 'nullable|string|max:5',
            'active' => 'nullable|string|max:5',
        ]);

        // Create a new UserType record.
        $userType = UserType::create($validatedData);

        return response()->json([
            'message' => 'User type created successfully.',
            'user_type' => $userType
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
        // Find the user type by its ID.
        $userType = UserType::find($id);

        if (!$userType) {
            return response()->json(['message' => 'User type not found.'], 404);
        }

        return response()->json($userType);
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
        // Find the user type to update.
        $userType = UserType::find($id);

        if (!$userType) {
            return response()->json(['message' => 'User type not found.'], 404);
        }

        // Validate the incoming request data.
        $validatedData = $request->validate([
            'client_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'created_by' => 'nullable|integer',
            'type_name' => 'required|string|max:20',
            'description' => 'nullable|string|max:100',
            'base_lang' => 'nullable|string|max:5',
            'active' => 'nullable|string|max:5',
        ]);

        // Update the record with the validated data.
        $userType->update($validatedData);

        return response()->json([
            'message' => 'User type updated successfully.',
            'user_type' => $userType
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
        // Find the user type to delete.
        $userType = UserType::find($id);

        if (!$userType) {
            return response()->json(['message' => 'User type not found.'], 404);
        }

        // Delete the record. This will be a soft delete.
        $userType->delete();

        return response()->json(['message' => 'User type deleted successfully.']);
    }
}
