<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GestionCartera extends Model
{
    protected $table = 'gestion_cartera';

    protected $casts = [
        'fecha_factura' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    protected $fillable = [
        'user_id',
        'empresa_id',
        'numero_factura',
        'user_comercial_id',
        'cliente_id',
        'valor_total',
        'saldo_pendiente',
        'fecha_vencimiento',
        'observaciones',
        'fecha_factura',
        'estado',
        'dias_credito',
        'base',
        'iva',
        'rete_renta',
        'rete_ica'

    ];

    public function pagos()
    {
        return $this->hasMany(GestionCarteraPivote::class, 'gestion_cartera_id');
    }

    public function cliente()
{
    return $this->belongsTo(Cliente::class, 'cliente_id');
}

public function comercial()
{
    return $this->belongsTo(User::class, 'user_comercial_id');
}

public function empresa()
{
    return $this->belongsTo(empresa::class, 'empresa_id');
}
}
