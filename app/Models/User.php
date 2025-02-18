<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Club;
use App\Models\Role;
use App\Traits\HasUuid;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasApiTokens, Notifiable, HasUuid;
    protected $keyType = 'string'; // Indique que la clé primaire est une chaîne (UUID)
    public $incrementing = false; // Désactive l'incrémentation pour l'UUID

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'phone',
        'password',
        'is_active',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *  @var array<string, string>
     * */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }


    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole($roleName)
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function isStaff()
    {
        return $this->role->name === 'staff';
    }

    public function isJoueur()
    {
        return $this->role->name === 'joueur';
    }

    public function isSupporter()
    {
        return $this->role->name === 'supporter';
    }

    public function isGestionnaireLigue()
    {
        return $this->role->name === 'gestionnaire_ligue';
    }

    public function isGestionnaireDistrict()
    {
        return $this->role->name === 'gestionnaire_district';
    }

    public function isGestionnaireClub()
    {
        return $this->role->name === 'gestionnaire_club';
    }

    public function club()
    {
        return $this->hasOne(Club::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
