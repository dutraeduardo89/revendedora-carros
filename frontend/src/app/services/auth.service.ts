import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private loggedIn = false;

  constructor() {
    // Verifica se já estava logado ao recarregar a página
    this.loggedIn = !!localStorage.getItem('logado');
  }

  login() {
    this.loggedIn = true;
    localStorage.setItem('logado', 'true');
  }

  logout() {
    this.loggedIn = false;
    localStorage.removeItem('logado');
  }

  isLoggedIn(): boolean {
    return this.loggedIn;
  }
}
