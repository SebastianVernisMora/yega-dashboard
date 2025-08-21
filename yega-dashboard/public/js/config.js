// Configuración global del dashboard
const DASHBOARD_CONFIG = {
    // Configuración de la API
    api: {
        baseUrl: '/api/v1',
        endpoints: {
            repositories: '/repositories',
            issues: '/issues',
            pullRequests: '/pull-requests',
            commits: '/commits'
        },
        timeout: 30000,
        retryAttempts: 3
    },
    
    // Configuración de colores neón
    colors: {
        neonPink: '#ff007f',
        neonPinkBright: '#ff1493',
        neonCyan: '#00ffff',
        neonCyanBright: '#00bfff',
        neonPurple: '#8a2be2',
        neonPurpleBright: '#9400d3',
        neonGreen: '#32ff32',
        neonGreenBright: '#00ff00',
        bgBlack: '#000000',
        bgDark: '#0a0a0a'
    },
    
    // Configuración de animaciones
    animations: {
        glowDuration: 2000,
        borderGlowDuration: 3000,
        numberAnimationDuration: 1000,
        hoverTransitionDuration: 300,
        modalAnimationDuration: 300
    },
    
    // Configuración de filtros
    filters: {
        defaultPeriod: 30,
        languages: [
            'JavaScript',
            'PHP', 
            'Python',
            'Go',
            'Java',
            'TypeScript',
            'Markdown'
        ],
        refreshInterval: 30000
    },
    
    // Configuración de Chart.js
    charts: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: '#ffffff',
                    font: {
                        family: 'Arial',
                        size: 12
                    }
                }
            }
        },
        scales: {
            r: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)'
                },
                pointLabels: {
                    color: '#ffffff'
                },
                ticks: {
                    color: '#ffffff',
                    backdropColor: 'transparent'
                }
            }
        }
    },
    
    // Configuración de notificaciones
    notifications: {
        duration: 5000,
        position: 'top-right',
        types: {
            success: {
                color: '#32ff32',
                icon: 'fas fa-check-circle'
            },
            error: {
                color: '#ff007f',
                icon: 'fas fa-exclamation-triangle'
            },
            info: {
                color: '#00ffff',
                icon: 'fas fa-info-circle'
            },
            warning: {
                color: '#8a2be2',
                icon: 'fas fa-exclamation-circle'
            }
        }
    },
    
    // Configuración de efectos especiales
    effects: {
        particles: {
            count: 50,
            speed: 5,
            opacity: 0.3
        },
        cursor: {
            glowSize: 20,
            glowColor: 'rgba(0, 255, 255, 0.3)'
        },
        hover: {
            scale: 1.02,
            translateY: -5,
            glowIntensity: 0.3
        }
    }
};

// Exportar configuración para uso global
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DASHBOARD_CONFIG;
} else {
    window.DASHBOARD_CONFIG = DASHBOARD_CONFIG;
}