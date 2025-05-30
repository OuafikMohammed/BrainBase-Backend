<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionShare;
use App\Models\Pdf;
use App\Models\User;
use App\Notifications\CollectionShared;
use App\Notifications\CollectionRoleUpdated;
use App\Services\CollectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CollectionController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            Log::info('Fetching collections for user', [
                'user_id' => $user->id,
                'user_type' => $user->user_type
            ]);

            $collections = Collection::where(function($query) use ($user) {
                $query->where('created_by', $user->id_profile)
                    ->orWhereHas('shares', function($q) use ($user) {
                        $q->where('idProfile', $user->id_profile);
                    });
                
                if (in_array($user->user_type, ['ADMIN', 'EDITOR'])) {
                    $query->orWhere('is_favorite_collection', false);
                }
            })
            ->with(['pdfs', 'creator'])
            ->get();

            Log::info('Collections fetched successfully', [
                'count' => $collections->count()
            ]);

            return response()->json($collections);
        } catch (\Exception $e) {
            Log::error('Failed to fetch collections', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to fetch collections',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function shared()
    {
        try {
            $collections = Collection::whereHas('shares', function($query) {
                $query->where('idProfile', Auth::user()->id_profile);
            })->with(['pdfs', 'creator'])->get();

            return response()->json($collections);
        } catch (\Exception $e) {
            Log::error('Failed to fetch shared collections: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch shared collections'], 500);
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
            }            $collection = Collection::create([
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
    }    public function show($id)
    {
        try {
            $user = Auth::user();
            
            Log::info('Attempting to fetch collection', [
                'collection_id' => $id,
                'user_id' => $user->id,
                'user_type' => $user->user_type,
                'user_profile_id' => $user->id_profile
            ]);

            $collection = Collection::with(['pdfs', 'creator', 'shares'])
                ->where('id', $id)
                ->first();

            if (!$collection) {
                Log::warning('Collection not found', [
                    'collection_id' => $id,
                    'user_id' => $user->id
                ]);
                return response()->json(['error' => 'Collection not found'], 404);
            }

            // Check if user has access
            $hasAccess = 
                $collection->created_by === $user->id_profile || // Is owner
                $collection->visibility === 'public' || // Is public
                $collection->shares()->where('idProfile', $user->id_profile)->exists() || // Is shared
                in_array($user->user_type, ['ADMIN', 'EDITOR']); // Is admin/editor

            Log::info('Access check result', [
                'collection_id' => $id,
                'user_id' => $user->id,
                'user_profile_id' => $user->id_profile,
                'collection_creator' => $collection->created_by,
                'visibility' => $collection->visibility,
                'is_shared' => $collection->shares()->where('idProfile', $user->id_profile)->exists(),
                'user_type' => $user->user_type,
                'has_access' => $hasAccess
            ]);

            if (!$hasAccess) {
                Log::warning('Access denied to collection', [
                    'collection_id' => $id,
                    'user_id' => $user->id,
                    'user_type' => $user->user_type,
                    'user_profile_id' => $user->id_profile,
                    'collection_creator' => $collection->created_by
                ]);
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

            // Add additional permissions info for the frontend
            $collectionData = $collection->toArray();
            $collectionData['canEdit'] = $collection->created_by === $user->id_profile || 
                                      in_array($user->user_type, ['ADMIN', 'EDITOR']);
            $collectionData['canShare'] = $collection->created_by === $user->id_profile || 
                                       in_array($user->user_type, ['ADMIN', 'EDITOR']);

            Log::info('Collection fetched successfully', [
                'collection_id' => $collection->id,
                'user_id' => $user->id,
                'permissions' => [
                    'canEdit' => $collectionData['canEdit'],
                    'canShare' => $collectionData['canShare']
                ]
            ]);

            return response()->json($collectionData);
        } catch (\Exception $e) {
            Log::error('Failed to fetch collection', [
                'error' => $e->getMessage(),
                'collection_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch collection',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'visibility' => 'required|string|in:private,public,shared'
            ]);

            $collection = Collection::findOrFail($id);
            
            if (!$collection->canBeModifiedBy(Auth::user())) {
                return response()->json(['error' => 'Unauthorized to modify this collection'], 403);
            }

            $collection->update($request->only(['name', 'description', 'visibility']));
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
    }    public function addPdf($collectionId, $pdfId)
    {
        try {
            $user = Auth::user();
            Log::info('Attempting to add PDF to collection', [
                'collection_id' => $collectionId,
                'pdf_id' => $pdfId,
                'user_id' => $user->id_profile
            ]);

            $collection = Collection::findOrFail($collectionId);
            $pdf = Pdf::findOrFail($pdfId);

            if (!$collection->canAddPdfs($user)) {
                Log::warning('Unauthorized attempt to add PDF to collection', [
                    'collection_id' => $collectionId,
                    'pdf_id' => $pdfId,
                    'user_id' => $user->id_profile
                ]);
                return response()->json(['error' => 'Unauthorized to add PDFs to this collection'], 403);
            }

            // Check if PDF is already in collection
            if ($collection->pdfs()->where('pdf_id', $pdfId)->exists()) {
                return response()->json(['message' => 'PDF is already in this collection']);
            }

            $collection->pdfs()->attach($pdf->id, ['added_by' => $user->id_profile]);
            
            Log::info('Successfully added PDF to collection', [
                'collection_id' => $collectionId,
                'pdf_id' => $pdfId,
                'user_id' => $user->id_profile
            ]);

            return response()->json([
                'message' => 'PDF added to collection successfully',
                'pdf' => [
                    'id' => $pdf->id,
                    'title' => $pdf->title,
                    'description' => $pdf->description,
                    'category' => $pdf->category,
                    'size' => $pdf->size,
                    'file_path' => $pdf->file_path,
                    'uploaded_by' => $pdf->uploaded_by,
                    'created_at' => $pdf->created_at,
                    'updated_at' => $pdf->updated_at
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Collection or PDF not found', [
                'collection_id' => $collectionId,
                'pdf_id' => $pdfId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Collection or PDF not found'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to add PDF to collection', [
                'collection_id' => $collectionId,
                'pdf_id' => $pdfId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to add PDF to collection',
                'message' => $e->getMessage()
            ], 500);
        }
    }    public function removePdf($collectionId, $pdfId)
    {
        try {
            $user = Auth::user();
            Log::info('Attempting to remove PDF from collection', [
                'collection_id' => $collectionId,
                'pdf_id' => $pdfId,
                'user_id' => $user->id_profile
            ]);

            $collection = Collection::findOrFail($collectionId);
            $pdf = Pdf::findOrFail($pdfId);

            if (!$collection->canBeModifiedBy($user)) {
                Log::warning('Unauthorized attempt to remove PDF from collection', [
                    'collection_id' => $collectionId,
                    'pdf_id' => $pdfId,
                    'user_id' => $user->id_profile
                ]);
                return response()->json(['error' => 'Unauthorized to remove PDFs from this collection'], 403);
            }

            // Check if PDF is actually in the collection
            if (!$collection->pdfs()->where('pdf_id', $pdfId)->exists()) {
                return response()->json(['message' => 'PDF is not in this collection'], 404);
            }

            $collection->pdfs()->detach($pdf->id);
            
            Log::info('Successfully removed PDF from collection', [
                'collection_id' => $collectionId,
                'pdf_id' => $pdfId,
                'user_id' => $user->id_profile
            ]);

            return response()->json(['message' => 'PDF removed from collection successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Collection or PDF not found', [
                'collection_id' => $collectionId,
                'pdf_id' => $pdfId,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Collection or PDF not found'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to remove PDF from collection', [
                'collection_id' => $collectionId,
                'pdf_id' => $pdfId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to remove PDF from collection',
                'message' => $e->getMessage()
            ], 500);
        }
    }public function getPdfs($id)
    {
        try {
            $user = Auth::user();
            Log::info('Attempting to fetch PDFs for collection', [
                'collection_id' => $id,
                'user_id' => $user->id_profile,
                'user_type' => $user->user_type
            ]);
            
            $collection = Collection::findOrFail($id);
            
            if (!$collection->canBeAccessedBy($user)) {
                Log::warning('Unauthorized access attempt to collection PDFs', [
                    'collection_id' => $id,
                    'user_id' => $user->id_profile
                ]);
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

            $pdfs = $collection->pdfs()
                ->with(['uploader'])
                ->get()
                ->map(function ($pdf) {
                    return [
                        'id' => $pdf->id,
                        'title' => $pdf->title,
                        'description' => $pdf->description,
                        'category' => $pdf->category,
                        'size' => $pdf->size,
                        'file_path' => $pdf->file_path,
                        'uploaded_by' => $pdf->uploaded_by,
                        'uploader' => $pdf->uploader,
                        'created_at' => $pdf->created_at,
                        'updated_at' => $pdf->updated_at
                    ];
                });

            Log::info('Successfully fetched PDFs for collection', [
                'collection_id' => $id,
                'pdfs_count' => $pdfs->count()
            ]);
            
            return response()->json($pdfs);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Collection not found', [
                'collection_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Collection not found'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to fetch collection PDFs', [
                'collection_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to fetch PDFs',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Share a collection with users
     */
    public function shareUserCollection(Request $request, $id)
    {
        try {
            $request->validate([
                'users' => 'required|array',
                'users.*' => 'exists:users,id_profile',
                'permission' => 'required|string|in:view,edit,admin'
            ]);

            $collection = Collection::with('creator')->findOrFail($id);
            $currentUser = Auth::user();

            if (!$collection->canManageShares($currentUser)) {
                return response()->json(['error' => 'Unauthorized to share this collection'], 403);
            }

            foreach ($request->users as $userId) {
                $user = User::findOrFail($userId);
                
                // Create or update share
                CollectionShare::updateOrCreate(
                    ['idCollection' => $id, 'idProfile' => $userId],
                    ['permission' => $request->permission]
                );

                // Send notification
                $user->notify(new CollectionShared(
                    $collection,
                    $currentUser,
                    $request->permission
                ));
            }

            return response()->json(['message' => 'Collection shared successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to share collection: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to share collection'], 500);
        }
    }

    /**
     * Get collection members with their details
     */
    public function getMembers($id)
    {
        try {
            $collection = Collection::with(['creator', 'shares.user'])->findOrFail($id);

            if (!$collection->canBeAccessedBy(Auth::user())) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

            $members = $collection->shares->map(function ($share) {
                $user = $share->user;
                return [
                    'user' => [
                        'id' => $user->id_profile,
                        'name' => $user->name,
                        'email' => $user->email,
                        'profileImage' => $user->profile_image ?? '/placeholder-user.jpg'
                    ],
                    'permission' => $share->permission,
                    'dateShared' => $share->dateShare
                ];
            });

            // Add creator as the first member
            $members->prepend([
                'user' => [
                    'id' => $collection->creator->id_profile,
                    'name' => $collection->creator->name,
                    'email' => $collection->creator->email,
                    'profileImage' => $collection->creator->profile_image ?? '/placeholder-user.jpg'
                ],
                'permission' => 'admin',
                'dateShared' => $collection->created_at,
                'isCreator' => true
            ]);

            return response()->json($members);
        } catch (\Exception $e) {
            Log::error('Failed to load collection members: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load collection members'], 500);
        }
    }

    /**
     * Update user's permission in a collection
     */
    public function updateUserCollectionPermission(Request $request, $id, $userId)
    {
        try {
            $request->validate([
                'permission' => 'required|string|in:view,edit,admin'
            ]);

            $collection = Collection::with('creator')->findOrFail($id);
            $currentUser = Auth::user();

            if (!$collection->canManageShares($currentUser)) {
                return response()->json(['error' => 'Unauthorized to modify sharing settings'], 403);
            }

            $share = $collection->shares()->where('idProfile', $userId)->firstOrFail();
            $oldPermission = $share->permission;
            $share->permission = $request->permission;
            $share->save();

            // Send notification if permissions changed
            if ($oldPermission !== $request->permission) {
                $user = User::findOrFail($userId);
                $user->notify(new CollectionRoleUpdated(
                    $collection,
                    $currentUser,
                    $request->permission,
                    $oldPermission
                ));
            }

            return response()->json(['message' => 'Share settings updated successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to update share settings: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update share settings'], 500);
        }
    }

    /**
     * Search for users by name or email
     */
    public function searchUsers(Request $request)
    {
        try {
            $request->validate([
                'query' => 'required|string|min:2'
            ]);

            $query = $request->query('query');

            $users = User::where('name', 'LIKE', "%{$query}%")
                ->orWhere('email', 'LIKE', "%{$query}%")
                ->select('id_profile', 'name', 'email', 'avatar')
                ->limit(10)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id_profile,
                        'name' => $user->name,
                        'email' => $user->email,
                        'profileImage' => $user->avatar ?? '/placeholder-user.jpg'
                    ];
                });

            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Failed to search users: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to search users',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}