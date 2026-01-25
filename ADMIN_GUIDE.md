# Guide de l'Interface d'Administration

Ce guide explique comment accéder et utiliser l'interface d'administration de votre site AL Escalade.

## 🎯 Fonctionnalités de l'Admin

L'interface d'administration vous permet de:

### 📊 Dashboard
- Vue d'ensemble des statistiques (produits, commandes, clients)
- Aperçu des dernières commandes

### 📦 Gestion des Produits
- ➕ Créer de nouveaux produits
- ✏️ Modifier des produits existants
- 🟢 Activer/Désactiver des produits
- 🗑️ Supprimer des produits
- Gérer: nom, prix, description, image, caractéristiques

### 🛒 Gestion des Commandes
- 📋 Voir toutes les commandes
- 👁️ Voir les détails d'une commande (client, articles)
- 🔄 Changer le statut des commandes:
  - En attente
  - Confirmée
  - Expédiée
  - Livrée
  - Annulée

---

## 🚀 Accéder à l'interface admin

### 1. Se connecter au site

1. Allez sur votre site: `https://votre-domaine.com`
2. Cliquez sur **"Se connecter avec Google"**
3. Autorisez la connexion avec votre compte Google

### 2. Obtenir les droits administrateur

**⚠️ Important:** Par défaut, les nouveaux utilisateurs ne sont PAS administrateurs. Vous devez vous donner les droits manuellement.

#### Via phpMyAdmin ou interface MySQL:

1. Connectez-vous à votre base de données (phpMyAdmin, Adminer, etc.)
2. Sélectionnez la base de données `site_escalade`
3. Ouvrez la table `users`
4. Trouvez votre utilisateur (cherchez par email)
5. Éditez la ligne et changez `is_admin` de `0` à `1`
6. Sauvegardez

#### Via ligne de commande (SSH):

```bash
# Connectez-vous à votre serveur
ssh votre_user@votre_serveur

# Connectez-vous à MySQL
mysql -u votre_user -p

# Utilisez la base de données
USE site_escalade;

# Donnez les droits admin à votre email
UPDATE users SET is_admin = TRUE WHERE email = 'votre-email@example.com';

# Vérifiez
SELECT email, is_admin FROM users;

# Quittez
exit;
```

#### Via la migration SQL (sur une base existante):

Si vous avez une base de données existante sans la colonne `is_admin`, exécutez d'abord:

```bash
mysql -u votre_user -p site_escalade < database/migration_add_admin.sql
```

Puis donnez les droits comme ci-dessus.

### 3. Accéder au panel admin

Une fois que vous avez les droits admin:

1. Allez sur: `https://votre-domaine.com/admin/`
2. Vous verrez le dashboard admin avec toutes les statistiques
3. Utilisez le menu de gauche pour naviguer

**Si vous n'êtes pas admin:** Vous serez automatiquement redirigé vers la page d'accueil.

---

## 📚 Guide d'utilisation

### Gérer les Produits

#### Créer un nouveau produit

1. Allez dans **Produits** (menu de gauche)
2. Cliquez sur **"➕ Nouveau Produit"**
3. Remplissez le formulaire:
   - **Nom**: Le nom du produit (ex: "Poutre Portable Pro")
   - **Prix**: Le prix en euros (ex: 89.99)
   - **Description**: Texte de description
   - **URL de l'image**: Chemin vers l'image (ex: `images/produit1.jpg`)
   - **Caractéristiques**: Une par ligne, avec ✓ au début
     ```
     ✓ Supports jusqu'à 150kg
     ✓ Installation facile
     ✓ Bois de hêtre ultra-résistant
     ```
   - **Produit actif**: Cochez pour que le produit soit visible sur le site
4. Cliquez sur **"Créer le produit"**

#### Modifier un produit

1. Dans la liste des produits, cliquez sur l'icône ✏️ à droite du produit
2. Modifiez les informations
3. Cliquez sur **"Enregistrer"**

#### Activer/Désactiver un produit

- Cliquez sur l'icône 🔴 (rouge) pour désactiver un produit actif
- Cliquez sur l'icône 🟢 (vert) pour activer un produit inactif
- Les produits inactifs ne sont **pas visibles** sur le site public

#### Supprimer un produit

1. Cliquez sur l'icône 🗑️ à droite du produit
2. Confirmez la suppression
3. **⚠️ Attention:** Cette action est irréversible !

---

### Gérer les Commandes

#### Voir toutes les commandes

1. Allez dans **Commandes** (menu de gauche)
2. Vous verrez la liste de toutes les commandes avec:
   - Numéro de commande
   - Nom et email du client
   - Total de la commande
   - Statut actuel
   - Date de création

#### Voir les détails d'une commande

1. Cliquez sur l'icône 👁️ à droite d'une commande
2. Vous verrez:
   - Informations du client (nom, email, avatar)
   - Liste des articles commandés
   - Quantités et prix
   - Total de la commande

#### Changer le statut d'une commande

1. Cliquez sur l'icône 🔄 à droite d'une commande
2. Sélectionnez le nouveau statut:
   - **En attente**: Commande reçue, en attente de traitement
   - **Confirmée**: Commande confirmée et en préparation
   - **Expédiée**: Commande envoyée au client
   - **Livrée**: Commande reçue par le client
   - **Annulée**: Commande annulée
3. Cliquez sur **"Enregistrer"**

**Workflow recommandé:**
```
En attente → Confirmée → Expédiée → Livrée
```

---

## 🔐 Sécurité

### Qui peut accéder à l'admin ?

- Seuls les utilisateurs avec `is_admin = 1` dans la base de données
- Connexion obligatoire via Google OAuth
- Vérification automatique à chaque page

### Donner les droits admin à d'autres utilisateurs

1. L'utilisateur doit d'abord se connecter au site (au moins une fois)
2. Trouvez son email dans la table `users`
3. Changez `is_admin` à `1` pour cet utilisateur

```sql
UPDATE users SET is_admin = TRUE WHERE email = 'utilisateur@example.com';
```

### Retirer les droits admin

```sql
UPDATE users SET is_admin = FALSE WHERE email = 'utilisateur@example.com';
```

---

## 🎨 Interface

L'interface admin utilise le même design dark mode que votre site:
- ✅ Mode sombre élégant
- ✅ Responsive (fonctionne sur mobile et tablette)
- ✅ Navigation intuitive avec sidebar
- ✅ Modals pour les actions (ajouter, modifier)
- ✅ Alertes de confirmation pour les actions importantes

---

## ❓ FAQ

### Je n'ai pas accès à l'interface admin

1. Vérifiez que vous êtes bien connecté avec Google
2. Vérifiez que `is_admin = 1` pour votre utilisateur dans la base de données
3. Déconnectez-vous et reconnectez-vous

### Les changements ne s'appliquent pas sur le site

- Pour les produits: Ils sont maintenant chargés depuis la base de données (plus depuis `data/produits.json`)
- Actualisez votre cache navigateur (Ctrl+F5)

### Je veux ajouter des images aux produits

1. Uploadez vos images dans le dossier `images/` via FTP/SFTP
2. Dans l'admin, entrez le chemin relatif: `images/nom-image.jpg`
3. Les images doivent être au format JPG, PNG ou WebP

### Comment créer une commande de test ?

Pour l'instant, les commandes doivent être créées via l'API. Le système de panier e-commerce sera implémenté dans une prochaine version.

---

## 🚀 Prochaines fonctionnalités

Fonctionnalités prévues pour l'admin:
- 💳 Intégration paiement (Stripe/PayPal)
- 📧 Envoi d'emails automatiques aux clients
- 💰 Gestion des bons de réduction
- 📊 Graphiques et statistiques avancées
- 🖼️ Upload d'images directement depuis l'admin
- 👥 Gestion détaillée des clients
- 📦 Gestion des stocks
- 🚚 Intégration transporteurs (tracking)

---

## 📞 Support

Pour toute question sur l'interface admin, consultez:
- Ce guide (ADMIN_GUIDE.md)
- La documentation backend (BACKEND.md)
- Les logs d'erreur sur votre serveur

---

**Bon courage avec votre site e-commerce ! 🚀**
