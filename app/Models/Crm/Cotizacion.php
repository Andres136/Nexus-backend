<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';
    protected $fillable = [
        'cliente_id',
        'empresa',
        'observaciones',
        'user_id',
        'valor_total',
    ];

protected $casts = [
    'valor_unitario' => 'float',
    'valor_paquete' => 'float',
    'valor_total' => 'float',
];
    
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detalles()
    {
        return $this->hasMany(CotizacionDetalles::class);
    }


}
