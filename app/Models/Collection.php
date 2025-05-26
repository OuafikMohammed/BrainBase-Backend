<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use HasFactory, SoftDeletes;    protected $fillable = [
        'name',
        'description',
        'created_by',
        'is_favorite_collection',
        'visibility'
    ];
      public $incrementing = true;
    protected $keyType = 'int';

    protected $casts = [
        'is_favorite_collection' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];    public function pdfs()
    {
        return $this->belongsToMany(Pdf::class, 'collection_pdfs')
            ->withTimestamps()
            ->withPivot('added_by')
            ->using(CollectionPdf::class);
    }public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_profile');
    }

    public function shares()
    {
        return $this->hasMany(CollectionShare::class);
    }

    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'collection_shares')
            ->withPivot('role', 'created_by')
            ->withTimestamps();
    }    public function canBeAccessedBy(User $user): bool
    {
        // Admins can access all collections
        if ($user->user_type === 'ADMIN') return true;
        
        // Owner can access
        if ($this->created_by === $user->id) return true;
        
        // Check if collection is shared with user
        if ($this->shares()->where('user_id', $user->id)->exists()) return true;
        
        // Check if collection is public
        if ($this->visibility === 'public') return true;
        
        // Users can access their own collections
        if ($this->created_by === $user->id_profile) return true;

        // Check if the collection is shared with the user
        return $this->shares()->where('user_id', $user->id_profile)->exists();
    }

    public function canBeModifiedBy(User $user): bool
    {
        // Admins can modify all collections
        if ($user->user_type === 'ADMIN') return true;

        // Owner can modify their collections
        if ($this->created_by === $user->id_profile) return true;

        // Check if user has editor or admin role
        return $this->shares()
            ->where('user_id', $user->id_profile)
            ->whereIn('role', ['editor', 'admin'])
            ->exists();
    }

    public function canAddPdfs(User $user): bool
    {
        return $this->canBeModifiedBy($user);
    }

    public function canManageShares(User $user): bool
    {
        // Admins can manage all collections
        if ($user->user_type === 'ADMIN') return true;

        // Owner can manage their collections
        if ($this->created_by === $user->id_profile) return true;

        // Only users with admin role can manage shares
        return $this->shares()
            ->where('user_id', $user->id_profile)
            ->where('role', 'admin')
            ->exists();
    }
}