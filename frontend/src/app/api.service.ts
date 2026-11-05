import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

export type DocumentStatus = 'pendiente' | 'firmado_parcial' | 'completo';

export interface Signer {
  rut: string;
  email: string;
  state: 'pendiente' | 'firmado';
  signed_at: string | null;
}

export interface DocumentItem {
  id: number;
  title: string;
  status: DocumentStatus;
  signed_count: number;
  signers_count: number;
  signers: Signer[];
  created_at: string;
}

export interface SignLink {
  rut: string;
  email: string;
  sign_url: string;
}

export interface SignPayload {
  document: { id: number; title: string; status: DocumentStatus };
  signer: Signer;
}

export interface NewSigner {
  rut: string;
  email: string;
}

/** Convierte cualquier error HTTP en un mensaje legible para el usuario. */
export function errorMessage(err: unknown): string {
  if (err instanceof HttpErrorResponse) {
    if (err.status === 0) return 'No se pudo conectar con el servidor.';
    const body = err.error as { message?: string; errors?: Record<string, string[]> } | null;
    const firstValidation = body?.errors ? Object.values(body.errors)[0]?.[0] : undefined;
    return firstValidation ?? body?.message ?? `Error inesperado (${err.status}).`;
  }
  return 'Error inesperado.';
}

@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly http = inject(HttpClient);

  getDocuments(): Observable<{ data: DocumentItem[] }> {
    return this.http.get<{ data: DocumentItem[] }>('/api/documents');
  }

  createDocument(title: string, file: File, signers: NewSigner[]): Observable<{ data: DocumentItem; sign_links: SignLink[] }> {
    const form = new FormData();
    form.append('title', title);
    form.append('file', file);
    signers.forEach((s, i) => {
      form.append(`signers[${i}][rut]`, s.rut);
      form.append(`signers[${i}][email]`, s.email);
    });
    return this.http.post<{ data: DocumentItem; sign_links: SignLink[] }>('/api/documents', form);
  }

  getSigning(token: string): Observable<{ data: SignPayload }> {
    return this.http.get<{ data: SignPayload }>(`/api/sign/${encodeURIComponent(token)}`);
  }

  sign(token: string): Observable<{ data: SignPayload }> {
    return this.http.post<{ data: SignPayload }>(`/api/sign/${encodeURIComponent(token)}`, {});
  }
}
