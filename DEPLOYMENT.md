# Guide de Déploiement Automatique SFTP

Ce document explique comment configurer le déploiement automatique de votre site vers votre serveur via SFTP en utilisant GitHub Actions.

## Configuration des Secrets GitHub

Pour que le workflow fonctionne, vous devez configurer les secrets suivants dans votre repository GitHub:

### Étapes:

1. Allez sur votre repository GitHub: https://github.com/antoluthi/calud
2. Cliquez sur **Settings** (Paramètres)
3. Dans le menu de gauche, cliquez sur **Secrets and variables** > **Actions**
4. Cliquez sur **New repository secret**
5. Ajoutez les secrets suivants un par un:

### Secrets requis:

| Nom du Secret | Description | Exemple |
|--------------|-------------|---------|
| `SFTP_USERNAME` | Votre nom d'utilisateur SFTP (celui que vous utilisez dans Filezilla) | `mon_username` |
| `SSH_PRIVATE_KEY` | Votre clé privée SSH (voir section "Génération clé SSH" ci-dessous) | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `SFTP_SERVER` | L'adresse de votre serveur SFTP | `ftp.monserveur.com` ou `192.168.1.100` |
| `SFTP_PORT` | Le port SFTP (généralement 22) | `22` |
| `SFTP_REMOTE_PATH` | Le chemin sur votre serveur où déployer les fichiers | `/var/www/html` ou `/home/user/public_html` |

### Génération de la clé SSH (Plus sécurisé que le mot de passe)

1. Sur votre ordinateur local, générez une paire de clés SSH:
```bash
ssh-keygen -t ed25519 -C "github-deploy" -f ~/.ssh/github_deploy
```
Appuyez sur Entrée pour le passphrase (laissez vide).

2. Copiez la clé **publique** sur votre serveur:
```bash
# Option A: Automatiquement
ssh-copy-id -i ~/.ssh/github_deploy.pub votre_user@votre-serveur.com

# Option B: Manuellement - ajoutez le contenu de la clé publique à ~/.ssh/authorized_keys sur le serveur
cat ~/.ssh/github_deploy.pub
# Puis collez ce contenu dans ~/.ssh/authorized_keys sur votre serveur
```

3. Récupérez la clé **privée** pour le secret GitHub:
```bash
cat ~/.ssh/github_deploy
```
Copiez **tout le contenu** (y compris `-----BEGIN OPENSSH PRIVATE KEY-----` et `-----END OPENSSH PRIVATE KEY-----`)

4. Ajoutez ce contenu comme secret `SSH_PRIVATE_KEY` dans GitHub

## Comment ça fonctionne?

Une fois les secrets configurés, le déploiement se fait automatiquement:

1. **Automatique**: Chaque fois que vous pushez des commits sur la branche `main`, le workflow se déclenche
2. **Manuel**: Vous pouvez aussi lancer le déploiement manuellement:
   - Allez dans l'onglet **Actions** de votre repository
   - Sélectionnez "Déploiement SFTP vers Serveur"
   - Cliquez sur **Run workflow**

## Structure déployée

Tous les fichiers suivants seront déployés sur votre serveur:
```
index.html
css/style.css
js/main.js
data/produits.json
images/
README.md
```

Les fichiers suivants **ne seront pas** déployés (grâce au .gitignore):
- `.git/`
- `.github/`
- Fichiers de développement (.vscode, etc.)

## Vérification du déploiement

Après chaque déploiement:
1. Allez dans l'onglet **Actions** de votre repository
2. Vous verrez l'exécution du workflow avec un ✅ (succès) ou ❌ (échec)
3. Cliquez dessus pour voir les détails et logs

## Utilisation quotidienne

Workflow typique:
```bash
# 1. Modifier vos fichiers localement
# 2. Tester localement
# 3. Commiter et pusher
git add .
git commit -m "Mise à jour du site"
git push origin main

# 4. Le déploiement se fait automatiquement!
# 5. Vérifiez sur votre site web
```

## Sécurité

- ✅ Utilise l'authentification SSH (plus sécurisée qu'un mot de passe)
- ✅ La clé privée est stockée de manière sécurisée dans GitHub Secrets
- ✅ Les secrets ne sont jamais visibles dans les logs ou le code
- ✅ Seuls les propriétaires du repository peuvent les voir/modifier
- ✅ La clé SSH peut être révoquée facilement si compromise

## Problèmes courants

### Le workflow échoue
- Vérifiez que tous les secrets sont correctement configurés
- Vérifiez que le serveur SFTP est accessible
- Consultez les logs dans l'onglet Actions

### Les fichiers ne s'affichent pas
- Vérifiez le `SFTP_REMOTE_PATH` (doit pointer vers le bon dossier web)
- Vérifiez les permissions des fichiers sur le serveur

## Prochaines étapes

Une fois le déploiement configuré, nous pourrons:
1. ✅ Déploiement automatique (vous êtes ici)
2. 🔲 Améliorer l'interface graphique
3. 🔲 Ajouter une base de données
4. 🔲 Intégrer la connexion Google
5. 🔲 Autres fonctionnalités
