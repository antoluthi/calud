/**
 * Gestion des messages - JavaScript
 */

let messages = [];
let currentMessageId = null;

// Charger les messages au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    loadMessages();
});

// Charger les messages depuis l'API
async function loadMessages() {
    try {
        const response = await fetch('../api/admin/messages.php');
        const data = await response.json();

        if (data.success && data.messages) {
            messages = data.messages;
            displayMessages(messages);
            updateUnreadCount();
        } else {
            showAlert('Erreur lors du chargement des messages', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showAlert('Erreur de connexion au serveur', 'error');
    }
}

// Afficher les messages
function displayMessages(messagesToDisplay) {
    const container = document.getElementById('messagesContainer');

    if (messagesToDisplay.length === 0) {
        container.innerHTML = `
            <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                Aucun message pour le moment.
            </div>
        `;
        return;
    }

    container.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;"></th>
                    <th>De</th>
                    <th>Email</th>
                    <th>Aperçu</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${messagesToDisplay.map(msg => `
                    <tr style="${msg.lu == 0 ? 'background-color: rgba(0, 212, 255, 0.05); font-weight: 500;' : ''}">
                        <td>
                            ${msg.lu == 0 ? '<span style="color: var(--accent); font-size: 1.2rem;">●</span>' : ''}
                        </td>
                        <td>${escapeHtml(msg.user_name)}</td>
                        <td><a href="mailto:${escapeHtml(msg.user_email)}" style="color: var(--accent); text-decoration: none;">${escapeHtml(msg.user_email)}</a></td>
                        <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            ${escapeHtml(msg.message.substring(0, 100))}${msg.message.length > 100 ? '...' : ''}
                        </td>
                        <td>${formatDate(msg.created_at)}</td>
                        <td>
                            <button class="btn-icon" onclick="viewMessage(${msg.id})" title="Voir le message">👁️</button>
                            <button class="btn-icon" onclick="toggleReadStatus(${msg.id}, ${msg.lu == 1 ? 0 : 1})" title="${msg.lu == 1 ? 'Marquer comme non lu' : 'Marquer comme lu'}">
                                ${msg.lu == 1 ? '📭' : '✅'}
                            </button>
                            <button class="btn-icon" onclick="deleteMessage(${msg.id}, '${escapeHtml(msg.user_name)}')" title="Supprimer">🗑️</button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

// Voir un message
async function viewMessage(id) {
    const message = messages.find(m => m.id === id);
    if (!message) return;

    currentMessageId = id;

    const modalContent = document.getElementById('messageContent');
    modalContent.innerHTML = `
        <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 0.5rem; margin-bottom: 0.5rem;">
                <strong style="color: var(--text-secondary);">De :</strong>
                <span>${escapeHtml(message.user_name)}</span>

                <strong style="color: var(--text-secondary);">Email :</strong>
                <a href="mailto:${escapeHtml(message.user_email)}" style="color: var(--accent); text-decoration: none;">${escapeHtml(message.user_email)}</a>

                <strong style="color: var(--text-secondary);">Date :</strong>
                <span>${formatDate(message.created_at)}</span>

                <strong style="color: var(--text-secondary);">Statut :</strong>
                <span class="badge ${message.lu == 1 ? 'badge-success' : 'badge-info'}">
                    ${message.lu == 1 ? 'Lu' : 'Non lu'}
                </span>
            </div>
        </div>
        <div style="background-color: var(--bg-secondary); padding: 1.5rem; border-radius: 8px; white-space: pre-wrap; line-height: 1.6;">
            ${escapeHtml(message.message)}
        </div>
    `;

    document.getElementById('messageModal').classList.add('active');

    // Marquer comme lu automatiquement
    if (message.lu == 0) {
        await toggleReadStatus(id, 1, false);
    }
}

// Fermer la modal
function closeMessageModal() {
    document.getElementById('messageModal').classList.remove('active');
    currentMessageId = null;
}

// Fermer la modal en cliquant en dehors
document.getElementById('messageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeMessageModal();
    }
});

// Marquer comme lu/non lu
async function toggleReadStatus(id, lu, reload = true) {
    try {
        const response = await fetch('../api/admin/messages.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id, lu })
        });

        const result = await response.json();

        if (response.ok) {
            if (reload) {
                showAlert(lu ? 'Message marqué comme lu' : 'Message marqué comme non lu', 'success');
                loadMessages();
            } else {
                // Mettre à jour localement
                const msg = messages.find(m => m.id === id);
                if (msg) msg.lu = lu;
                updateUnreadCount();
            }
        } else {
            showAlert(result.error || 'Erreur lors de la mise à jour', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showAlert('Erreur de connexion au serveur', 'error');
    }
}

// Supprimer un message
async function deleteMessage(id, userName) {
    if (!confirm(`Êtes-vous sûr de vouloir supprimer le message de "${userName}" ?\nCette action est irréversible.`)) {
        return;
    }

    try {
        const response = await fetch('../api/admin/messages.php?id=' + id, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (response.ok) {
            showAlert('Message supprimé avec succès', 'success');
            loadMessages();
        } else {
            showAlert(result.error || 'Erreur lors de la suppression', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showAlert('Erreur de connexion au serveur', 'error');
    }
}

// Supprimer le message actuellement affiché
function deleteCurrentMessage() {
    if (currentMessageId) {
        const message = messages.find(m => m.id === currentMessageId);
        if (message) {
            closeMessageModal();
            deleteMessage(currentMessageId, message.user_name);
        }
    }
}

// Mettre à jour le compteur de non lus
function updateUnreadCount() {
    const unreadCount = messages.filter(m => m.lu == 0).length;
    document.getElementById('unreadCount').textContent = unreadCount;
}

// Formater la date
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'À l\'instant';
    if (diffMins < 60) return `Il y a ${diffMins} min`;
    if (diffHours < 24) return `Il y a ${diffHours}h`;
    if (diffDays < 7) return `Il y a ${diffDays} jour${diffDays > 1 ? 's' : ''}`;

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
