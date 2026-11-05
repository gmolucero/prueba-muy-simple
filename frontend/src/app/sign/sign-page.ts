import { DatePipe } from '@angular/common';
import { Component, OnInit, inject, input, signal } from '@angular/core';
import { ApiService, SignPayload, errorMessage } from '../api.service';

@Component({
  selector: 'app-sign-page',
  imports: [DatePipe],
  template: `
    <h2>Firma de documento</h2>

    @if (loading()) {
      <p>Cargando…</p>
    } @else if (loadError()) {
      <p class="error" role="alert">{{ loadError() }}</p>
    } @else if (data(); as d) {
      <p>Documento: <strong>{{ d.document.title }}</strong></p>
      <p>Firmante: {{ d.signer.email }} ({{ d.signer.rut }})</p>

      @if (d.signer.state === 'firmado') {
        <p class="success" role="status">
          {{ justSigned() ? '¡Firma registrada correctamente!' : 'Este firmante ya firmó este documento.' }}
        </p>
      } @else {
        <p><small>Este enlace es válido hasta el {{ d.signer.expires_at | date: 'dd-MM-yyyy HH:mm' }}.</small></p>
        <button type="button" (click)="sign()" [disabled]="signing()">
          {{ signing() ? 'Firmando…' : 'Firmar documento' }}
        </button>
      }
      @if (signError()) {
        <p class="error" role="alert">{{ signError() }}</p>
      }
    }
  `,
})
export class SignPage implements OnInit {
  /** Parámetro de ruta :token (withComponentInputBinding). */
  readonly token = input.required<string>();

  private readonly api = inject(ApiService);

  protected readonly data = signal<SignPayload | null>(null);
  protected readonly loading = signal(true);
  protected readonly loadError = signal<string | null>(null);
  protected readonly signing = signal(false);
  protected readonly signError = signal<string | null>(null);
  protected readonly justSigned = signal(false);

  ngOnInit(): void {
    this.api.getSigning(this.token()).subscribe({
      next: (res) => {
        this.data.set(res.data);
        this.loading.set(false);
      },
      error: (err) => {
        this.loadError.set(errorMessage(err));
        this.loading.set(false);
      },
    });
  }

  protected sign(): void {
    this.signing.set(true);
    this.signError.set(null);
    this.api.sign(this.token()).subscribe({
      next: (res) => {
        this.data.set(res.data);
        this.justSigned.set(true);
        this.signing.set(false);
      },
      error: (err) => {
        this.signError.set(errorMessage(err));
        this.signing.set(false);
      },
    });
  }
}
