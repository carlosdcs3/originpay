#!/bin/bash

# DigiKash Database Restore Script
# Uso: ./restore-database.sh [caminho_do_arquivo.sql.gz]

source ../.env

if [ -z "$1" ]; then
    echo "Erro: Caminho do arquivo de backup não fornecido."
    echo "Uso: ./restore-database.sh ../storage/backups/db_backup_X.sql.gz"
    exit 1
fi

BACKUP_FILE=$1
CHECKSUM_FILE="${BACKUP_FILE}.sha256"

# Verify Checksum
if [ -f "$CHECKSUM_FILE" ]; then
    echo "Verifying Checksum..."
    sha256sum -c $CHECKSUM_FILE
    if [ $? -ne 0 ]; then
        echo "Erro CRÍTICO: Checksum inválido. O arquivo pode estar corrompido."
        exit 1
    fi
    echo "Checksum Validated."
else
    echo "Aviso: Arquivo de checksum não encontrado. Restaurando sem verificação de hash."
fi

echo "Iniciando Restore do Banco de Dados..."
# Unzip em tempo real e joga pro mysql
gunzip < $BACKUP_FILE | mysql -u ${DB_USERNAME} -p${DB_PASSWORD} -h ${DB_HOST} ${DB_DATABASE}

if [ $? -eq 0 ]; then
    echo "Restore concluído com sucesso."
    exit 0
else
    echo "Falha no Restore."
    exit 1
fi
