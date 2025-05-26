<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CollectionShare extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'collection_id',
        'user_id',
        'role', // 'viewer', 'editor', 'admin'
        'created_by'
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
