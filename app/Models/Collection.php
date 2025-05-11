<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'is_favorite_collection'
    ];

    protected $casts = [
        'is_favorite_collection' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function pdfs()
    {
        return $this->belongsToMany(Pdf::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_profile');
    }

    public function canBeAccessedBy(User $user): bool
    {
        // Admins can access all collections
        if ($user->user_type === 'ADMIN') return true;
        
        // Users can access their own collections
        if ($this->created_by === $user->id_profile) return true;

        // Check for shared access (you may want to implement sharing logic here)
        return false;
    }

    public function canBeModifiedBy(User $user): bool
    {
        // Only admins and the owner can modify collections
        return $user->user_type === 'ADMIN' || $this->created_by === $user->id_profile;
    }

    public function canAddPdfs(User $user): bool
    {
        return $this->canBeModifiedBy($user);
    }
}