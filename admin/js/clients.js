/**
 * Gestion des clients - JavaScript
 */

// Recherche en temps réel
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.client-row');

    rows.forEach(row => {
        const name = row.getAttribute('data-name').toLowerCase();
        const email = row.getAttribute('data-email').toLowerCase();

        if (name.includes(searchTerm) || email.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Voir les détails d'un client
async function viewClient(client) {
    try {
        // Récupérer les commandes du client
        const response = await fetch(`../api/admin/clients.php?id=${client.id}`);
        const data = await response.json();

        if (response.ok) {
            displayClientDetails(data);
            document.getElementById('clientModal').classList.add('active');
        } else {
            showAlert(data.error || 'Erreur lors du chargement des détails', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showAlert('Erreur de connexion au serveur', 'error');
    }
}

// Afficher les détails du client
function displayClientDetails(data) {
    const client = data.client;
    const commandes = data.commandes;

    let html = `
        <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 2rem; padding: 1.5rem; background-color: var(--bg-secondary); border-radius: 12px;">
            ${client.picture ? `<img src="${client.picture}" alt="Avatar" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--accent);">` : ''}
            <div style="flex: 1;">
                <h3 style="margin-bottom: 0.5rem;">${client.name}</h3>
                <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">${client.email}</p>
                <div style="display: flex; gap: 0.5rem;">
                    ${client.is_admin ? '<span class="badge badge-warning">👑 Administrateur</span>' : '<span class="badge badge-info">Client</span>'}
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 2rem;">
            <div style="background-color: var(--bg-secondary); padding: 1rem; border-radius: 8px;">
                <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.25rem;">Inscrit le</div>
                <div style="font-weight: 600;">${new Date(client.created_at).toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                })}</div>
            </div>
            <div style="background-color: var(--bg-secondary); padding: 1rem; border-radius: 8px;">
                <div style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.25rem;">Dernière connexion</div>
                <div style="font-weight: 600;">${new Date(client.last_login).toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                })}</div>
            </div>
        </div>

        <h4 style="margin-bottom: 1rem;">Historique des commandes (${commandes.length})</h4>
    `;

    if (commandes.length === 0) {
        html += `<div style="padding: 2rem; text-align: center; color: var(--text-secondary); background-color: var(--bg-secondary); border-radius: 8px;">
            Ce client n'a pas encore passé de commande
        </div>`;
    } else {
        html += `<table style="width: 100%;">
            <thead>
                <tr>
                    <th style="text-align: left;">ID</th>
                    <th style="text-align: left;">Date</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: center;">Statut</th>
                </tr>
            </thead>
            <tbody>`;

        const statusLabels = {
            'en_attente': 'En attente',
            'confirmee': 'Confirmée',
            'expediee': 'Expédiée',
            'livree': 'Livrée',
            'annulee': 'Annulée'
        };

        const badgeClass = {
            'en_attente': 'badge-warning',
            'confirmee': 'badge-info',
            'expediee': 'badge-success',
            'livree': 'badge-success',
            'annulee': 'badge-danger'
        };

        commandes.forEach(cmd => {
            html += `
                <tr>
                    <td>#${cmd.id}</td>
                    <td>${new Date(cmd.created_at).toLocaleDateString('fr-FR')}</td>
                    <td style="text-align: right; font-weight: 600;">${parseFloat(cmd.total).toFixed(2)} €</td>
                    <td style="text-align: center;"><span class="badge ${badgeClass[cmd.status]}">${statusLabels[cmd.status]}</span></td>
                </tr>
            `;
        });

        html += `</tbody></table>`;

        // Total dépensé
        const totalDepense = commandes.reduce((sum, cmd) => sum + parseFloat(cmd.total), 0);
        html += `
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid var(--border-color); text-align: right;">
                <div style="font-size: 1.2rem; font-weight: 700; color: var(--accent);">
                    Total dépensé: ${totalDepense.toFixed(2)} €
                </div>
            </div>
        `;
    }

    document.getElementById('clientDetails').innerHTML = html;
}

// Fermer la modal
function closeClientModal() {
    document.getElementById('clientModal').classList.remove('active');
}

// Fermer en cliquant en dehors
document.getElementById('clientModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeClientModal();
    }
});

// Donner/retirer les droits admin
async function toggleAdmin(userId, makeAdmin, userName) {
    const action = makeAdmin ? 'donner les droits administrateur à' : 'retirer les droits administrateur de';

    if (!confirm(`Êtes-vous sûr de vouloir ${action} ${userName} ?`)) {
        return;
    }

    try {
        const response = await fetch('../api/admin/clients.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: userId,
                is_admin: makeAdmin ? 1 : 0
            })
        });

        const result = await response.json();

        if (response.ok) {
            showAlert(makeAdmin ? 'Droits administrateur accordés!' : 'Droits administrateur retirés!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.error || 'Une erreur est survenue', 'error');
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
