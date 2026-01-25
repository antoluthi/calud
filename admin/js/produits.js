/**
 * Gestion des produits - JavaScript
 */

// Ouvrir la modal pour créer un produit
function openProductModal() {
    document.getElementById('modalTitle').textContent = 'Nouveau Produit';
    document.getElementById('submitBtnText').textContent = 'Créer le produit';
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('productModal').classList.add('active');
}

// Ouvrir la modal pour éditer un produit
function editProduct(produit) {
    console.log('editProduct appelé avec:', produit);
    try {
        document.getElementById('modalTitle').textContent = 'Modifier le Produit';
        document.getElementById('submitBtnText').textContent = 'Enregistrer';

        document.getElementById('productId').value = produit.id;
        document.getElementById('nom').value = produit.nom;
        document.getElementById('prix').value = produit.prix;
        document.getElementById('description').value = produit.description || '';
        document.getElementById('image').value = produit.image || '';

        // Convertir JSON array en texte multiligne
        if (produit.caracteristiques) {
            const carac = typeof produit.caracteristiques === 'string'
                ? JSON.parse(produit.caracteristiques)
                : produit.caracteristiques;
            document.getElementById('caracteristiques').value = carac.join('\n');
        } else {
            document.getElementById('caracteristiques').value = '';
        }

        document.getElementById('actif').checked = produit.actif == 1;
        document.getElementById('productModal').classList.add('active');
    } catch (error) {
        console.error('Erreur lors de l\'édition du produit:', error);
        showAlert('Erreur lors du chargement du produit', 'error');
    }
}

// Fermer la modal
function closeProductModal() {
    document.getElementById('productModal').classList.remove('active');
}

// Fermer la modal en cliquant en dehors
document.getElementById('productModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeProductModal();
    }
});

// Soumettre le formulaire
document.getElementById('productForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtnText');
    const loader = document.getElementById('submitBtnLoader');

    // Afficher le loader
    submitBtn.style.display = 'none';
    loader.style.display = 'inline-block';

    // Préparer les données
    const formData = new FormData(this);
    const data = {
        nom: formData.get('nom'),
        prix: parseFloat(formData.get('prix')),
        description: formData.get('description'),
        image: formData.get('image'),
        caracteristiques: formData.get('caracteristiques')
            .split('\n')
            .filter(line => line.trim())
            .map(line => line.trim()),
        actif: formData.get('actif') ? 1 : 0
    };

    const productId = formData.get('id');
    const isEdit = productId !== '';

    if (isEdit) {
        data.id = parseInt(productId);
    }

    try {
        const url = isEdit ? '../api/admin/produits.php' : '../api/admin/produits.php';
        const method = isEdit ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (response.ok) {
            showAlert(isEdit ? 'Produit modifié avec succès!' : 'Produit créé avec succès!', 'success');
            closeProductModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.error || 'Une erreur est survenue', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showAlert('Erreur de connexion au serveur', 'error');
    } finally {
        // Cacher le loader
        submitBtn.style.display = 'inline';
        loader.style.display = 'none';
    }
});

// Activer/Désactiver un produit
async function toggleProductStatus(id, actif) {
    try {
        const response = await fetch('../api/admin/produits.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id, actif: actif ? 1 : 0 })
        });

        const result = await response.json();

        if (response.ok) {
            showAlert('Statut du produit mis à jour!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.error || 'Erreur lors de la mise à jour', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showAlert('Erreur de connexion au serveur', 'error');
    }
}

// Supprimer un produit
async function deleteProduct(id, nom) {
    if (!confirm(`Êtes-vous sûr de vouloir supprimer "${nom}" ?\nCette action est irréversible.`)) {
        return;
    }

    try {
        const response = await fetch('../api/admin/produits.php?id=' + id, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (response.ok) {
            showAlert('Produit supprimé avec succès!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.error || 'Erreur lors de la suppression', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showAlert('Erreur de connexion au serveur', 'error');
    }
}

// Afficher une alerte
function showAlert(message, type) {
    const container = document.getElementById('alert-container');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    container.appendChild(alert);

    setTimeout(() => {
        alert.remove();
    }, 5000);
}
