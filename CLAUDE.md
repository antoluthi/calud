# Instructions Claude - AL Escalade

Ce fichier contient toutes les informations essentielles pour travailler sur ce projet.

## Projet

**AL Escalade** - Site e-commerce vitrine pour matériel d'escalade (poutres de suspension portables).

## Infrastructure

| Élément | Valeur |
|---------|--------|
| **GitHub** | https://github.com/antoluthi/calud |
| **Branche principale** | `main` |
| **Serveur web** | antonin.luthi.eu |
| **Accès SSH/DB** | gates.luthi.eu |
| **Chemin déploiement** | `/var/www/antonin` |
| **Utilisateur SSH** | Configuré dans GitHub Secrets |

## Déploiement

Le déploiement est **automatique** via GitHub Actions:
- Push sur `main` → Déploiement automatique SFTP
- Workflow: `.github/workflows/deploy.yml`
- Action utilisée: `wlixcc/SFTP-Deploy-Action@v1.2.4`

**Secrets GitHub requis:**
- `SFTP_USERNAME` - Nom d'utilisateur SSH
- `SSH_PRIVATE_KEY` - Clé SSH privée
- `SFTP_SERVER` - Serveur (gates.luthi.eu ou antonin.luthi.eu)
- `SFTP_PORT` - Port SSH (généralement 22)
- `SFTP_REMOTE_PATH` - `/var/www/antonin`

## Stack Technique

### Backend
- **PHP** 7.4+ avec PDO
- **MySQL/MariaDB** 5.7+
- **Google OAuth 2.0** pour l'authentification

### Frontend
- **HTML5** / **CSS3** (thème sombre, accent #e75480)
- **JavaScript vanilla** (pas de framework)
- **Responsive** mobile-first

## Structure du Projet

```
site-escalade/
├── index.html              # Site public principal
├── css/style.css           # Styles (thème sombre)
├── js/main.js              # Logique frontend
├── api/                    # API REST PHP
│   ├── config.php          # Config DB + OAuth
│   ├── auth/               # Authentification Google
│   ├── produits.php        # API produits
│   ├── messages.php        # Formulaire contact
│   ├── newsletter.php      # Inscriptions newsletter
│   └── admin/              # Endpoints admin
├── admin/                  # Dashboard admin
│   ├── index.php           # Tableau de bord
│   ├── produits.php        # Gestion produits
│   ├── commandes.php       # Gestion commandes
│   ├── clients.php         # Gestion clients
│   ├── messages.php        # Gestion messages
│   └── newsletter.php      # Gestion newsletter
├── database/               # Schéma et migrations SQL
└── .github/workflows/      # GitHub Actions
```

## Base de Données

### Tables principales
- `users` - Comptes utilisateurs (Google OAuth, champ `is_admin`)
- `produits` - Catalogue produits (nom, prix, images, caractéristiques)
- `messages` - Messages du formulaire de contact
- `newsletter` - Abonnés newsletter
- `commandes` - Commandes (pour futur e-commerce)
- `commande_items` - Lignes de commande

### Migrations
Les migrations sont dans `/database/`. À exécuter dans l'ordre:
1. `schema.sql` - Schéma initial
2. `migration_add_admin.sql`
3. `migration_messages.sql`
4. `migration_tailles.sql`
5. `migration_newsletter.sql`
6. `migration_modal_produit.sql`
7. `migration_users_newsletter.sql`

## Configuration Serveur

Le fichier `.env` (non versionné) doit contenir:
```env
DB_HOST=localhost
DB_NAME=site_escalade
DB_USER=xxx
DB_PASS=xxx

GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxx

BASE_URL=https://antonin.luthi.eu
```

## API Endpoints

### Public
- `GET /api/produits.php` - Liste des produits
- `POST /api/messages.php` - Envoyer un message
- `POST /api/newsletter.php` - S'inscrire à la newsletter

### Authentification
- `GET /api/auth/login.php` - Redirection Google OAuth
- `GET /api/auth/callback.php` - Callback OAuth
- `GET /api/auth/logout.php` - Déconnexion
- `GET /api/auth/status.php` - Statut connexion

### Admin (requiert `is_admin=1`)
- `/api/admin/produits.php` - CRUD produits
- `/api/admin/messages.php` - Gestion messages
- `/api/admin/newsletter.php` - Gestion abonnés

## Workflow de Développement

1. **Développer localement**:
   ```bash
   php -S localhost:8000
   ```

2. **Tester les changements**

3. **Commit et push**:
   ```bash
   git add .
   git commit -m "Description du changement"
   git push origin main
   ```

4. **Déploiement automatique** via GitHub Actions

## Accès SSH

Pour accéder au serveur manuellement:
```bash
ssh utilisateur@gates.luthi.eu
```

La base de données est accessible depuis gates.luthi.eu.

## Fichiers Importants

| Fichier | Description |
|---------|-------------|
| `api/config.php` | Configuration centrale (DB, OAuth, fonctions) |
| `index.html` | Page principale du site |
| `js/main.js` | Toute la logique frontend |
| `css/style.css` | Styles du site public |
| `admin/css/admin.css` | Styles du dashboard admin |

## Notes de Sécurité

- Utilise des requêtes préparées PDO (anti SQL injection)
- Google OAuth 2.0 (pas de gestion de mots de passe)
- `.env` exclu du versioning
- CORS configuré dans `config.php`
- Vérification `is_admin` pour l'accès admin

## Aide-mémoire Git

```bash
# Voir le statut
git status

# Voir les changements
git diff

# Commit
git add fichier.php
git commit -m "Message"
git push

# Voir les logs
git log --oneline -10
```

---
*Dernière mise à jour: Janvier 2026*
