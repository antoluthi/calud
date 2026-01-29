# Guide de l'Interface d'Administration

## Acces a l'interface admin

### 1. Se connecter

1. Allez sur `https://antonin.luthi.eu`
2. Cliquez sur l'icone de profil puis **"Se connecter avec Google"**
3. Autorisez la connexion

### 2. Obtenir les droits administrateur

Par defaut, les nouveaux utilisateurs ne sont PAS administrateurs.

```sql
-- Via MySQL
USE site_escalade;
UPDATE users SET is_admin = 1 WHERE email = 'votre-email@example.com';
```

### 3. Acceder au panel

Une fois admin, le lien **"Administration"** apparait dans le dropdown du profil.
URL directe : `https://antonin.luthi.eu/admin/`

---

## Dashboard (admin/index.php)

Vue d'ensemble avec les statistiques : nombre de produits, commandes, clients.

## Gestion des Produits (admin/produits.php)

### Creer un produit
1. Cliquez sur **"Nouveau Produit"**
2. Remplissez : nom, prix, description, URL image, caracteristiques (une par ligne)
3. Cochez "Produit actif" pour le rendre visible
4. Cliquez sur **"Creer le produit"**

### Modifier un produit
Cliquez sur l'icone crayon, modifiez, puis **"Enregistrer"**.

### Activer/Desactiver
Bouton vert = activer, bouton rouge = desactiver. Les produits inactifs sont caches du site public.

### Supprimer
Cliquez sur l'icone poubelle. Action irreversible.

## Gestion des Commandes (admin/commandes.php)

### Liste des commandes
Tableau avec : ID, client (nom + email + avatar), total, statut, date, actions.

### Statuts disponibles

| Statut | Badge | Description |
|--------|-------|-------------|
| En attente (pending) | Jaune | Commande recue, paiement non confirme |
| Payee (paid) | Bleu | Paiement recu |
| En preparation (processing) | Bleu | Commande en cours de preparation |
| Expediee (shipped) | Vert | Colis envoye |
| Livree (delivered) | Vert | Colis recu par le client |
| Annulee (cancelled) | Rouge | Commande annulee |

**Workflow recommande :** En attente -> Payee -> En preparation -> Expediee -> Livree

### Voir les details
Cliquez sur l'icone oeil pour ouvrir une modal avec : infos client, articles commandes (nom, quantite, prix), total.

### Changer le statut
Cliquez sur l'icone fleche circulaire, selectionnez le nouveau statut, puis **"Enregistrer"**.

### Masquer les commandes annulees
Cochez la case **"Masquer les commandes annulees"** en haut du tableau pour cacher les commandes avec le statut "Annulee".

## Gestion des Messages (admin/messages.php)

Messages recus via le formulaire de contact. Vous pouvez les marquer comme lus ou les supprimer.

## Gestion Newsletter (admin/newsletter.php)

- Voir la liste des abonnes
- Envoyer un email a tous les abonnes (sujet + message)
- Supprimer un abonne

## Gestion Clients (admin/clients.php)

Liste de tous les utilisateurs inscrits via Google OAuth.

---

## Paiement

Le site utilise le **virement bancaire** :
- **IBAN** : BE65 0018 1297 8496
- **BIC** : GEBABEBB
- **Communication** : le numero de commande (format AL-YYYYMMDD-XXXXXX)

Un email de confirmation est envoye automatiquement au client avec ces informations apres chaque commande.

---

## Depannage

**"Non authentifie"** : Connectez-vous via Google sur la page d'accueil.

**"Acces refuse"** : Votre compte n'a pas `is_admin = 1`. Executez la requete SQL ci-dessus.

**"Erreur base de donnees"** : Verifiez le fichier `.env` dans `api/`.

**Les produits ne s'affichent pas** : Verifiez que le produit est marque comme "Actif" dans l'admin.

**Le detail d'une commande ne s'affiche pas** : Verifiez que la table `commande_items` utilise les colonnes `product_name`, `quantity`, `price` (pas l'ancien schema).
