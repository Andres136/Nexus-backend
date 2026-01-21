<?php

namespace App\Models\Scan;

use Illuminate\Database\Eloquent\Model;

class CreateUrl extends Model
{
    protected $table = 'create_url';

    protected $fillable = [
        'original_url',
        'short_url',
        'product_code',
    ];
}
