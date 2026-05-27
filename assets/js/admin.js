/**
 * Painel Administrativo — Gráficos e custos de IA
 */

var iaChartsInited = false;
var iaCostsFetched = false;

function initIaCharts() {
    if (iaChartsInited) return;
    iaChartsInited = true;

    var lineCanvas = document.getElementById('chartCustoDiario');
    var pieCanvas = document.getElementById('chartPorModelo');

    if (lineCanvas) {
        var labels, values;
        try { labels = JSON.parse(lineCanvas.getAttribute('data-labels') || '[]'); } catch(e) { labels = []; }
        try { values = JSON.parse(lineCanvas.getAttribute('data-values') || '[]'); } catch(e) { values = []; }

        new Chart(lineCanvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Custo (USD)',
                    data: values,
                    borderColor: '#00d4aa',
                    backgroundColor: 'rgba(0,212,170,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    pointBackgroundColor: '#00d4aa',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        ticks: { color: '#7a7f9a', maxTicksLimit: 10, font: { size: 10 } },
                        grid: { color: 'rgba(30,35,64,0.5)' }
                    },
                    y: {
                        ticks: { color: '#7a7f9a', font: { size: 10 }, callback: function(v) { return '$' + v.toFixed(4); } },
                        grid: { color: 'rgba(30,35,64,0.5)' }
                    }
                }
            }
        });
    }

    if (pieCanvas) {
        var pieLabels, pieValues;
        try { pieLabels = JSON.parse(pieCanvas.getAttribute('data-labels') || '[]'); } catch(e) { pieLabels = []; }
        try { pieValues = JSON.parse(pieCanvas.getAttribute('data-values') || '[]'); } catch(e) { pieValues = []; }

        var colors = ['#00d4aa', '#60a5fa', '#fbbf24', '#f87171', '#a78bfa', '#34d399', '#f472b6'];

        new Chart(pieCanvas, {
            type: 'doughnut',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieValues,
                    backgroundColor: colors.slice(0, pieLabels.length),
                    borderColor: '#131627',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#e2e4f0', font: { size: 10 }, padding: 12 }
                    }
                }
            }
        });
    }
}

function fetchIaCosts() {
    if (iaCostsFetched) return;
    iaCostsFetched = true;

    var temPendentes = false;
    document.querySelectorAll('.admin-kpi').forEach(function(kpi) {
        var label = kpi.querySelector('.kpi-label');
        if (label && label.textContent.trim() === 'Pendentes') {
            var val = kpi.querySelector('.kpi-value');
            if (val && parseInt(val.textContent) > 0) {
                temPendentes = true;
            }
        }
    });

    if (!temPendentes) return;

    fetch('admin_action.php?acao=buscar_custos_ia')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.sucesso && data.atualizados > 0) {
                location.reload();
            }
        })
        .catch(function() {});
}

document.addEventListener('DOMContentLoaded', function() {
    var iaMenuItem = document.querySelector('.admin-sidebar .menu-item[data-section="ia_usage"]');
    if (iaMenuItem) {
        iaMenuItem.addEventListener('click', function onFirstClick() {
            iaMenuItem.removeEventListener('click', onFirstClick);
            setTimeout(function() {
                initIaCharts();
                fetchIaCosts();
            }, 100);
        });

        if (iaMenuItem.classList.contains('active')) {
            setTimeout(function() {
                initIaCharts();
                fetchIaCosts();
            }, 100);
        }
    }
});
