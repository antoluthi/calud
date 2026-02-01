/**
 * Admin - Maintenance settings
 */

(function() {
    let settings = {
        maintenance_enabled: false,
        maintenance_ips: [],
        password_enabled: false,
        has_password: false,
        client_ip: ''
    };

    let dirty = false;

    const maintenanceToggle = document.getElementById('maintenanceToggle');
    const passwordToggle = document.getElementById('passwordToggle');
    const ipList = document.getElementById('ipList');
    const newIpInput = document.getElementById('newIpInput');
    const addIpBtn = document.getElementById('addIpBtn');
    const currentIpDisplay = document.getElementById('currentIpDisplay');
    const addCurrentIpBtn = document.getElementById('addCurrentIpBtn');
    const passwordInput = document.getElementById('passwordInput');
    const setPasswordBtn = document.getElementById('setPasswordBtn');
    const passwordStatus = document.getElementById('passwordStatus');
    const saveBar = document.getElementById('saveBar');
    const saveBtn = document.getElementById('saveBtn');
    const alertContainer = document.getElementById('alertContainer');

    // Load settings
    async function loadSettings() {
        try {
            const res = await fetch('/api/admin/maintenance.php');
            if (!res.ok) throw new Error('Erreur serveur');
            settings = await res.json();

            maintenanceToggle.checked = settings.maintenance_enabled;
            passwordToggle.checked = settings.password_enabled;
            currentIpDisplay.textContent = settings.client_ip || 'inconnue';

            updatePasswordStatus();
            renderIpList();
            dirty = false;
            saveBar.classList.remove('visible');
        } catch (err) {
            showAlert('Erreur lors du chargement des parametres', 'error');
            console.error(err);
        }
    }

    function updatePasswordStatus() {
        if (settings.has_password) {
            passwordStatus.className = 'status-indicator status-on';
            passwordStatus.textContent = 'Mot de passe defini';
        } else {
            passwordStatus.className = 'status-indicator status-off';
            passwordStatus.textContent = 'Aucun mot de passe defini';
        }
    }

    function renderIpList() {
        const ips = settings.maintenance_ips || [];
        if (ips.length === 0) {
            ipList.innerHTML = '<div class="empty-list">Aucune IP autorisee</div>';
            return;
        }
        ipList.innerHTML = ips.map((ip, i) => `
            <div class="ip-item">
                <span>${escapeHtml(ip)}</span>
                <button class="remove-ip" data-index="${i}" title="Supprimer">&times;</button>
            </div>
        `).join('');

        // Bind remove buttons
        ipList.querySelectorAll('.remove-ip').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.dataset.index);
                settings.maintenance_ips.splice(idx, 1);
                renderIpList();
                markDirty();
            });
        });
    }

    function addIp(ip) {
        ip = ip.trim();
        if (!ip) return;
        // Basic IP validation (IPv4 or IPv6)
        if (!/^[\d.:a-fA-F]+$/.test(ip)) {
            showAlert('Format d\'IP invalide', 'error');
            return;
        }
        if (!settings.maintenance_ips) settings.maintenance_ips = [];
        if (settings.maintenance_ips.includes(ip)) {
            showAlert('Cette IP est deja dans la liste', 'error');
            return;
        }
        settings.maintenance_ips.push(ip);
        renderIpList();
        markDirty();
    }

    function markDirty() {
        dirty = true;
        saveBar.classList.add('visible');
    }

    function showAlert(message, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type === 'error' ? 'error' : 'success'}`;
        alert.textContent = message;
        alertContainer.innerHTML = '';
        alertContainer.appendChild(alert);
        setTimeout(() => alert.remove(), 4000);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Save toggles + IPs
    async function saveSettings() {
        try {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Enregistrement...';

            const payload = {
                maintenance_enabled: maintenanceToggle.checked,
                maintenance_ips: settings.maintenance_ips || [],
                password_enabled: passwordToggle.checked
            };

            const res = await fetch('/api/admin/maintenance.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!res.ok) throw new Error('Erreur serveur');

            const result = await res.json();
            if (result.success) {
                showAlert('Parametres enregistres', 'success');
                dirty = false;
                saveBar.classList.remove('visible');
            } else {
                throw new Error(result.error || 'Erreur');
            }
        } catch (err) {
            showAlert('Erreur lors de l\'enregistrement', 'error');
            console.error(err);
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Enregistrer';
        }
    }

    // Set password (separate action)
    async function setPassword() {
        const pwd = passwordInput.value;
        if (!pwd) {
            showAlert('Veuillez entrer un mot de passe', 'error');
            return;
        }
        if (pwd.length < 4) {
            showAlert('Le mot de passe doit contenir au moins 4 caracteres', 'error');
            return;
        }

        try {
            setPasswordBtn.disabled = true;
            setPasswordBtn.textContent = '...';

            const res = await fetch('/api/admin/maintenance.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ password_hash: pwd })
            });

            if (!res.ok) throw new Error('Erreur serveur');

            const result = await res.json();
            if (result.success) {
                showAlert('Mot de passe defini', 'success');
                passwordInput.value = '';
                settings.has_password = true;
                updatePasswordStatus();
            } else {
                throw new Error(result.error || 'Erreur');
            }
        } catch (err) {
            showAlert('Erreur lors de la definition du mot de passe', 'error');
            console.error(err);
        } finally {
            setPasswordBtn.disabled = false;
            setPasswordBtn.textContent = 'Definir';
        }
    }

    // Event listeners
    maintenanceToggle.addEventListener('change', markDirty);
    passwordToggle.addEventListener('change', markDirty);

    addIpBtn.addEventListener('click', () => {
        addIp(newIpInput.value);
        newIpInput.value = '';
    });

    newIpInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addIp(newIpInput.value);
            newIpInput.value = '';
        }
    });

    addCurrentIpBtn.addEventListener('click', () => {
        if (settings.client_ip) {
            addIp(settings.client_ip);
        }
    });

    setPasswordBtn.addEventListener('click', setPassword);

    passwordInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            setPassword();
        }
    });

    saveBtn.addEventListener('click', saveSettings);

    // Init
    loadSettings();
})();
