<?php

namespace App\Models;

use App\Traits\HasUuid;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model
{
    use HasFactory, HasApiTokens, Notifiable, HasUuid;
    protected $keyType = 'string'; // Indique que la clé primaire est une chaîne (UUID)
    public $incrementing = false; // Désactive l'incrémentation pour l'UUID

    protected $fillable = [
        'name',
        'description',
        'date',
        'start_time',
        'club_id',
        'club_away_id',
        'location_id',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function club()
    {
        return $this->belongsTo(Club::class);
    }
    public function clubAway()
    {
        return $this->belongsTo(Club::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
