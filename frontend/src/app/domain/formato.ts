export function mascaraPlaca(texto: string): string {
  return (texto || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 7);
}

export function mascaraChassi(texto: string): string {
  return (texto || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 30);
}

export function mascaraKm(texto: string): string {
  const digitos = (texto || '').replace(/\D/g, '');
  if (!digitos) return '';
  let n = Number(digitos.replace(/^0+(?=\d)/, '').slice(0, 8));
  if (!Number.isFinite(n)) return '';
  if (n > 2000000) n = 2000000;
  return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

export function parseKm(texto: string | number | null | undefined): number | null {
  if (texto == null || texto === '') return null;
  if (typeof texto === 'number') return Number.isInteger(texto) && texto >= 0 && texto <= 2000000 ? texto : null;
  const n = Number(String(texto).replace(/\./g, ''));
  return Number.isInteger(n) && n >= 0 && n <= 2000000 ? n : null;
}

export function mascaraValor(texto: string): string {
  const limpo = (texto || '').replace(/[^\d,]/g, '');
  if (!limpo) return '';
  const temVirgula = limpo.includes(',');
  const [brutoInteiro, ...resto] = limpo.split(',');
  let inteiro = brutoInteiro.replace(/^0+(?=\d)/, '').slice(0, 8);
  if (!inteiro && temVirgula) inteiro = '0';
  const centavos = resto.join('').slice(0, 2);
  const formatado = inteiro.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  return temVirgula ? `${formatado},${centavos}` : formatado;
}

export function parseValor(texto: string | number | null | undefined): number | null {
  if (texto == null || texto === '') return null;
  if (typeof texto === 'number') return Number.isFinite(texto) ? texto : null;
  const normal = String(texto).trim().replace(/\./g, '').replace(',', '.');
  if (!/^\d+(\.\d{1,2})?$/.test(normal)) return null;
  const n = Number(normal);
  return n >= 0 && n <= 99999999.99 ? Math.round(n * 100) / 100 : null;
}

export function formatarValorPronto(valor: number | null): string {
  if (valor == null || Number.isNaN(valor)) return '';
  const centavos = Math.round(valor * 100);
  const inteiro = Math.trunc(centavos / 100);
  const frac = String(Math.abs(centavos % 100)).padStart(2, '0');
  return `${String(inteiro).replace(/\B(?=(\d{3})+(?!\d))/g, '.')},${frac}`;
}
