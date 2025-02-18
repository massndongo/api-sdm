<?php

namespace App\Models;

use App\Traits\HasUuid;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OTP extends Model
{
    use HasFactory, HasApiTokens, Notifiable, HasUuid;
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'otp',
        'phone',
        'expires_at'
    ];
}
