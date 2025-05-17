#!/bin/bash
set -e

# Preparar chave
mkdir -p /root/.ssh
cp /etc/secrets/id_ed25519 /root/.ssh/id_ed25519
chmod 600 /root/.ssh/id_ed25519

# Evitar prompt de verificação
echo -e "Host *\n\tStrictHostKeyChecking no\n" > /root/.ssh/config

# Iniciar túnel com reconexão automática
autossh -M 0 -N -i /root/.ssh/id_ed25519 \
-o "ServerAliveInterval 60" -o "ServerAliveCountMax 3" \
-L 3307:127.0.0.1:3306 root@159.203.42.146 &

# Iniciar Apache
exec apache2-foreground