<?php
require __DIR__ . '/../lib.php';

$falhas = 0;
function espera(mixed $obtido, mixed $esperado, string $mensagem): void
{
    global $falhas;
    if ($obtido !== $esperado) {
        $falhas++;
        fwrite(STDERR, $mensagem . ' | obtido=' . var_export($obtido, true) . ' esperado=' . var_export($esperado, true) . PHP_EOL);
    }
}

$regras = regras_padrao();
espera(sugerir_condicao(0, 2026, 'otima', $regras, 2026), 'novo', 'zero-quilômetro recente');
espera(sugerir_condicao(30000, 2022, 'boa', $regras, 2026), 'seminovo', 'faixa de seminovo');
espera(sugerir_condicao(30001, 2020, 'boa', $regras, 2026), 'usado', 'acima de 30 mil');
espera(sugerir_condicao(1200, 2008, 'ruim', $regras, 2026), 'velho', 'idade e conservação');
espera(sugerir_condicao(0, 2000, 'otima', $regras, 2026), 'seminovo', 'antigo bem conservado não é novo');
espera(placa_formato_valido('ABC1D23'), true, 'mercosul');
espera(placa_formato_valido('ABC1234'), true, 'placa antiga');
espera(placa_formato_valido('DEF2B32'), true, 'DEF2B32 é Mercosul');
espera(placa_formato_valido('12ABCDE'), false, 'placa fora do padrão');
espera(anosModeloDisponivel(2026, 2027), true, '2026/2027');
espera(anosModeloDisponivel(2027, 2027), true, '2027/2027');
espera(anosModeloDisponivel(2026, 2028), false, '2026/2028');

if ($falhas > 0) {
    fwrite(STDERR, "{$falhas} falha(s) nas regras.\n");
    exit(1);
}
echo "regras ok\n";

function anosModeloDisponivel(int $fabricacao, int $modelo): bool
{
    if ($modelo !== $fabricacao && $modelo !== $fabricacao + 1) {
        return false;
    }
    return $modelo <= ano_catalogo_max();
}
