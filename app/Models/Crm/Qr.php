<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class Qr extends Model
{
    protected $table = 'qrs';
    protected $fillable = [
        'url',
        'path',
        'public_url',
    ];
}
