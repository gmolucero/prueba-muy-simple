<?php

namespace App\Support;

/**
 * Utilidades para RUT chileno. Formato canónico de almacenamiento: "18062028-1".
 */
final class Rut
{
    /** Quita puntos, espacios y guion; devuelve "cuerpo-DV" en mayúscula, o null si no tiene forma de RUT. */
    public static function normalize(string $rut): ?string
    {
        $clean = strtoupper(preg_replace('/[\.\s\-]/', '', $rut) ?? '');

        if (! preg_match('/^\d{7,8}[\dK]$/', $clean)) {
            return null;
        }

        return substr($clean, 0, -1).'-'.substr($clean, -1);
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
