<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vault;
use Illuminate\Http\Request;

class VaultController extends Controller
{
    // Get all vaults for a user
    public function index(Request $request)
    {
        $userProfileId = $request->user()->id_profile; // Assuming authenticated user
        $vaults = Vault::where('id_profile', $userProfileId)->get();
        return response()->json($vaults);
    }

    // Create a new vault
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $vault = Vault::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'id_profile' => $request->user()->id_profile,
        ]);

        return response()->json($vault, 201);
    }

    // Delete a vault
    public function destroy($id)
    {
        $vault = Vault::findOrFail($id);
        $vault->delete();
        return response()->json(['message' => 'Vault deleted successfully']);
    }
}