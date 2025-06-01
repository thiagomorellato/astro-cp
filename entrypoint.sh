#!/bin/bash
set -e

# Preparar chave
mkdir -p /root/.ssh
cp /etc/secrets/id_ed25519 /root/.ssh/id_ed25519
chmod 600 /root/.ssh/id_ed25519

# Evitar prompt de verificação
echo -e "Host *\n\tStrictHostKeyChecking no\n" > /root/.ssh/config

# --- Adicionar esta seção para www-data ---
# Preparar chave SSH para www-data (usuário do Apache/PHP) para usar com scp
WWW_DATA_SSH_DIR="/var/www/.ssh" # Diretório .ssh para www-data
WWW_DATA_SSH_KEY_PATH="$WWW_DATA_SSH_DIR/id_ed25519_scp" # Caminho da chave para scp

mkdir -p "$WWW_DATA_SSH_DIR"
cp "$SECRET_KEY_PATH" "$WWW_DATA_SSH_KEY_PATH" # Copia a mesma chave

# Define propriedade e permissões estritas para www-data
# É crucial que www-data seja o proprietário e as permissões sejam restritas
chown -R www-data:www-data "$WWW_DATA_SSH_DIR"
chmod 700 "$WWW_DATA_SSH_DIR" # Apenas o proprietário pode ler, escrever, executar
chmod 600 "$WWW_DATA_SSH_KEY_PATH" # Apenas o proprietário pode ler, escrever
# --- Fim da seção para www-data ---

# Iniciar túnel com reconexão automática
autossh -M 0 -N -i /root/.ssh/id_ed25519 \
-o "ServerAliveInterval 60" -o "ServerAliveCountMax 3" \
-L 3307:127.0.0.1:3306 root@159.203.42.146 &

# Iniciar Apache
exec apache2-foreground