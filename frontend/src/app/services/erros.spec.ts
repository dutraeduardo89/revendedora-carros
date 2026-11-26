import { mensagemErro } from './erros';

describe('mensagemErro', () => {
  it('identifica falta de conexão', () => {
    expect(mensagemErro({ status: 0 })).toBe('Erro de conexão com o servidor.');
  });

  it('usa a mensagem específica da API', () => {
    expect(mensagemErro({ status: 409, error: { message: 'Esta placa já está cadastrada.' } })).toBe('Esta placa já está cadastrada.');
  });

  it('não expõe falha interna sem mensagem segura', () => {
    expect(mensagemErro({ status: 500, error: {} })).toBe('Falha inesperada do sistema.');
  });

  it('explica sessão expirada', () => {
    expect(mensagemErro({ status: 401 })).toBe('Operação não autorizada. Faça login novamente.');
  });
});
