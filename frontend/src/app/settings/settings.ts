import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../services/api';

@Component({
  selector: 'app-settings',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './settings.html'
})
export class SettingsComponent implements OnInit {
  stats = { total: 0, disponiveis: 0, vendidos: 0 };
  senhaAtual = '';
  novaSenha = '';
  confirmarSenha = '';
  msg = '';
  tipoMsg = '';

  constructor(private api: ApiService) {}

  ngOnInit() {
    this.api.getStats().subscribe(res => this.stats = res);
  }

  salvarSenha() {
    if (this.novaSenha !== this.confirmarSenha) {
      this.msg = 'A nova senha e a confirmação não batem.';
      this.tipoMsg = 'alert-danger';
      return;
    }
    if (this.novaSenha.length < 3) {
      this.msg = 'A nova senha deve ter pelo menos 3 caracteres.';
      this.tipoMsg = 'alert-danger';
      return;
    }

    // Pega o usuário real que está logado no momento
    const usuarioLogado = localStorage.getItem('usuarioLogado') || 'admin';
    const dados = { login: usuarioLogado, senha_atual: this.senhaAtual, nova_senha: this.novaSenha };
    
    this.api.alterarSenha(dados).subscribe({
      next: (res: any) => {
        this.msg = res.message;
        this.tipoMsg = 'alert-success';
        this.senhaAtual = ''; this.novaSenha = ''; this.confirmarSenha = '';
      },
      error: (err: any) => {
        this.msg = err.error.message;
        this.tipoMsg = 'alert-danger';
      }
    });
  }
}
