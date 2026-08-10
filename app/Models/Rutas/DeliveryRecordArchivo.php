<?php

namespace App\Models\Rutas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DeliveryRecordArchivo extends Model
{
    protected $table = 'delivery_record_archivos';

    protected $appends = ['url'];

    protected $fillable = [
        'delivery_record_id',
        'archivo',
        'tipo',
        'descripcion',
    ];

    public function getUrlAttribute()
    {
        return Storage::disk('public')->url($this->archivo);
    }

    public function record()
    {
        return $this->belongsTo(DeliveryRecord::class, 'delivery_record_id');
    }
}
