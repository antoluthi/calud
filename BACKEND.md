# Documentation Backend - AL Escalade

## Architecture

- **PHP 7.4+** avec PDO pour la base de donnees
- **MySQL/MariaDB** pour le stockage
- **Google OAuth 2.0** pour l'authentification
- **API REST** en JSON
- **mail()** PHP pour les emails (confirmation de commande, newsletter)

## Structure des fichiers

```
api/
├── config.php              # Configuration et fonctions utilitaires
├── produits.php            # GET : produits actifs
├── messages.php            # POST : formulaire contact
├── newsletter.php          # POST : inscription newsletter
├── checkout.php            # POST : creer commande + email confirmation
├── commandes.php           # GET : commandes utilisateur connecte
├── auth/
│   ├── login.php           # Redirection vers Google OAuth
│   ├── callback.php        # Callback OAuth (cree/MAJ user en DB)
│   ├── logout.php          # Deconnexion
│   └── status.php          # Statut de connexion
├── admin/
│   ├── produits.php        # CRUD produits
│   ├── commandes.php       # GET detail, PUT statut, POST/DELETE
│   ├── messages.php        # GET/PUT/DELETE messages
│   ├── newsletter.php      # GET abonnes, POST envoi, DELETE
│   ├── clients.php         # GET clients
│   └── check-duplicates.php
└── users/
    └── me.php              # GET : info utilisateur connecte

database/
├── schema.sql              # Schema initial
├── migration_checkout.sql  # Structure actuelle commandes/commande_items
└── ...                     # Autres migrations (voir CLAUDE.md)
```

## Installation

### Prerequis serveur

- PHP 7.4+
- MySQL 5.7+ ou MariaDB 10.2+
- Extensions PHP : `pdo`, `pdo_mysql`, `curl`, `json`, `session`

### Base de donnees

```bash
mysql -u root -p < database/schema.sql
# Puis executer les migrations dans l'ordre (voir CLAUDE.md)
```

### Configuration

Creer un fichier `.env` dans le dossier `api/` (voir `SETUP_ENV.md` pour le guide detaille) :

```env
DB_HOST=localhost
DB_NAME=site_escalade
DB_USER=xxx
DB_PASS=xxx
GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxx
BASE_URL=https://antonin.luthi.eu
```

### Google OAuth

1. Google Cloud Console > APIs & Services > Credentials > OAuth 2.0 Client ID
2. Authorized redirect URIs : `https://votre-domaine.com/api/auth/callback.php`
3. Reporter le Client ID et Secret dans `.env`

## Fonctions utilitaires (config.php)

| Fonction | Description |
|----------|-------------|
| `getDB()` | Retourne une connexion PDO |
| `sendJSON($data, $code)` | Envoie une reponse JSON et exit |
| `getCurrentUser()` | Retourne le user connecte ou null |
| `requireAuth()` | Bloque avec 401 si non connecte |
| `isAdmin()` | Retourne true/false |
| `requireAdmin()` | Bloque avec 403 si non admin |

## API Endpoints

### Public

| Methode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/produits.php` | Liste des produits actifs |
| POST | `/api/messages.php` | Envoyer un message contact |
| POST | `/api/newsletter.php` | S'inscrire a la newsletter |
| POST | `/api/checkout.php` | Creer une commande + email confirmation |

### Authentification

| Methode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/auth/login.php` | Redirection vers Google OAuth |
| GET | `/api/auth/callback.php` | Callback OAuth |
| GET | `/api/auth/logout.php` | Deconnexion |
| GET | `/api/auth/status.php` | Statut connexion (`authenticated`, `user`) |

### Utilisateur connecte

| Methode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/commandes.php` | Commandes de l'utilisateur (par user_id ou email) |
| GET | `/api/users/me.php` | Informations utilisateur |

### Admin (requireAdmin)

| Methode | Endpoint | Description |
|---------|----------|-------------|
| GET/POST/PUT/DELETE | `/api/admin/produits.php` | CRUD produits |
| GET/PUT/POST/DELETE | `/api/admin/commandes.php` | Commandes (detail, statut, CRUD) |
| GET/PUT/DELETE | `/api/admin/messages.php` | Messages contact |
| GET/POST/DELETE | `/api/admin/newsletter.php` | Abonnes + envoi email |
| GET | `/api/admin/clients.php` | Liste clients |

## Schema de base de donnees

### Table `users`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT | Auto-increment |
| `google_id` | VARCHAR(255) | ID Google unique |
| `email` | VARCHAR(255) | Email |
| `name` | VARCHAR(255) | Nom complet |
| `picture` | VARCHAR(500) | URL avatar |
| `is_admin` | TINYINT | 0 ou 1 |
| `created_at` | TIMESTAMP | Date creation |
| `last_login` | TIMESTAMP | Derniere connexion |

### Table `commandes` (migration_checkout.sql)

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT | Auto-increment |
| `order_id` | VARCHAR(50) | Format AL-YYYYMMDD-XXXXXX |
| `user_id` | INT NULL | FK users (NULL = guest) |
| `email` | VARCHAR(255) | Email du client |
| `first_name`, `last_name` | VARCHAR(100) | Nom du client |
| `address`, `postal_code`, `city`, `country` | - | Adresse de livraison |
| `subtotal`, `shipping`, `total` | DECIMAL(10,2) | Montants |
| `status` | ENUM | pending, paid, processing, shipped, delivered, cancelled |

### Table `commande_items`

| Colonne | Type | Description |
|---------|------|-------------|
| `commande_id` | INT | FK commandes |
| `product_name` | VARCHAR(255) | Nom du produit |
| `product_size` | VARCHAR(50) | Taille |
| `quantity` | INT | Quantite |
| `price` | DECIMAL(10,2) | Prix unitaire |

### Table `produits`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT | Auto-increment |
| `nom` | VARCHAR(255) | Nom du produit |
| `prix` | DECIMAL(10,2) | Prix |
| `description` | TEXT | Description |
| `image` | VARCHAR(500) | URL image principale |
| `caracteristiques` | JSON | Features |
| `actif` | BOOLEAN | Visible sur le site |
| `dimensions`, `poids`, `materiaux` | - | Specifications |
| `guide_tailles`, `video_url`, `guide_pdf` | - | Contenu supplementaire |

## Checkout et email

Le checkout (`api/checkout.php`) :
1. Recoit les donnees du formulaire (POST JSON)
2. Genere un `order_id` unique
3. Insere la commande avec `user_id` si connecte (guest = NULL)
4. Insere les items
5. Envoie un email de confirmation HTML via `mail()` (dans un try/catch)
6. Retourne `{ success, orderId }`

L'email de confirmation contient : recap articles, totaux, infos IBAN (BE65 0018 1297 8496, BIC GEBABEBB), adresse de livraison. Template table-based avec inline styles, design sombre.

## Securite

- Requetes preparees PDO (anti SQL injection)
- Google OAuth 2.0 (pas de mots de passe)
- `.env` hors du versioning
- CORS configure dans `config.php`
- Verification `is_admin` sur tous les endpoints admin
- `htmlspecialchars()` pour l'echappement dans les emails
- Protection CSRF via `state` OAuth

## Debogage

```bash
# Logs serveur
tail -f /var/log/apache2/error.log

# Test API
curl http://localhost:8000/api/auth/status.php
curl http://localhost:8000/api/produits.php
```

## Etat des fonctionnalites

- [x] Authentification Google OAuth
- [x] CRUD produits (admin)
- [x] Panier d'achat
- [x] Checkout avec commandes en DB
- [x] Email de confirmation de commande
- [x] Historique des commandes (utilisateur)
- [x] Gestion des commandes (admin)
- [x] Formulaire de contact
- [x] Newsletter (inscription + envoi)
- [x] Dashboard admin
