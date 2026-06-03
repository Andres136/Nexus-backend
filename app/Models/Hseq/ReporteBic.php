<?php

namespace App\Models\Hseq;

use App\Models\Crm\empresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReporteBic extends Model
{
    protected $table = 'reportes_bic';

    protected $fillable = [
        'uuid',
        'empresa_id',
        'nombre',
        'fecha_reporte',
        'archivo_path'
    ];

    protected static function booted()
    {
        static::creating(function ($reporte) {
            $reporte->uuid = (string) Str::uuid();
        });
    }

    public function empresa()
    {
        return $this->belongsTo(empresa::class, 'empresa_id');
    }
}