# AL - Site Vitrine Équipement d'Escalade

Site vitrine en mode sombre pour poutres de suspension portables.

## 📁 Structure du projet

```
site-escalade/
├── index.html          # Page principale
├── css/
│   └── style.css       # Styles (mode sombre)
├── js/
│   └── main.js         # JavaScript (chargement des produits)
├── data/
│   └── produits.json   # Données des produits (MODIFIER ICI)
├── images/             # Images des produits
└── README.md           # Ce fichier
```

## ✏️ Comment modifier les produits

**Fichier à éditer** : `data/produits.json`

Exemple de produit :
```json
{
    "nom": "Nom du produit",
    "prix": "99.99 €",
    "description": "Description du produit",
    "image": "images/mon-produit.jpg",
    "caracteristiques": [
        "Caractéristique 1",
        "Caractéristique 2"
    ]
}
```

**Notes** :
- Laissez `"image": ""` si pas d'image (emoji 🧗 par défaut)
- Maximum 5 produits recommandé
- Les changements sont automatiques au rafraîchissement de la page

## 🖼️ Ajouter des images

1. Placez vos photos dans le dossier `images/`
2. Dans `produits.json`, référencez : `"image": "images/nom-de-votre-photo.jpg"`

## 🚀 Déploiement

### Méthode 1 : Git + SSH (recommandé)
```bash
git add .
git commit -m "Description des changements"
git push origin main
```

Puis sur le serveur :
```bash
cd /chemin/vers/site
git pull origin main
```

### Méthode 2 : FTP/Filezilla
Uploadez tous les fichiers vers le serveur via Filezilla.

## 🌐 Tester localement

Ouvrez simplement `index.html` dans votre navigateur.

**Note** : Pour que le JSON se charge correctement, utilisez un serveur local :
```bash
# Python 3
python -m http.server 8000

# Puis ouvrez : http://localhost:8000
```

## 📝 Personnalisation

### Changer les couleurs
Éditez `css/style.css` lignes 2-9 (variables CSS)

### Changer l'email de contact
Éditez `index.html` ligne 39

### Changer le logo "AL"
Éditez `index.html` ligne 12
