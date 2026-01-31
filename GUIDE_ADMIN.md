# Guide rapide - Interface Admin

**URL** : `https://antonin.luthi.eu/admin`

**Prerequis** : Etre connecte avec Google et avoir `is_admin = 1` dans la table `users`.

## Pages disponibles

| Page | Description |
|------|-------------|
| Dashboard | Statistiques generales |
| Produits | Creer, modifier, activer/desactiver, supprimer des produits |
| Commandes | Voir les commandes, consulter les details, changer les statuts |
| Messages | Lire et gerer les messages du formulaire de contact |
| Newsletter | Voir les abonnes et envoyer des emails |
| Clients | Liste des utilisateurs inscrits |

## Gestion des commandes

### Statuts

- **En attente** (jaune) : commande recue, en attente du virement
- **Payee** (bleu) : virement recu
- **En preparation** (bleu) : commande en cours de preparation
- **Expediee** (vert) : colis envoye
- **Livree** (vert) : colis recu
- **Annulee** (rouge) : commande annulee

### Actions

- Cliquer sur l'oeil pour voir les details (articles, quantites, prix)
- Cliquer sur la fleche pour changer le statut
- Cocher "Masquer les commandes annulees" pour filtrer la liste

## Paiement par virement

Les clients paient par virement bancaire :
- **IBAN** : BE65 0018 1297 8496
- **BIC** : GEBABEBB
- **Communication** : numero de commande (PC-YYYYMMDD-XXXXXX)

Un email de confirmation avec ces infos est envoye automatiquement au client.

## Devenir admin

```sql
UPDATE users SET is_admin = 1 WHERE email = 'votre@email.com';
```

## Configuration serveur

Le fichier `.env` dans `api/` doit contenir les credentials DB et Google OAuth.
Voir `SETUP_ENV.md` pour le guide complet.

---

Pour la documentation complete, voir `ADMIN_GUIDE.md` et `BACKEND.md`.
