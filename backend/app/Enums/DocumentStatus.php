<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pendiente = 'pendiente';
    case FirmadoParcial = 'firmado_parcial';
    case Completo = 'completo';
}
