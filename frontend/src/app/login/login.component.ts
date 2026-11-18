import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { ApiService } from '../services/api.service';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [FormsModule, CommonModule],
  templateUrl: './login.component.html'
})
export class LoginComponent {
  credenciais = { login: '', senha: '' };
  erro = '';

  constructor(private api: ApiService, private router: Router) {}

  entrar() {
    this.api.login(this.credenciais).subscribe({
      next: (res) => {
        this.router.navigate(['/dashboard']);
      },
      error: (err) => {
        this.erro = 'Usuário ou senha inválidos!';
      }
    });
  }
}
