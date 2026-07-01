#!/usr/bin/env bash
# Démarre les services simulés (services/docker-compose.yml) sur le démon Docker
# interne du devcontainer (feature docker-in-docker). Les conteneurs publient leurs
# ports sur le localhost du devcontainer : l'application Symfony les joint ensuite
# directement via localhost (aucune configuration réseau particulière à prévoir).
#
# Usage (depuis le devcontainer) :
#     bash .devcontainer/start-services.sh
set -euo pipefail

# Racine du projet = parent du dossier .devcontainer
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_DIR="$ROOT/services"

if ! docker info >/dev/null 2>&1; then
    echo "❌ Le démon Docker interne (DinD) ne répond pas." >&2
    echo "   Relancez le devcontainer, ou démarrez Docker à la main." >&2
    exit 1
fi

if [ ! -f "$COMPOSE_DIR/docker-compose.yml" ]; then
    echo "❌ $COMPOSE_DIR/docker-compose.yml introuvable." >&2
    exit 1
fi

echo "→ Démarrage des services depuis $COMPOSE_DIR ..."
docker compose --project-directory "$COMPOSE_DIR" -f "$COMPOSE_DIR/docker-compose.yml" up -d --build

echo ""
echo "✅ Services démarrés. Symfony les joint via localhost :"
echo "    Base de données (MySQL) : mysql://admin:my_password@localhost:3306/<dbname>"
echo "    Annuaire LDAP           : ldap://localhost:389"
echo "    SSO (direct)            : http://localhost:5000"
echo "    SSO / phpMyAdmin / Mail : http://localhost  (/sso/, /phpmyadmin/, /mail/)"
echo "    MailCrab (UI)           : http://localhost:1080"
echo ""
echo "Cycle de vie (cf. slides « conteneurs ») :"
echo "    docker compose --project-directory $COMPOSE_DIR logs -f   # suivre les logs"
echo "    docker compose --project-directory $COMPOSE_DIR stop      # mettre en pause"
echo "    docker compose --project-directory $COMPOSE_DIR down      # tout supprimer"