<?php

namespace App\Models;

use App\Traits\HasUuid;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Entree extends Model
{
    use HasFactory, HasApiTokens, Notifiable, HasUuid;
    protected $keyType = 'string'; // Indique que la clé primaire est une chaîne (UUID)
    public $incrementing = false; // Désactive l'incrémentation pour l'UUID
    
    protected $fillable = [
        'ticket_id',
        'portier_id',
        'checkin_time',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function portier()
    {
        return $this->belongsTo(User::class, 'portier_id');
    }
}
