#!/usr/bin/env bash
# Crée includes/ et connexion.php à partir du template (après clone ou sur le VPS).
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
mkdir -p "$ROOT/includes"
if [[ ! -f "$ROOT/includes/connexion.php" ]]; then
  cp "$ROOT/includes/connexion.example.php" "$ROOT/includes/connexion.php"
  echo "OK: $ROOT/includes/connexion.php créé depuis connexion.example.php"
  echo "    Édite ce fichier avec tes identifiants DB."
else
  echo "Déjà présent: $ROOT/includes/connexion.php (rien modifié)"
fi
