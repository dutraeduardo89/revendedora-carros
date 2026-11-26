import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { forkJoin } from 'rxjs';
import { ApiService } from '../services/api';
import { camposErro, mensagemErro } from '../services/erros';
import {
  CATEGORIAS, COMBUSTIVEIS, CONDICOES, CONSERVACOES, ROTULOS_CONDICAO, ROTULOS_CONSERVACAO,
  TRANSMISSOES, VeiculoForm, anosDisponiveis, anosModelo, limitePassageiros, motivoCondicao,
  sugerirCondicao, validarVeiculo, veiculoVazio, type RegrasClassificacao,
} from '../domain/vehicle-rules';
import { formatarValorPronto, mascaraChassi, mascaraKm, mascaraPlaca, mascaraValor, parseKm, parseValor } from '../domain/formato';

@Component({
  selector: 'app-car-form',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './car-form.html',
})
export class CarFormComponent implements OnInit {
  carro: VeiculoForm = veiculoVazio();
  kmTexto = '';
  valorTexto = '';
  idCarro: number | null = null;
  marcas: any[] = [];
  modelos: any[] = [];
  acessorios: any[] = [];
  cores: any[] = [];
  idMarca: number | null = null;
  regras: RegrasClassificacao | null = null;
  anos: number[] = [];
  categorias = CATEGORIAS;
  combustiveis = COMBUSTIVEIS;
  transmissoes = TRANSMISSOES;
  condicoes = CONDICOES;
  conservacoes = CONSERVACOES;
  rotulosCondicao = ROTULOS_CONDICAO;
  rotulosConservacao = ROTULOS_CONSERVACAO;
  erros: Record<string, string> = {};
  msg = '';
  tipoMsg = '';
  carregando = true;
  salvando = false;
  sugestao = '';
  motivo = '';
  placaOriginal = '';
  chassiOriginal = '';
  anoOriginal: number | null = null;
  private classificacaoManual = false;

  constructor(
    private api: ApiService,
    private router: Router,
    private route: ActivatedRoute,
    private cdr: ChangeDetectorRef,
  ) {}

  ngOnInit() {
    const idParam = this.route.snapshot.queryParamMap.get('id');
    this.idCarro = idParam ? Number(idParam) : null;
    forkJoin({
      marcas: this.api.getAuxiliares('marcas'),
      modelos: this.api.getAuxiliares('modelos'),
      acessorios: this.api.getAuxiliares('acessorios'),
      cores: this.api.getAuxiliares('cores'),
      regras: this.api.getRegras(),
    }).subscribe({
      next: (res) => {
        this.marcas = res.marcas || [];
        this.modelos = res.modelos || [];
        this.acessorios = res.acessorios || [];
        this.cores = res.cores || [];
        this.regras = res.regras;
        this.anos = anosDisponiveis();
        if (this.idCarro) this.carregarVeiculo(this.idCarro);
        else {
          this.carregando = false;
          this.cdr.detectChanges();
        }
      },
      error: (err) => {
        this.carregando = false;
        this.msg = mensagemErro(err, 'Falha ao carregar dados.');
        this.tipoMsg = 'alert-danger';
        this.cdr.detectChanges();
      },
    });
  }

  get modelosFiltrados() {
    if (!this.idMarca) return [];
    return this.modelos.filter((m) => Number(m.id_marca) === Number(this.idMarca));
  }

  get anosDoModelo() {
    return anosModelo(this.carro.ano_fabricacao);
  }

  get tetoPassageiros() {
    return limitePassageiros(this.carro.categoria);
  }

  get placeholderMotor() {
    return this.carro.combustivel === 'Elétrico' ? 'Ex.: 150 kW' : 'Ex.: 1.0 Turbo';
  }

  erro(campo: string) {
    return this.erros[campo] || '';
  }

  carregarVeiculo(id: number) {
    this.api.getCarro(id).subscribe({
      next: (carro) => {
        this.aplicarCarro(carro);
        this.carregando = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.carregando = false;
        this.msg = mensagemErro(err, 'Falha ao carregar dados.');
        this.tipoMsg = 'alert-danger';
        this.cdr.detectChanges();
      },
    });
  }

  aplicarCarro(carro: any) {
    this.idCarro = Number(carro.id_carro);
    this.placaOriginal = carro.placa || '';
    this.chassiOriginal = carro.chassi || '';
    this.anoOriginal = Number(carro.ano_fabricacao ?? carro.ano);
    this.carro = {
      ...veiculoVazio(),
      placa: carro.placa || '',
      chassi: carro.chassi || '',
      id_cor: carro.id_cor != null ? Number(carro.id_cor) : null,
      ano_fabricacao: this.anoOriginal,
      ano_modelo: carro.ano_modelo != null ? Number(carro.ano_modelo) : this.anoOriginal,
      valor: carro.valor != null ? Number(carro.valor) : null,
      quilometragem: carro.quilometragem != null ? Number(carro.quilometragem) : null,
      situacao: carro.situacao || 'D',
      id_modelo: carro.id_modelo != null ? Number(carro.id_modelo) : null,
      acessorios: Array.isArray(carro.acessorios_ids) ? carro.acessorios_ids.map((id: any) => Number(id)) : [],
      versao: carro.versao || '',
      categoria: carro.categoria || '',
      combustivel: carro.combustivel || '',
      transmissao: carro.transmissao || '',
      motorizacao: carro.motorizacao || '',
      portas: carro.portas != null ? Number(carro.portas) : null,
      passageiros: carro.passageiros != null ? Number(carro.passageiros) : null,
      condicao: carro.condicao || '',
      conservacao: carro.conservacao || '',
      historico: carro.historico || '',
    };
    this.idMarca = carro.id_marca != null ? Number(carro.id_marca) : null;
    if (!this.anos.includes(this.anoOriginal!)) {
      this.anos = anosDisponiveis(new Date().getFullYear(), [this.anoOriginal!]);
    }
    this.kmTexto = this.carro.quilometragem != null ? mascaraKm(String(this.carro.quilometragem)) : '';
    this.valorTexto = this.carro.valor != null ? formatarValorPronto(Number(this.carro.valor)) : '';
    this.classificacaoManual = !!this.carro.condicao;
    this.atualizarSugestao(false);
  }

  aoDigitarPlaca(evento: Event) {
    this.escreverMascara(evento, mascaraPlaca, (valor) => { this.carro.placa = valor; });
  }

  aoDigitarChassi(evento: Event) {
    this.escreverMascara(evento, mascaraChassi, (valor) => { this.carro.chassi = valor; });
  }

  aoDigitarKm(evento: Event) {
    this.escreverMascara(evento, mascaraKm, (valor) => {
      this.kmTexto = valor;
      this.carro.quilometragem = parseKm(valor);
    });
    this.atualizarSugestao(!this.classificacaoManual);
  }

  aoDigitarValor(evento: Event) {
    this.escreverMascara(evento, mascaraValor, (valor) => {
      this.valorTexto = valor;
      this.carro.valor = parseValor(valor);
    });
  }

  aoSairValor(evento: Event) {
    const input = evento.target as HTMLInputElement;
    const valor = parseValor(input.value);
    this.carro.valor = valor;
    if (valor == null) return;
    this.valorTexto = formatarValorPronto(valor);
    input.value = this.valorTexto;
  }

  private escreverMascara(evento: Event, formatar: (valor: string) => string, aplicar: (valor: string) => void) {
    const input = evento.target as HTMLInputElement;
    const formatado = formatar(input.value);
    aplicar(formatado);
    if (input.value !== formatado) {
      input.value = formatado;
      input.setSelectionRange(formatado.length, formatado.length);
    }
  }

  aoMudarMarca() {
    if (!this.modelosFiltrados.some((m) => Number(m.id_modelo) === Number(this.carro.id_modelo))) {
      this.carro.id_modelo = null;
    }
  }

  aoMudarAno() {
    const opcoes = this.anosDoModelo;
    if (this.carro.ano_modelo != null && !opcoes.includes(this.carro.ano_modelo)) {
      this.carro.ano_modelo = opcoes[0] ?? null;
    }
    this.atualizarSugestao(true);
  }

  aoMudarCategoria() {
    if (this.carro.passageiros != null && this.carro.passageiros > this.tetoPassageiros) {
      this.carro.passageiros = this.tetoPassageiros;
    }
  }

  atualizarSugestao(preencher: boolean) {
    if (!this.regras || this.carro.ano_fabricacao == null || this.carro.quilometragem == null || !this.carro.conservacao) {
      this.sugestao = '';
      this.motivo = '';
      return;
    }
    const km = Number(this.carro.quilometragem);
    if (!Number.isInteger(km) || km < 0) {
      this.sugestao = '';
      this.motivo = '';
      return;
    }
    this.sugestao = sugerirCondicao(km, Number(this.carro.ano_fabricacao), this.carro.conservacao, this.regras);
    this.motivo = motivoCondicao(this.sugestao, km, Number(this.carro.ano_fabricacao), this.carro.conservacao, this.regras);
    if (preencher && !this.classificacaoManual) {
      this.carro.condicao = this.sugestao;
    }
  }

  escolherCondicao(valor: string) {
    this.classificacaoManual = true;
    this.carro.condicao = valor;
  }

  toggleAcessorio(id: number) {
    const indice = this.carro.acessorios.indexOf(id);
    if (indice >= 0) this.carro.acessorios.splice(indice, 1);
    else this.carro.acessorios.push(id);
  }

  salvar() {
    if (this.salvando || !this.regras) return;
    this.erros = validarVeiculo(
      this.carro,
      this.regras,
      new Date().getFullYear(),
      this.placaOriginal,
      this.chassiOriginal,
      this.anoOriginal,
    );
    this.msg = '';
    if (Object.keys(this.erros).length) {
      this.msg = 'Verifique os campos destacados.';
      this.tipoMsg = 'alert-danger';
      return;
    }
    this.salvando = true;
    const payload = {
      ...this.carro,
      id_carro: this.idCarro,
      placa: this.carro.placa,
      chassi: this.carro.chassi,
      versao: this.carro.versao.trim(),
      motorizacao: this.carro.motorizacao.trim(),
      historico: this.carro.historico.trim(),
    };
    const acao = this.idCarro ? this.api.atualizarCarro(payload) : this.api.cadastrarCarro(payload);
    acao.subscribe({
      next: (res) => {
        this.salvando = false;
        this.msg = res.message;
        this.tipoMsg = 'alert-success';
        this.cdr.detectChanges();
        setTimeout(() => this.router.navigate(['/dashboard']), 900);
      },
      error: (err) => {
        this.salvando = false;
        this.erros = camposErro(err);
        this.msg = mensagemErro(err, 'Falha ao salvar informações.');
        this.tipoMsg = 'alert-danger';
        this.cdr.detectChanges();
      },
    });
  }

  cancelar() {
    this.router.navigate(['/dashboard']);
  }
}
