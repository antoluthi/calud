# CRIMP. - Train Anywhere.

Site e-commerce pour materiel d'escalade (poutres de suspension portables).

**Production** : https://antonin.luthi.eu

## Stack

- **Frontend** : HTML5, CSS3 (theme sombre), JavaScript vanilla
- **Backend** : PHP 7.4+ avec PDO, MySQL/MariaDB
- **Auth** : Google OAuth 2.0
- **Deploiement** : GitHub Actions (SFTP automatique sur push `main`)

## Structure du projet

```
site-escalade/
├── index.html              # Page principale (vitrine, produits, contact)
├── checkout.html           # Checkout (formulaire + paiement)
├── mes-commandes.html      # Historique des commandes utilisateur
├── css/style.css           # Styles (theme sombre)
├── js/main.js              # Logique frontend
├── api/                    # API REST PHP
│   ├── config.php          # Configuration (DB, OAuth, fonctions)
│   ├── produits.php        # Liste des produits
│   ├── checkout.php        # Creation de commande + email confirmation
│   ├── commandes.php       # Commandes de l'utilisateur connecte
│   ├── messages.php        # Formulaire de contact
│   ├── newsletter.php      # Inscription newsletter
│   ├── auth/               # Authentification Google OAuth
│   └── admin/              # Endpoints admin (CRUD)
├── admin/                  # Dashboard admin (PHP)
│   ├── index.php           # Tableau de bord
│   ├── produits.php        # Gestion produits
│   ├── commandes.php       # Gestion commandes
│   ├── clients.php         # Gestion clients
│   ├── messages.php        # Messages contact
│   └── newsletter.php      # Gestion newsletter
├── database/               # Schema et migrations SQL
├── images/                 # Images produits
└── guides/                 # Guides PDF produits
```

## Installation locale

1. Cloner le repo :
   ```bash
   git clone https://github.com/antoluthi/calud.git
   cd calud
   ```

2. Creer la base de donnees et executer les migrations :
   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p site_escalade < database/migration_checkout.sql
   # (et les autres migrations dans l'ordre - voir CLAUDE.md)
   ```

3. Configurer le fichier `.env` dans le dossier `api/` :
   ```env
   DB_HOST=localhost
   DB_NAME=site_escalade
   DB_USER=root
   DB_PASS=

   GOOGLE_CLIENT_ID=votre_client_id.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=votre_client_secret

   BASE_URL=http://localhost:8000
   ```

4. Lancer le serveur local :
   ```bash
   php -S localhost:8000
   ```

5. Ouvrir http://localhost:8000

## Deploiement

Le deploiement est automatique via GitHub Actions. Chaque push sur `main` deploie les fichiers par SFTP.

Les secrets GitHub a configurer sont documentes dans `DEPLOYMENT.md`.

## Fonctionnalites

- Catalogue de produits avec modal detaillee (images, specs, video, guide PDF)
- Panier d'achat (localStorage)
- Checkout avec email de confirmation (paiement par virement bancaire)
- Historique des commandes pour les utilisateurs connectes
- Formulaire de contact
- Inscription newsletter
- Connexion Google OAuth
- Dashboard admin complet (produits, commandes, clients, messages, newsletter)

## Documentation

| Fichier | Contenu |
|---------|---------|
| `CLAUDE.md` | Reference technique complete du projet |
| `ADMIN_GUIDE.md` | Guide d'utilisation de l'interface admin |
| `BACKEND.md` | Documentation backend et API |
| `DEPLOYMENT.md` | Configuration du deploiement SFTP |
| `SETUP_ENV.md` | Guide de configuration du fichier .env |
