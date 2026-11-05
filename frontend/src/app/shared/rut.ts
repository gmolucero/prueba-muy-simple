import { AbstractControl, ValidationErrors } from '@angular/forms';

/** El RUT debe ir sin puntos y con guion ("12345678-5"). Devuelve ese formato (K en mayúscula) o null. */
export function normalizeRut(rut: string): string | null {
  const match = /^(\d{7,8})-([\dKk])$/.exec(rut);
  return match ? `${match[1]}-${match[2].toUpperCase()}` : null;
}

export function isValidRut(rut: string): boolean {
  const normalized = normalizeRut(rut);
  if (!normalized) return false;
  const [body, dv] = normalized.split('-');
  let sum = 0;
  let factor = 2;
  for (const digit of body.split('').reverse()) {
    sum += Number(digit) * factor;
    factor = factor === 7 ? 2 : factor + 1;
  }
  const rest = 11 - (sum % 11);
  const expected = rest === 11 ? '0' : rest === 10 ? 'K' : String(rest);
  return expected === dv;
}

/** Solo para UX: la validación real es la del servidor. */
export function rutValidator(control: AbstractControl): ValidationErrors | null {
  const value = String(control.value ?? '');
  return value === '' || isValidRut(value) ? null : { rut: true };
}
