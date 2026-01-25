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
| `SFTP_PASSWORD` | Votre mot de passe SFTP | `mon_mot_de_passe` |
| `SFTP_SERVER` | L'adresse de votre serveur SFTP | `ftp.monserveur.com` ou `192.168.1.100` |
| `SFTP_PORT` | Le port SFTP (généralement 22) | `22` |
| `SFTP_REMOTE_PATH` | Le chemin sur votre serveur où déployer les fichiers | `/var/www/html` ou `/home/user/public_html` |

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

- ✅ Les mots de passe sont stockés de manière sécurisée dans GitHub Secrets
- ✅ Ils ne sont jamais visibles dans les logs ou le code
- ✅ Seuls les propriétaires du repository peuvent les voir/modifier

## Alternative: Déploiement par clé SSH

Pour plus de sécurité, vous pouvez utiliser une clé SSH au lieu d'un mot de passe. Si vous souhaitez cette option, je peux modifier le workflow pour utiliser `ssh_private_key` au lieu de `password`.

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
