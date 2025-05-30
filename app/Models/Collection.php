<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;
use App\Notifications\CollectionShared;
use Illuminate\Support\Carbon;

class Collection extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'name',
        'description',
        'created_by',
        'is_favorite_collection',
        'visibility'
    ];

    const VISIBILITY_PRIVATE = 'private';
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_SHARED = 'shared';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $casts = [
        'is_favorite_collection' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->id = Uuid::uuid4()->toString();
        });
    }

    public function pdfs()
    {
        return $this->belongsToMany(Pdf::class, 'collection_pdfs')
            ->withTimestamps()
            ->withPivot('added_by')
            ->using(CollectionPdf::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_profile');
    }

    public function shares()
    {
        return $this->hasMany(CollectionShare::class, 'idCollection', 'id');
    }

    public function sharedWith()
    {
        return $this->belongsToMany(User::class, 'collection_shares', 'idCollection', 'idProfile')
            ->withPivot('permission', 'dateShare', 'accepted_at', 'rejected_at')
            ->withTimestamps();
    }

    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'collection_shares', 'idCollection', 'idProfile')
            ->withPivot('permission', 'dateShare', 'accepted_at', 'rejected_at')
            ->withTimestamps();
    }

    public function notifyNewShareRequest(User $targetUser, User $fromUser, string $permission): void
    {
        $targetUser->notify(new CollectionShared(
            $this,
            $fromUser,
            $permission
        ));
    }

    public function getPendingShareRequests(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->shares()
                    ->whereNull('accepted_at')
                    ->whereNull('rejected_at')
                    ->get();
    }

    public function respondToShareRequest(User $user, bool $accept): void
    {
        $share = $this->shares()
                      ->where('idProfile', $user->id_profile)
                      ->whereNull('accepted_at')
                      ->whereNull('rejected_at')
                      ->first();

        if (!$share) {
            throw new \Exception('No pending share request found');
        }

        if ($accept) {
            $share->accepted_at = now();
        } else {
            $share->rejected_at = now();
        }
        $share->save();
    }

    public function isSharedWith(User $user): bool
    {
        return $this->shares()
                    ->where('idProfile', $user->id_profile)
                    ->whereNotNull('accepted_at')
                    ->whereNull('rejected_at')
                    ->exists();
    }

    public function canAddPdfs(User $user): bool
    {
        return $this->canBeModifiedBy($user);
    }

    public function canManageShares(User $user): bool
    {
        return $this->created_by === $user->id_profile || 
               in_array($user->user_type, ['ADMIN']) ||
               $this->shares()
                    ->where('idProfile', $user->id_profile)
                    ->where('permission', 'admin')
                    ->exists();
    }

    public function canBeAccessedBy(User $user): bool
    {
        if ($this->visibility === self::VISIBILITY_PUBLIC) {
            return true;
        }
        
        return $this->created_by === $user->id_profile || 
               in_array($user->user_type, ['ADMIN', 'EDITOR']) ||
               $this->shares()
                    ->where('idProfile', $user->id_profile)
                    ->whereNotNull('accepted_at')
                    ->whereNull('rejected_at')
                    ->exists();
    }

    public function canBeModifiedBy(User $user): bool
    {
        return $this->created_by === $user->id_profile || 
               in_array($user->user_type, ['ADMIN', 'EDITOR']) ||
               $this->shares()
                    ->where('idProfile', $user->id_profile)
                    ->whereNotNull('accepted_at')
                    ->whereNull('rejected_at')
                    ->whereIn('permission', ['edit', 'admin'])
                    ->exists();
    }
}