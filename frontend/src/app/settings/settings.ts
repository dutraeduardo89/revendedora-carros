import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { ApiService } from '../services/api';
import { AuthService } from '../services/auth.service';
import { camposErro, mensagemErro } from '../services/erros';
import { RegrasClassificacao } from '../domain/vehicle-rules';

@Component({
  selector: 'app-settings',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './settings.html',
})
export class SettingsComponent implements OnInit {
  regras: RegrasClassificacao = { novo_km_max: 0, seminovo_km_max: 30000, velho_idade_min: 15 };
  senhaAtual = '';
  novaSenha = '';
  confirmarSenha = '';
  msgSenha = '';
  tipoSenha = '';
  msgRegras = '';
  tipoRegras = '';
  errosSenha: Record<string, string> = {};
  errosRegras: Record<string, string> = {};
  salvandoSenha = false;
  salvandoRegras = false;
  carregando = true;

  constructor(
    private api: ApiService,
    private auth: AuthService,
    private router: Router,
    private cdr: ChangeDetectorRef,
  ) {}

  ngOnInit() {
    this.api.getRegras().subscribe({
      next: (regras) => {
        this.regras = regras;
        this.carregando = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.carregando = false;
        this.msgRegras = mensagemErro(err, 'Falha ao carregar dados.');
        this.tipoRegras = 'alert-danger';
        this.cdr.detectChanges();
      },
    });
  }

  salvarSenha() {
    this.errosSenha = {};
    this.msgSenha = '';
    if (!this.senhaAtual) this.errosSenha['senha_atual'] = 'Informe a senha atual.';
    if (this.novaSenha.length < 6) this.errosSenha['nova_senha'] = 'A nova senha deve ter pelo menos 6 caracteres.';
    if (this.novaSenha !== this.confirmarSenha) this.errosSenha['confirmar'] = 'A confirmação não é igual à nova senha.';
    if (Object.keys(this.errosSenha).length || this.salvandoSenha) {
      if (Object.keys(this.errosSenha).length) {
        this.msgSenha = 'Verifique os campos destacados.';
        this.tipoSenha = 'alert-danger';
      }
      return;
    }
    this.salvandoSenha = true;
    this.api.alterarSenha({ senha_atual: this.senhaAtual, nova_senha: this.novaSenha }).subscribe({
      next: (res) => {
        this.auth.logout();
        this.router.navigate(['/'], { state: { aviso: res.message } });
      },
      error: (err) => {
        this.salvandoSenha = false;
        this.errosSenha = camposErro(err);
        this.msgSenha = mensagemErro(err, 'Falha ao salvar informações.');
        this.tipoSenha = 'alert-danger';
        this.cdr.detectChanges();
      },
    });
  }

  salvarRegras() {
    if (this.salvandoRegras) return;
    this.errosRegras = {};
    this.msgRegras = '';
    this.salvandoRegras = true;
    this.api.salvarRegras({
      novo_km_max: Number(this.regras.novo_km_max),
      seminovo_km_max: Number(this.regras.seminovo_km_max),
      velho_idade_min: Number(this.regras.velho_idade_min),
    }).subscribe({
      next: (res) => {
        this.salvandoRegras = false;
        this.msgRegras = res.message;
        this.tipoRegras = 'alert-success';
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.salvandoRegras = false;
        this.errosRegras = camposErro(err);
        this.msgRegras = mensagemErro(err, 'Falha ao salvar informações.');
        this.tipoRegras = 'alert-danger';
        this.cdr.detectChanges();
      },
    });
  }
}
