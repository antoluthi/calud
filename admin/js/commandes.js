/**
 * Gestion des commandes - JavaScript
 */

let currentOrderId = null;

// Voir les détails d'une commande
async function viewOrder(commande) {
    try {
        // Récupérer les détails de la commande avec les items
        const response = await fetch(`../api/admin/commandes.php?id=${commande.id}`);
        const data = await response.json();

        if (response.ok) {
            displayOrderDetails(data);
            document.getElementById('orderModal').classList.add('active');
        } else {
            showAlert(data.error || 'Erreur lors du chargement des détails', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showAlert('Erreur de connexion au serveur', 'error');
    }
}

// Afficher les détails de la commande
function displayOrderDetails(data) {
    const statusLabels = {
        'pending': 'En attente',
        'paid': 'Payée',
        'processing': 'En préparation',
        'shipped': 'Expédiée',
        'delivered': 'Livrée',
        'cancelled': 'Annulée'
    };

    const badgeClass = {
        'pending': 'badge-warning',
        'paid': 'badge-info',
        'processing': 'badge-info',
        'shipped': 'badge-success',
        'delivered': 'badge-success',
        'cancelled': 'badge-danger'
    };

    let html = `
        <div style="margin-bottom: 2rem;">
            <h3>Commande #${data.commande.id}</h3>
            <p style="color: var(--text-secondary);">
                Date: ${new Date(data.commande.created_at).toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                })}
            </p>
            <span class="badge ${badgeClass[data.commande.status]}">${statusLabels[data.commande.status]}</span>
        </div>

        <div style="background-color: var(--bg-secondary); padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
            <h4 style="margin-bottom: 1rem;">Client</h4>
            <div style="display: flex; align-items: center; gap: 1rem;">
                ${data.client.picture ? `<img src="${data.client.picture}" alt="Avatar" style="width: 48px; height: 48px; border-radius: 50%;">` : ''}
                <div>
                    <div style="font-weight: 600;">${data.client.name}</div>
                    <div style="color: var(--text-secondary); font-size: 0.9rem;">${data.client.email}</div>
                </div>
            </div>
        </div>

        <h4 style="margin-bottom: 1rem;">Articles</h4>
        <table style="width: 100%; margin-bottom: 2rem;">
            <thead>
                <tr>
                    <th style="text-align: left;">Produit</th>
                    <th style="text-align: center;">Quantité</th>
                    <th style="text-align: right;">Prix unitaire</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
    `;

    data.items.forEach(item => {
        html += `
            <tr>
                <td>${item.produit_nom}</td>
                <td style="text-align: center;">${item.quantite}</td>
                <td style="text-align: right;">${parseFloat(item.prix_unitaire).toFixed(2)} €</td>
                <td style="text-align: right; font-weight: 600;">${(item.quantite * item.prix_unitaire).toFixed(2)} €</td>
            </tr>
        `;
    });

    html += `
            </tbody>
        </table>

        <div style="text-align: right; padding-top: 1rem; border-top: 2px solid var(--border-color);">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent);">
                Total: ${parseFloat(data.commande.total).toFixed(2)} €
            </div>
        </div>
    `;

    document.getElementById('orderDetails').innerHTML = html;
}

// Fermer la modal des détails
function closeOrderModal() {
    document.getElementById('orderModal').classList.remove('active');
}

// Ouvrir la modal pour changer le statut
function changeStatus(orderId, currentStatus) {
    currentOrderId = orderId;
    document.getElementById('orderId').value = orderId;
    document.getElementById('newStatus').value = currentStatus;
    document.getElementById('statusModal').classList.add('active');
}

// Fermer la modal de changement de statut
function closeStatusModal() {
    document.getElementById('statusModal').classList.remove('active');
    currentOrderId = null;
}

// Fermer les modals en cliquant en dehors
document.getElementById('orderModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeOrderModal();
    }
});

document.getElementById('statusModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeStatusModal();
    }
});

// Soumettre le changement de statut
document.getElementById('statusForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtnText');
    const loader = document.getElementById('submitBtnLoader');

    // Afficher le loader
    submitBtn.style.display = 'none';
    loader.style.display = 'inline-block';

    const orderId = document.getElementById('orderId').value;
    const newStatus = document.getElementById('newStatus').value;

    try {
        const response = await fetch('../api/admin/commandes.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: parseInt(orderId),
                status: newStatus
            })
        });

        const result = await response.json();

        if (response.ok) {
            showAlert('Statut de la commande mis à jour!', 'success');
            closeStatusModal();
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
