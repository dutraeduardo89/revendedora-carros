import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { CommonModule } from '@angular/common';
import { ApiService } from '../services/api';
import { camposErro, mensagemErro } from '../services/erros';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [FormsModule, CommonModule, RouterLink],
  templateUrl: './register.html',
})
export class RegisterComponent {
  dados = { login: '', senha: '', confirmar: '' };
  msg = '';
  tipoMsg = '';
  erros: Record<string, string> = {};
  enviando = false;

  constructor(private api: ApiService, private router: Router) {}

  registrar() {
    this.erros = {};
    this.msg = '';
    if (!/^[A-Za-z0-9._-]{3,50}$/.test(this.dados.login.trim())) {
      this.erros['login'] = 'Use de 3 a 50 caracteres: letras, números, ponto, hífen ou sublinhado.';
    }
    if (this.dados.senha.length < 6) this.erros['senha'] = 'A senha deve ter pelo menos 6 caracteres.';
    if (this.dados.senha !== this.dados.confirmar) this.erros['confirmar'] = 'A confirmação não é igual à senha.';
    if (Object.keys(this.erros).length || this.enviando) {
      if (Object.keys(this.erros).length) {
        this.msg = 'Verifique os campos destacados.';
        this.tipoMsg = 'alert-danger';
      }
      return;
    }
    this.enviando = true;
    this.api.registrar({ login: this.dados.login.trim(), senha: this.dados.senha }).subscribe({
      next: (res) => {
        this.enviando = false;
        this.msg = res.message;
        this.tipoMsg = 'alert-success';
        setTimeout(() => this.router.navigate(['/']), 1200);
      },
      error: (err) => {
        this.enviando = false;
        this.erros = camposErro(err);
        this.msg = mensagemErro(err, 'Falha ao salvar informações.');
        this.tipoMsg = 'alert-danger';
      },
    });
  }
}
