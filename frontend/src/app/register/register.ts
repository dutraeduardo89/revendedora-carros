import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { ApiService } from '../services/api';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [FormsModule, CommonModule, RouterLink],
  templateUrl: './register.html'
})
export class RegisterComponent {
  dados = { login: '', senha: '' };
  msg = '';
  tipoMsg = '';

  constructor(private api: ApiService, private router: Router) {}

  registrar() {
    if (!this.dados.login || !this.dados.senha) {
      this.msg = 'Preencha todos os campos.'; this.tipoMsg = 'alert-danger'; return;
    }
    
    this.api.registrar(this.dados).subscribe({
      next: (res) => {
        this.msg = res.message; this.tipoMsg = 'alert-success';
        setTimeout(() => this.router.navigate(['/']), 2000);
      },
      error: (err) => {
        this.msg = err.error.message; this.tipoMsg = 'alert-danger';
      }
    });
  }
}
