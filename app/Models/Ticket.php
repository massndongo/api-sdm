<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use  HasUuid;
    protected $keyType = 'string'; // Indique que la clé primaire est une chaîne (UUID)
    public $incrementing = false; // Désactive l'incrémentation pour l'UUID
    
    protected $fillable = [
        'ticket_category_id',
        'event_id',
        'qr_code',
        'prix',
        'sale_id',
        'channel',
        'status'
    ];

    public function ticketCategory()
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
    