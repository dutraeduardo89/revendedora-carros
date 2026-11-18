import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { ApiService } from '../services/api';
import { AuthService } from '../services/auth.service';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [FormsModule, CommonModule, RouterLink],
  templateUrl: './login.component.html'
})
export class LoginComponent {
  credenciais = { login: '', senha: '' };
  erro = '';

  constructor(private api: ApiService, private router: Router, private authService: AuthService) {}

  entrar() {
    this.api.login(this.credenciais).subscribe({
      next: (res) => {
        this.authService.login();
        localStorage.setItem('usuarioLogado', res.user); // Salva QUEM logou
        this.router.navigate(['/dashboard']);
      },
      error: (err) => {
        this.erro = 'Usuário ou senha inválidos!';
      }
    });
  }
}
