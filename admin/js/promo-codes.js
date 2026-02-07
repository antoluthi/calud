/**
 * Admin - Gestion des codes promo
 */

document.addEventListener('DOMContentLoaded', function () {
    loadPromoCodes();

    document.getElementById('promoForm').addEventListener('submit', function (e) {
        e.preventDefault();
        savePromoCode();
    });
});

// Charger les codes promo
async function loadPromoCodes() {
    try {
        var response = await fetch('/api/admin/promo-codes.php');
        var codes = await response.json();
        displayCodes(codes);
    } catch (e) {
        document.getElementById('codesContainer').innerHTML =
            '<div style="padding: 2rem; text-align: center; color: #f87171;">Erreur de chargement</div>';
    }
}

// Afficher les codes dans le tableau
function displayCodes(codes) {
    var container = document.getElementById('codesContainer');

    if (codes.length === 0) {
        container.innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--text-secondary);">Aucun code promo</div>';
        return;
    }

    var html = '<table class="admin-table"><thead><tr>' +
        '<th>Code</th><th>Reduction</th><th>Min. commande</th><th>Utilisations</th><th>Validite</th><th>Statut</th><th>Actions</th>' +
        '</tr></thead><tbody>';

    codes.forEach(function (code) {
        var discountLabel = code.discount_type === 'percent'
            ? code.discount_value + '%'
            : parseFloat(code.discount_value).toFixed(2) + ' EUR';

        var usesLabel = code.used_count + (code.max_uses ? ' / ' + code.max_uses : ' / illimite');

        var now = new Date();
        var isExpired = code.expires_at && new Date(code.expires_at) < now;
        var isNotStarted = code.starts_at && new Date(code.starts_at) > now;
        var isMaxedOut = code.max_uses && code.used_count >= code.max_uses;

        var statusClass, statusText;
        if (!code.active) {
            statusClass = 'badge-cancelled';
            statusText = 'Inactif';
        } else if (isExpired) {
            statusClass = 'badge-cancelled';
            statusText = 'Expire';
        } else if (isMaxedOut) {
            statusClass = 'badge-cancelled';
            statusText = 'Epuise';
        } else if (isNotStarted) {
            statusClass = 'badge-pending';
            statusText = 'Planifie';
        } else {
            statusClass = 'badge-paid';
            statusText = 'Actif';
        }

        var validityLabel = '';
        if (code.starts_at) validityLabel += 'Du ' + formatDate(code.starts_at);
        if (code.expires_at) validityLabel += (validityLabel ? '<br>' : '') + 'Au ' + formatDate(code.expires_at);
        if (!validityLabel) validityLabel = 'Permanent';

        html += '<tr>' +
            '<td><strong style="letter-spacing: 1px;">' + escapeHtml(code.code) + '</strong>' +
            (code.description ? '<br><small style="color: var(--text-secondary);">' + escapeHtml(code.description) + '</small>' : '') + '</td>' +
            '<td>' + discountLabel + '</td>' +
            '<td>' + (parseFloat(code.min_order_amount) > 0 ? parseFloat(code.min_order_amount).toFixed(2) + ' EUR' : '-') + '</td>' +
            '<td>' + usesLabel + '</td>' +
            '<td style="font-size: 0.85rem;">' + validityLabel + '</td>' +
            '<td><span class="badge ' + statusClass + '">' + statusText + '</span></td>' +
            '<td>' +
            '<button class="btn-icon" onclick="editPromoCode(' + code.id + ')" title="Modifier">✏️</button> ' +
            '<button class="btn-icon" onclick="togglePromoCode(' + code.id + ', ' + (code.active ? 'false' : 'true') + ')" title="' + (code.active ? 'Desactiver' : 'Activer') + '">' + (code.active ? '⏸️' : '▶️') + '</button> ' +
            '<button class="btn-icon" onclick="deletePromoCode(' + code.id + ', \'' + escapeHtml(code.code) + '\')" title="Supprimer" style="color: #f87171;">🗑️</button>' +
            '</td>' +
            '</tr>';
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

// Sauvegarder (creer ou modifier)
async function savePromoCode() {
    var id = document.getElementById('promoId').value;
    var data = {
        code: document.getElementById('promoCode').value,
        description: document.getElementById('promoDescription').value,
        discount_type: document.getElementById('promoType').value,
        discount_value: parseFloat(document.getElementById('promoValue').value),
        min_order_amount: parseFloat(document.getElementById('promoMinOrder').value) || 0,
        max_uses: document.getElementById('promoMaxUses').value || null,
        starts_at: document.getElementById('promoStartsAt').value || null,
        expires_at: document.getElementById('promoExpiresAt').value || null
    };

    try {
        var url = '/api/admin/promo-codes.php' + (id ? '?id=' + id : '');
        var response = await fetch(url, {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        var result = await response.json();

        if (result.success) {
            showAlert(id ? 'Code promo modifie' : 'Code promo cree', 'success');
            resetForm();
            loadPromoCodes();
        } else {
            showAlert(result.error || 'Erreur', 'error');
        }
    } catch (e) {
        showAlert('Erreur de connexion', 'error');
    }
}

// Charger un code dans le formulaire pour edition
async function editPromoCode(id) {
    try {
        var response = await fetch('/api/admin/promo-codes.php');
        var codes = await response.json();
        var code = codes.find(function (c) { return c.id == id; });
        if (!code) return;

        document.getElementById('promoId').value = code.id;
        document.getElementById('promoCode').value = code.code;
        document.getElementById('promoDescription').value = code.description || '';
        document.getElementById('promoType').value = code.discount_type;
        document.getElementById('promoValue').value = code.discount_value;
        document.getElementById('promoMinOrder').value = code.min_order_amount || 0;
        document.getElementById('promoMaxUses').value = code.max_uses || '';
        document.getElementById('promoStartsAt').value = code.starts_at ? code.starts_at.replace(' ', 'T').slice(0, 16) : '';
        document.getElementById('promoExpiresAt').value = code.expires_at ? code.expires_at.replace(' ', 'T').slice(0, 16) : '';

        document.getElementById('formTitle').textContent = 'Modifier le code : ' + code.code;
        document.getElementById('submitBtn').textContent = 'Enregistrer';
        document.getElementById('cancelBtn').style.display = 'inline-block';

        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (e) {
        showAlert('Erreur de chargement', 'error');
    }
}

// Activer / desactiver un code
async function togglePromoCode(id, active) {
    try {
        var response = await fetch('/api/admin/promo-codes.php?id=' + id, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ active: active })
        });
        var result = await response.json();
        if (result.success) {
            showAlert(active ? 'Code active' : 'Code desactive', 'success');
            loadPromoCodes();
        }
    } catch (e) {
        showAlert('Erreur', 'error');
    }
}

// Supprimer un code
async function deletePromoCode(id, code) {
    if (!confirm('Supprimer le code promo "' + code + '" ?')) return;

    try {
        var response = await fetch('/api/admin/promo-codes.php?id=' + id, { method: 'DELETE' });
        var result = await response.json();
        if (result.success) {
            showAlert('Code supprime', 'success');
            loadPromoCodes();
        }
    } catch (e) {
        showAlert('Erreur', 'error');
    }
}

// Reset le formulaire
function resetForm() {
    document.getElementById('promoForm').reset();
    document.getElementById('promoId').value = '';
    document.getElementById('formTitle').textContent = 'Creer un code promo';
    document.getElementById('submitBtn').textContent = 'Creer le code';
    document.getElementById('cancelBtn').style.display = 'none';
}

// Formater une date
function formatDate(dateStr) {
    if (!dateStr) return '';
    var d = new Date(dateStr);
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

// Echapper le HTML
function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Afficher une alerte
function showAlert(message, type) {
    var container = document.getElementById('alert-container');
    var alert = document.createElement('div');
    alert.className = 'alert alert-' + type;
    alert.textContent = message;
    container.innerHTML = '';
    container.appendChild(alert);
    setTimeout(function () { alert.remove(); }, 3000);
}
