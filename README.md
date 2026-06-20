# Application Patrimoine PHP

Application PHP 8 auto-hébergée pour gérer un patrimoine personnel sur un hébergement mutualisé IONOS sans Node.js.

## Installation

Envoyer le dossier par FTP sur l’hébergement. Le point d’entrée est :

```text
index.php
```

Au premier lancement, l’application crée si besoin :

- `data/users.json`
- `data/patrimoine-nicolas.json`
- `data/backups/`

Identifiants initiaux :

- utilisateur : `nicolas`
- mot de passe : `change-me-before-deploy`

Le mot de passe est stocké avec `password_hash()`.

## Test local

```bash
php -S localhost:8000
```

Puis ouvrir :

```text
http://localhost:8000
```

## Fonctions

- sessions PHP
- création d’utilisateurs depuis le menu `Utilisateurs`
- un fichier patrimoine JSON séparé par utilisateur
- données locales JSON version 2.0
- grandes rubriques regroupées
- formulaires générés depuis `includes/schema.php`
- niveau 1 pour le bilan patrimonial
- niveau 2 pour les détails et mémos
- liens web multiples par fiche
- montants mensuels ou annuels avec conversion automatique
- dashboard compact
- exports JSON et CSV
- sauvegarde automatique avant modification
- protection du dossier `data/` par `.htaccess`
