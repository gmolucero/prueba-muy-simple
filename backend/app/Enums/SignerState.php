<?php

namespace App\Enums;

enum SignerState: string
{
    case Pendiente = 'pendiente';
    case Firmado = 'firmado';
}
