<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Pdf;
use App\Services\CollectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CollectionController extends Controller
{
    public function index()
    {
        try {
            // For now, return all collections the user has access to
            // You might want to implement pagination here
            $collections = Collection::where(function($query) {
                $query->where('created_by', Auth::id())
                      ->orWhere(function($q) {
                          $q->where('is_favorite_collection', true);
                      });
            })->with(['pdfs', 'owner'])->get();

            return response()->json($collections);
        } catch (\Exception $e) {
            Log::error('Failed to fetch collections: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch collections'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string'
            ]);

            // Only Admin and Editor can create collections
            if (!in_array(Auth::user()->user_type, ['ADMIN', 'EDITOR'])) {
                return response()->json(['error' => 'Unauthorized to create collections'], 403);
            }

            $collection = Collection::create([
                'name' => $request->name,
                'description' => $request->description,
                'created_by' => Auth::id(),
                'is_favorite_collection' => false
            ]);

            return response()->json([
                'message' => 'Collection created successfully',
                'collection' => $collection
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create collection: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create collection',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function show($id)
    {
        try {
            $collection = Collection::with(['pdfs', 'owner'])->findOrFail($id);
            
            if (!$collection->canBeAccessedBy(Auth::user())) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

            return response()->json($collection);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Collection not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string'
            ]);

            $collection = Collection::findOrFail($id);
            
            if (!$collection->canBeModifiedBy(Auth::user())) {
                return response()->json(['error' => 'Unauthorized to modify this collection'], 403);
            }

            $collection->update($request->only(['name', 'description']));
            return response()->json([
                'message' => 'Collection updated successfully',
                'collection' => $collection
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update collection: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to update collection',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $collection = Collection::findOrFail($id);
            
            if (!$collection->canBeModifiedBy(Auth::user())) {
                return response()->json(['error' => 'Unauthorized to delete this collection'], 403);
            }

            $collection->delete();
            return response()->json(['message' => 'Collection deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to delete collection: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to delete collection',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function addPdf($collectionId, $pdfId)
    {
        try {
            $collection = Collection::findOrFail($collectionId);
            $pdf = Pdf::findOrFail($pdfId);

            if (!$collection->canAddPdfs(Auth::user())) {
                return response()->json(['error' => 'Unauthorized to add PDFs to this collection'], 403);
            }

            $collection->pdfs()->attach($pdf->id);
            return response()->json(['message' => 'PDF added to collection successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to add PDF to collection: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to add PDF to collection',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function removePdf($collectionId, $pdfId)
    {
        try {
            $collection = Collection::findOrFail($collectionId);
            $pdf = Pdf::findOrFail($pdfId);

            if (!$collection->canBeModifiedBy(Auth::user())) {
                return response()->json(['error' => 'Unauthorized to remove PDFs from this collection'], 403);
            }

            $collection->pdfs()->detach($pdf->id);
            return response()->json(['message' => 'PDF removed from collection successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to remove PDF from collection: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to remove PDF from collection',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}