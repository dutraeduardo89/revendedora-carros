import { REGRAS_PADRAO, anosModelo, sugerirCondicao, validarVeiculo, veiculoVazio } from './vehicle-rules';

const anoAtual = 2026;

function completo() {
  return {
    ...veiculoVazio(),
    placa: 'ABC1D23',
    chassi: '9BWZZZ377VT004251',
    id_cor: 1,
    ano_fabricacao: 2026,
    ano_modelo: 2026,
    valor: 80000,
    quilometragem: 0,
    situacao: 'D',
    id_modelo: 1,
    categoria: 'Sedan',
    combustivel: 'Flex',
    transmissao: 'Automático',
    motorizacao: '2.0',
    portas: 4,
    passageiros: 5,
    condicao: 'novo',
    conservacao: 'otima',
  };
}

describe('regras de veículo', () => {
  it('aceita 2026/2026, 2026/2027 e 2027/2027', () => {
    expect(anosModelo(2026, anoAtual)).toEqual([2026, 2027]);
    expect(anosModelo(2027, anoAtual)).toEqual([2027]);
  });

  it('recusa ano-modelo dois anos à frente', () => {
    const form = { ...completo(), ano_fabricacao: 2026, ano_modelo: 2028, condicao: 'novo' };
    expect(validarVeiculo(form, REGRAS_PADRAO, anoAtual)['ano_modelo']).toContain('ano seguinte');
  });

  it('classifica zero-quilômetro recente como novo', () => {
    expect(sugerirCondicao(0, 2026, 'otima', REGRAS_PADRAO, anoAtual)).toBe('novo');
  });

  it('classifica até 30.000 km como seminovo', () => {
    expect(sugerirCondicao(30000, 2022, 'boa', REGRAS_PADRAO, anoAtual)).toBe('seminovo');
  });

  it('classifica acima de 30.000 km como usado', () => {
    expect(sugerirCondicao(30001, 2020, 'boa', REGRAS_PADRAO, anoAtual)).toBe('usado');
  });

  it('classifica carro antigo e mal conservado como velho mesmo com baixa quilometragem', () => {
    expect(sugerirCondicao(1200, 2008, 'ruim', REGRAS_PADRAO, anoAtual)).toBe('velho');
  });

  it('não chama de novo um carro antigo só porque está com 0 km', () => {
    expect(sugerirCondicao(0, 2000, 'otima', REGRAS_PADRAO, anoAtual)).toBe('seminovo');
  });

  it('rejeita quilometragem negativa e placa inválida', () => {
    const form = { ...completo(), placa: '12', quilometragem: -1, condicao: 'usado' };
    const erros = validarVeiculo(form, REGRAS_PADRAO, anoAtual);
    expect(erros['placa']).toBeTruthy();
    expect(erros['quilometragem']).toContain('negativa');
  });

  it('aceita zero-quilômetro', () => {
    expect(validarVeiculo(completo(), REGRAS_PADRAO, anoAtual)).toEqual({});
  });

  it('mantém placa antiga já gravada', () => {
    const form = { ...completo(), placa: '12ABCDE' };
    expect(validarVeiculo(form, REGRAS_PADRAO, anoAtual, '12ABCDE')['placa']).toBeUndefined();
  });
});
