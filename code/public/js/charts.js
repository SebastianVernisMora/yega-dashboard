// Gestión de gráficos con Chart.js
class ChartsManager {
    constructor(dashboard) {
        this.dashboard = dashboard;
        this.charts = {};
        this.defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: this.getTextColor()
                    }
                },
                tooltip: {
                    backgroundColor: this.getTooltipBg(),
                    titleColor: this.getTextColor(),
                    bodyColor: this.getTextColor(),
                    borderColor: this.getBorderColor(),
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: this.getSecondaryTextColor()
                    },
                    grid: {
                        color: this.getBorderColor()
                    }
                },
                y: {
                    ticks: {
                        color: this.getSecondaryTextColor()
                    },
                    grid: {
                        color: this.getBorderColor()
                    }
                }
            }
        };
    }

    getTextColor() {
        return getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim();
    }

    getSecondaryTextColor() {
        return getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim();
    }

    getBorderColor() {
        return getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim();
    }

    getTooltipBg() {
        return getComputedStyle(document.documentElement).getPropertyValue('--bg-secondary').trim();
    }

    createChart(canvasId, type, data, customOptions = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !window.Chart) return null;

        // Destruir gráfico existente
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
        }

        const ctx = canvas.getContext('2d');
        const options = this.mergeOptions(customOptions);

        this.charts[canvasId] = new Chart(ctx, {
            type,
            data,
            options
        });

        return this.charts[canvasId];
    }

    mergeOptions(customOptions) {
        return {
            ...this.defaultOptions,
            ...customOptions,
            plugins: {
                ...this.defaultOptions.plugins,
                ...customOptions.plugins
            },
            scales: {
                ...this.defaultOptions.scales,
                ...customOptions.scales
            }
        };
    }

    updateTheme() {
        Object.values(this.charts).forEach(chart => {
            if (chart && chart.options) {
                // Actualizar colores de texto
                chart.options.plugins.legend.labels.color = this.getTextColor();
                chart.options.plugins.tooltip.backgroundColor = this.getTooltipBg();
                chart.options.plugins.tooltip.titleColor = this.getTextColor();
                chart.options.plugins.tooltip.bodyColor = this.getTextColor();
                chart.options.plugins.tooltip.borderColor = this.getBorderColor();

                // Actualizar escalas
                if (chart.options.scales) {
                    Object.values(chart.options.scales).forEach(scale => {
                        if (scale.ticks) scale.ticks.color = this.getSecondaryTextColor();
                        if (scale.grid) scale.grid.color = this.getBorderColor();
                    });
                }

                chart.update();
            }
        });
    }

    destroyChart(canvasId) {
        if (this.charts[canvasId]) {
            this.charts[canvasId].destroy();
            delete this.charts[canvasId];
        }
    }

    destroyAllCharts() {
        Object.keys(this.charts).forEach(canvasId => {
            this.destroyChart(canvasId);
        });
    }

    // Gráficos específicos
    createActivityChart(repositories) {
        const data = {
            labels: repositories.map(repo => repo.name),
            datasets: [{
                label: 'Estrellas',
                data: repositories.map(repo => repo.stars || 0),
                backgroundColor: 'rgba(0, 123, 255, 0.8)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 2,
                borderRadius: 4
            }]
        };

        return this.createChart('activityChart', 'bar', data, {
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value >= 1000 ? (value / 1000).toFixed(1) + 'K' : value;
                        }
                    }
                }
            }
        });
    }

    createIssuesPRsChart(repositories) {
        const totalIssues = repositories.reduce((sum, repo) => sum + (repo.open_issues || 0), 0);
        const totalPRs = repositories.reduce((sum, repo) => sum + (repo.open_prs || 0), 0);

        const data = {
            labels: ['Issues Abiertas', 'Pull Requests'],
            datasets: [{
                data: [totalIssues, totalPRs],
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(40, 167, 69, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 193, 7, 1)',
                    'rgba(40, 167, 69, 1)'
                ],
                borderWidth: 2
            }]
        };

        return this.createChart('issuesPrsChart', 'doughnut', data, {
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                }
            }
        });
    }

    createRadarChart(repositories) {
        const repos = repositories.slice(0, 3); // Top 3 repos
        
        const data = {
            labels: ['Estrellas', 'Forks', 'Issues', 'PRs', 'Actividad'],
            datasets: repos.map((repo, index) => {
                const colors = [
                    'rgba(0, 123, 255, 0.6)',
                    'rgba(40, 167, 69, 0.6)',
                    'rgba(255, 193, 7, 0.6)'
                ];
                const borderColors = [
                    'rgba(0, 123, 255, 1)',
                    'rgba(40, 167, 69, 1)',
                    'rgba(255, 193, 7, 1)'
                ];

                return {
                    label: repo.name,
                    data: [
                        Math.min((repo.stars || 0) / 100, 10),
                        Math.min((repo.forks || 0) / 20, 10),
                        Math.min((repo.open_issues || 0) / 5, 10),
                        Math.min((repo.open_prs || 0) / 3, 10),
                        Math.random() * 10 // Actividad simulada
                    ],
                    backgroundColor: colors[index],
                    borderColor: borderColors[index],
                    borderWidth: 2,
                    pointBackgroundColor: borderColors[index],
                    pointRadius: 4
                };
            })
        };

        return this.createChart('radarChart', 'radar', data, {
            scales: {
                r: {
                    beginAtZero: true,
                    max: 10,
                    ticks: {
                        stepSize: 2,
                        color: this.getSecondaryTextColor()
                    },
                    grid: {
                        color: this.getBorderColor()
                    },
                    angleLines: {
                        color: this.getBorderColor()
                    },
                    pointLabels: {
                        color: this.getTextColor(),
                        font: {
                            size: 12
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        });
    }

    createTimelineChart() {
        // Generar datos de los últimos 7 días
        const last7Days = Array.from({ length: 7 }, (_, i) => {
            const date = new Date();
            date.setDate(date.getDate() - (6 - i));
            return date.toLocaleDateString('es-ES', { month: 'short', day: 'numeric' });
        });

        const data = {
            labels: last7Days,
            datasets: [
                {
                    label: 'Commits',
                    data: [12, 19, 8, 15, 22, 13, 17],
                    borderColor: 'rgba(0, 123, 255, 1)',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7
                },
                {
                    label: 'Issues',
                    data: [5, 8, 3, 6, 9, 4, 7],
                    borderColor: 'rgba(255, 193, 7, 1)',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7
                },
                {
                    label: 'PRs',
                    data: [2, 4, 1, 3, 5, 2, 4],
                    borderColor: 'rgba(40, 167, 69, 1)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }
            ]
        };

        return this.createChart('timelineChart', 'line', data, {
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        });
    }

    createIssuesDistributionChart(repositories) {
        const colors = [
            'rgba(255, 99, 132, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 205, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)'
        ];

        const data = {
            labels: repositories.map(repo => repo.name),
            datasets: [{
                data: repositories.map(repo => repo.open_issues || 0),
                backgroundColor: colors.slice(0, repositories.length),
                borderColor: colors.slice(0, repositories.length).map(color => color.replace('0.8', '1')),
                borderWidth: 2
            }]
        };

        return this.createChart('issuesDistributionChart', 'pie', data, {
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        });
    }

    // Métodos de animación
    animateChart(canvasId, delay = 0) {
        setTimeout(() => {
            if (this.charts[canvasId]) {
                this.charts[canvasId].update('active');
            }
        }, delay);
    }

    animateAllCharts() {
        let delay = 0;
        Object.keys(this.charts).forEach(canvasId => {
            this.animateChart(canvasId, delay);
            delay += 200;
        });
    }

    // Métodos de exportación
    exportChart(canvasId, filename) {
        const chart = this.charts[canvasId];
        if (!chart) return;

        const canvas = chart.canvas;
        const url = canvas.toDataURL('image/png');
        
        const link = document.createElement('a');
        link.download = filename || `chart-${canvasId}.png`;
        link.href = url;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    exportAllCharts() {
        Object.keys(this.charts).forEach(canvasId => {
            this.exportChart(canvasId, `yega-${canvasId}.png`);
        });
    }

    // Utilidades
    formatValue(value) {
        if (value >= 1000000) {
            return (value / 1000000).toFixed(1) + 'M';
        } else if (value >= 1000) {
            return (value / 1000).toFixed(1) + 'K';
        }
        return value.toString();
    }

    generateGradient(ctx, startColor, endColor) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, startColor);
        gradient.addColorStop(1, endColor);
        return gradient;
    }
}

// Exportar para uso global
window.ChartsManager = ChartsManager;