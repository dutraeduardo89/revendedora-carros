import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { ApiService } from '../services/api';
import { AuthService } from '../services/auth.service';
import { camposErro, mensagemErro } from '../services/erros';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [FormsModule, CommonModule, RouterLink],
  templateUrl: './login.component.html',
})
export class LoginComponent {
  credenciais = { login: '', senha: '' };
  erro = '';
  aviso = '';
  erros: Record<string, string> = {};
  enviando = false;

  constructor(private api: ApiService, private router: Router, private authService: AuthService) {
    this.aviso = history.state?.aviso || '';
    if (this.authService.isLoggedIn()) {
      this.router.navigate(['/dashboard']);
    }
  }

  entrar() {
    this.erros = {};
    this.erro = '';
    if (!this.credenciais.login.trim()) this.erros['login'] = 'Informe o usuário.';
    if (!this.credenciais.senha) this.erros['senha'] = 'Informe a senha.';
    if (Object.keys(this.erros).length || this.enviando) return;
    this.enviando = true;
    this.api.login({ login: this.credenciais.login.trim(), senha: this.credenciais.senha }).subscribe({
      next: (res) => {
        this.authService.login(res.user, res.token);
        this.router.navigate(['/dashboard']);
      },
      error: (err) => {
        this.enviando = false;
        this.erros = camposErro(err);
        this.erro = mensagemErro(err, 'Usuário ou senha inválidos.');
      },
    });
  }
}
