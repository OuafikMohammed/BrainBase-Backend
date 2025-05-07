<?php

namespace App\Http\Controllers;

use App\Models\Pdf;
use App\Models\Collection;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PdfController extends Controller
{
    protected $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['title', 'category', 'date_from', 'date_to']);
        return $this->pdfService->search($filters);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimetypes:application/pdf|max:10240',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'collections' => 'nullable|array',
            'collections.*' => 'exists:collections,id'
        ]);

        return $this->pdfService->uploadPdf(
            $request->file('file'),
            $request->only(['title', 'description', 'category', 'collections']),
            Auth::user()
        );
    }

    public function show($id)
    {
        /** @var Pdf $pdf */
        $pdf = Pdf::query()->findOrFail($id);
        
        if (!$pdf->canBeAccessedBy(Auth::user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'pdf' => $pdf,
            'url' => $this->pdfService->getPdfUrl($pdf)
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string'
        ]);

        /** @var Pdf $pdf */
        $pdf = Pdf::query()->findOrFail($id);
        
        if (!$pdf->canBeModifiedBy(Auth::user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pdf->update($request->only(['title', 'description', 'category']));
        return response()->json($pdf);
    }

    public function destroy($id)
    {
        /** @var Pdf $pdf */
        $pdf = Pdf::query()->findOrFail($id);
        
        if (!$pdf->canBeModifiedBy(Auth::user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->pdfService->deletePdf($pdf);
        return response()->noContent();
    }

    public function download($id)
    {
        /** @var Pdf $pdf */
        $pdf = Pdf::query()->findOrFail($id);
        
        if (!$pdf->canBeAccessedBy(Auth::user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $this->pdfService->downloadPdf($pdf);
    }

    public function addToCollection(Request $request, $id)
    {
        $request->validate([
            'collection_id' => 'required|exists:collections,id'
        ]);

        /** @var Pdf $pdf */
        $pdf = Pdf::query()->findOrFail($id);
        /** @var Collection $collection */
        $collection = Collection::query()->findOrFail($request->collection_id);

        if (!$collection->canAddPdfs(Auth::user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pdf->collections()->attach($collection->id);
        return response()->noContent();
    }

    public function removeFromCollection(Request $request, $id)
    {
        $request->validate([
            'collection_id' => 'required|exists:collections,id'
        ]);

        /** @var Pdf $pdf */
        $pdf = Pdf::query()->findOrFail($id);
        /** @var Collection $collection */
        $collection = Collection::query()->findOrFail($request->collection_id);

        if (!$collection->canBeModifiedBy(Auth::user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pdf->collections()->detach($collection->id);
        return response()->noContent();
    }
}