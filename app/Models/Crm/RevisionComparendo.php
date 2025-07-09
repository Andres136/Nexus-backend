<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class RevisionComparendo extends Model
{
    protected $table = 'revision_comparendos';

    protected $fillable = [
        'conductor_id',
        'fecha_revision',
        'archivo_soporte',
        'observaciones',
    ];

    public function conductor()
    {
        return $this->belongsTo(DatoConductor::class, 'conductor_id');
    }
}
