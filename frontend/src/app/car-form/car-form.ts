import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../services/api';
import { Router } from '@angular/router';

@Component({
  selector: 'app-car-form',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './car-form.html'
})
export class CarFormComponent implements OnInit {
  carro: any = { placa: '', chassi: '', cor: '', ano: null, valor: null, quilometragem: null, situacao: 'D', id_modelo: null, acessorios: [] };
  marcas: any[] = []; 
  modelos: any[] = []; 
  acessorios: any[] = [];
  isEdicao = false; 
  msg = ''; 
  tipoMsg = '';

  constructor(private api: ApiService, private router: Router, private cdr: ChangeDetectorRef) {
    // Captura os dados na hora exata do clique
    const state = history.state;
    if (state && state.carro) {
      this.isEdicao = true;
      this.carro = { ...state.carro };
      // Garante que acessorios nunca seja undefined para não quebrar o HTML
      if (!this.carro.acessorios) this.carro.acessorios = [];
    }
  }

  ngOnInit() {
    // Busca as listas e força a atualização da tela
    this.api.getAuxiliares('marcas').subscribe(res => { this.marcas = res; this.cdr.detectChanges(); });
    this.api.getAuxiliares('modelos').subscribe(res => { this.modelos = res; this.cdr.detectChanges(); });
    this.api.getAuxiliares('acessorios').subscribe(res => {
      this.acessorios = res;
      
      // Só faz o mapeamento se for edição e se os dados chegaram
      if (this.isEdicao) {
        const nomesAcessorios = this.carro.acessorios || [];
        // Converte os nomes (que vêm do banco) para IDs (que as caixinhas usam)
        this.carro.acessorios = this.acessorios
          .filter(a => nomesAcessorios.includes(a.nome))
          .map(a => a.id_acessorio);

        // Se o id_modelo não veio por algum motivo, busca pelo nome
        if (!this.carro.id_modelo && this.carro.modelo) {
          const mod = this.modelos.find(m => m.nome === this.carro.modelo);
          if (mod) this.carro.id_modelo = mod.id_modelo;
        }
      }
      this.cdr.detectChanges(); // Força a tela a desenhar as caixinhas
    });
  }

  toggleAcessorio(id: number) {
    if (!this.carro.acessorios) this.carro.acessorios = [];
    const idx = this.carro.acessorios.indexOf(id);
    if (idx > -1) this.carro.acessorios.splice(idx, 1);
    else this.carro.acessorios.push(id);
  }

  salvar() {
    const acao = this.isEdicao ? this.api.atualizarCarro(this.carro) : this.api.cadastrarCarro(this.carro);
    acao.subscribe({
      next: (res: any) => {
        this.msg = res.message; 
        this.tipoMsg = 'alert-success';
        setTimeout(() => this.router.navigate(['/dashboard']), 1500);
      },
      error: (err: any) => { 
        this.msg = err.error?.message || 'Erro ao salvar'; 
        this.tipoMsg = 'alert-danger'; 
      }
    });
  }

  cancelar() {
    this.router.navigate(['/dashboard']);
  }
}
