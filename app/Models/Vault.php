<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

class Vault extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'id_vault';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'description',
        'id_profile',
        'settings'
    ];

    protected $casts = [
        'settings' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id_vault = Uuid::uuid4()->toString();
        });
    }

    // Relationship with user profile
    public function profile()
    {
        return $this->belongsTo(User::class, 'id_profile');
    }

    // Relationship with elements (files/folders)
    public function elements()
    {
        return $this->hasMany(Element::class, 'id_vault');
    }

    // Get the root elements (files/folders with no parent)
    public function getRootElements()
    {
        return $this->elements()->whereNull('id_parent')->get();
    }
}