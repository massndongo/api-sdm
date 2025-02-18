<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class TicketCategory extends Model
{
    use HasUuid;
    protected $keyType = 'string'; // Indique que la clé primaire est une chaîne (UUID)
    public $incrementing = false; // Désactive l'incrémentation pour l'UUID
    
    protected $fillable = [
        'name',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
