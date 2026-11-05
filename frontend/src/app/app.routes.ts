import { Routes } from '@angular/router';
import { DocumentForm } from './documents/document-form';
import { DocumentList } from './documents/document-list';
import { SignPage } from './sign/sign-page';

export const routes: Routes = [
  { path: '', component: DocumentList, title: 'Documentos' },
  { path: 'new', component: DocumentForm, title: 'Nuevo documento' },
  { path: 'sign/:token', component: SignPage, title: 'Firmar documento' },
  { path: '**', redirectTo: '' },
];
