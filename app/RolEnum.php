<?php

namespace App;

enum RolEnum:int
{
    case ADMINISTRADOR = 1;
    case HSEQ = 2;
    case INVITADO = 3;
    case ADMINISTRATIVO = 4;
    case COMPRAS = 5;
    case INVENTARIO = 6;
    case COMERCIAL = 7;
    case TRANSPORTE = 8;
    case EJECUTIVO_COMERCIAL = 9;
    case GERENTE_COMERCIAL = 10;
    case TECNOLOGIA = 11;
}