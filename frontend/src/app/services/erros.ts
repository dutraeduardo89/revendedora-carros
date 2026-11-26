export function mensagemErro(err: unknown, fallback = 'Falha inesperada do sistema.'): string {
  const erro = err as { name?: string; status?: number; error?: { message?: string } | string };
  if (erro?.name === 'TimeoutError') {
    return 'A conexão com o servidor demorou demais. Tente novamente.';
  }
  if (erro?.status === 0 || erro?.status == null) {
    return 'Erro de conexão com o servidor.';
  }
  const corpo = erro.error;
  if (typeof corpo === 'string' && corpo.trim()) {
    return corpo;
  }
  if (corpo && typeof corpo === 'object' && typeof corpo.message === 'string' && corpo.message.trim()) {
    return corpo.message;
  }
  if (erro.status === 401) return 'Operação não autorizada. Faça login novamente.';
  if (erro.status === 404) return 'Registro não encontrado.';
  if (erro.status === 409) return 'Esta informação já está cadastrada.';
  if (erro.status >= 500) return 'Falha inesperada do sistema.';
  return fallback;
}

export function camposErro(err: unknown): Record<string, string> {
  const erro = err as { error?: { fields?: Record<string, string> } };
  const fields = erro?.error?.fields;
  if (!fields || typeof fields !== 'object') return {};
  return fields;
}
