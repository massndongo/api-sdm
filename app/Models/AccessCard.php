<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class AccessCard extends Model
{
    use HasFactory, HasApiTokens, Notifiable, HasUuid;
    protected $keyType = 'string'; // Indique que la clé primaire est une chaîne (UUID)
    public $incrementing = false; // Désactive l'incrémentation pour l'UUID
    
    protected $fillable = [
        'user_id', 
        'card_number', 
        'prix',
        'qr_code',
        'status',
        'is_sold'
    ];
    

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
