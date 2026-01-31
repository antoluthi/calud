# Instructions Claude - PROJET CRIMP.

Ce fichier contient toutes les informations essentielles pour travailler sur ce projet.

## Projet

**PROJET CRIMP.** - Site e-commerce pour materiel d'escalade (poutres de suspension portables).
Le site est en production sur `https://antonin.luthi.eu`.

## Infrastructure

| Element | Valeur |
|---------|--------|
| **GitHub** | https://github.com/antoluthi/calud |
| **Branche principale** | `main` |
| **Serveur web** | antonin.luthi.eu |
| **Acces SSH/DB** | gates.luthi.eu |
| **Chemin deploiement** | `/var/www/antonin` |
| **Utilisateur SSH** | Configure dans GitHub Secrets |

## Deploiement

Le deploiement est **automatique** via GitHub Actions :
- Push sur `main` -> Deploiement automatique SFTP
- Workflow : `.github/workflows/deploy.yml`
- Action utilisee : `wlixcc/SFTP-Deploy-Action@v1.2.4`

**Secrets GitHub requis :**
- `SFTP_USERNAME` - Nom d'utilisateur SSH
- `SSH_PRIVATE_KEY` - Cle SSH privee
- `SFTP_SERVER` - Serveur (gates.luthi.eu ou antonin.luthi.eu)
- `SFTP_PORT` - Port SSH (generalement 22)
- `SFTP_REMOTE_PATH` - `/var/www/antonin`

## Stack Technique

### Backend
- **PHP** 7.4+ avec PDO
- **MySQL/MariaDB** 5.7+
- **Google OAuth 2.0** pour l'authentification
- **mail()** PHP pour les emails (confirmation de commande, newsletter)

### Frontend
- **HTML5** / **CSS3** (theme sombre, fond #0a0a0a, cartes #181818, grille en fond)
- **JavaScript vanilla** (pas de framework)
- **Police** : Space Grotesk (Google Fonts)
- **Responsive** mobile-first

## Structure du Projet

```
site-escalade/
├── index.html                # Site public principal (vitrine + produits + contact)
├── checkout.html             # Page de checkout (panier -> commande)
├── mes-commandes.html        # Historique des commandes (utilisateur connecte)
├── css/style.css             # Styles du site public (theme sombre)
├── js/main.js                # Logique frontend (produits, panier, auth, modal produit)
├── images/                   # Images des produits
├── guides/                   # Guides d'utilisation PDF des produits
├── favicon.ico
│
├── api/                      # API REST PHP
│   ├── config.php            # Config centrale (DB, OAuth, fonctions utilitaires)
│   ├── produits.php          # GET : liste des produits actifs
│   ├── messages.php          # POST : formulaire de contact
│   ├── newsletter.php        # POST : inscription newsletter
│   ├── checkout.php          # POST : creer une commande + envoi email confirmation
│   ├── commandes.php         # GET : commandes de l'utilisateur connecte (requireAuth)
│   ├── auth/
│   │   ├── login.php         # Redirection vers Google OAuth
│   │   ├── callback.php      # Callback OAuth (cree/met a jour le user en DB)
│   │   ├── logout.php        # Deconnexion (detruit la session)
│   │   └── status.php        # Statut de connexion (retourne user + is_admin)
│   ├── admin/                # Endpoints admin (tous proteges par requireAdmin())
│   │   ├── produits.php      # CRUD produits
│   │   ├── commandes.php     # GET detail commande, PUT statut, POST/DELETE
│   │   ├── messages.php      # GET/PUT/DELETE messages
│   │   ├── newsletter.php    # GET abonnes, POST envoi email, DELETE
│   │   ├── clients.php       # GET liste clients
│   │   └── check-duplicates.php
│   └── users/
│       └── me.php            # GET : info utilisateur connecte
│
├── admin/                    # Dashboard admin (pages PHP, protegees par isAdmin())
│   ├── index.php             # Tableau de bord (stats)
│   ├── produits.php          # Gestion produits
│   ├── commandes.php         # Gestion commandes (+ checkbox masquer annulees)
│   ├── clients.php           # Gestion clients
│   ├── messages.php          # Gestion messages contact
│   ├── newsletter.php        # Gestion newsletter + envoi
│   ├── css/admin.css         # Styles du dashboard admin
│   └── js/                   # JS specifique a chaque page admin
│       ├── produits.js
│       ├── commandes.js
│       ├── clients.js
│       ├── messages.js
│       └── newsletter.js
│
├── database/                 # Schema et migrations SQL
│   ├── schema.sql            # Schema initial
│   ├── migration_add_admin.sql
│   ├── migration_messages.sql
│   ├── migration_tailles.sql
│   ├── migration_newsletter.sql
│   ├── migration_modal_produit.sql
│   ├── migration_users_newsletter.sql
│   ├── migration_guide_pdf.sql
│   ├── migration_checkout.sql  # Structure actuelle des tables commandes/commande_items
│   └── fix_duplicates.sql
│
├── data/produits.json        # Ancien fichier produits (plus utilise, tout est en DB)
├── .env.example              # Exemple de configuration .env
├── .htaccess                 # Config Apache (racine)
├── .github/workflows/
│   └── deploy.yml            # GitHub Actions : deploiement SFTP auto
└── .gitignore
```

## Base de Donnees

### Tables principales

**`users`** - Comptes utilisateurs
- `id`, `google_id`, `email`, `name`, `picture`, `is_admin` (0 ou 1), `created_at`, `last_login`

**`produits`** - Catalogue
- `id`, `nom`, `prix`, `description`, `image`, `caracteristiques` (JSON), `actif`, `dimensions`, `poids`, `materiaux`, `guide_tailles`, `video_url`, `guide_pdf`, `created_at`, `updated_at`
- Peut avoir plusieurs images et tailles (tables liees)

**`commandes`** - Commandes clients (structure definie par `migration_checkout.sql`)
- `id`, `order_id` (format PC-YYYYMMDD-XXXXXX), `user_id` (NULL pour guest), `email`, `phone`, `first_name`, `last_name`
- `address`, `address2`, `postal_code`, `city`, `country`
- `payment_method`, `subtotal`, `shipping`, `total`
- `status` : ENUM(`pending`, `paid`, `processing`, `shipped`, `delivered`, `cancelled`)
- `created_at`, `updated_at`

**`commande_items`** - Lignes de commande
- `id`, `commande_id` (FK), `product_id`, `product_name`, `product_size`, `quantity`, `price`
- **Attention** : les colonnes sont `product_name`, `quantity`, `price` (PAS `produit_id`, `quantite`, `prix_unitaire` du schema original)

**`messages`** - Messages du formulaire de contact
- `id`, `name`, `email`, `message`, `is_read`, `created_at`

**`newsletter`** - Abonnes newsletter
- `id`, `email`, `created_at`

### Migrations
Les migrations sont dans `/database/`. A executer dans l'ordre :
1. `schema.sql` - Schema initial
2. `migration_add_admin.sql`
3. `migration_messages.sql`
4. `migration_tailles.sql`
5. `migration_newsletter.sql`
6. `migration_modal_produit.sql`
7. `migration_users_newsletter.sql`
8. `migration_guide_pdf.sql`
9. `migration_checkout.sql` - Structure actuelle des commandes
10. `fix_duplicates.sql` - Diagnostic/nettoyage des doublons

## Configuration Serveur

Le fichier `.env` (non versionne, dans le dossier `api/` sur le serveur) doit contenir :
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
- `GET /api/produits.php` - Liste des produits actifs
- `POST /api/messages.php` - Envoyer un message de contact
- `POST /api/newsletter.php` - S'inscrire a la newsletter
- `POST /api/checkout.php` - Creer une commande (lie user_id si connecte, envoie email de confirmation)

### Authentification
- `GET /api/auth/login.php` - Redirection Google OAuth
- `GET /api/auth/callback.php` - Callback OAuth
- `GET /api/auth/logout.php` - Deconnexion
- `GET /api/auth/status.php` - Statut connexion (retourne `authenticated`, `user` avec `is_admin`)

### Utilisateur connecte (requireAuth)
- `GET /api/commandes.php` - Commandes de l'utilisateur (par user_id OU email)
- `GET /api/users/me.php` - Info utilisateur

### Admin (requireAdmin)
- `/api/admin/produits.php` - CRUD produits (GET/POST/PUT/DELETE)
- `/api/admin/commandes.php` - GET detail avec items, PUT statut, POST/DELETE
- `/api/admin/messages.php` - GET/PUT (marquer lu)/DELETE
- `/api/admin/newsletter.php` - GET abonnes, POST envoi email a tous, DELETE
- `/api/admin/clients.php` - GET liste clients

## Fonctions utilitaires (api/config.php)

- `getDB()` - Retourne une connexion PDO
- `sendJSON($data, $statusCode)` - Envoie une reponse JSON et exit
- `getCurrentUser()` - Retourne le user connecte depuis la session (ou null)
- `requireAuth()` - Bloque si non connecte (401)
- `isAdmin()` - Retourne true/false
- `requireAdmin()` - Bloque si non admin (403)

## Parcours utilisateur cle

### Checkout (api/checkout.php)
1. L'utilisateur remplit le formulaire sur `checkout.html` (email, adresse, etc.)
2. Le JS envoie un POST a `api/checkout.php` avec les articles du panier (localStorage)
3. Le backend genere un `order_id` (format `PC-YYYYMMDD-XXXXXX`)
4. Insert dans `commandes` avec `user_id` si connecte (NULL sinon = guest checkout)
5. Insert les items dans `commande_items`
6. Envoie un email de confirmation HTML (template sombre, recap articles, infos IBAN)
7. La page affiche la confirmation avec les infos de virement et un rappel de l'email envoye
- **Paiement** : virement bancaire uniquement (IBAN BE65 0018 1297 8496, BIC GEBABEBB, communication = order_id)
- **Livraison** : gratuite des 100 EUR, sinon 5.90 EUR. Belgique uniquement.

### Mes commandes (mes-commandes.html)
1. Verifie l'auth via `api/auth/status.php`
2. Si non connecte : affiche bouton Google Login
3. Si connecte : fetch `api/commandes.php` et affiche les commandes en cartes
4. Chaque carte a : order_id, date, total, badge de statut colore, bouton "Details" (expand/collapse)

### Profil dropdown (js/main.js -> checkAuthStatus())
Quand l'utilisateur est connecte, le dropdown du profil contient :
- Header avec avatar, nom, email
- Lien "Administration" (si is_admin)
- Lien "Mes commandes" (tous les utilisateurs connectes)
- Bouton "Deconnexion"

### Email de confirmation (dans api/checkout.php -> sendConfirmationEmail())
- Template HTML table-based avec inline styles (compatibilite email)
- Design sombre (bg #0a0a0a, cards #181818, texte blanc/gris)
- Contenu : logo PROJET CRIMP., checkmark vert, recap articles (nom, taille, qte, prix), sous-total/livraison/total, infos IBAN, adresse de livraison
- Headers : From `noreply@{HTTP_HOST}`, Reply-To `contact@{HTTP_HOST}`
- Appele dans un try/catch : si l'email echoue, la commande reste valide

### Admin commandes (admin/commandes.php)
- Liste toutes les commandes avec client, total, statut, date
- Bouton detail (ouvre une modal via `api/admin/commandes.php?id=X`)
- Bouton changer statut (modal avec select)
- Checkbox "Masquer les commandes annulees" (filtre cote client via JS)

## Design

- **Theme** : sombre (fond #0a0a0a, cartes #111111/#181818, bordures #2a2a2a)
- **Texte** : blanc #ffffff (primaire), gris #888888 (secondaire)
- **Accent** : blanc #ffffff (boutons), rose #e75480 (accent dans le CSS principal)
- **Succes** : vert #4ade80
- **Erreur** : rouge #f87171
- **Warning** : jaune #facc15
- **Info** : bleu #60a5fa
- **Grille de fond** : lignes rgba(255,255,255,0.07) en 50x50px (sur toutes les pages standalone)
- **Boutons** : border-radius 100px (pills), uppercase, letter-spacing
- **Cartes** : border-radius 20px, border 1px solid #2a2a2a
- **Police** : Space Grotesk (Google Fonts)

## Workflow de Developpement

1. Developper localement : `php -S localhost:8000`
2. Tester les changements
3. Commit et push : `git add ... && git commit -m "..." && git push origin main`
4. Deploiement automatique via GitHub Actions

## Acces SSH

```bash
ssh utilisateur@gates.luthi.eu
```
La base de donnees est accessible depuis gates.luthi.eu.

## Fichiers Importants

| Fichier | Description |
|---------|-------------|
| `api/config.php` | Config centrale (DB, OAuth, fonctions utilitaires) |
| `api/checkout.php` | Checkout + email de confirmation |
| `api/commandes.php` | API commandes utilisateur |
| `api/admin/commandes.php` | API admin commandes (detail, statut) |
| `index.html` | Page principale du site |
| `checkout.html` | Page checkout |
| `mes-commandes.html` | Page historique commandes |
| `js/main.js` | Logique frontend (produits, panier, auth, modal) |
| `css/style.css` | Styles du site public |
| `admin/css/admin.css` | Styles du dashboard admin |
| `admin/commandes.php` | Page admin commandes |
| `admin/js/commandes.js` | JS admin commandes (detail, statut, filtre annulees) |
| `database/migration_checkout.sql` | Schema actuel des tables commandes |

## Notes de Securite

- Requetes preparees PDO (anti SQL injection)
- Google OAuth 2.0 (pas de gestion de mots de passe)
- `.env` exclu du versioning
- CORS configure dans `config.php`
- Verification `is_admin` pour l'acces admin
- `htmlspecialchars()` pour l'echappement dans les emails
- `escapeHtml()` cote client pour les donnees affichees

## Points d'attention

- La table `commande_items` utilise les colonnes `product_name`, `quantity`, `price` (pas les noms de l'ancien schema `produit_id`, `quantite`, `prix_unitaire`)
- Le checkout fonctionne en mode connecte (user_id lie) ET guest (user_id = NULL)
- L'API `api/commandes.php` cherche par `user_id` OU `email` pour retrouver les commandes meme si elles ont ete passees avant la liaison user_id
- Les emails utilisent `mail()` PHP natif - necessite un serveur mail configure
- Le panier est stocke dans `localStorage` (`projetcrimp_cart`)

---
*Derniere mise a jour : Janvier 2026*
