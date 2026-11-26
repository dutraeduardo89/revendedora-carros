<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}

require __DIR__ . '/config.php';

function coluna_existe(PDO $pdo, string $tabela, string $coluna): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabela AND COLUMN_NAME = :coluna'
    );
    $stmt->execute(['tabela' => $tabela, 'coluna' => $coluna]);
    return (int) $stmt->fetchColumn() > 0;
}

function indice_existe(PDO $pdo, string $tabela, string $nome): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabela AND INDEX_NAME = :nome'
    );
    $stmt->execute(['tabela' => $tabela, 'nome' => $nome]);
    return (int) $stmt->fetchColumn() > 0;
}

function constraint_existe(PDO $pdo, string $nome): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_NAME = :nome'
    );
    $stmt->execute(['nome' => $nome]);
    return (int) $stmt->fetchColumn() > 0;
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS tb_sessao (
        id_sessao INT AUTO_INCREMENT PRIMARY KEY,
        login VARCHAR(50) NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_token (token_hash),
        KEY idx_sessao_login (login),
        CONSTRAINT fk_sessao_user FOREIGN KEY (login) REFERENCES tb_usuario (login) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci'
);
echo "tb_sessao ok\n";

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS tb_cor (
        id_cor INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(40) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        login_usuario VARCHAR(50) NOT NULL,
        UNIQUE KEY uk_cor_usuario (login_usuario, nome),
        CONSTRAINT fk_tb_cor_user FOREIGN KEY (login_usuario) REFERENCES tb_usuario (login) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci'
);
echo "tb_cor ok\n";

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS tb_config_classificacao (
        login_usuario VARCHAR(50) PRIMARY KEY,
        novo_km_max INT NOT NULL DEFAULT 0,
        seminovo_km_max INT NOT NULL DEFAULT 30000,
        velho_idade_min INT NOT NULL DEFAULT 15,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_config_user FOREIGN KEY (login_usuario) REFERENCES tb_usuario (login) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci'
);
echo "tb_config_classificacao ok\n";

$colunas = [
    'id_cor' => 'ALTER TABLE tb_carro ADD COLUMN id_cor INT NULL AFTER cor',
    'ano_modelo' => 'ALTER TABLE tb_carro ADD COLUMN ano_modelo INT NULL AFTER ano',
    'versao' => 'ALTER TABLE tb_carro ADD COLUMN versao VARCHAR(80) NULL AFTER ano_modelo',
    'categoria' => 'ALTER TABLE tb_carro ADD COLUMN categoria VARCHAR(40) NULL AFTER versao',
    'combustivel' => 'ALTER TABLE tb_carro ADD COLUMN combustivel VARCHAR(30) NULL AFTER categoria',
    'transmissao' => 'ALTER TABLE tb_carro ADD COLUMN transmissao VARCHAR(30) NULL AFTER combustivel',
    'motorizacao' => 'ALTER TABLE tb_carro ADD COLUMN motorizacao VARCHAR(40) NULL AFTER transmissao',
    'portas' => 'ALTER TABLE tb_carro ADD COLUMN portas TINYINT NULL AFTER motorizacao',
    'passageiros' => 'ALTER TABLE tb_carro ADD COLUMN passageiros TINYINT NULL AFTER portas',
    'condicao' => "ALTER TABLE tb_carro ADD COLUMN condicao ENUM('novo','seminovo','usado','velho') NULL AFTER passageiros",
    'conservacao' => "ALTER TABLE tb_carro ADD COLUMN conservacao ENUM('otima','boa','regular','ruim') NULL AFTER condicao",
    'historico' => 'ALTER TABLE tb_carro ADD COLUMN historico VARCHAR(500) NULL AFTER conservacao',
];
foreach ($colunas as $coluna => $sql) {
    if (!coluna_existe($pdo, 'tb_carro', $coluna)) {
        $pdo->exec($sql);
        echo "coluna {$coluna} adicionada\n";
    }
}

$pdo->exec('UPDATE tb_carro SET ano_modelo = ano WHERE ano_modelo IS NULL');
$nulos = (int) $pdo->query('SELECT COUNT(*) FROM tb_carro WHERE ano_modelo IS NULL')->fetchColumn();
if ($nulos === 0 && coluna_existe($pdo, 'tb_carro', 'ano_modelo')) {
    $tipo = $pdo->query("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tb_carro' AND COLUMN_NAME = 'ano_modelo'")->fetchColumn();
    if ($tipo === 'YES') {
        $pdo->exec('ALTER TABLE tb_carro MODIFY ano_modelo INT NOT NULL');
        echo "ano_modelo obrigatório\n";
    }
}

if (!constraint_existe($pdo, 'tb_carro_chk_anos')) {
    try {
        $pdo->exec('ALTER TABLE tb_carro ADD CONSTRAINT tb_carro_chk_anos CHECK (ano_modelo >= ano AND ano_modelo <= ano + 1)');
        echo "check de anos adicionado\n";
    } catch (PDOException $e) {
        fwrite(STDERR, "Check de anos não aplicado: dados existentes fora da regra. A validação continua na aplicação.\n");
    }
}

if (indice_existe($pdo, 'tb_marca', 'nome')) {
    $pdo->exec('ALTER TABLE tb_marca DROP INDEX nome');
    echo "unicidade global de marca removida\n";
}
if (!indice_existe($pdo, 'tb_marca', 'uk_marca_usuario')) {
    $pdo->exec('ALTER TABLE tb_marca ADD UNIQUE KEY uk_marca_usuario (login_usuario, nome)');
    echo "unicidade de marca por usuário criada\n";
}
if (indice_existe($pdo, 'tb_acessorio', 'nome')) {
    $pdo->exec('ALTER TABLE tb_acessorio DROP INDEX nome');
    echo "unicidade global de acessório removida\n";
}
if (!indice_existe($pdo, 'tb_acessorio', 'uk_acessorio_usuario')) {
    $pdo->exec('ALTER TABLE tb_acessorio ADD UNIQUE KEY uk_acessorio_usuario (login_usuario, nome)');
    echo "unicidade de acessório por usuário criada\n";
}

$tipoSituacao = (string) $pdo->query(
    "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tb_carro' AND COLUMN_NAME = 'situacao'"
)->fetchColumn();
if (!str_contains($tipoSituacao, "'R'")) {
    $pdo->exec("ALTER TABLE tb_carro MODIFY situacao ENUM('D', 'V', 'R') NOT NULL");
    echo "situação reservada adicionada\n";
}

$paleta = ['Branco', 'Preto', 'Prata', 'Cinza', 'Vermelho', 'Azul', 'Verde', 'Marrom', 'Bege', 'Grafite'];
$usuarios = $pdo->query('SELECT login FROM tb_usuario')->fetchAll(PDO::FETCH_COLUMN);
$insertCor = $pdo->prepare('INSERT IGNORE INTO tb_cor (nome, login_usuario) VALUES (:nome, :login)');
foreach ($usuarios as $login) {
    foreach ($paleta as $nome) {
        $insertCor->execute(['nome' => $nome, 'login' => $login]);
    }
    $pdo->prepare(
        'INSERT IGNORE INTO tb_config_classificacao (login_usuario, novo_km_max, seminovo_km_max, velho_idade_min)
         VALUES (:login, 0, 30000, 15)'
    )->execute(['login' => $login]);
}
$pdo->exec(
    "INSERT IGNORE INTO tb_cor (nome, login_usuario)
     SELECT DISTINCT LEFT(TRIM(cor), 40), login_usuario FROM tb_carro
     WHERE cor IS NOT NULL AND TRIM(cor) <> ''"
);
$pdo->exec(
    'UPDATE tb_carro c
     JOIN tb_cor k ON k.login_usuario = c.login_usuario AND k.nome = c.cor
     SET c.id_cor = k.id_cor
     WHERE c.id_cor IS NULL'
);
echo "cores e regras preservadas\n";

if (!constraint_existe($pdo, 'fk_carro_cor')) {
    try {
        $pdo->exec('ALTER TABLE tb_carro ADD CONSTRAINT fk_carro_cor FOREIGN KEY (id_cor) REFERENCES tb_cor (id_cor) ON DELETE SET NULL');
        echo "fk de cor adicionada\n";
    } catch (PDOException $e) {
        fwrite(STDERR, "FK de cor não aplicada. Os veículos continuam com a cor em texto.\n");
    }
}

$antes = (int) $pdo->query('SELECT COUNT(*) FROM tb_carro')->fetchColumn();
echo "veículos preservados: {$antes}\n";
echo "migração concluída\n";
