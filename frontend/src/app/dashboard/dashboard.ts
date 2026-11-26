import { Component, HostListener, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { forkJoin } from 'rxjs';
import { ApiService } from '../services/api';
import { mensagemErro } from '../services/erros';
import { ROTULOS_CONDICAO, ROTULOS_CONSERVACAO } from '../domain/vehicle-rules';
import { mascaraKm, parseKm } from '../domain/formato';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule],
  templateUrl: './dashboard.html',
})
export class DashboardComponent implements OnInit {
  carros: any[] = [];
  carrosFiltrados: any[] = [];
  marcas: any[] = [];
  cores: string[] = [];
  stats = { total: 0, disponiveis: 0, reservados: 0, vendidos: 0, por_condicao: { novo: 0, seminovo: 0, usado: 0, velho: 0, indefinida: 0 } };
  loading = true;
  erro = '';
  msg = '';
  tipoMsg = 'alert-success';
  carroSelecionado: any = null;
  confirmacao: { id: number; placa: string } | null = null;
  excluindo = false;
  filtro = {
    texto: '',
    situacao: '',
    condicao: '',
    id_marca: '' as number | '',
    cor: '',
    kmMin: '' as number | string,
    kmMax: '' as number | string,
    anoMin: null as number | null,
    anoMax: null as number | null,
  };
  ordenacao = 'id-desc';
  alterando = 0;
  rotulos = ROTULOS_CONDICAO;
  rotulosConservacao = ROTULOS_CONSERVACAO;

  constructor(private api: ApiService, private router: Router, private cdr: ChangeDetectorRef) {}

  ngOnInit() { this.carregar(); }

  @HostListener('document:keydown.escape')
  fecharPorTecla() {
    this.carroSelecionado = null;
    this.confirmacao = null;
  }

  carregar() {
    this.loading = true;
    this.erro = '';
    forkJoin({
      carros: this.api.getCarros(),
      stats: this.api.getStats(),
      marcas: this.api.getAuxiliares('marcas'),
      cores: this.api.getAuxiliares('cores'),
    }).subscribe({
      next: ({ carros, stats, marcas, cores }) => {
        const lista = Array.isArray(carros) ? carros : [];
        this.carros = lista.map((carro: any) => ({
          ...carro,
          valor: Number(carro.valor) || 0,
          ano: Number(carro.ano_fabricacao ?? carro.ano) || 0,
          ano_modelo: Number(carro.ano_modelo ?? carro.ano) || 0,
          quilometragem: Number(carro.quilometragem) || 0,
          acessorios: Array.isArray(carro.acessorios) ? carro.acessorios : [],
        }));
        this.stats = {
          total: Number(stats.total) || 0,
          disponiveis: Number(stats.disponiveis) || 0,
          reservados: Number(stats.reservados) || 0,
          vendidos: Number(stats.vendidos) || 0,
          por_condicao: stats.por_condicao || this.stats.por_condicao,
        };
        this.marcas = [...(marcas || [])].sort((a, b) => String(a.nome).localeCompare(String(b.nome), 'pt-BR'));
        const nomesCor = [
          ...(cores || []).map((cor: any) => cor.nome),
          ...this.carros.map((carro) => carro.cor),
        ].filter(Boolean);
        this.cores = [...new Set(nomesCor)].sort((a, b) => a.localeCompare(b, 'pt-BR'));
        this.aplicarFiltros();
        this.loading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.erro = mensagemErro(err, 'Falha ao carregar dados.');
        this.loading = false;
        this.cdr.detectChanges();
      },
    });
  }

  aplicarFiltros() {
    const texto = this.filtro.texto.toLowerCase().trim();
    const lista = this.carros.filter((carro) => this.passaFiltro(carro, texto));
    this.carrosFiltrados = this.ordenar(lista);
  }

  ordenar(lista: any[]) {
    const [campo, dir] = this.ordenacao.split('-');
    const fator = dir === 'desc' ? -1 : 1;
    return [...lista].sort((a, b) => {
      const valor = (carro: any) => campo === 'placa' ? carro.placa : campo === 'ano' ? carro.ano : campo === 'km' ? carro.quilometragem : campo === 'valor' ? carro.valor : carro.id_carro;
      const esquerda = valor(a);
      const direita = valor(b);
      if (typeof esquerda === 'string') return String(esquerda).localeCompare(String(direita), 'pt-BR') * fator;
      return (Number(esquerda) - Number(direita)) * fator;
    });
  }

  passaFiltro(carro: any, texto = this.filtro.texto.toLowerCase().trim()): boolean {
    if (texto) {
      const alvo = [carro.placa, carro.chassi, carro.modelo, carro.marca, carro.versao, carro.cor, carro.id_carro]
        .map((parte) => String(parte || '').toLowerCase())
        .join(' ');
      if (!alvo.includes(texto)) return false;
    }
    if (this.filtro.situacao && carro.situacao !== this.filtro.situacao) return false;
    if (this.filtro.condicao === 'indefinida' && carro.condicao) return false;
    if (this.filtro.condicao && this.filtro.condicao !== 'indefinida' && carro.condicao !== this.filtro.condicao) return false;
    if (this.filtro.id_marca !== '' && Number(carro.id_marca) !== Number(this.filtro.id_marca)) return false;
    if (this.filtro.cor && carro.cor !== this.filtro.cor) return false;
    const kmMin = parseKm(this.filtro.kmMin);
    const kmMax = parseKm(this.filtro.kmMax);
    if (kmMin != null && carro.quilometragem < kmMin) return false;
    if (kmMax != null && carro.quilometragem > kmMax) return false;
    if (this.filtro.anoMin != null && this.filtro.anoMin !== ('' as unknown as number) && carro.ano < Number(this.filtro.anoMin)) return false;
    if (this.filtro.anoMax != null && this.filtro.anoMax !== ('' as unknown as number) && carro.ano > Number(this.filtro.anoMax)) return false;
    return true;
  }

  limparFiltros() {
    this.filtro = { texto: '', situacao: '', condicao: '', id_marca: '', cor: '', kmMin: '', kmMax: '', anoMin: null, anoMax: null };
    this.aplicarFiltros();
  }

  verDetalhes(carro: any) { this.carroSelecionado = carro; }
  editar(carro: any) { this.router.navigate(['/cadastrar'], { queryParams: { id: carro.id_carro } }); }

  pedirExclusao(carro: any) {
    this.confirmacao = { id: carro.id_carro, placa: carro.placa };
  }

  confirmarExclusao() {
    if (!this.confirmacao || this.excluindo) return;
    this.excluindo = true;
    const id = this.confirmacao.id;
    this.api.excluirCarro(id).subscribe({
      next: (res) => {
        this.excluindo = false;
        this.msg = res.message;
        this.tipoMsg = 'alert-success';
        if (this.carroSelecionado?.id_carro === id) this.carroSelecionado = null;
        this.confirmacao = null;
        this.carregar();
      },
      error: (err) => {
        this.excluindo = false;
        this.msg = mensagemErro(err, 'Falha ao excluir o veículo.');
        this.tipoMsg = 'alert-danger';
        this.confirmacao = null;
        this.cdr.detectChanges();
      },
    });
  }

  rotuloCondicao(condicao: string | null) {
    if (!condicao) return 'A classificar';
    return this.rotulos[condicao] || condicao;
  }

  formatarKmFiltro(campo: 'kmMin' | 'kmMax', evento: Event) {
    const input = evento.target as HTMLInputElement;
    const formatado = mascaraKm(input.value);
    this.filtro[campo] = formatado;
    if (input.value !== formatado) input.value = formatado;
    this.aplicarFiltros();
  }

  alterarSituacao(carro: any, situacao: string) {
    if (!situacao || carro.situacao === situacao || this.alterando === carro.id_carro) return;
    const anterior = carro.situacao;
    carro.situacao = situacao;
    this.alterando = carro.id_carro;
    this.api.alterarSituacao(carro.id_carro, situacao).subscribe({
      next: () => {
        this.alterando = 0;
        this.stats = {
          ...this.stats,
          disponiveis: this.carros.filter((item) => item.situacao === 'D').length,
          reservados: this.carros.filter((item) => item.situacao === 'R').length,
          vendidos: this.carros.filter((item) => item.situacao === 'V').length,
        };
        this.msg = 'Situação atualizada.';
        this.tipoMsg = 'alert-success';
        this.cdr.detectChanges();
      },
      error: (err) => {
        carro.situacao = anterior;
        this.alterando = 0;
        this.msg = mensagemErro(err, 'Não foi possível alterar a situação.');
        this.tipoMsg = 'alert-danger';
        this.cdr.detectChanges();
      },
    });
  }

  classePlaca(situacao: string) {
    if (situacao === 'V') return 'placa-chip placa-vendida';
    if (situacao === 'R') return 'placa-chip placa-reservada';
    return 'placa-chip placa-disponivel';
  }

  rotuloSituacao(situacao: string) {
    if (situacao === 'V') return 'Vendido';
    if (situacao === 'R') return 'Reservado';
    return 'Disponível';
  }

  classeCondicao(condicao: string | null) {
    if (condicao === 'novo') return 'text-bg-success';
    if (condicao === 'seminovo') return 'text-bg-primary';
    if (condicao === 'usado') return 'text-bg-warning';
    if (condicao === 'velho') return 'text-bg-secondary';
    return 'text-bg-light';
  }

  trackCarro(_index: number, carro: any) {
    return carro.id_carro;
  }
}
