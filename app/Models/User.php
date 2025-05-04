<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Ramsey\Uuid\Uuid;
/**
 * @method \Laravel\Sanctum\NewAccessToken createToken(string $name, array $abilities = ['*'])
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'id_profile'; // <--- IMPORTANT
    public $incrementing = false; // <--- UUIDs ne sont pas auto-incrémentés
    protected $keyType = 'string'; // <--- UUID est une chaîne de caractères

    // Without fillable all inputs you insert won't be saved !
    protected $fillable = [
        'id_profile',
        'name',
        'email',
        'password',
        'user_type',
        'avatar'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Generate UUID on creation
    // Just to know if we need to use it
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id_profile = Uuid::uuid4()->toString();
        });
    }

    // Relationships
    public function vaults()
    {
        return $this->hasMany(Vault::class, 'id_profile', 'id_profile');
    }
}