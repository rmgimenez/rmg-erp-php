/**
 * Dashboard JS - Cantina Sant'Anna
 * Gerencia a renderização de gráficos interativos usando a biblioteca Chart.js.
 */

/**
 * Inicializa os gráficos do dashboard financeiro
 * 
 * @param {Object} cashFlowData Dados para o gráfico de fluxo de caixa mensal
 * @param {Object} categoryData Dados para o gráfico de distribuição de despesas
 */
function initDashboardCharts(cashFlowData, categoryData) {
    // 1. Gráfico de Fluxo de Caixa Mensal (Receitas vs Despesas)
    const ctxFlow = document.getElementById('cashFlowChart');
    if (ctxFlow) {
        new Chart(ctxFlow.getContext('2d'), {
            type: 'bar',
            data: {
                labels: cashFlowData.labels,
                datasets: [
                    {
                        label: 'Receitas (R$)',
                        data: cashFlowData.receitas,
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderColor: '#10b981',
                        borderWidth: 1.5,
                        borderRadius: 6,
                        hoverBackgroundColor: 'rgba(16, 185, 129, 1)'
                    },
                    {
                        label: 'Despesas (R$)',
                        data: cashFlowData.despesas,
                        backgroundColor: 'rgba(239, 68, 68, 0.85)',
                        borderColor: '#ef4444',
                        borderWidth: 1.5,
                        borderRadius: 6,
                        hoverBackgroundColor: 'rgba(239, 68, 68, 1)'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                family: 'Outfit',
                                size: 12,
                                weight: '500'
                            },
                            color: '#475569'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleFont: { family: 'Outfit', size: 13, weight: '600' },
                        bodyFont: { family: 'Outfit', size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { family: 'Outfit', size: 12 },
                            color: '#64748b'
                        }
                    },
                    y: {
                        grid: {
                            color: '#f1f5f9'
                        },
                        ticks: {
                            font: { family: 'Outfit', size: 12 },
                            color: '#64748b',
                            callback: function(value) {
                                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 0 }).format(value);
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Gráfico de Despesas por Categoria (Doughnut)
    const ctxCat = document.getElementById('categoryChart');
    if (ctxCat) {
        new Chart(ctxCat.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: categoryData.labels,
                datasets: [{
                    data: categoryData.valores,
                    backgroundColor: [
                        '#4f46e5', // Indigo
                        '#06b6d4', // Cyan
                        '#f59e0b', // Amber
                        '#ef4444', // Red
                        '#10b981', // Emerald
                        '#ec4899', // Pink
                        '#8b5cf6', // Violet
                        '#64748b'  // Slate/Outros
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 16,
                            font: {
                                family: 'Outfit',
                                size: 12,
                                weight: '500'
                            },
                            color: '#475569'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        bodyFont: { family: 'Outfit', size: 12 },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const formattedValue = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
                                return ' ' + context.label + ': ' + formattedValue;
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }
}
