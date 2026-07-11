#!/bin/bash

# DigiKash Database Backup Script
# PASSO 1: Dump Local Temporário
# PASSO 2: Upload para AWS S3 (ou Cloudflare R2 equivalente)
# Gera Checksum para auditoria

source ../.env

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="../storage/backups"
FILE_NAME="db_backup_${TIMESTAMP}.sql.gz"
FILE_PATH="${BACKUP_DIR}/${FILE_NAME}"
CHECKSUM_FILE="${FILE_PATH}.sha256"

mkdir -p ${BACKUP_DIR}

echo "Starting Local Database Dump..."
mysqldump -u ${DB_USERNAME} -p${DB_PASSWORD} -h ${DB_HOST} ${DB_DATABASE} | gzip > ${FILE_PATH}

if [ $? -eq 0 ]; then
    echo "Local Dump Successful: ${FILE_PATH}"
    
    # Generate Checksum
    sha256sum ${FILE_PATH} > ${CHECKSUM_FILE}
    echo "Checksum Generated."

    # Upload to External Storage (S3 / R2)
    # Requer AWS CLI configurado ou env vars exportadas
    if [ ! -z "$AWS_BUCKET" ]; then
        echo "Uploading to S3 Bucket: ${AWS_BUCKET}"
        aws s3 cp ${FILE_PATH} s3://${AWS_BUCKET}/backups/database/${FILE_NAME}
        aws s3 cp ${CHECKSUM_FILE} s3://${AWS_BUCKET}/backups/database/${FILE_NAME}.sha256
        
        if [ $? -eq 0 ]; then
            echo "Remote Upload Successful."
        else
            echo "Remote Upload FAILED."
            exit 2
        fi
    else
        echo "AWS_BUCKET not configured. Skipping remote upload."
    fi

    # Limpeza Local (Retenção 7 dias local)
    find ${BACKUP_DIR} -name "db_backup_*.sql.gz" -type f -mtime +7 -delete
    find ${BACKUP_DIR} -name "db_backup_*.sha256" -type f -mtime +7 -delete

    exit 0
else
    echo "Local Dump FAILED."
    exit 1
fi
