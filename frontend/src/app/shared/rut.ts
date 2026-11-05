import { AbstractControl, ValidationErrors } from '@angular/forms';

/** Quita puntos, espacios y guion; devuelve "cuerpo-DV" o null si no parece un RUT. */
export function normalizeRut(rut: string): string | null {
  const clean = rut.replace(/[.\s-]/g, '').toUpperCase();
  if (!/^\d{7,8}[\dK]$/.test(clean)) return null;
  return `${clean.slice(0, -1)}-${clean.slice(-1)}`;
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
