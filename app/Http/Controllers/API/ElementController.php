<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Element;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ElementController extends Controller
{
    // Get all elements in a vault
    public function index(Request $request, $vaultId)
    {
        $vault = Vault::findOrFail($vaultId);
        $elements = $vault->elements;
        return response()->json($elements);
    }

    // Create a new element
    public function store(Request $request, $vaultId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'element_type' => 'required|in:FILE,FOLDER',
            'content_html' => 'nullable|string',
            'id_parent' => 'nullable|string|exists:elements,id_element'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vault = Vault::findOrFail($vaultId);
        
        $element = new Element($request->all());
        $element->id_vault = $vaultId;
        $element->tags = $request->tags ?? [];
        $element->versions = [];
        $element->last_edited = now();
        $element->save();

        return response()->json($element, 201);
    }

    // Update an element
    public function update(Request $request, $elementId)
    {
        $element = Element::findOrFail($elementId);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'content_html' => 'nullable|string',
            'id_parent' => 'nullable|string|exists:elements,id_element',
            'tags' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Handle version creation if content is being updated
        if ($request->has('content_html') && $element->content_html !== $request->content_html) {
            $newVersion = [
                'id' => count($element->versions) + 1,
                'date' => now()->toISOString(),
                'content' => $element->content_html
            ];
            
            $versions = $element->versions;
            $versions[] = $newVersion;
            $element->versions = $versions;
        }

        $element->fill($request->all());
        $element->last_edited = now();
        $element->save();

        return response()->json($element);
    }

    // Delete an element
    public function destroy($elementId)
    {
        $element = Element::findOrFail($elementId);
        
        // Also delete all child elements if this is a folder
        if ($element->element_type === 'FOLDER') {
            $this->deleteChildren($element);
        }
        
        $element->delete();
        return response()->json(['message' => 'Element deleted successfully']);
    }

    // Save element content
    public function saveContent(Request $request, $elementId)
    {
        $element = Element::findOrFail($elementId);
        
        $validator = Validator::make($request->all(), [
            'content' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create a new version from the current content
        $newVersion = [
            'id' => count($element->versions) + 1,
            'date' => now()->toISOString(),
            'content' => $element->content_html
        ];
        
        $versions = $element->versions;
        $versions[] = $newVersion;

        $element->content_html = $request->content;
        $element->versions = $versions;
        $element->last_edited = now();
        $element->save();

        return response()->json($element);
    }

    // Helper function to recursively delete children
    private function deleteChildren(Element $element)
    {
        foreach ($element->children as $child) {
            if ($child->element_type === 'FOLDER') {
                $this->deleteChildren($child);
            }
            $child->delete();
        }
    }
}