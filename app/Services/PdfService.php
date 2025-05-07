<?php

namespace App\Services;

use App\Models\Pdf;
use App\Models\User;
use App\Models\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfService
{
    public function search(array $filters)
    {
        $query = Pdf::query();

        if (isset($filters['title'])) {
            $query->where('title', 'like', "%{$filters['title']}%");
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function uploadPdf(UploadedFile $file, array $data, User $user)
    {
        if (!in_array($user->user_type, ['ADMIN', 'EDITOR'])) {
            throw new \Exception('Unauthorized to upload PDFs');
        }

        // Generate a unique filename
        $filename = Str::uuid() . '.pdf';
        $path = 'pdfs/' . $filename;

        // Store file in local storage
        Storage::disk('public')->put($path, file_get_contents($file));

        // Create PDF record
        $pdf = Pdf::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'size' => $file->getSize(),
            'file_path' => $path,
            'uploaded_by' => $user->id_profile
        ]);

        // Add to collections if specified
        if (!empty($data['collections'])) {
            $collections = Collection::whereIn('id', $data['collections'])->get();
            foreach ($collections as $collection) {
                if ($collection->canAddPdfs($user)) {
                    $pdf->collections()->attach($collection->id);
                }
            }
        }

        return $pdf;
    }

    public function getPdfUrl(Pdf $pdf)
    {
        if (!Storage::disk('public')->exists($pdf->file_path)) {
            throw new \Exception('PDF file not found');
        }

        return Storage::disk('public')->url($pdf->file_path);
    }

    public function downloadPdf(Pdf $pdf): StreamedResponse
    {
        if (!Storage::disk('public')->exists($pdf->file_path)) {
            throw new \Exception('PDF file not found');
        }

        return Storage::disk('public')->download($pdf->file_path, $pdf->title . '.pdf');
    }

    public function deletePdf(Pdf $pdf)
    {
        // Delete the file from storage
        if (Storage::disk('public')->exists($pdf->file_path)) {
            Storage::disk('public')->delete($pdf->file_path);
        }

        // Delete from database
        $pdf->delete();
    }
}