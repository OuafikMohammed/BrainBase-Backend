<?php

namespace App\Services;

use App\Models\Pdf;
use App\Models\User;
use App\Models\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Ramsey\Uuid\Uuid;
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

    public function downloadPdf(Pdf $pdf)
    {
        $path = storage_path('app/' . $pdf->file_path);
        return response()->download($path, $pdf->title . '.pdf');
    }

    public function uploadPdf(UploadedFile $file, array $data, User $user)
    {
        if (!in_array($user->user_type, ['ADMIN', 'EDITOR'])) {
            throw new \Exception('Unauthorized to upload PDFs');
        }

        // Validate file type and size
        if ($file->getMimeType() !== 'application/pdf') {
            throw new \Exception('Invalid file type. Only PDF files are allowed.');
        }
        if ($file->getSize() > 10485760) { // 10MB limit
            throw new \Exception('File size exceeds the limit of 10MB.');
        }

        // Generate UUID
        $uuid = (string) Str::uuid(); // Laravel helper
        $filename = $uuid . '.pdf';
        $path = 'pdfs/' . $filename;

        try {
            // Store file
            Storage::disk('public')->put($path, file_get_contents($file));

            // Create PDF record with explicit UUID
            $pdf = Pdf::create([
                'id' => $uuid,
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
        } catch (\Exception $e) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }
    }

    public function deletePdf(Pdf $pdf)
    {
        if (Storage::disk('public')->exists($pdf->file_path)) {
            Storage::disk('public')->delete($pdf->file_path);
        }

        $pdf->collections()->detach(); // Remove from all collections
        $pdf->delete(); // Soft-delete
    }
}