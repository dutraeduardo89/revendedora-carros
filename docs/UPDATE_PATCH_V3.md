# Patch de Atualização V3 - Isolamento Total (Multi-tenancy)

## 1. Isolamento de Auxiliares (Marcas, Modelos, Acessórios)
- Anteriormente, as tabelas de auxiliares eram globais. 
- Foi adicionada a coluna `login_usuario` nas tabelas `tb_marca`, `tb_modelo` e `tb_acessorio`.
- O backend (`auxiliares.php`) agora filtra todos os GETs, POSTs e DELETEs pelo usuário logado (Header `X-User-Login`).
- **Resultado:** Se o usuário A criar a marca "Fiat", o usuário B não verá essa marca. Cada um gerencia seu próprio inventário de forma isolada.

## 2. Correção no Endpoint de Senha
- O arquivo `senha.php` foi blindado para evitar erros 500.
- Agora ele verifica a sessão do usuário e criptografa corretamente a nova senha com `password_hash`.

## 3. Validação de Integridade (Cascata)
- Testado e aprovado: Ao deletar uma Marca, todos os Modelos e Carros atrelados a ela são deletados em cascata, sem afetar outras contas.
