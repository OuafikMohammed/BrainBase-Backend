<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Element;
use Illuminate\Http\Request;

class ElementController extends Controller
{
    // Get all elements for a vault
    public function index(Request $request, $vaultId)
    {
        $elements = Element::where('id_vault', $vaultId)->get();
        return response()->json($elements);
    }

    // Create a new element
    public function store(Request $request, $vaultId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'element_type' => 'required|in:FOLDER,FILE',
            'content_html' => 'nullable|string', // Only required for FILE type
            'id_parent' => 'nullable|exists:elements,id_element',
        ]);

        $element = Element::create([
            'name' => $validated['name'],
            'element_type' => $validated['element_type'],
            'content_html' => $validated['content_html'] ?? null,
            'id_vault' => $vaultId,
            'id_parent' => $validated['id_parent'] ?? null,
        ]);

        return response()->json($element, 201);
    }

    // Update an element
    public function update(Request $request, $id)
    {
        $element = Element::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'content_html' => 'sometimes|string',
        ]);

        $element->update($validated);
        return response()->json($element);
    }

    // Delete an element
    public function destroy($id)
    {
        $element = Element::findOrFail($id);
        $element->delete();
        return response()->json(['message' => 'Element deleted successfully']);
    }
}