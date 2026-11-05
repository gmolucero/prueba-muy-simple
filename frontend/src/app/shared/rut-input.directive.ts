import { Directive, ElementRef, inject } from '@angular/core';
import { NgControl } from '@angular/forms';
import { formatRutInput } from './rut';

/** Formatea el RUT mientras se escribe o se pega. Se usa en un <input> con formControl/formControlName. */
@Directive({
  selector: 'input[appRutInput]',
  host: { autocomplete: 'off', '(input)': 'onInput()' },
})
export class RutInput {
  private readonly input = inject<ElementRef<HTMLInputElement>>(ElementRef).nativeElement;
  private readonly ngControl = inject(NgControl);

  protected onInput(): void {
    const raw = this.input.value;
    const formatted = formatRutInput(raw);
    if (formatted === raw) return;

    // Caracteres válidos que había antes del cursor, para devolver el cursor al lugar correcto.
    const caret = this.input.selectionStart ?? raw.length;
    const significant = raw.slice(0, caret).toUpperCase().replace(/[^0-9K]/g, '').length;

    this.ngControl.control?.setValue(formatted); // actualiza el control y el valor mostrado

    let position = 0;
    for (let seen = 0; position < formatted.length && seen < significant; position++) {
      if (formatted[position] !== '-') seen++;
    }
    this.input.setSelectionRange(position, position);
  }
}
