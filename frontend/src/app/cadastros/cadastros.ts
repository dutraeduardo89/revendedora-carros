import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink, RouterLinkActive } from '@angular/router';
import { forkJoin } from 'rxjs';
import { ApiService } from '../services/api';
import { mensagemErro } from '../services/erros';

interface Confirmacao {
  tipo: string;
  id: number;
  nome: string;
  aviso: string;
}

@Component({
  selector: 'app-cadastros',
  standalone: true,
  imports: [FormsModule, CommonModule, RouterLink, RouterLinkActive],
  templateUrl: './cadastros.html',
})
export class CadastrosComponent implements OnInit {
  marcas: any[] = [];
  modelos: any[] = [];
  acessorios: any[] = [];
  cores: any[] = [];
  secao = 'marcas';
  filtro = '';
  ordem = 'nome-asc';
  readonly secoes = [
    { id: 'marcas', tipo: 'marca', titulo: 'Marcas', texto: 'Cada marca fica só na sua conta. Excluir a marca também exclui os modelos e os veículos ligados a ela.' },
    { id: 'modelos', tipo: 'modelo', titulo: 'Modelos', texto: 'Todo modelo pertence a uma marca da sua conta.' },
    { id: 'acessorios', tipo: 'acessorio', titulo: 'Acessórios', texto: 'O mesmo acessório pode ser ligado a vários veículos. Excluir o acessório não apaga os veículos.' },
    { id: 'cores', tipo: 'cor', titulo: 'Cores', texto: 'A cor em uso por um veículo não pode ser excluída. Renomear atualiza os veículos que a usam.' },
  ];
  novaMarca = '';
  novoModelo = { nome: '', id_marca: null as number | null };
  novoAcessorio = '';
  novaCor = '';
  edicao: { tipo: string; id: number } | null = null;
  msg = '';
  tipoMsg = '';
  carregando = true;
  salvando = '';
  erro = '';
  confirmacao: Confirmacao | null = null;
  excluindo = false;

  constructor(private api: ApiService, private cdr: ChangeDetectorRef, private route: ActivatedRoute, private router: Router) {}

  ngOnInit() {
    this.route.paramMap.subscribe((params) => {
      const secao = params.get('secao');
      if (!secao) return;
      if (!this.secoes.some((item) => item.id === secao)) {
        this.router.navigate(['/cadastros/marcas']);
        return;
      }
      this.secao = secao;
      this.filtro = '';
      this.cdr.detectChanges();
    });
    this.carregarTudo();
  }

  get atual() {
    return this.secoes.find((item) => item.id === this.secao) || this.secoes[0];
  }

  get listaAtual() {
    const origem = this.secao === 'marcas' ? this.marcas
      : this.secao === 'modelos' ? this.modelos
      : this.secao === 'acessorios' ? this.acessorios
      : this.cores;
    const campos = this.secao === 'modelos' ? ['nome', 'marca_nome'] : ['nome'];
    return this.ordenar(this.filtrar(origem, this.filtro, campos));
  }

  carregarTudo() {
    this.carregando = true;
    this.erro = '';
    forkJoin({
      marcas: this.api.getAuxiliares('marcas'),
      modelos: this.api.getAuxiliares('modelos'),
      acessorios: this.api.getAuxiliares('acessorios'),
      cores: this.api.getAuxiliares('cores'),
    }).subscribe({
      next: (res) => {
        this.marcas = res.marcas || [];
        this.modelos = res.modelos || [];
        this.acessorios = res.acessorios || [];
        this.cores = res.cores || [];
        this.carregando = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.carregando = false;
        this.erro = mensagemErro(err, 'Falha ao carregar dados.');
        this.cdr.detectChanges();
      },
    });
  }

  filtrar(lista: any[], termo: string, campos: string[]) {
    const texto = termo.toLowerCase().trim();
    if (!texto) return lista;
    return lista.filter((item) => campos.some((campo) => String(item[campo] ?? '').toLowerCase().includes(texto)));
  }

  ordenar(lista: any[]) {
    const [campo, dir] = this.ordem.split('-');
    const fator = dir === 'desc' ? -1 : 1;
    const idCampo = this.secao === 'marcas' ? 'id_marca'
      : this.secao === 'modelos' ? 'id_modelo'
      : this.secao === 'cores' ? 'id_cor'
      : 'id_acessorio';
    return [...lista].sort((a, b) => {
      if (campo === 'id') return (Number(a[idCampo]) - Number(b[idCampo])) * fator;
      return String(a.nome || '').localeCompare(String(b.nome || ''), 'pt-BR') * fator;
    });
  }

  idItem(item: any) {
    return this.idDe(this.atual.tipo, item);
  }

  editar(tipo: string, item: any) {
    this.edicao = { tipo, id: this.idDe(tipo, item) };
    if (tipo === 'marca') this.novaMarca = item.nome;
    if (tipo === 'modelo') this.novoModelo = { nome: item.nome, id_marca: Number(item.id_marca) };
    if (tipo === 'acessorio') this.novoAcessorio = item.nome;
    if (tipo === 'cor') this.novaCor = item.nome;
  }

  cancelarEdicao(tipo: string) {
    if (this.edicao?.tipo === tipo) this.edicao = null;
    this.limpar(tipo);
  }

  salvar(tipo: string) {
    if (this.salvando) return;
    const dados = this.dadosDe(tipo);
    if (!dados) {
      this.msg = 'Preencha os campos obrigatórios.';
      this.tipoMsg = 'alert-danger';
      return;
    }
    this.salvando = tipo;
    const edicao = this.edicao?.tipo === tipo ? this.edicao : null;
    const req = edicao
      ? this.api.editarAuxiliar(tipo, edicao.id, dados)
      : this.api.cadastrarAuxiliar(tipo, dados);
    req.subscribe({
      next: (res) => {
        this.salvando = '';
        this.msg = res.message;
        this.tipoMsg = 'alert-success';
        this.edicao = null;
        this.limpar(tipo);
        this.carregarTudo();
      },
      error: (err) => {
        this.salvando = '';
        this.msg = mensagemErro(err, 'Falha ao salvar informações.');
        this.tipoMsg = 'alert-danger';
        this.cdr.detectChanges();
      },
    });
  }

  pedirExclusao(tipo: string, item: any) {
    const avisos: Record<string, string> = {
      marca: 'Excluir a marca também exclui os modelos e os veículos vinculados a ela.',
      modelo: 'Excluir o modelo também exclui os veículos vinculados a ele.',
      acessorio: 'O acessório será removido dos veículos que o utilizam. Os veículos permanecem.',
      cor: 'A cor só pode ser excluída se nenhum veículo a estiver usando.',
    };
    this.confirmacao = { tipo, id: this.idDe(tipo, item), nome: item.nome, aviso: avisos[tipo] };
  }

  confirmarExclusao() {
    if (!this.confirmacao || this.excluindo) return;
    this.excluindo = true;
    this.api.excluirAuxiliar(this.confirmacao.tipo, this.confirmacao.id).subscribe({
      next: (res) => {
        this.excluindo = false;
        this.msg = res.message;
        this.tipoMsg = 'alert-success';
        this.confirmacao = null;
        this.carregarTudo();
      },
      error: (err) => {
        this.excluindo = false;
        this.msg = mensagemErro(err, 'Não foi possível excluir.');
        this.tipoMsg = 'alert-danger';
        this.confirmacao = null;
        this.cdr.detectChanges();
      },
    });
  }

  private dadosDe(tipo: string): { nome: string; id_marca?: number } | null {
    if (tipo === 'marca') return this.novaMarca.trim() ? { nome: this.novaMarca.trim() } : null;
    if (tipo === 'acessorio') return this.novoAcessorio.trim() ? { nome: this.novoAcessorio.trim() } : null;
    if (tipo === 'cor') return this.novaCor.trim() ? { nome: this.novaCor.trim() } : null;
    if (!this.novoModelo.nome.trim() || !this.novoModelo.id_marca) return null;
    return { nome: this.novoModelo.nome.trim(), id_marca: Number(this.novoModelo.id_marca) };
  }

  private limpar(tipo: string) {
    if (tipo === 'marca') this.novaMarca = '';
    if (tipo === 'modelo') this.novoModelo = { nome: '', id_marca: null };
    if (tipo === 'acessorio') this.novoAcessorio = '';
    if (tipo === 'cor') this.novaCor = '';
  }

  private idDe(tipo: string, item: any): number {
    if (tipo === 'marca') return Number(item.id_marca);
    if (tipo === 'modelo') return Number(item.id_modelo);
    if (tipo === 'cor') return Number(item.id_cor);
    return Number(item.id_acessorio);
  }
}
