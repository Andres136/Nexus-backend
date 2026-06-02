<?php

namespace App\Models\Nomina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Licencia extends Model
{
    use SoftDeletes;

    protected $table = 'licencias';

    protected $fillable = [
        'uuid',
        'user_id',
        'tipo',
        'inicio',
        'fin',
        'dias_calendario',
        'soporte',
        'motivo',
        'status',
        'autorizador_id',
        'observacion',
        'fecha_gestion',
    ];

    protected $casts = [
        'inicio'        => 'date',
        'fin'           => 'date',
        'fecha_gestion' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function empleado()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function autorizador()
    {
        return $this->belongsTo(User::class, 'autorizador_id');
    }
}
