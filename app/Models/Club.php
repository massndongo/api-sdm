<?php

namespace App\Models;

use App\Models\User;
use App\Traits\HasUuid;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Ramsey\Uuid\Uuid;

class Club extends Model
{
    use HasFactory, HasApiTokens, Notifiable, HasUuid;
    protected $keyType = 'string'; // Indique que la clé primaire est une chaîne (UUID)
    public $incrementing = false; // Désactive l'incrémentation pour l'UUID
    
    protected $fillable = [
        'name',
        'date_creation',
        'stade',
        'entraineur',
        'president',
        'user_id',
        'logo'
    ];

    protected $appends = ['logo_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getLogoUrlAttribute()
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

}
