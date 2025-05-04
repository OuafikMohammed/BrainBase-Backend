<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VaultController extends Controller
{
    // Get all vaults for the authenticated user
    public function index(Request $request)
    {
        $vaults = Vault::where('id_profile', $request->user()->id_profile)
            ->with(['elements' => function ($query) {
                $query->orderBy('name');
            }])
            ->get();
        
        return response()->json($vaults);
    }

    // Get a specific vault with its elements
    public function show(Request $request, $id)
    {
        $vault = Vault::with(['elements' => function ($query) {
            $query->orderBy('name');
        }])->findOrFail($id);

        // Check if user has access to this vault
        if ($vault->id_profile !== $request->user()->id_profile) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($vault);
    }

    // Create a new vault
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vault = new Vault($request->all());
        $vault->id_profile = $request->user()->id_profile;
        $vault->settings = $request->settings ?? [];
        $vault->save();

        // Initialize the vault with empty items array for frontend compatibility
        $vault->items = [];
        
        return response()->json($vault, 201);
    }

    // Update a vault
    public function update(Request $request, $id)
    {
        $vault = Vault::findOrFail($id);

        // Check if user has access to this vault
        if ($vault->id_profile !== $request->user()->id_profile) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'settings' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vault->fill($request->all());
        $vault->save();

        return response()->json($vault);
    }

    // Delete a vault
    public function destroy(Request $request, $id)
    {
        $vault = Vault::findOrFail($id);

        // Check if user has access to this vault
        if ($vault->id_profile !== $request->user()->id_profile) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete all elements in the vault
        $vault->elements()->delete();
        $vault->delete();

        return response()->json(['message' => 'Vault deleted successfully']);
    }
}