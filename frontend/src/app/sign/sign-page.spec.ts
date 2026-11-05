import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { SignPage } from './sign-page';

const payload = (state: 'pendiente' | 'firmado') => ({
  data: {
    document: { id: 1, title: 'Contrato', status: 'pendiente' },
    signer: { rut: '11111111-1', email: 'a@x.cl', state, signed_at: null, expires_at: '2026-12-01T10:00:00+00:00' },
  },
});

describe('SignPage', () => {
  function setup(token = 'abc') {
    TestBed.configureTestingModule({ providers: [provideHttpClient(), provideHttpClientTesting()] });
    const fixture = TestBed.createComponent(SignPage);
    fixture.componentRef.setInput('token', token);
    const http = TestBed.inject(HttpTestingController);
    fixture.detectChanges();
    return { fixture, http, el: () => fixture.nativeElement as HTMLElement };
  }

  it('muestra un error claro si el token no existe', async () => {
    const { fixture, http, el } = setup('malo');
    http.expectOne('/api/sign/malo').flush({ message: 'Enlace de firma no válido.' }, { status: 404, statusText: 'Not Found' });
    await fixture.whenStable();
    fixture.detectChanges();
    expect(el().querySelector('[role=alert]')?.textContent).toContain('Enlace de firma no válido.');
  });

  it('muestra un mensaje claro si el enlace expiró', async () => {
    const { fixture, http, el } = setup('viejo');
    http.expectOne('/api/sign/viejo').flush({ message: 'El enlace de firma expiró.' }, { status: 410, statusText: 'Gone' });
    await fixture.whenStable();
    fixture.detectChanges();
    expect(el().querySelector('[role=alert]')?.textContent).toContain('expiró');
  });

  it('permite firmar y confirma el resultado', async () => {
    const { fixture, http, el } = setup();
    http.expectOne('/api/sign/abc').flush(payload('pendiente'));
    await fixture.whenStable();
    fixture.detectChanges();

    (el().querySelector('button') as HTMLButtonElement).click();
    http.expectOne({ method: 'POST', url: '/api/sign/abc' }).flush(payload('firmado'));
    await fixture.whenStable();
    fixture.detectChanges();

    expect(el().textContent).toContain('Firma registrada correctamente');
  });

  it('informa si el firmante ya había firmado', async () => {
    const { fixture, http, el } = setup();
    http.expectOne('/api/sign/abc').flush(payload('firmado'));
    await fixture.whenStable();
    fixture.detectChanges();
    expect(el().textContent).toContain('ya firmó');
    expect(el().querySelector('button')).toBeNull();
  });
});
