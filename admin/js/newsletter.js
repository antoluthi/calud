/**
 * Gestion de la newsletter - JavaScript
 */

let subscribers = [];

// Charger les abonnés au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    loadSubscribers();
});

// Charger les abonnés depuis l'API
async function loadSubscribers() {
    try {
        const response = await fetch('../api/admin/newsletter.php');
        const data = await response.json();

        if (data.success) {
            subscribers = data.abonnes;
            displaySubscribers(subscribers);
            updateStats(data.total, data.actifs);
        } else {
            showAlert('Erreur lors du chargement des abonnés', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showAlert('Erreur de connexion au serveur', 'error');
    }
}

// Afficher les abonnés
function displaySubscribers(subscribersData) {
    const container = document.getElementById('subscribersContainer');

    if (subscribersData.length === 0) {
        container.innerHTML = `
            <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                Aucun abonné pour le moment.
            </div>
        `;
        return;
    }

    container.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Statut</th>
                    <th>Date d'inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${subscribersData.map(sub => `
                    <tr>
                        <td>#${sub.id}</td>
                        <td><a href="mailto:${escapeHtml(sub.email)}" style="color: var(--accent); text-decoration: none;">${escapeHtml(sub.email)}</a></td>
                        <td>
                            <span class="badge ${sub.actif == 1 ? 'badge-success' : 'badge-danger'}">
                                ${sub.actif == 1 ? 'Actif' : 'Inactif'}
                            </span>
                        </td>
                        <td>${formatDate(sub.created_at)}</td>
                        <td>
                            ${sub.actif == 1 ? `<button class="btn-icon" onclick="unsubscribe(${sub.id}, '${escapeHtml(sub.email)}')" title="Désabonner">🔕</button>` : ''}
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

// Mettre à jour les stats
function updateStats(total, actifs) {
    document.getElementById('totalSubscribers').textContent = total;
    document.getElementById('activeSubscribers').textContent = actifs;
}

// Désabonner un utilisateur
async function unsubscribe(id, email) {
    if (!confirm(`Êtes-vous sûr de vouloir désabonner "${email}" ?`)) {
        return;
    }

    try {
        const response = await fetch('../api/admin/newsletter.php?id=' + id, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (response.ok) {
            showAlert('Abonnement désactivé avec succès', 'success');
            loadSubscribers();
        } else {
            showAlert(result.error || 'Erreur lors du désabonnement', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showAlert('Erreur de connexion au serveur', 'error');
    }
}

// Envoyer un email à tous les abonnés
document.getElementById('emailForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('sendBtnText');
    const loader = document.getElementById('sendBtnLoader');

    const formData = new FormData(this);
    const data = {
        subject: formData.get('subject'),
        message: formData.get('message')
    };

    const activeCount = subscribers.filter(s => s.actif == 1).length;

    if (activeCount === 0) {
        showAlert('Aucun abonné actif pour recevoir l\'email', 'error');
        return;
    }

    if (!confirm(`Êtes-vous sûr de vouloir envoyer cet email à ${activeCount} abonné(s) ?`)) {
        return;
    }

    // Afficher le loader
    submitBtn.style.display = 'none';
    loader.style.display = 'inline-block';

    try {
        const response = await fetch('../api/admin/newsletter.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (response.ok && result.success) {
            showAlert(result.message, 'success');
            this.reset();
        } else {
            showAlert(result.error || result.message || 'Erreur lors de l\'envoi', 'error');
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

// Formater la date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Échapper le HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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
