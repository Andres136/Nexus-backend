<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class MetaMensual extends Model
{
    protected $fillable = ['anio', 'mes', 'valor_meta'];

    /**
     * Get the formatted month name.
     */
    public function getMesNombreAttribute()
    {
        return date('F', mktime(0, 0, 0, $this->mes, 1));
    }

    /**
     * Get the formatted year and month.
     */
    public function getAnioMesAttribute()
    {
        return "{$this->anio}-{$this->mes}";
    }
}
