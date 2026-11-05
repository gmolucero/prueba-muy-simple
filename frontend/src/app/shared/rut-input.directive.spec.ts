import { Component } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { RutInput } from './rut-input.directive';

@Component({
  imports: [ReactiveFormsModule, RutInput],
  template: `<input [formControl]="rut" appRutInput />`,
})
class Host {
  rut = new FormControl('', { nonNullable: true });
}

describe('RutInput', () => {
  function setup() {
    const fixture = TestBed.createComponent(Host);
    fixture.detectChanges();
    const input = fixture.nativeElement.querySelector('input') as HTMLInputElement;
    const type = (value: string) => {
      input.value = value;
      input.dispatchEvent(new Event('input'));
    };
    return { fixture, input, type };
  }

  it('formatea al escribir y actualiza el control', () => {
    const { fixture, input, type } = setup();
    type('123456785');
    expect(input.value).toBe('12345678-5');
    expect(fixture.componentInstance.rut.value).toBe('12345678-5');
  });

  it('limpia lo pegado con puntos', () => {
    const { fixture, input, type } = setup();
    type('12.345.678-5');
    expect(input.value).toBe('12345678-5');
    expect(fixture.componentInstance.rut.value).toBe('12345678-5');
  });

  it('descarta caracteres no permitidos', () => {
    const { input, type } = setup();
    type('abc$%');
    expect(input.value).toBe('');
  });
});
