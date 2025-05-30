<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

class CollectionShare extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'idShare';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'idCollection',
        'idProfile',
        'permission',
        'dateShare'
    ];

    protected $casts = [
        'dateShare' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->idShare = Uuid::uuid4()->toString();
            $model->dateShare = now();
        });
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class, 'idCollection', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'idProfile', 'id_profile');
    }

    public function canView()
    {
        return true;
    }

    public function canEdit()
    {
        return in_array($this->permission, ['edit', 'admin']);
    }

    public function canManage()
    {
        return $this->permission === 'admin';
    }
}
