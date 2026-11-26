export interface RegrasClassificacao {
  novo_km_max: number;
  seminovo_km_max: number;
  velho_idade_min: number;
}

export const REGRAS_PADRAO: RegrasClassificacao = {
  novo_km_max: 0,
  seminovo_km_max: 30000,
  velho_idade_min: 15,
};

export const CATEGORIAS = ['Hatch', 'Sedan', 'SUV', 'Picape', 'Coupé', 'Perua', 'Minivan', 'Conversível', 'Utilitário'];
export const COMBUSTIVEIS = ['Gasolina', 'Etanol', 'Flex', 'Diesel', 'Elétrico', 'Híbrido', 'GNV'];
export const TRANSMISSOES = ['Manual', 'Automático', 'Automatizado', 'CVT'];
export const CONDICOES = ['novo', 'seminovo', 'usado', 'velho'];
export const CONSERVACOES = ['otima', 'boa', 'regular', 'ruim'];

export const ROTULOS_CONDICAO: Record<string, string> = {
  novo: 'Novo',
  seminovo: 'Seminovo',
  usado: 'Usado',
  velho: 'Velho',
};

export const ROTULOS_CONSERVACAO: Record<string, string> = {
  otima: 'Ótima',
  boa: 'Boa',
  regular: 'Regular',
  ruim: 'Ruim',
};

export function anoCatalogoMin(anoAtual = new Date().getFullYear()): number {
  return anoAtual - 40;
}

export function anoCatalogoMax(anoAtual = new Date().getFullYear()): number {
  return anoAtual + 1;
}

export function anosDisponiveis(anoAtual = new Date().getFullYear(), extras: number[] = []): number[] {
  const anos: number[] = [];
  for (let ano = anoCatalogoMax(anoAtual); ano >= anoCatalogoMin(anoAtual); ano--) {
    anos.push(ano);
  }
  for (const extra of extras) {
    if (Number.isInteger(extra) && !anos.includes(extra)) {
      anos.push(extra);
    }
  }
  return anos.sort((a, b) => b - a);
}

export function anosModelo(fabricacao: number | null, anoAtual = new Date().getFullYear()): number[] {
  if (fabricacao == null || !Number.isInteger(fabricacao)) {
    return [];
  }
  const opcoes = [fabricacao];
  const seguintePermitido = fabricacao + 1 <= anoCatalogoMax(anoAtual) || fabricacao < anoCatalogoMin(anoAtual);
  if (seguintePermitido) {
    opcoes.push(fabricacao + 1);
  }
  return opcoes;
}

export function normalizarDocumento(valor: string): string {
  return (valor || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
}

export function placaValida(placa: string): boolean {
  return /^[A-Z]{3}[0-9]{4}$/.test(placa) || /^[A-Z]{3}[0-9][A-Z][0-9]{2}$/.test(placa);
}

export function limitePassageiros(categoria: string): number {
  if (categoria === 'Utilitário') return 20;
  if (categoria === 'Minivan') return 15;
  return 9;
}

export function conservacaoBaixa(conservacao: string): boolean {
  return conservacao === 'regular' || conservacao === 'ruim';
}

export function sugerirCondicao(
  km: number,
  anoFabricacao: number,
  conservacao: string,
  regras: RegrasClassificacao,
  anoAtual = new Date().getFullYear(),
): string {
  const idade = anoAtual - anoFabricacao;
  const ruim = conservacaoBaixa(conservacao);
  const velhoPorIdade = idade >= regras.velho_idade_min;
  if (velhoPorIdade && ruim) return 'velho';
  if (!ruim && !velhoPorIdade && km <= regras.novo_km_max) return 'novo';
  if (!ruim && km <= regras.seminovo_km_max) return 'seminovo';
  return 'usado';
}

export function motivoCondicao(
  condicao: string,
  km: number,
  anoFabricacao: number,
  conservacao: string,
  regras: RegrasClassificacao,
  anoAtual = new Date().getFullYear(),
): string {
  const idade = anoAtual - anoFabricacao;
  const kmTexto = `${km.toLocaleString('pt-BR')} km`;
  if (condicao === 'velho') {
    return `Idade de ${idade} anos e conservação baixa. A classificação velho não depende apenas da quilometragem.`;
  }
  if (condicao === 'novo') {
    return `${kmTexto} dentro da faixa de novo, com conservação boa e idade abaixo de ${regras.velho_idade_min} anos.`;
  }
  if (condicao === 'seminovo') {
    return `${kmTexto} dentro da faixa de seminovo e conservação boa.`;
  }
  if (conservacaoBaixa(conservacao)) {
    return 'A conservação não permite classificar como novo ou seminovo, e a idade ainda não caracteriza velho.';
  }
  return `${kmTexto} acima da faixa de seminovo (${regras.seminovo_km_max.toLocaleString('pt-BR')} km).`;
}

export interface VeiculoForm {
  placa: string;
  chassi: string;
  id_cor: number | null;
  ano_fabricacao: number | null;
  ano_modelo: number | null;
  valor: number | null;
  quilometragem: number | null;
  situacao: string;
  id_modelo: number | null;
  acessorios: number[];
  versao: string;
  categoria: string;
  combustivel: string;
  transmissao: string;
  motorizacao: string;
  portas: number | null;
  passageiros: number | null;
  condicao: string;
  conservacao: string;
  historico: string;
}

export function veiculoVazio(): VeiculoForm {
  return {
    placa: '',
    chassi: '',
    id_cor: null,
    ano_fabricacao: null,
    ano_modelo: null,
    valor: null,
    quilometragem: null,
    situacao: 'D',
    id_modelo: null,
    acessorios: [],
    versao: '',
    categoria: '',
    combustivel: '',
    transmissao: '',
    motorizacao: '',
    portas: null,
    passageiros: null,
    condicao: '',
    conservacao: '',
    historico: '',
  };
}

function inteiroInformado(valor: number | null): boolean {
  if (valor === null || valor === undefined) return false;
  const texto = String(valor).trim();
  if (texto === '') return false;
  return Number.isInteger(Number(valor));
}

export function validarVeiculo(
  form: VeiculoForm,
  regras: RegrasClassificacao,
  anoAtual = new Date().getFullYear(),
  placaAtual = '',
  chassiAtual = '',
  anoOriginal: number | null = null,
): Record<string, string> {
  const erros: Record<string, string> = {};
  const placa = normalizarDocumento(form.placa);
  const chassi = normalizarDocumento(form.chassi);
  const placaHerdada = placaAtual !== '' && placa === normalizarDocumento(placaAtual);
  const chassiHerdado = chassiAtual !== '' && chassi === normalizarDocumento(chassiAtual);

  if (!placa) erros['placa'] = 'Informe a placa.';
  else if (!placaValida(placa) && !placaHerdada) erros['placa'] = 'Use o padrão antigo (ABC1234) ou o Mercosul (ABC1D23).';

  if (!chassi) erros['chassi'] = 'Informe o chassi.';
  else if (!chassiHerdado && (chassi.length < 5 || chassi.length > 30)) {
    erros['chassi'] = 'O chassi deve ter entre 5 e 30 caracteres, sem símbolos.';
  }

  if (!form.id_cor) erros['cor'] = 'Selecione uma cor.';
  if (!form.id_modelo) erros['id_modelo'] = 'Selecione o modelo.';
  if (!CATEGORIAS.includes(form.categoria)) erros['categoria'] = 'Selecione a categoria do veículo.';
  if (!COMBUSTIVEIS.includes(form.combustivel)) erros['combustivel'] = 'Selecione o tipo de combustível.';
  if (!TRANSMISSOES.includes(form.transmissao)) erros['transmissao'] = 'Selecione o tipo de transmissão.';
  if (!form.motorizacao.trim()) erros['motorizacao'] = 'Informe a motorização.';
  else if (form.motorizacao.trim().length > 40) erros['motorizacao'] = 'A motorização deve ter no máximo 40 caracteres.';
  if (form.versao.trim().length > 80) erros['versao'] = 'A versão deve ter no máximo 80 caracteres.';

  if (!inteiroInformado(form.portas) || Number(form.portas) < 2 || Number(form.portas) > 6) {
    erros['portas'] = 'Informe o número de portas entre 2 e 6.';
  }
  const teto = limitePassageiros(form.categoria);
  if (!inteiroInformado(form.passageiros) || Number(form.passageiros) < 1 || Number(form.passageiros) > teto) {
    erros['passageiros'] = `Informe a quantidade de passageiros entre 1 e ${teto}.`;
  }

  const min = anoCatalogoMin(anoAtual);
  const max = anoCatalogoMax(anoAtual);
  if (!inteiroInformado(form.ano_fabricacao)) erros['ano_fabricacao'] = 'Informe o ano de fabricação.';
  else if ((Number(form.ano_fabricacao) < min || Number(form.ano_fabricacao) > max) && Number(form.ano_fabricacao) !== anoOriginal) {
    erros['ano_fabricacao'] = `O ano de fabricação deve estar entre ${min} e ${max}.`;
  }
  if (!inteiroInformado(form.ano_modelo)) erros['ano_modelo'] = 'Informe o ano-modelo.';
  else if (inteiroInformado(form.ano_fabricacao)) {
    const permitidos = anosModelo(Number(form.ano_fabricacao), anoAtual);
    if (!permitidos.includes(Number(form.ano_modelo))) {
      erros['ano_modelo'] = 'O ano-modelo deve ser igual ao de fabricação ou o ano seguinte.';
    }
  }

  if (!inteiroInformado(form.quilometragem)) erros['quilometragem'] = 'Informe a quilometragem em quilômetros, sem casas decimais.';
  else if (Number(form.quilometragem) < 0) erros['quilometragem'] = 'A quilometragem não pode ser negativa. Use 0 para zero-quilômetro.';
  else if (Number(form.quilometragem) > 2000000) erros['quilometragem'] = 'A quilometragem informada passa do limite aceito (2.000.000 km).';

  if (form.valor == null || Number.isNaN(Number(form.valor))) erros['valor'] = 'Informe o valor do veículo.';
  else if (Number(form.valor) <= 0) erros['valor'] = 'O valor deve ser maior que zero.';
  else if (Number(form.valor) > 99999999.99) erros['valor'] = 'O valor passa do limite aceito.';

  if (form.situacao !== 'D' && form.situacao !== 'V' && form.situacao !== 'R') erros['situacao'] = 'Selecione disponível, reservado ou vendido.';
  if (!CONSERVACOES.includes(form.conservacao)) erros['conservacao'] = 'Selecione a conservação.';
  if (form.historico.trim().length > 500) erros['historico'] = 'O histórico deve ter no máximo 500 caracteres.';

  if (!CONDICOES.includes(form.condicao)) {
    erros['condicao'] = 'Selecione a classificação do veículo.';
  } else if (!erros['quilometragem'] && !erros['ano_fabricacao'] && !erros['conservacao'] && form.ano_fabricacao != null) {
    const esperada = sugerirCondicao(Number(form.quilometragem), Number(form.ano_fabricacao), form.conservacao, regras, anoAtual);
    if (form.condicao !== esperada) {
      erros['condicao'] = `A classificação não combina com a quilometragem, a idade e a conservação. A regra atual indica: ${ROTULOS_CONDICAO[esperada]}.`;
    }
  }

  return erros;
}
