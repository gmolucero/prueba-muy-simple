import { Component, inject, signal } from '@angular/core';
import { FormArray, FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { ApiService, SignLink, errorMessage } from '../api.service';
import { rutValidator } from '../shared/rut';

@Component({
  selector: 'app-document-form',
  imports: [ReactiveFormsModule, RouterLink],
  template: `
    <h2>Nuevo documento</h2>

    @if (links(); as created) {
      <div class="success" role="status">
        <p><strong>Documento creado.</strong> Enlaces de firma (no se envían correos en esta PoC):</p>
        <ul>
          @for (link of created; track link.rut) {
            <li>{{ link.email }} ({{ link.rut }}): <a [href]="link.sign_url">{{ link.sign_url }}</a></li>
          }
        </ul>
        <a routerLink="/">Ver documentos</a>
      </div>
    } @else {
      <form [formGroup]="form" (ngSubmit)="submit()">
        <label>Título
          <input formControlName="title" />
        </label>
        @if (touched('title') && form.controls.title.invalid) {
          <small class="error">El título es obligatorio.</small>
        }

        <label>Archivo PDF (máx. 5 MB)
          <input type="file" accept="application/pdf" (change)="onFile($event)" />
        </label>
        @if (submitted() && !file()) {
          <small class="error">Selecciona un PDF.</small>
        }

        <h3>Firmantes</h3>
        <div formArrayName="signers">
          @for (signer of signers.controls; track signer; let i = $index) {
            <div class="signer" [formGroupName]="i">
              <input formControlName="rut" placeholder="RUT (ej. 12.345.678-5)" />
              <input formControlName="email" type="email" placeholder="email@dominio.cl" />
              <button type="button" (click)="removeSigner(i)" [disabled]="signers.length === 1">Quitar</button>
              @if ((signer.controls.rut.touched || submitted()) && signer.controls.rut.invalid) {
                <small class="error">RUT inválido.</small>
              }
              @if ((signer.controls.email.touched || submitted()) && signer.controls.email.invalid) {
                <small class="error">Email inválido.</small>
              }
            </div>
          }
        </div>
        <button type="button" (click)="addSigner()">+ Agregar firmante</button>

        @if (error()) {
          <p class="error" role="alert">{{ error() }}</p>
        }
        <button type="submit" [disabled]="saving()">{{ saving() ? 'Guardando…' : 'Crear documento' }}</button>
      </form>
    }
  `,
})
export class DocumentForm {
  private readonly api = inject(ApiService);
  private readonly fb = inject(FormBuilder);

  protected readonly file = signal<File | null>(null);
  protected readonly saving = signal(false);
  protected readonly submitted = signal(false);
  protected readonly error = signal<string | null>(null);
  protected readonly links = signal<SignLink[] | null>(null);

  protected readonly form = this.fb.nonNullable.group({
    title: ['', [Validators.required, Validators.maxLength(255)]],
    signers: this.fb.array([this.newSigner()]),
  });

  protected get signers(): FormArray<ReturnType<DocumentForm['newSigner']>> {
    return this.form.controls.signers;
  }

  private newSigner() {
    return this.fb.nonNullable.group({
      rut: ['', [Validators.required, rutValidator]],
      email: ['', [Validators.required, Validators.email]],
    });
  }

  protected touched(name: 'title'): boolean {
    return this.form.controls[name].touched || this.submitted();
  }

  protected addSigner(): void {
    this.signers.push(this.newSigner());
  }

  protected removeSigner(index: number): void {
    if (this.signers.length > 1) this.signers.removeAt(index);
  }

  protected onFile(event: Event): void {
    this.file.set((event.target as HTMLInputElement).files?.item(0) ?? null);
  }

  protected submit(): void {
    this.submitted.set(true);
    this.error.set(null);
    this.form.markAllAsTouched();

    const file = this.file();
    if (this.form.invalid || !file) return;

    const { title, signers } = this.form.getRawValue();
    this.saving.set(true);
    this.api.createDocument(title, file, signers).subscribe({
      next: (res) => {
        this.links.set(res.sign_links);
        this.saving.set(false);
      },
      error: (err) => {
        this.error.set(errorMessage(err));
        this.saving.set(false);
      },
    });
  }
}
