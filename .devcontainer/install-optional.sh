#!/usr/bin/env bash
# Installation manuelle et facultative d'Ollama et Claude Code.
# Lancer ce script à l'intérieur du conteneur :
#     bash .devcontainer/install-optional.sh
set -eo pipefail

echo "================================================"
echo "  Installation des outils facultatifs"
echo "================================================"
echo ""

# ---------------------------------------------------------------------------
# Ollama
# ---------------------------------------------------------------------------
read -r -p "Installer Ollama ? [o/N] " reponse
if [[ "$reponse" =~ ^([oO][uU][iI]|[oO])$ ]]; then
    echo "→ Installation d'Ollama..."
    curl -fsSL https://ollama.com/install.sh | sh
    echo "✅ Ollama installé."
    echo "   Pour démarrer le service :"
    echo "       ollama serve &"
    echo "   Puis télécharger un modèle :"
    echo "       ollama pull llama3"
    echo "   Vérifier : ollama --version"
else
    echo "→ Ollama ignoré."
fi
echo ""

# ---------------------------------------------------------------------------
# Claude Code
# ---------------------------------------------------------------------------
read -r -p "Installer Claude Code ? [o/N] " reponse
if [[ "$reponse" =~ ^([oO][uU][iI]|[oO])$ ]]; then
    echo "→ Installation de Claude Code..."
    sudo npm install -g @anthropic-ai/claude-code
    echo "✅ Claude Code installé."
    echo "   Pour lancer : claude"
    echo "   Vérifier : claude --version"
else
    echo "→ Claude Code ignoré."
fi
echo ""

echo "================================================"
echo "  Terminé."
echo "================================================"
