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
        try {
            $filters = $request->only(['title', 'category', 'date_from', 'date_to']);
            $pdfs = $this->pdfService->search($filters);
            return response()->json($pdfs);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch PDFs', 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validate file presence first
            if (!$request->hasFile('file')) {
                return response()->json([
                    'error' => 'Upload failed',
                    'message' => 'No PDF file was provided'
                ], 400);
            }

            $file = $request->file('file');

            // Validate file type and mime type
            if (!$file->isValid() || $file->getMimeType() !== 'application/pdf') {
                return response()->json([
                    'error' => 'Upload failed',
                    'message' => 'Invalid file type. Only PDF files are allowed.'
                ], 400);
            }

            // Validate other fields
            $request->validate([
                'file' => 'required|file|mimetypes:application/pdf|max:10240', // 10MB max
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string',
                'collections' => 'nullable|array',
                'collections.*' => 'exists:collections,id'
            ]);

            $pdf = $this->pdfService->uploadPdf(
                $file,
                $request->only(['title', 'description', 'category', 'collections']),
                Auth::user()
            );

            return response()->json([
                'message' => 'PDF uploaded successfully',
                'pdf' => $pdf
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Upload failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }    public function show($id)
    {
        try {
            /** @var Pdf $pdf */
            $pdf = Pdf::findOrFail($id);
            
            if (!$pdf->canBeAccessedBy(Auth::user())) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

            return response()->json([
                'pdf' => $pdf,
                'url' => $this->pdfService->getPdfUrl($pdf)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'PDF not found'], 404);
        }
    }

    public function view($id)
    {
        try {
            /** @var Pdf $pdf */
            $pdf = Pdf::findOrFail($id);
            
            if (!$pdf->canBeAccessedBy(Auth::user())) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

            return $this->pdfService->viewPdf($pdf);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to view PDF', 'message' => $e->getMessage()], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string'
            ]);

            /** @var Pdf $pdf */
            $pdf = Pdf::findOrFail($id);

            if (!$pdf->canBeModifiedBy(Auth::user())) {
                return response()->json(['error' => 'Unauthorized to modify this PDF'], 403);
            }

            $pdf->update($request->only(['title', 'description', 'category']));

            return response()->json([
                'message' => 'PDF updated successfully',
                'pdf' => $pdf
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update PDF',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy($id)
    {
        try {
            /** @var Pdf $pdf */
            $pdf = Pdf::findOrFail($id);

            if (!$pdf->canBeDeletedBy(Auth::user())) {
                return response()->json(['error' => 'Unauthorized to delete this PDF'], 403);
            }

            $this->pdfService->deletePdf($pdf);

            return response()->json(['message' => 'PDF deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete PDF',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function download($id)
{
    try {
        /** @var Pdf $pdf */
        $pdf = Pdf::findOrFail($id);

        if (!$pdf->canBeAccessedBy(Auth::user())) {
            return response()->json(['error' => 'Unauthorized to download this PDF'], 403);
        }

        return $this->pdfService->downloadPdf($pdf);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to download PDF', 'message' => $e->getMessage()], 404);
    }
}
}