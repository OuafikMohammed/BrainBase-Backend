<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class Element extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_element';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'element_type',
        'id_vault',
        'id_parent',
        'content_html',
        'position'
    ];

    // Generate UUID on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id_element = Uuid::uuid4()->toString();
        });
    }

    // Relationship: An element belongs to a vault
    public function vault()
    {
        return $this->belongsTo(Vault::class, 'id_vault', 'id_vault');
    }

    // Relationship: An element can have a parent (self-referencing)
    public function parent()
    {
        return $this->belongsTo(Element::class, 'id_parent', 'id_element');
    }

    // Relationship: An element can have children (self-referencing)
    public function children()
    {
        return $this->hasMany(Element::class, 'id_parent', 'id_element');
    }
}
