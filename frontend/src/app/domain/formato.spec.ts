import { describe, expect, it } from 'vitest';
import { formatarValorPronto, limitarInteiro, mascaraChassi, mascaraKm, mascaraPlaca, mascaraValor, parseKm, parseValor } from './formato';

describe('máscaras de entrada', () => {
  it('prende inteiros pequenos no intervalo permitido', () => {
    expect(limitarInteiro('9', 2, 6)).toBe('6');
    expect(limitarInteiro('1', 2, 6)).toBe('2');
    expect(limitarInteiro('25', 1, 20)).toBe('20');
    expect(limitarInteiro('1', 1, 20)).toBe('1');
    expect(limitarInteiro('', 2, 6)).toBe('');
  });

  it('aceita placa antiga e Mercosul e corta o que passa de 7', () => {
    expect(mascaraPlaca('abc-1d23')).toBe('ABC1D23');
    expect(mascaraPlaca('tgn2483xyz')).toBe('TGN2483');
  });

  it('limita o chassi a 30 caracteres alfanuméricos', () => {
    expect(mascaraChassi('ab-12!')).toBe('AB12');
    expect(mascaraChassi('x'.repeat(40))).toHaveLength(30);
  });

  it('mostra quilometragem com ponto de milhar e até 7 dígitos', () => {
    expect(mascaraKm('1000')).toBe('1.000');
    expect(mascaraKm('12345678')).toBe('2.000.000');
    expect(parseKm('1.000')).toBe(1000);
    expect(parseKm('')).toBeNull();
  });

  it('mostra o valor em reais e completa os centavos ao sair do campo', () => {
    expect(mascaraValor('1000')).toBe('1.000');
    expect(mascaraValor('1000,5')).toBe('1.000,5');
    expect(parseValor('1.000')).toBe(1000);
    expect(formatarValorPronto(1000)).toBe('1.000,00');
    expect(parseValor('99.999.999,99')).toBe(99999999.99);
    expect(parseValor('100.000.000,00')).toBeNull();
  });
});
