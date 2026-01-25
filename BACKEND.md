# Documentation Backend - Site AL Escalade

Ce document explique comment configurer et utiliser le backend PHP avec MySQL et Google OAuth.

## Architecture

Le backend est construit avec:
- **PHP 7.4+** avec PDO pour la base de données
- **MySQL/MariaDB** pour stocker les utilisateurs et produits
- **Google OAuth 2.0** pour l'authentification
- **API REST** en JSON pour la communication avec le frontend

## Structure des fichiers

```
api/
├── config.php              # Configuration générale et fonctions utilitaires
├── auth/
│   ├── login.php          # Redirection vers Google OAuth
│   ├── callback.php       # Callback OAuth après autorisation
│   ├── logout.php         # Déconnexion
│   └── status.php         # Vérifier l'état de connexion
└── users/
    └── me.php             # Récupérer l'utilisateur connecté

database/
└── schema.sql             # Schéma de la base de données
```

## Installation

### 1. Prérequis serveur

Assurez-vous que votre serveur a:
- PHP 7.4 ou supérieur
- MySQL 5.7+ ou MariaDB 10.2+
- Extensions PHP activées:
  - `pdo`
  - `pdo_mysql`
  - `curl`
  - `json`
  - `session`

### 2. Configuration de la base de données

#### Créer la base de données

Connectez-vous à MySQL et exécutez:

```bash
mysql -u root -p < database/schema.sql
```

Ou via phpMyAdmin:
1. Créez une nouvelle base de données nommée `site_escalade`
2. Importez le fichier `database/schema.sql`

#### Configurer les credentials

Éditez le fichier `api/config.php` et mettez à jour:

```php
define('DB_HOST', 'localhost');          // Adresse du serveur MySQL
define('DB_NAME', 'site_escalade');      // Nom de la base de données
define('DB_USER', 'votre_utilisateur');  // Utilisateur MySQL
define('DB_PASS', 'votre_mot_de_passe'); // Mot de passe MySQL
```

### 3. Configuration Google OAuth

#### Obtenir les credentials

1. Allez sur https://console.cloud.google.com/
2. Créez un nouveau projet (ou sélectionnez-en un)
3. **APIs & Services** → **Credentials** → **Create Credentials** → **OAuth 2.0 Client ID**
4. Configurez l'écran de consentement OAuth si demandé
5. Type: **Web application**
6. Ajoutez vos URIs:

**Authorized JavaScript origins:**
```
http://localhost:8000
https://votre-domaine.com
```

**Authorized redirect URIs:**
```
http://localhost:8000/api/auth/callback.php
https://votre-domaine.com/api/auth/callback.php
```

7. Notez votre **Client ID** et **Client Secret**

#### Configurer dans l'application

Éditez `api/config.php`:

```php
define('GOOGLE_CLIENT_ID', 'VOTRE_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'VOTRE_CLIENT_SECRET');
define('BASE_URL', 'https://votre-domaine.com'); // Adapter selon votre environnement
```

### 4. Configuration du serveur web

#### Apache (.htaccess)

Si vous utilisez Apache, créez un fichier `.htaccess` dans le dossier `api/`:

```apache
# Activer le rewrite engine
RewriteEngine On

# Permettre l'accès depuis n'importe quelle origine (CORS)
Header set Access-Control-Allow-Origin "*"
Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header set Access-Control-Allow-Headers "Content-Type, Authorization"

# Autoriser les sessions
php_flag session.auto_start off
php_flag session.use_cookies on
php_flag session.use_only_cookies on
```

#### Nginx

Ajoutez dans votre configuration nginx:

```nginx
location /api/ {
    add_header Access-Control-Allow-Origin *;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS";
    add_header Access-Control-Allow-Headers "Content-Type, Authorization";

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

## API Endpoints

### Authentification

#### `GET /api/auth/login.php`
Redirige l'utilisateur vers la page de connexion Google.

**Réponse:** Redirection HTTP vers Google OAuth

---

#### `GET /api/auth/callback.php`
Callback OAuth après autorisation Google. Ne pas appeler directement.

**Paramètres:**
- `code`: Code d'autorisation (fourni par Google)
- `state`: État CSRF (fourni par Google)

**Réponse:** Redirection vers `index.html?login=success`

---

#### `GET /api/auth/status.php`
Vérifier si l'utilisateur est connecté.

**Réponse:**
```json
{
  "authenticated": true,
  "user": {
    "id": 1,
    "email": "user@example.com",
    "name": "John Doe",
    "picture": "https://lh3.googleusercontent.com/..."
  }
}
```

Ou si non connecté:
```json
{
  "authenticated": false,
  "user": null
}
```

---

#### `GET /api/auth/logout.php`
Déconnecte l'utilisateur.

**Réponse:**
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

### Utilisateurs

#### `GET /api/users/me.php`
Récupérer les informations de l'utilisateur connecté.

**Authentification:** Requise

**Réponse:**
```json
{
  "id": 1,
  "email": "user@example.com",
  "name": "John Doe",
  "picture": "https://lh3.googleusercontent.com/..."
}
```

**Erreur (401):**
```json
{
  "error": "Non authentifié"
}
```

## Schéma de base de données

### Table `users`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT | Identifiant unique (auto-increment) |
| `google_id` | VARCHAR(255) | ID Google unique |
| `email` | VARCHAR(255) | Email de l'utilisateur |
| `name` | VARCHAR(255) | Nom complet |
| `picture` | VARCHAR(500) | URL de l'avatar |
| `created_at` | TIMESTAMP | Date de création |
| `last_login` | TIMESTAMP | Dernière connexion |

### Table `produits`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT | Identifiant unique |
| `nom` | VARCHAR(255) | Nom du produit |
| `prix` | DECIMAL(10,2) | Prix en euros |
| `description` | TEXT | Description |
| `image` | VARCHAR(500) | URL de l'image |
| `caracteristiques` | JSON | Features (tableau) |
| `actif` | BOOLEAN | Produit actif/inactif |
| `created_at` | TIMESTAMP | Date de création |
| `updated_at` | TIMESTAMP | Dernière modification |

## Sécurité

### Bonnes pratiques

1. **HTTPS en production**: Activez toujours HTTPS pour protéger les données
2. **Variables d'environnement**: Ne commitez jamais les credentials réels
3. **Sessions sécurisées**:
   ```php
   session_set_cookie_params([
       'lifetime' => 0,
       'path' => '/',
       'domain' => 'votre-domaine.com',
       'secure' => true,      // HTTPS uniquement
       'httponly' => true,    // Pas d'accès JavaScript
       'samesite' => 'Lax'    // Protection CSRF
   ]);
   ```
4. **SQL Injection**: Utilisez toujours des prepared statements (déjà fait avec PDO)
5. **XSS**: Échappez toujours les données affichées (JSON encode les données)

### Protection CSRF

Le système utilise un `state` unique pour chaque requête OAuth:

```php
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
// Vérification dans le callback
if ($_GET['state'] !== $_SESSION['oauth_state']) {
    die('Erreur CSRF');
}
```

## Tests

### Test local

1. Démarrer un serveur PHP local:
```bash
php -S localhost:8000
```

2. Ouvrir http://localhost:8000 dans votre navigateur

3. Tester le flux de connexion:
   - Cliquer sur "Se connecter avec Google"
   - Autoriser l'application
   - Vérifier que vous êtes redirigé et connecté
   - Vérifier que votre profil s'affiche
   - Cliquer sur "Déconnexion"

### Test de l'API

Avec curl:

```bash
# Vérifier le statut (non connecté)
curl http://localhost:8000/api/auth/status.php

# Vérifier le statut (connecté - avec cookies de session)
curl -b cookies.txt http://localhost:8000/api/auth/status.php

# Récupérer l'utilisateur connecté
curl -b cookies.txt http://localhost:8000/api/users/me.php
```

## Débogage

### Activer les erreurs PHP

Dans `api/config.php`:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Logs

Les erreurs PDO sont automatiquement loggées. Pour voir les logs:

```bash
tail -f /var/log/apache2/error.log  # Apache
tail -f /var/log/nginx/error.log    # Nginx
```

### Problèmes courants

#### "Erreur de connexion à la base de données"
- Vérifier les credentials dans `config.php`
- Vérifier que MySQL est démarré
- Vérifier que la base de données existe

#### "État OAuth invalide"
- Vérifier que les cookies/sessions fonctionnent
- Vérifier que le `state` n'a pas expiré

#### "Callback OAuth ne fonctionne pas"
- Vérifier que l'URL de callback est correcte dans Google Console
- Vérifier que `REDIRECT_URI` dans `config.php` correspond

#### "CORS errors"
- Vérifier les headers dans `config.php`
- Vérifier la configuration Apache/Nginx

## Migration vers la production

### Checklist

- [ ] Modifier `DB_USER` et `DB_PASS` avec des credentials sécurisés
- [ ] Modifier `BASE_URL` avec votre domaine
- [ ] Ajouter l'URL de production dans Google OAuth Console
- [ ] Activer HTTPS
- [ ] Désactiver `display_errors` en production
- [ ] Configurer les sessions sécurisées (secure, httponly)
- [ ] Sauvegarder la base de données régulièrement
- [ ] Monitorer les logs d'erreurs

## Support

Pour toute question ou problème:
- Consultez les logs d'erreur
- Vérifiez la configuration Google OAuth Console
- Testez la connexion à la base de données

## Prochaines étapes

Fonctionnalités à implémenter:
1. ✅ Authentification Google OAuth
2. 🔲 Système de panier d'achat
3. 🔲 Gestion des commandes
4. 🔲 Interface admin pour gérer les produits
5. 🔲 Paiement en ligne (Stripe/PayPal)
6. 🔲 Emails de confirmation
