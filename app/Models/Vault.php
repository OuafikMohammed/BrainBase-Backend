<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Vault extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_vault';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'description',
        'id_profile'
    ];

    // Generate UUID on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id_vault = Uuid::uuid4()->toString();
        });
    }

    // Relationship: A vault belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class, 'id_profile', 'id_profile');
    }

    // Relationship: A vault has many elements
    public function elements()
    {
        return $this->hasMany(Element::class, 'id_vault', 'id_vault');
    }
}