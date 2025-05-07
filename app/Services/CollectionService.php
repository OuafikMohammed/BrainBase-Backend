<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\User;

class CollectionService
{
    public function getUserCollections(User $user, bool $includeFavorites = true)
    {
        $query = Collection::query();

        if ($includeFavorites) {
            $query->where(function($q) use ($user) {
                $q->where('created_by', $user->id_profile)
                  ->orWhere(function($q) use ($user) {
                      if (in_array($user->user_type, ['ADMIN', 'EDITOR'])) {
                          $q->where('is_favorite_collection', false);
                      }
                  });
            });
        } else {
            if (in_array($user->user_type, ['ADMIN', 'EDITOR'])) {
                $query->where('is_favorite_collection', false);
            } else {
                $query->where('created_by', $user->id_profile)
                      ->where('is_favorite_collection', false);
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function createCollection(array $data, User $user)
    {
        if (!$data['is_favorite_collection'] && !in_array($user->user_type, ['ADMIN', 'EDITOR'])) {
            throw new \Exception('Unauthorized to create general collections');
        }

        return Collection::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'created_by' => $user->id_profile,
            'is_favorite_collection' => $data['is_favorite_collection'] ?? false
        ]);
    }

    public function getCollection(Collection $collection, User $user)
    {
        if (!in_array($user->user_type, ['ADMIN', 'EDITOR']) && 
            $collection->created_by !== $user->id_profile) {
            throw new \Exception('Unauthorized to view this collection');
        }

        return $collection->load('pdfs');
    }

    public function updateCollection(Collection $collection, array $data, User $user)
    {
        if (!$collection->canBeModifiedBy($user)) {
            throw new \Exception('Unauthorized to modify this collection');
        }

        $collection->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $collection->description
        ]);

        return $collection;
    }

    public function deleteCollection(Collection $collection, User $user)
    {
        if (!$collection->canBeModifiedBy($user)) {
            throw new \Exception('Unauthorized to delete this collection');
        }

        if ($collection->is_favorite_collection) {
            throw new \Exception('Cannot delete favorite collections');
        }

        return $collection->delete();
    }

    public function getFavoriteCollection(User $user)
    {
        $favoriteCollection = Collection::where('created_by', $user->id_profile)
            ->where('is_favorite_collection', true)
            ->first();

        if (!$favoriteCollection) {
            $favoriteCollection = Collection::create([
                'name' => $user->name . "'s Favorites",
                'description' => 'Personal favorite PDFs collection',
                'created_by' => $user->id_profile,
                'is_favorite_collection' => true
            ]);
        }

        return $favoriteCollection;
    }
}