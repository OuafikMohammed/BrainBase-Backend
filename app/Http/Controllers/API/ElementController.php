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
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // If parent is being updated
        if ($request->has('id_parent')) {
            $newParentId = $request->id_parent;
            
            // If moving to root
            if ($newParentId === null) {
                $element->id_parent = null;
            } else {
                // Check that new parent exists and is a folder
                $newParent = Element::findOrFail($newParentId);
                if ($newParent->element_type !== 'FOLDER') {
                    return response()->json([
                        'message' => 'Parent element must be a folder'
                    ], 422);
                }
                
                // Check that we're not moving a folder into itself or its descendants
                if ($element->element_type === 'FOLDER') {
                    $descendantIds = $this->getDescendantIds($element);
                    if (in_array($newParentId, $descendantIds)) {
                        return response()->json([
                            'message' => 'Cannot move a folder into itself or its descendants'
                        ], 422);
                    }
                }
                
                $element->id_parent = $newParentId;
            }
        }

        // Update other fields if they're present
        if ($request->has('name')) {
            $element->name = $request->name;
        }
        
        if ($request->has('content_html')) {
            // Create a new version from the current content if content is changing
            if ($element->content_html !== $request->content_html) {
                $newVersion = [
                    'id' => count($element->versions ?? []) + 1,
                    'date' => now()->toISOString(),
                    'content' => $element->content_html
                ];
                $versions = $element->versions ?? [];
                $versions[] = $newVersion;
                $element->versions = $versions;
            }
            $element->content_html = $request->content_html;
        }
        
        if ($request->has('tags')) {
            $element->tags = $request->tags;
        }

        $element->last_edited = now();
        $element->save();

        return response()->json($element);
    }

    // Update an element's parent
    public function updateParent(Request $request, $elementId)
    {
        $validator = Validator::make($request->all(), [
            'parent_id' => 'nullable|string|exists:elements,id_element'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $element = Element::findOrFail($elementId);
        $newParentId = $request->parent_id;
            
        // If moving to root
        if ($newParentId === null) {
            $element->id_parent = null;
        } else {
            // Check that new parent exists and is a folder
            $newParent = Element::findOrFail($newParentId);
            if ($newParent->element_type !== 'FOLDER') {
                return response()->json([
                    'message' => 'Parent element must be a folder'
                ], 422);
            }
            
            // Check that we're not moving a folder into itself or its descendants
            if ($element->element_type === 'FOLDER') {
                $descendantIds = $this->getDescendantIds($element);
                if (in_array($newParentId, $descendantIds)) {
                    return response()->json([
                        'message' => 'Cannot move a folder into itself or its descendants'
                    ], 422);
                }
            }
            
            $element->id_parent = $newParentId;
        }

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

    // Search elements by tags
    public function searchByTags(Request $request, $vaultId)
    {
        $validator = Validator::make($request->all(), [
            'tags' => 'required|array',
            'tags.*' => 'string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Clean and normalize tags
        $searchTags = collect($request->tags)
            ->map(fn($tag) => trim($tag))
            ->filter()
            ->values()
            ->all();

        $elements = Element::where('id_vault', $vaultId)
            ->where(function($query) use ($searchTags) {
                foreach($searchTags as $tag) {
                    $query->whereJsonContains('tags', $tag);
                }
            })
            ->get();

        return response()->json($elements);
    }

    // Restore a specific version
    public function restoreVersion(Request $request, $elementId)
    {
        $validator = Validator::make($request->all(), [
            'versionId' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $element = Element::findOrFail($elementId);
        
        // Find the version to restore
        $versionToRestore = collect($element->versions)->firstWhere('id', $request->versionId);
        
        if (!$versionToRestore) {
            return response()->json(['error' => 'Version not found'], 404);
        }

        // Create a new version from current content
        $currentVersion = [
            'id' => count($element->versions) + 1,
            'date' => now()->toISOString(),
            'content' => $element->content_html
        ];

        // Update element with old version content and add current content as new version
        $versions = $element->versions;
        $versions[] = $currentVersion;
        
        $element->content_html = $versionToRestore['content'];
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

        // Helper function to get all descendant IDs of a folder
    private function getDescendantIds(Element $folder)
    {
        $descendants = [];
        foreach ($folder->children as $child) {
            $descendants[] = $child->id_element;
            if ($child->element_type === 'FOLDER') {
                $descendants = array_merge($descendants, $this->getDescendantIds($child));
            }
        }
        return $descendants;
    }
}