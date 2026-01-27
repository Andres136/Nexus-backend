<?php

namespace App\Exceptions\Traslados;

use Exception;

class EstadoTrasladoInvalidoException extends Exception
{
  //Si el traslado  de bodega  ya fue aprobado o rechazado, no se puede volver a aprobar
  public function __construct(string $message)
  {
      parent::__construct();
  }
}
