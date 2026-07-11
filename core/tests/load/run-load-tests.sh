#!/bin/bash
# Script utilitário para execução da Bateria K6 e gravação de logs

PROFILE=${1:-quick}
REPORT_DIR="reports/load-tests"

echo "==========================================="
echo "Iniciando bateria K6: $PROFILE"
echo "==========================================="

mkdir -p $REPORT_DIR

# O k6 gravará um json e o output standard no terminal
k6 run -e PROFILE=$PROFILE --out json=$REPORT_DIR/result_${PROFILE}_$(date +%s).json k6-webhooks.js

echo "==========================================="
echo "Testes Concluídos. Relatório gravado em $REPORT_DIR"
echo "==========================================="
