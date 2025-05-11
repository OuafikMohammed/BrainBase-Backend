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
    }
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id_profile');
    }
    public function canBeAccessedBy($user): bool
    {
        return true; // 👈 Allow all users to view any PDF
    }
    //We must check it later
    public function canBeModifiedBy($user): bool
    {
        return $user->user_type === 'ADMIN' || 
               ($user->user_type === 'EDITOR' && $this->uploaded_by === $user->id_profile);
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->canBeModifiedBy($user); // Same logic
    }
}