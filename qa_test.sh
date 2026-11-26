#!/bin/bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
php "$ROOT/backend/tests/regras_test.php"
php "$ROOT/backend/tests/fluxos_test.php"
echo "=== TESTES FINALIZADOS ==="
