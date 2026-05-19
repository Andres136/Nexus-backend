<?php

namespace App;

enum EstadoEnum:int
{
        case PENDIENTE = 1;
    case COMPLETADO = 2;
    case ACTIVO = 3;
    case INACTIVO = 4;
    case ENTREGA_PARCIAL = 5;


}
