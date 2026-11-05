import { isValidRut, normalizeRut } from './rut';

describe('rut', () => {
  it('acepta RUT válidos en distintos formatos', () => {
    for (const rut of ['11111111-1', '12.345.678-5', '123456785', '10000013-K', '10000013-k', '11111117-0']) {
      expect(isValidRut(rut)).toBe(true);
    }
  });

  it('rechaza RUT inválidos', () => {
    for (const rut of ['11111111-2', '11-1', 'abc', '']) {
      expect(isValidRut(rut)).toBe(false);
    }
  });

  it('normaliza al formato canónico', () => {
    expect(normalizeRut('12.345.678-5')).toBe('12345678-5');
    expect(normalizeRut('basura')).toBeNull();
  });
});
