import { isValidRut, normalizeRut } from './rut';

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
