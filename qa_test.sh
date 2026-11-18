#!/bin/bash
BASE="http://localhost:9090"
echo "=== INICIANDO BATERIA DE TESTES QA ==="

echo -n "1. Login Admin (1234)... "
curl -s -o /dev/null -w "%{http_code}" -X POST $BASE/login.php -H "Content-Type: application/json" -d '{"login":"admin","senha":"1234"}'; echo " (Esperado: 200)"

echo -n "2. Criar Marca Duplicada (Fiat)... "
curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/auxiliares.php?acao=marca" -H "X-User-Login: admin" -H "Content-Type: application/json" -d '{"nome":"Fiat"}'; echo " (Esperado: 400 - Erro de Redundância)"

echo -n "3. Criar Carro com Placa Duplicada... "
curl -s -o /dev/null -w "%{http_code}" -X POST $BASE/carros.php -H "X-User-Login: admin" -H "Content-Type: application/json" -d '{"placa":"ABC1234","chassi":"DSDS6580","cor":"Preto","ano":2020,"valor":50000,"quilometragem":0,"id_modelo":1}'; echo " (Esperado: 400 - Unicidade)"

echo -n "4. Buscar Carros apenas do Admin... "
curl -s -o /dev/null -w "%{http_code}" $BASE/carros.php -H "X-User-Login: admin"; echo " (Esperado: 200)"

echo -n "5. Estatísticas do Admin... "
curl -s $BASE/stats.php -H "X-User-Login: admin"; echo ""

echo "=== TESTES FINALIZADOS ==="
