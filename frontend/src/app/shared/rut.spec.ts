import { formatRutInput, isValidRut, normalizeRut } from './rut';

describe('rut', () => {
  it('acepta RUT válidos en distintos formatos', () => {
    for (const rut of ['11111111-1', '12345678-5', '10000013-K', '10000013-k', '11111117-0']) {
      expect(isValidRut(rut)).toBe(true);
    }
  });

  it('rechaza RUT inválidos', () => {
    for (const rut of ['11111111-2', '11-1', 'abc', '', '12.345.678-5', '123456785']) {
      expect(isValidRut(rut)).toBe(false);
    }
  });

  it('solo acepta formato sin puntos y con guion', () => {
    expect(normalizeRut('10000013-k')).toBe('10000013-K');
    expect(normalizeRut('12.345.678-5')).toBeNull();
    expect(normalizeRut('basura')).toBeNull();
  });
});

describe('formatRutInput', () => {
  it('formatea mientras se escribe', () => {
    expect(formatRutInput('')).toBe('');
    expect(formatRutInput('1')).toBe('1');
    expect(formatRutInput('12')).toBe('1-2');
    expect(formatRutInput('123456785')).toBe('12345678-5');
  });

  it('descarta puntos, espacios, letras y símbolos', () => {
    expect(formatRutInput('12.345.678-5')).toBe('12345678-5');
    expect(formatRutInput(' 1a2b3$4 ')).toBe('123-4');
  });

  it('acepta la K solo como último carácter y en mayúscula', () => {
    expect(formatRutInput('10000013k')).toBe('10000013-K');
    expect(formatRutInput('1k2345678')).toBe('1234567-8'); // la K del medio se descarta
  });

  it('limita a 9 caracteres', () => {
    expect(formatRutInput('1234567890123')).toBe('12345678-9');
  });
});
