<?php

namespace App\Services;

use App\Models\Pdf;
use App\Models\User;
use App\Models\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfService
{
    public function search(array $filters = [])
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
    }    public function getPdfUrl(Pdf $pdf)
    {
        if (!Storage::disk('public')->exists($pdf->file_path)) {
            throw new \Exception('PDF file not found');
        }
        return asset('storage/' . $pdf->file_path);
    }

    public function viewPdf(Pdf $pdf)
    {
        $path = storage_path('app/public/' . $pdf->file_path);
        if (!file_exists($path)) {
            throw new \Exception('PDF file not found');
        }
        return response()->file($path);
    }

    public function downloadPdf(Pdf $pdf)
    {
        $path = storage_path('app/public/' . $pdf->file_path);
        if (!file_exists($path)) {
            throw new \Exception('PDF file not found');
        }
        return response()->download($path, $pdf->title . '.pdf');
    }

    public function uploadPdf(UploadedFile $file, array $data, User $user)
    {
        if (!in_array($user->user_type, ['ADMIN', 'EDITOR'])) {
            throw new \Exception('Unauthorized to upload PDFs');
        }

        // Double check file type and size
        if ($file->getMimeType() !== 'application/pdf') {
            throw new \Exception('Invalid file type. Only PDF files are allowed.');
        }
        
        if ($file->getSize() > 10485760) { // 10MB limit
            throw new \Exception('File size exceeds the limit of 10MB.');
        }

        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload.');
        }

        // Generate UUID for the file
        $uuid = (string) Str::uuid();
        $filename = $uuid . '.pdf';
        $path = 'pdfs/' . $filename;

        try {
            // Store file
            $result = $file->storeAs('pdfs', $filename, 'public');
            
            if (!$result) {
                throw new \Exception('Failed to store PDF file.');
            }

            // Create PDF record
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
                $collections = Collection::whereIn('id', $data['collections'])
                    ->where(function ($query) use ($user) {
                        $query->where('owner_id', $user->id_profile)
                            ->orWhereHas('shares', function ($q) use ($user) {
                                $q->where('user_id', $user->id_profile)
                                    ->where('permissions', 'edit');
                            });
                    })->get();
                    
                $pdf->collections()->attach($collections->pluck('id'));
            }

            return $pdf;
        } catch (\Exception $e) {
            // Clean up the stored file if it exists
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
        $pdf->delete();
        return true;
    }
}