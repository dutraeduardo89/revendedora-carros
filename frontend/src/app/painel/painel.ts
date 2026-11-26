import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { forkJoin } from 'rxjs';
import { ApiService } from '../services/api';
import { mensagemErro } from '../services/erros';
import { pendenciasVeiculo } from '../domain/vehicle-rules';
import { AvisoComponent } from '../aviso/aviso';

interface Fatia {
  nome: string;
  total: number;
  pct: number;
}

@Component({
  selector: 'app-painel',
  standalone: true,
  imports: [CommonModule, RouterLink, AvisoComponent],
  templateUrl: './painel.html',
})
export class PainelComponent implements OnInit {
  loading = true;
  erro = '';
  total = 0;
  valorMedio = 0;
  valorTotal = 0;
  disponiveis = 0;
  reservados = 0;
  vendidos = 0;
  marcas: Fatia[] = [];
  modelos: Fatia[] = [];
  cores: Fatia[] = [];
  acessorios: Fatia[] = [];
  pendentes: { id_carro: number; placa: string; modelo: string; faltas: string }[] = [];

  get grupos() {
    return [
      { titulo: 'Marcas', itens: this.marcas },
      { titulo: 'Modelos', itens: this.modelos },
      { titulo: 'Cores', itens: this.cores },
      { titulo: 'Acessórios', itens: this.acessorios },
    ];
  }

  constructor(private api: ApiService, private cdr: ChangeDetectorRef) {}

  ngOnInit() {
    this.carregar();
  }

  carregar() {
    this.loading = true;
    this.erro = '';
    forkJoin({
      carros: this.api.getCarros(),
      stats: this.api.getStats(),
    }).subscribe({
      next: ({ carros, stats }) => {
        const lista = Array.isArray(carros) ? carros : [];
        const valores = lista.map((carro: any) => Number(carro.valor) || 0);
        this.total = lista.length;
        this.valorTotal = valores.reduce((soma: number, valor: number) => soma + valor, 0);
        this.valorMedio = this.total ? this.valorTotal / this.total : 0;
        this.disponiveis = Number(stats.disponiveis) || 0;
        this.reservados = Number(stats.reservados) || 0;
        this.vendidos = Number(stats.vendidos) || 0;
        this.marcas = this.contar(lista, (carro) => carro.marca);
        this.modelos = this.contar(lista, (carro) => carro.modelo);
        this.cores = this.contar(lista, (carro) => carro.cor);
        this.acessorios = this.contar(lista.flatMap((carro: any) => carro.acessorios || []), (nome) => nome);
        this.pendentes = lista
          .map((carro: any) => ({
            id_carro: Number(carro.id_carro),
            placa: carro.placa,
            modelo: carro.modelo,
            faltas: pendenciasVeiculo(carro).join(', '),
          }))
          .filter((carro: { faltas: string }) => carro.faltas);
        this.loading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.loading = false;
        this.erro = mensagemErro(err, 'Falha ao carregar dados.');
        this.cdr.detectChanges();
      },
    });
  }

  private contar(lista: any[], nomeDe: (item: any) => string): Fatia[] {
    const mapa = new Map<string, number>();
    for (const item of lista) {
      const nome = String(nomeDe(item) || '').trim();
      if (!nome) continue;
      mapa.set(nome, (mapa.get(nome) || 0) + 1);
    }
    const fatias = [...mapa.entries()]
      .map(([nome, total]) => ({ nome, total }))
      .sort((a, b) => b.total - a.total || a.nome.localeCompare(b.nome, 'pt-BR'));
    const maior = fatias[0]?.total || 1;
    return fatias.map((fatia) => ({ ...fatia, pct: Math.round((fatia.total / maior) * 100) }));
  }
}
