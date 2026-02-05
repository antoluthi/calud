/**
 * Statistiques Admin - JavaScript
 * Gère les graphiques Chart.js et le chargement des données
 */

// Variables globales pour les graphiques
let visitesChart = null;
let devicesChart = null;
let browsersChart = null;
let osChart = null;
let hoursChart = null;

// Couleurs du thème
const chartColors = {
    primary: '#00d4ff',
    secondary: '#00ff88',
    tertiary: '#ffaa00',
    quaternary: '#ff4444',
    quinary: '#a855f7',
    grid: '#333333',
    text: '#b0b0b0'
};

const colorPalette = [
    chartColors.primary,
    chartColors.secondary,
    chartColors.tertiary,
    chartColors.quaternary,
    chartColors.quinary,
    '#f472b6', // pink
    '#60a5fa', // blue
    '#34d399', // emerald
];

// Configuration Chart.js par défaut
Chart.defaults.color = chartColors.text;
Chart.defaults.borderColor = chartColors.grid;

// Période sélectionnée
let currentPeriode = 30;

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    // Event listeners pour les boutons de période
    document.querySelectorAll('.periode-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.periode-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentPeriode = parseInt(btn.dataset.periode);
            loadStats();
        });
    });

    // Charger les stats
    loadStats();
});

// Charger les statistiques
async function loadStats() {
    showLoading(true);

    try {
        const response = await fetch(`../api/admin/stats.php?periode=${currentPeriode}`);
        const data = await response.json();

        if (data.success) {
            updateSummaryCards(data.summary);
            updateVisitesChart(data.graphData);
            updateDevicesChart(data.devices);
            updateBrowsersChart(data.browsers);
            updateOsChart(data.os);
            updateHoursChart(data.heures);
            updateTopPages(data.topPages);
            updateReferrers(data.referrers);
            updateRecentVisits(data.dernieresVisites);
        } else {
            console.error('Erreur API:', data.error);
        }
    } catch (error) {
        console.error('Erreur chargement stats:', error);
    } finally {
        showLoading(false);
    }
}

// Afficher/masquer le loading
function showLoading(show) {
    document.getElementById('loading').style.display = show ? 'flex' : 'none';
}

// Formater les nombres
function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

// Mettre à jour les cartes résumé
function updateSummaryCards(summary) {
    document.getElementById('visites-today').textContent = formatNumber(summary.today.visites);
    document.getElementById('sessions-today').textContent = `${summary.today.sessions} sessions`;

    document.getElementById('visites-week').textContent = formatNumber(summary.week.visites);
    document.getElementById('sessions-week').textContent = `${summary.week.sessions} sessions`;

    document.getElementById('visites-month').textContent = formatNumber(summary.month.visites);
    document.getElementById('sessions-month').textContent = `${summary.month.sessions} sessions`;

    document.getElementById('visites-total').textContent = formatNumber(summary.allTime.visites);

    // Tendance
    const tendanceEl = document.getElementById('tendance');
    const tendance = summary.tendance;
    if (tendance > 0) {
        tendanceEl.textContent = `↑ ${tendance}%`;
        tendanceEl.className = 'stat-card-change positive';
    } else if (tendance < 0) {
        tendanceEl.textContent = `↓ ${Math.abs(tendance)}%`;
        tendanceEl.className = 'stat-card-change negative';
    } else {
        tendanceEl.textContent = '→ 0%';
        tendanceEl.className = 'stat-card-change neutral';
    }
}

// Graphique des visites par jour
function updateVisitesChart(graphData) {
    const ctx = document.getElementById('visitesChart').getContext('2d');

    // Détruire le graphique existant
    if (visitesChart) {
        visitesChart.destroy();
    }

    // Préparer les données
    const labels = graphData.map(d => {
        const date = new Date(d.date);
        return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
    });
    const visites = graphData.map(d => d.visites);
    const sessions = graphData.map(d => d.sessions);

    visitesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Visites',
                    data: visites,
                    borderColor: chartColors.primary,
                    backgroundColor: chartColors.primary + '20',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 6
                },
                {
                    label: 'Sessions',
                    data: sessions,
                    borderColor: chartColors.secondary,
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    tension: 0.4,
                    pointRadius: 2,
                    pointHoverRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: chartColors.grid
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// Graphique des appareils
function updateDevicesChart(devices) {
    const ctx = document.getElementById('devicesChart').getContext('2d');

    if (devicesChart) {
        devicesChart.destroy();
    }

    const deviceLabels = {
        'desktop': '💻 Desktop',
        'mobile': '📱 Mobile',
        'tablet': '📲 Tablet'
    };

    const labels = devices.map(d => deviceLabels[d.device_type] || d.device_type);
    const data = devices.map(d => d.count);

    devicesChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [chartColors.primary, chartColors.secondary, chartColors.tertiary],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Graphique des navigateurs
function updateBrowsersChart(browsers) {
    const ctx = document.getElementById('browsersChart').getContext('2d');

    if (browsersChart) {
        browsersChart.destroy();
    }

    const labels = browsers.map(b => b.browser);
    const data = browsers.map(b => b.count);

    browsersChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colorPalette.slice(0, browsers.length),
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: chartColors.grid
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// Graphique des OS
function updateOsChart(osList) {
    const ctx = document.getElementById('osChart').getContext('2d');

    if (osChart) {
        osChart.destroy();
    }

    const labels = osList.map(o => o.os);
    const data = osList.map(o => o.count);

    osChart = new Chart(ctx, {
        type: 'polarArea',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colorPalette.map(c => c + '80'),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            },
            scales: {
                r: {
                    grid: {
                        color: chartColors.grid
                    }
                }
            }
        }
    });
}

// Graphique des heures de pointe
function updateHoursChart(heures) {
    const ctx = document.getElementById('hoursChart').getContext('2d');

    if (hoursChart) {
        hoursChart.destroy();
    }

    // Créer un tableau avec toutes les heures (0-23)
    const allHours = Array.from({ length: 24 }, (_, i) => i);
    const hoursMap = new Map(heures.map(h => [h.heure, h.count]));
    const data = allHours.map(h => hoursMap.get(h) || 0);
    const labels = allHours.map(h => `${h}h`);

    hoursChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: data.map((v, i) => {
                    const max = Math.max(...data);
                    const intensity = v / max;
                    return `rgba(0, 212, 255, ${0.3 + intensity * 0.7})`;
                }),
                borderRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: chartColors.grid
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// Mettre à jour la liste des top pages
function updateTopPages(topPages) {
    const container = document.getElementById('top-pages');

    if (topPages.length === 0) {
        container.innerHTML = '<li class="stats-list-item"><span class="stats-list-label">Aucune donnée</span></li>';
        return;
    }

    container.innerHTML = topPages.map(page => {
        const pageName = page.page.replace(/^\//, '').replace(/\.(html|php)$/, '') || 'Accueil';
        return `
            <li class="stats-list-item">
                <span class="stats-list-label" title="${page.page}">${pageName}</span>
                <span class="stats-list-value">${formatNumber(page.visites)}</span>
            </li>
        `;
    }).join('');
}

// Mettre à jour la liste des referrers
function updateReferrers(referrers) {
    const container = document.getElementById('referrers');

    if (referrers.length === 0) {
        container.innerHTML = '<li class="stats-list-item"><span class="stats-list-label">Aucune donnée</span></li>';
        return;
    }

    container.innerHTML = referrers.map(ref => `
        <li class="stats-list-item">
            <span class="stats-list-label" title="${ref.source}">${ref.source}</span>
            <span class="stats-list-value">${formatNumber(ref.count)}</span>
        </li>
    `).join('');
}

// Mettre à jour les dernières visites
function updateRecentVisits(visits) {
    const container = document.getElementById('recent-visits');

    if (visits.length === 0) {
        container.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem;">Aucune visite enregistrée</td></tr>';
        return;
    }

    const deviceIcons = {
        'desktop': '💻',
        'mobile': '📱',
        'tablet': '📲'
    };

    container.innerHTML = visits.map(visit => {
        const date = new Date(visit.created_at);
        const formattedDate = date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit'
        });
        const pageName = visit.page.replace(/^\//, '').replace(/\.(html|php)$/, '') || 'Accueil';

        return `
            <tr>
                <td title="${visit.page}">${pageName}</td>
                <td><span class="device-icon">${deviceIcons[visit.device_type] || '❓'} ${visit.device_type}</span></td>
                <td>${visit.browser}</td>
                <td>${visit.os}</td>
                <td>${formattedDate}</td>
            </tr>
        `;
    }).join('');
}
