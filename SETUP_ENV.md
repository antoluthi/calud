# Configuration du fichier .env sur le serveur

Ce guide explique comment créer le fichier `.env` sur votre serveur pour protéger vos credentials.

## Pourquoi utiliser .env ?

- ✅ Vos credentials ne sont **jamais** dans Git
- ✅ Le fichier `config.php` peut être mis à jour sans écraser vos credentials
- ✅ Sécurisé: le .env reste sur le serveur uniquement

## Étape 1: Se connecter au serveur

### Via SSH (recommandé)

```bash
ssh -i ~/.ssh/github_deploy votre_user@votre_serveur
```

### Via Filezilla

Connectez-vous avec Filezilla et naviguez vers le dossier `api/`

## Étape 2: Créer le fichier .env

### Via SSH

```bash
# Aller dans le dossier api
cd /var/www/html/api  # Ou votre chemin

# Créer le fichier .env
nano .env
```

Collez ce contenu (en remplaçant par vos vraies valeurs):

```env
# Configuration de la base de données
DB_HOST=localhost
DB_NAME=nom_de_votre_base
DB_USER=votre_utilisateur_mysql
DB_PASS=votre_mot_de_passe_mysql

# Configuration Google OAuth
GOOGLE_CLIENT_ID=votre-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=votre-client-secret

# URL de base de l'application
BASE_URL=https://votre-domaine.com
```

Sauvegardez:
- **Ctrl+X**, puis **Y**, puis **Entrée**

Sécurisez les permissions:
```bash
chmod 600 .env
```

### Via Filezilla

1. Dans Filezilla, allez dans le dossier `api/`
2. Clic droit dans l'espace vide → **"Create new file"** (Créer un nouveau fichier)
3. Nommez-le exactement: `.env`
4. Clic droit sur `.env` → **"View/Edit"**
5. Collez le contenu ci-dessus (avec vos vraies valeurs)
6. Sauvegardez et fermez

## Étape 3: Vérifier les credentials

Vérifiez que vous avez les bonnes informations:

### Base de données
Demandez à votre père ou vérifiez dans le panneau d'administration:
- `DB_HOST`: généralement `localhost`
- `DB_NAME`: le nom de la base créée
- `DB_USER`: votre utilisateur MySQL
- `DB_PASS`: le mot de passe MySQL

### Google OAuth
Vous avez déjà ces infos:
- `GOOGLE_CLIENT_ID`: votre Client ID Google
- `GOOGLE_CLIENT_SECRET`: votre Client Secret Google

### URL
- `BASE_URL`: l'URL complète de votre site (ex: `https://monsite.com`)

## Étape 4: Tester

Une fois le fichier `.env` créé et configuré sur le serveur:

1. Visitez votre site
2. Cliquez sur "Se connecter avec Google"
3. Ça devrait maintenant fonctionner ! ✅

## Important

- ⚠️ Ne créez **JAMAIS** de fichier `.env` dans votre projet local Git
- ✅ Le `.env` existe **uniquement sur le serveur**
- ✅ Git ignore déjà `.env` (voir `.gitignore`)
- ✅ Vous pouvez pusher autant que vous voulez, le `.env` ne sera jamais écrasé

## Exemple de fichier .env complet

```env
# Configuration de la base de données
DB_HOST=localhost
DB_NAME=site_escalade_db
DB_USER=escalade_user
DB_PASS=MonMotDePasseSuperSecret123!

# Configuration Google OAuth
GOOGLE_CLIENT_ID=123456789-abcdefgh.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-abcdefghijklmnop

# URL de base
BASE_URL=https://al-escalade.com
```

Remplacez par vos vraies valeurs !
