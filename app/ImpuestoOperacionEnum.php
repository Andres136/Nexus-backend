<?php

namespace App;

enum ImpuestoOperacionEnum: string
{
    case SUMA = 'suma';
    case RESTA = 'resta';

    public function factor(): int
    {
        return $this === self::RESTA ? -1 : 1;
    }

    public function nombre(): string
    {
        return $this === self::RESTA ? 'Resta' : 'Suma';
    }
}
