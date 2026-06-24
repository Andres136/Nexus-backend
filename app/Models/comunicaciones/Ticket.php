<?php

namespace App\Models\comunicaciones;

use App\Models\Crm\product;
use App\Models\Departamentos;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_solicitante_id',
        'user_asignado_id',
        'producto_id',
        'departamento_id',
        'descripcion',
        'estado',
        'archivo',
        'prioridad',
        'fecha_entrega',
        'hora_entrega',
        'fecha_solucion',
    ];

    protected $casts = [
        'fecha_entrega' => 'date:Y-m-d',
        'fecha_solucion' => 'datetime',
    ];

    protected $appends = [
        'fecha_creacion',
        'hora_creacion',
        'fecha_edicion',
        'hora_edicion',
        'fecha_cierre',
        'hora_cierre',
    ];

    public function getFechaCreacionAttribute(): ?string
    {
        return $this->created_at?->format('Y-m-d');
    }

    public function getHoraCreacionAttribute(): ?string
    {
        return $this->created_at?->format('H:i:s');
    }

    public function getFechaEdicionAttribute(): ?string
    {
        return $this->updated_at?->format('Y-m-d');
    }

    public function getHoraEdicionAttribute(): ?string
    {
        return $this->updated_at?->format('H:i:s');
    }

    public function getFechaCierreAttribute(): ?string
    {
        return $this->fecha_solucion?->format('Y-m-d');
    }

    public function getHoraCierreAttribute(): ?string
    {
        return $this->fecha_solucion?->format('H:i:s');
    }

    public function solicitante()
    {
        return $this->belongsTo(User::class, 'user_solicitante_id');
    }

    public function asignado()
    {
        return $this->belongsTo(User::class, 'user_asignado_id');
    }

    public function producto()
    {
        return $this->belongsTo(product::class, 'producto_id');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamentos::class, 'departamento_id');
    }

    public function historial()
    {
        return $this->hasMany(HistorialTickect::class, 'ticket_id')->latest();
    }
}
