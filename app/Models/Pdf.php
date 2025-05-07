<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pdf extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category',
        'file_path',
        'size',
        'uploaded_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id_profile');
    }

    public function canBeAccessedBy(User $user): bool
    {
        // Check if user uploaded the PDF
        if ($this->uploaded_by === $user->id_profile) {
            return true;
        }

        // Check if PDF is in any collections shared with user
        foreach ($this->collections as $collection) {
            if ($collection->canBeAccessedBy($user)) {
                return true;
            }
        }

        // Admins can access all PDFs
        return $user->user_type === 'ADMIN';
    }

    public function canBeModifiedBy(User $user): bool
    {
        return $user->user_type === 'ADMIN' || 
               ($user->user_type === 'EDITOR' && $this->uploaded_by === $user->id_profile);
    }
}