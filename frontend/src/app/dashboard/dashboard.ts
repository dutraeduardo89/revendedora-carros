import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { ApiService } from '../services/api';
import { CommonModule } from '@angular/common';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule],
  templateUrl: './dashboard.html'
})
export class DashboardComponent implements OnInit {
  carros: any[] = [];
  carrosFiltrados: any[] = [];
  loading = true;
  erro = '';
  filtroTexto = '';
  carroSelecionado: any = null;
  msg = '';

  constructor(private api: ApiService, private router: Router, private cdr: ChangeDetectorRef) {}

  ngOnInit() { this.carregarCarros(); }

  carregarCarros() {
    this.loading = true;
    this.erro = '';
    this.api.getCarros().subscribe({
      next: (res) => {
        console.log('DADOS RECEBIDOS DO BANCO:', res);
        const lista = Array.isArray(res) ? res : [];
        this.carros = lista.map((c: any) => ({
          ...c,
          valor: parseFloat(c.valor) || 0,
          ano: parseInt(c.ano) || 0,
          quilometragem: parseInt(c.quilometragem) || 0,
          acessorios: Array.isArray(c.acessorios) ? c.acessorios : []
        }));
        this.carrosFiltrados = this.carros;
        this.loading = false;
        this.cdr.detectChanges(); // FORÇA A TELA A ATUALIZAR AQUI
      },
      error: (err) => {
        console.error('Falha ao buscar carros:', err);
        this.erro = 'Falha ao carregar veículos.';
        this.loading = false;
        this.cdr.detectChanges();
      }
    });
  }

  filtrar() {
    const t = this.filtroTexto.toLowerCase().trim();
    if (!t) { this.carrosFiltrados = this.carros; return; }
    this.carrosFiltrados = this.carros.filter(c =>
      String(c.placa || '').toLowerCase().includes(t) ||
      String(c.id_carro || '').includes(t) ||
      String(c.modelo || '').toLowerCase().includes(t) ||
      String(c.marca || '').toLowerCase().includes(t)
    );
  }

  verDetalhes(carro: any) { this.carroSelecionado = carro; }
  editar(carro: any) { this.router.navigate(['/cadastrar'], { state: { carro } }); }
  excluir(id: number) {
    if (confirm('Apagar este veículo?')) {
      this.api.excluirCarro(id).subscribe({
        next: (res: any) => { this.msg = res.message; this.carregarCarros(); },
        error: (err: any) => alert(err.error?.message || 'Erro ao excluir.')
      });
    }
  }
}
