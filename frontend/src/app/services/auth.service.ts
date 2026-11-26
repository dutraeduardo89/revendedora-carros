import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private loggedIn = false;
  usuario = '';

  constructor() {
    this.loggedIn = !!localStorage.getItem('token');
    this.usuario = localStorage.getItem('usuarioLogado') || '';
    if (!this.loggedIn) {
      localStorage.removeItem('logado');
      localStorage.removeItem('usuarioLogado');
    }
  }

  login(usuario: string, token: string) {
    this.loggedIn = true;
    this.usuario = usuario;
    localStorage.setItem('logado', 'true');
    localStorage.setItem('usuarioLogado', usuario);
    localStorage.setItem('token', token);
  }

  logout() {
    this.loggedIn = false;
    this.usuario = '';
    localStorage.removeItem('logado');
    localStorage.removeItem('usuarioLogado');
    localStorage.removeItem('token');
  }

  token(): string {
    return localStorage.getItem('token') || '';
  }

  isLoggedIn(): boolean {
    return this.loggedIn && !!this.token();
  }
}
