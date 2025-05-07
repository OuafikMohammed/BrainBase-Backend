<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'created_by'
    ];

    public function pdfs()
    {
        return $this->belongsToMany(Pdf::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canAddPdfs(User $user): bool
    {
        // Admins can always add
        if ($user->user_type === 'ADMIN') return true;
        
        // Users can add to their own collections (including Editors)
        return $this->created_by === $user->id_profile;
    }
}