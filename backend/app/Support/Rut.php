<?php

namespace App\Support;

/**
 * Utilidades para RUT chileno. Formato único aceptado y almacenado: sin puntos y con guion ("18062028-1").
 */
final class Rut
{
    /**
     * El RUT debe venir sin puntos y con guion ("12345678-5"). Devuelve ese formato con el
     * dígito verificador en mayúscula (K), o null si no cumple el formato.
     */
    public static function normalize(string $rut): ?string
    {
        if (! preg_match('/^(\d{7,8})-([\dKk])$/', $rut, $m)) {
            return null;
        }

        return $m[1].'-'.strtoupper($m[2]);
    }

    public static function isValid(string $rut): bool
    {
        $normalized = self::normalize($rut);

        if ($normalized === null) {
            return false;
        }

        [$body, $dv] = explode('-', $normalized);

        return self::checkDigit($body) === $dv;
    }

    /** Dígito verificador por módulo 11. */
    public static function checkDigit(string $body): string
    {
        $sum = 0;
        $factor = 2;

        foreach (array_reverse(str_split($body)) as $digit) {
            $sum += (int) $digit * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }

        $remainder = 11 - ($sum % 11);

        return match ($remainder) {
            11 => '0',
            10 => 'K',
            default => (string) $remainder,
        };
    }
}
