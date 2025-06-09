#!/bin/bash
set -e

# Preparar chave
mkdir -p /root/.ssh
cp /etc/secrets/id_ed25519 /root/.ssh/id_ed25519
chmod 600 /root/.ssh/id_ed25519

# Evitar prompt de verificação
echo -e "Host *\n\tStrictHostKeyChecking no\n" > /root/.ssh/config

# --- Seção para www-data ---
WWW_DATA_SSH_DIR="/var/www/.ssh"
WWW_DATA_SSH_KEY_PATH="$WWW_DATA_SSH_DIR/id_ed25519_scp"

mkdir -p "$WWW_DATA_SSH_DIR"
# Use o caminho direto aqui também:
cp /etc/secrets/id_ed25519 "$WWW_DATA_SSH_KEY_PATH"

chown -R www-data:www-data "$WWW_DATA_SSH_DIR"
chmod 700 "$WWW_DATA_SSH_DIR"
chmod 600 "$WWW_DATA_SSH_KEY_PATH"
# --- Fim da seção para www-data ---

# Iniciar túnel com reconexão automática
autossh -M 0 -N -i /root/.ssh/id_ed25519 \
-o "ServerAliveInterval 60" -o "ServerAliveCountMax 3" \
-L 3307:127.0.0.1:3306 root@159.203.15.99 &

# Iniciar Apache
exec apache2-foreground