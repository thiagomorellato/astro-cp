#!/bin/bash
set -e

# Copiar a chave da pasta de secrets para ~/.ssh e ajustar permissão
mkdir -p /root/.ssh
cp /etc/secrets/id_ed25519 /root/.ssh/id_ed25519
chmod 600 /root/.ssh/id_ed25519

# Desabilita StrictHostKeyChecking para evitar prompt
echo -e "Host *\n\tStrictHostKeyChecking no\n" > /root/.ssh/config

# 🔌 Inicia o túnel SSH
ssh -i /root/.ssh/id_ed25519 -N -L 3307:127.0.0.1:3306 root@http://159.203.42.146/ &

# ✅ Depois disso, inicia o Apache normalmente
exec apache2-foreground
