# Decisões de negócio

Estas regras foram fechadas para o sistema poder validar o cadastro sem contradizer a especificação. Onde o pedido deixava mais de uma leitura possível, a escolha está registrada aqui.

## Acesso do administrador

No banco em uso, o usuário `admin` entra com a senha `1234`. A senha fica armazenada com `password_hash`. O `database/seed.sql` usa a mesma senha para uma instalação nova. As sessões antigas desse usuário foram encerradas quando a senha foi definida.

## Ano de fabricação e ano-modelo

São campos independentes. O ano-modelo pode ser igual ao ano de fabricação ou o ano seguinte. O catálogo vai de 40 anos atrás até o ano calendário atual mais um.

Um veículo cujo ano de fabricação já é o teto do catálogo só pode usar o mesmo ano como ano-modelo. Exemplo no calendário de 2026: 2027/2027 é válido e 2027/2028 não entra no catálogo. Veículos antigos já gravados fora dessa janela continuam editáveis se o ano de fabricação não for alterado.

A coluna original `ano` guarda o ano de fabricação, para as consultas e os registros anteriores continuarem válidos.

## Classificação

A classificação não é copiada da quilometragem. A sugestão obrigatória usa, juntos:

- a quilometragem;
- a idade, calculada pelo ano de fabricação;
- a conservação informada no cadastro.

Parâmetros iniciais, editáveis em Configurações por usuário, sem reclassificar os veículos já salvos:

- Novo: quilometragem até o teto de novo (0 km), conservação ótima ou boa, e idade abaixo do limite de velho.
- Seminovo: acima do teto de novo e até 30.000 km, com conservação ótima ou boa. Um veículo antigo e bem conservado, com baixa quilometragem, cai aqui e não em novo.
- Usado: acima de 30.000 km, ou conservação regular/ruim sem idade suficiente para velho.
- Velho: idade igual ou maior que 15 anos e conservação regular ou ruim. A quilometragem não decide sozinha essa classe.

O histórico é um texto opcional de até 500 caracteres. Ele fica visível no cadastro e no detalhe, mas não é interpretado automaticamente: não há um histórico estruturado de manutenção no sistema.

## Placa, chassi e cor

Placas novas precisam seguir o padrão antigo ou o Mercosul. Placas já gravadas fora desses padrões permanecem válidas até serem alteradas. O chassi continua com o tamanho do schema original (5 a 30 caracteres); o VIN de 17 caracteres não foi imposto porque os registros existentes e o enunciado usam chassis curtos.

A cor do veículo passa a apontar para um cadastro por usuário. O nome também permanece na coluna `cor`, que já existia.

A placa aceita só 7 letras ou números (ABC1234 ou ABC1D23). O chassi aceita de 5 a 30, sem símbolos. A quilometragem vai de 0 a 2.000.000 e o valor vai até 99.999.999,99. Na tela, milhar e centavos aparecem no padrão brasileiro enquanto a pessoa digita.

A situação de venda é Disponível, Reservado ou Vendido. Na tabela, a placa fica verde, amarela ou vermelha nessa ordem. A linha do inventário mostra só placa, veículo (modelo, marca e cor), ano, quilometragem, valor e situação. Classificação, versão e o restante ficam no detalhe, para a tabela não espremer o texto.

Os filtros de marca e cor listam o catálogo inteiro da conta, não só o que já está num veículo. A opção vazia desses filtros se chama Nenhum: ela não restringe a lista.

Marcas, modelos, acessórios e cores ficam em páginas separadas, com ordem por nome ou por ID.

## O que não foi automatizado

A tabela `tb_trabalheconosco` existe no banco e não tem tela nem API no projeto. Nenhuma candidatura foi inventada.

Não há reclassificação em massa quando as faixas de quilometragem mudam. Cada veículo mantém a classificação gravada até alguém editá-lo.
