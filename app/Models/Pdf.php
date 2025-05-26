<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

class Pdf extends Model
{
    use SoftDeletes;
    protected $primaryKey = 'id';         
    public $incrementing = false;         
    protected $keyType = 'string'; 
    protected $fillable = [
        'id', 'title', 'description', 'category', 'file_path', 'size', 'uploaded_by'
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = Uuid::uuid4()->toString();
        });
    }    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_pdfs')
            ->withTimestamps()
            ->withPivot('added_by')
            ->using(CollectionPdf::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id_profile');
    }

    /**
     * Get the shares for the PDF.
     */
    public function shares()
    {
        return $this->hasMany(Share::class);
    }

    /**
     * Get users this PDF is shared with.
     */
    public function sharedWith(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shares')
                    ->withPivot('permissions')
                    ->withTimestamps();
    }

    public function canBeAccessedBy($user): bool
    {
        // Admin can access all PDFs
        if ($user->user_type === 'ADMIN') {
            return true;
        }

        // Owner can access their own PDFs
        if ($this->uploaded_by === $user->id_profile) {
            return true;
        }

        // Check if PDF is shared with the user
        return $this->shares()
            ->where('user_id', $user->id_profile)
            ->exists();
    }

    public function canBeModifiedBy($user): bool
    {
        // Admin can modify all PDFs
        if ($user->user_type === 'ADMIN') {
            return true;
        }

        // Owner can modify their own PDFs
        if ($this->uploaded_by === $user->id_profile) {
            return true;
        }

        // Check if user has edit permissions
        return $this->shares()
            ->where('user_id', $user->id_profile)
            ->where('permissions', 'edit')
            ->exists();
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->canBeModifiedBy($user); // Same logic
    }
}