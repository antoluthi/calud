# Guide d'utilisation - Interface Admin

## 🎯 Accès à l'interface admin

**URL** : `https://antonin.luthi.eu/admin`

**Prérequis** :
- Être connecté avec votre compte Google
- Avoir les droits administrateur (colonne `is_admin = 1` dans la table `users`)

---

## ✨ Gestion des produits

### Ajouter un nouveau produit

1. Connectez-vous à `https://antonin.luthi.eu/admin/produits.php`
2. Cliquez sur le bouton **➕ Nouveau Produit**
3. Remplissez le formulaire :
   - **Nom du produit** *(obligatoire)* : Ex. "Poutre d'escalade Pro"
   - **Prix** *(obligatoire)* : Ex. 89.99
   - **Description** : Description détaillée du produit
   - **URL de l'image** : Chemin vers l'image (ex: `images/produit.jpg`)
   - **Caractéristiques** : Une caractéristique par ligne
     ```
     ✓ Facile à installer
     ✓ Matériaux durables
     ✓ Différentes prises
     ```
   - **Produit actif** : Coché = visible sur le site public
4. Cliquez sur **Créer le produit**

### Modifier un produit existant

1. Dans la liste des produits, cliquez sur l'icône **✏️** (modifier)
2. Modifiez les champs souhaités
3. Cliquez sur **Enregistrer**

### Mettre en ligne / Hors ligne un produit

- **🟢** (bouton vert) = Activer le produit → Il apparaîtra sur le site public
- **🔴** (bouton rouge) = Désactiver le produit → Il sera caché du site public

**Note** : Seuls les produits avec le statut "Actif" sont affichés sur `https://antonin.luthi.eu`

### Supprimer un produit

1. Cliquez sur l'icône **🗑️** (supprimer)
2. Confirmez la suppression
3. **⚠️ Attention** : Cette action est irréversible

---

## 🔧 Configuration requise sur le serveur

Pour que l'interface admin fonctionne, assurez-vous que :

### 1. Base de données configurée

Le fichier `.env` doit exister à la racine du serveur avec :

```env
# Configuration de la base de données
DB_HOST=localhost
DB_NAME=site_escalade
DB_USER=votre_utilisateur_mysql
DB_PASS=votre_mot_de_passe_mysql

# Configuration Google OAuth
GOOGLE_CLIENT_ID=votre_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=votre_client_secret

# URL de base
BASE_URL=https://antonin.luthi.eu
```

### 2. Base de données initialisée

Exécutez le fichier `database/schema.sql` pour créer les tables :

```bash
mysql -u root -p site_escalade < database/schema.sql
```

### 3. Compte admin créé

Après la première connexion Google OAuth, votre compte sera créé dans la table `users`. Pour devenir admin, exécutez :

```sql
UPDATE users SET is_admin = 1 WHERE email = 'votre@email.com';
```

---

## 🧪 Tester le système

### Test 1 : Connexion admin
1. Allez sur `https://antonin.luthi.eu`
2. Connectez-vous avec Google
3. Accédez à `https://antonin.luthi.eu/admin`
4. Si vous êtes redirigé → Vous n'avez pas les droits admin (voir point 3 ci-dessus)
5. Si vous voyez le dashboard → ✅ Connexion réussie

### Test 2 : Créer un produit
1. Allez sur `https://antonin.luthi.eu/admin/produits.php`
2. Cliquez sur **➕ Nouveau Produit**
3. Remplissez et soumettez le formulaire
4. Vérifiez que le produit apparaît dans la liste

### Test 3 : Activation/Désactivation
1. Créez un produit et cochez "Produit actif"
2. Ouvrez `https://antonin.luthi.eu` dans un nouvel onglet
3. Vérifiez que le produit est visible dans la section "Produits"
4. Retournez sur l'admin et cliquez sur le bouton 🔴
5. Rechargez `https://antonin.luthi.eu`
6. Le produit ne doit plus être visible → ✅ Système fonctionnel

---

## 🐛 Dépannage

### Erreur : "Non authentifié"
→ Connectez-vous via Google OAuth sur la page d'accueil

### Erreur : "Accès refusé: droits administrateur requis"
→ Votre compte n'a pas les droits admin. Exécutez la requête SQL du point 3

### Erreur : "Erreur de connexion à la base de données"
→ Vérifiez le fichier `.env` et les credentials de la base de données

### Les produits ne s'affichent pas sur le site public
→ Vérifiez que :
- Le produit est bien coché comme "Actif" (badge vert dans l'admin)
- La base de données contient des produits avec `actif = 1`
- L'API `/api/produits.php` est accessible

---

## 📁 Architecture des fichiers

```
├── admin/
│   ├── produits.php          # Interface de gestion des produits
│   ├── js/produits.js         # JavaScript pour le CRUD
│   └── css/admin.css          # Styles de l'interface admin
│
├── api/
│   ├── produits.php           # API publique (GET produits actifs)
│   └── admin/
│       └── produits.php       # API admin (CRUD complet)
│
└── database/
    └── schema.sql             # Schéma de la base de données
```

---

## ✅ Checklist de déploiement

- [ ] Base de données créée
- [ ] Fichier `.env` configuré sur le serveur
- [ ] Schema SQL exécuté
- [ ] Compte admin configuré (`is_admin = 1`)
- [ ] Test de connexion admin réussi
- [ ] Test création/modification de produit réussi
- [ ] Test activation/désactivation réussi
- [ ] Produits visibles sur le site public

---

**Besoin d'aide ?** Consultez la documentation complète dans `BACKEND.md`
