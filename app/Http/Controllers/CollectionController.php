<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Pdf;
use App\Services\CollectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CollectionController extends Controller
{
    protected $collectionService;

    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    public function index(Request $request)
    {
        $includeFavorites = $request->boolean('include_favorites', true);
        return $this->collectionService->getUserCollections(Auth::user(), $includeFavorites);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_favorite_collection' => 'boolean'
        ]);

        return $this->collectionService->createCollection($request->all(), Auth::user());
    }

    public function show($collectionId)
    {
        /** @var Collection $collection */
        $collection = Collection::query()->findOrFail($collectionId);
        return $this->collectionService->getCollection($collection, Auth::user());
    }

    public function update(Request $request, $collectionId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        /** @var Collection $collection */
        $collection = Collection::query()->findOrFail($collectionId);
        return $this->collectionService->updateCollection($collection, $request->all(), Auth::user());
    }

    public function destroy($collectionId)
    {
        /** @var Collection $collection */
        $collection = Collection::query()->findOrFail($collectionId);
        $this->collectionService->deleteCollection($collection, Auth::user());
        return response()->noContent();
    }

    public function getFavorites()
    {
        return $this->collectionService->getFavoriteCollection(Auth::user());
    }

    public function addPdf($collectionId, $pdfId)
    {
        /** @var Collection $collection */
        $collection = Collection::query()->findOrFail($collectionId);
        /** @var Pdf $pdf */
        $pdf = Pdf::query()->findOrFail($pdfId);

        if (!$collection->canAddPdfs(Auth::user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $collection->pdfs()->attach($pdf->id);
        return response()->noContent();
    }

    public function removePdf($collectionId, $pdfId)
    {
        /** @var Collection $collection */
        $collection = Collection::query()->findOrFail($collectionId);
        /** @var Pdf $pdf */
        $pdf = Pdf::query()->findOrFail($pdfId);

        if (!$collection->canBeModifiedBy(Auth::user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $collection->pdfs()->detach($pdf->id);
        return response()->noContent();
    }
}