<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    protected $table = 'event_registtration';
    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
    ];
}
