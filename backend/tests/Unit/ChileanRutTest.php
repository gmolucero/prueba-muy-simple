<?php

namespace Tests\Unit;

use App\Support\Rut;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ChileanRutTest extends TestCase
{
    #[DataProvider('validRuts')]
    public function test_accepts_valid_ruts(string $rut): void
    {
        $this->assertTrue(Rut::isValid($rut));
    }

    #[DataProvider('invalidRuts')]
    public function test_rejects_invalid_ruts(string $rut): void
    {
        $this->assertFalse(Rut::isValid($rut));
    }

    public static function validRuts(): array
    {
        return [
            'con guion' => ['11111111-1'],
            'dv K' => ['10000013-K'],
            'dv k minúscula' => ['10000013-k'],
            'dv cero' => ['11111117-0'],
        ];
    }

    public static function invalidRuts(): array
    {
        return [
            'dv incorrecto' => ['11111111-2'],
            'con puntos' => ['12.345.678-5'],
            'sin guion' => ['123456785'],
            'espacios' => [' 12345678-5'],
            'muy corto' => ['11-1'],
            'letras' => ['abcdefgh-1'],
            'vacío' => [''],
        ];
    }

    public function test_normalize(): void
    {
        $this->assertSame('12345678-5', Rut::normalize('12345678-5'));
        $this->assertSame('10000013-K', Rut::normalize('10000013-k'));
        $this->assertNull(Rut::normalize('12.345.678-5'));
        $this->assertNull(Rut::normalize('basura'));
    }
}
