#!/usr/bin/env bash
# Lancé automatiquement à la création du conteneur.
set -e

# Rendre les scripts auxiliaires exécutables.
chmod +x install-optional.sh start-services.sh

# --- docker-in-docker : forcer le backend iptables-nft ----------------------
# Le noyau hôte (Cachyos 7.x) n'expose pas la table `nat` legacy d'iptables
# (seul nftables est utilisable). Or la feature docker-in-docker fige l'alternative
# iptables sur `iptables-legacy` à l'install (son test ne vérifie que la table
# `filter`, qui fonctionne en legacy, pas la table `nat`). Résultat : dockerd
# échoue au démarrage ("can't initialize iptables table `nat'").
# On bascule explicitement sur nft. iptablesSwitchAtRuntime:false dans
# devcontainer.json empêche le runtime de remettre legacy à chaque démarrage,
# donc ce réglage effectué une fois ici persiste pour tous les redémarrages.
if command -v update-alternatives >/dev/null 2>&1 \
    && update-alternatives --list iptables 2>/dev/null | grep -q '/usr/sbin/iptables-nft'; then
    sudo update-alternatives --set iptables /usr/sbin/iptables-nft >/dev/null 2>&1 || true
    sudo update-alternatives --set ip6tables /usr/sbin/ip6tables-nft >/dev/null 2>&1 || true
    echo "→ iptables basculé sur nft ($(iptables --version 2>/dev/null | awk '{print $1" "$2" "$NF}'))."
fi

# Si dockerd n'est pas démarré (il a pu échouer avant ce script avec legacy),
# on le relance maintenant que le backend nft est en place.
if ! docker info >/dev/null 2>&1; then
    echo "→ dockerd inactif, redémarrage avec iptables-nft..."
    sudo pkill dockerd 2>/dev/null || true
    sudo pkill containerd 2>/dev/null || true
    sleep 1
    sudo bash /usr/local/share/docker-init.sh >/tmp/dockerd.log 2>&1 &
    # Attendre que le socket réponde (max ~30 s).
    for _ in $(seq 1 30); do
        docker info >/dev/null 2>&1 && break
        sleep 1
    done
    if docker info >/dev/null 2>&1; then
        echo "✅ dockerd démarré (nft)."
    else
        echo "⚠️  dockerd toujours inactif — voir /tmp/dockerd.log dans le conteneur." >&2
    fi
fi

echo ""
echo "✅ Environnement prêt à l'emploi !"
echo ""
echo "Outils disponibles :"
echo "    git, php, composer, symfony, node, npm, docker, docker compose, mysql"
echo ""
echo "Pour vérifier une installation :"
echo "    symfony --version && composer --version && php --version"
echo ""
echo "Services simulés (SSO, MySQL, LDAP, MailCrab) — dossier services/ :"
echo "    Ils tournent sur le Docker interne du devcontainer (DinD) et se joignent"
echo "    via localhost. Pour les démarrer :"
echo "        bash .devcontainer/start-services.sh"
echo "    Puis côté Symfony (localhost) :"
echo "        MySQL  : mysql://admin:my_password@localhost:3306/<dbname>"
echo "        LDAP   : ldap://localhost:389"
echo "        SSO    : http://localhost:5000  (ou http://localhost/sso/ via reverse-proxy)"
echo ""
echo "Outils facultatifs (non installés par défaut) :"
echo "    Pour ajouter Ollama et Claude Code, lancez :"
echo "        bash .devcontainer/install-optional.sh"
echo ""
