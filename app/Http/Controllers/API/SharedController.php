<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pdf;
use App\Models\User;
use App\Models\Share;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SharedController extends Controller
{
    /**
     * Get all PDFs shared with the authenticated user
     */
    public function getSharedPdfs()
    {
        $user = Auth::user();
        $sharedPdfs = Pdf::whereHas('shares', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with('owner')->get();

        return response()->json($sharedPdfs);
    }

    /**
     * Share a PDF with other users
     */
    public function sharePdf(Request $request, $pdfId)
    {
        $request->validate([
            'users' => 'required|array',
            'users.*' => 'exists:users,id',
            'permissions' => 'required|string|in:view,edit'
        ]);

        $pdf = Pdf::findOrFail($pdfId);

        // Check if user is the owner of the PDF
        if ($pdf->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        foreach ($request->users as $userId) {
            Share::updateOrCreate(
                ['pdf_id' => $pdfId, 'user_id' => $userId],
                ['permissions' => $request->permissions]
            );
        }

        return response()->json(['message' => 'PDF shared successfully']);
    }

    /**
     * Update PDF sharing settings
     */
    public function updateSharing(Request $request, $pdfId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permissions' => 'required|string|in:view,edit'
        ]);

        $pdf = Pdf::findOrFail($pdfId);

        if ($pdf->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $share = Share::where('pdf_id', $pdfId)
            ->where('user_id', $request->user_id)
            ->firstOrFail();

        $share->permissions = $request->permissions;
        $share->save();

        return response()->json(['message' => 'Sharing settings updated successfully']);
    }

    /**
     * Remove PDF sharing for a user
     */
    public function removeSharing($pdfId, $userId)
    {
        $pdf = Pdf::findOrFail($pdfId);

        if ($pdf->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Share::where('pdf_id', $pdfId)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['message' => 'Sharing removed successfully']);
    }

    /**
     * Get sharing settings for a PDF
     */
    public function getSharingSettings($pdfId)
    {
        $pdf = Pdf::findOrFail($pdfId);

        if ($pdf->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $shares = Share::where('pdf_id', $pdfId)
            ->with('user:id,name,email')
            ->get();

        return response()->json($shares);
    }
}
