<?php

namespace App\Rules;

use App\Support\Rut;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ChileanRut implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Rut::isValid($value)) {
            $fail('El :attribute no es válido: debe ingresarse sin puntos y con guion (ej. 12345678-5).');
        }
    }
}
