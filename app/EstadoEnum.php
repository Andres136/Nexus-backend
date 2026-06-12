<?php

namespace App;

enum EstadoEnum:int
{
    case PENDIENTE = 1;
    case COMPLETADO = 2;
    case ACTIVO = 3;
    case INACTIVO = 4;
    case ENTREGA_PARCIAL = 5;
    case PAGADA = 101;
    case PAGO_PARCIAL = 102;
    case ANULADA = 103;

    public function nombre(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::COMPLETADO => 'Completado',
            self::ACTIVO => 'Activo',
            self::INACTIVO => 'Inactivo',
            self::ENTREGA_PARCIAL => 'Entrega Parcial',
            self::PAGADA => 'Pagada',
            self::PAGO_PARCIAL => 'Pago parcial',
            self::ANULADA => 'Anulada',
        };
    }
}
