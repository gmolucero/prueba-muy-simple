import { DatePipe } from '@angular/common';
import { Component, OnInit, inject, signal } from '@angular/core';
import { ApiService, DocumentItem, errorMessage } from '../api.service';

@Component({
  selector: 'app-document-list',
  imports: [DatePipe],
  template: `
    <div class="row">
      <h2>Documentos</h2>
      <button type="button" (click)="load()" [disabled]="loading()">Actualizar</button>
    </div>

    @if (loading()) {
      <p>Cargando…</p>
    } @else if (error()) {
      <p class="error" role="alert">{{ error() }}</p>
    } @else if (documents().length === 0) {
      <p>Aún no hay documentos. Crea el primero desde «Nuevo documento».</p>
    } @else {
      <table>
        <thead>
          <tr><th>Título</th><th>Estado</th><th>Firmas</th><th>Creado</th></tr>
        </thead>
        <tbody>
          @for (doc of documents(); track doc.id) {
            <tr>
              <td>{{ doc.title }}</td>
              <td><span class="badge" [class]="'badge ' + doc.status">{{ label(doc.status) }}</span></td>
              <td>{{ doc.signed_count }} / {{ doc.signers_count }}</td>
              <td>{{ doc.created_at | date: 'dd-MM-yyyy HH:mm' }}</td>
            </tr>
          }
        </tbody>
      </table>
    }
  `,
})
export class DocumentList implements OnInit {
  private readonly api = inject(ApiService);

  protected readonly documents = signal<DocumentItem[]>([]);
  protected readonly loading = signal(false);
  protected readonly error = signal<string | null>(null);

  ngOnInit(): void {
    this.load();
  }

  protected load(): void {
    this.loading.set(true);
    this.error.set(null);
    this.api.getDocuments().subscribe({
      next: (res) => {
        this.documents.set(res.data);
        this.loading.set(false);
      },
      error: (err) => {
        this.error.set(errorMessage(err));
        this.loading.set(false);
      },
    });
  }

  protected label(status: string): string {
    return { pendiente: 'Pendiente', firmado_parcial: 'Firmado parcial', completo: 'Completo' }[status] ?? status;
  }
}
