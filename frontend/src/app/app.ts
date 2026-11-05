import { Component } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';

@Component({
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  selector: 'app-root',
  styleUrl: './app.scss',
  template: `
    <header>
      <strong>Firma de documentos</strong>
      <nav>
        <a routerLink="/" routerLinkActive="active" [routerLinkActiveOptions]="{ exact: true }">Documentos</a>
        <a routerLink="/new" routerLinkActive="active">Nuevo documento</a>
      </nav>
    </header>
    <main><router-outlet /></main>
  `,
})
export class App {}
