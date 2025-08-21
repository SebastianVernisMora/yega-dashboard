// Configuración global del Dashboard Yega
window.YEGA_CONFIG = {
    // URLs de la API
    API_BASE_URL: './api/',
    ENDPOINTS: {
        overview: 'overview.php',
        repositories: 'repositories.php',
        activity: 'activity.php',
        timeline: 'timeline.php',
        issues: 'issues.php',
        prs: 'prs.php',
        readme: 'readme.php'
    },
    
    // Configuración de GitHub
    GITHUB_CONFIG: {
        baseUrl: 'https://api.github.com',
        owner: 'yegaai',
        repositories: [
            'yega',
            'yega-cli', 
            'yega-desktop',
            'yega-web',
            'yega-docs'
        ],
        itemsPerPage: 30,
        maxRetries: 3,
        retryDelay: 1000
    },
    
    // Configuración de cache
    CACHE_CONFIG: {
        ttl: 5 * 60 * 1000, // 5 minutos
        maxSize: 100,
        prefix: 'yega_dashboard_'
    },
    
    // Configuración de filtros
    FILTER_CONFIG: {
        debounceDelay: 300,
        itemsPerPage: 10,
        maxPages: 100
    },
    
    // Configuración de temas
    THEME_CONFIG: {
        storageKey: 'yega-dashboard-theme',
        default: 'dark',
        transitions: true
    },
    
    // Configuración de notificaciones
    NOTIFICATION_CONFIG: {
        duration: 5000,
        maxNotifications: 5,
        position: 'top-right'
    },
    
    // Configuración de gráficos
    CHART_CONFIG: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 750,
            easing: 'easeInOutQuart'
        },
        colors: {
            primary: '#6366f1',
            secondary: '#8b5cf6',
            accent: '#06b6d4',
            success: '#10b981',
            warning: '#f59e0b',
            error: '#ef4444',
            background: '#161b22',
            text: '#f0f6fc'
        },
        gradients: {
            primary: ['#6366f1', '#8b5cf6'],
            accent: ['#06b6d4', '#6366f1'],
            success: ['#10b981', '#06b6d4']
        }
    },
    
    // Configuración de timeline
    TIMELINE_CONFIG: {
        itemsPerLoad: 20,
        maxItems: 1000,
        autoRefresh: true,
        refreshInterval: 30000, // 30 segundos
        lazyLoad: true
    },
    
    // Configuración de sincronización
    SYNC_CONFIG: {
        autoSync: true,
        syncInterval: 300000, // 5 minutos
        maxConcurrentRequests: 5,
        timeout: 30000
    },
    
    // Mensajes de error
    ERROR_MESSAGES: {
        loadError: 'Error al cargar los datos. Reintentando...',
        networkError: 'Error de conexión. Revisa tu internet.',
        apiError: 'Error en la API de GitHub. Intenta más tarde.',
        parseError: 'Error al procesar los datos recibidos.',
        cacheError: 'Error en el sistema de cache.',
        genericError: 'Ha ocurrido un error inesperado.'
    },
    
    // Mensajes de éxito
    SUCCESS_MESSAGES: {
        syncComplete: 'Sincronización completada exitosamente',
        dataLoaded: 'Datos cargados correctamente',
        themeChanged: 'Tema cambiado correctamente',
        exportComplete: 'Exportación completada'
    },
    
    // Configuración de performance
    PERFORMANCE_CONFIG: {
        enableMetrics: true,
        logLevel: 'info', // 'debug', 'info', 'warn', 'error'
        enableServiceWorker: true,
        enableLazyLoading: true,
        compressionEnabled: true
    },
    
    // Configuración de accesibilidad
    ACCESSIBILITY_CONFIG: {
        enableKeyboardNavigation: true,
        enableScreenReader: true,
        highContrast: false,
        reducedMotion: false
    },
    
    // URLs externas
    EXTERNAL_URLS: {
        github: 'https://github.com',
        documentation: 'https://docs.yega.ai',
        support: 'https://support.yega.ai',
        community: 'https://community.yega.ai'
    },
    
    // Configuración de desarrollo
    DEV_CONFIG: {
        enableDebug: false,
        mockData: false,
        verbose: false,
        enableHotReload: false
    }
};

// Configuración de Chart.js
Chart.defaults.font.family = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
Chart.defaults.color = window.YEGA_CONFIG.CHART_CONFIG.colors.text;
Chart.defaults.borderColor = '#30363d';
Chart.defaults.backgroundColor = 'rgba(99, 102, 241, 0.1)';

// Configuración responsive
Chart.defaults.responsive = true;
Chart.defaults.maintainAspectRatio = false;

// Configuración de animaciones
Chart.defaults.animation.duration = window.YEGA_CONFIG.CHART_CONFIG.animation.duration;
Chart.defaults.animation.easing = window.YEGA_CONFIG.CHART_CONFIG.animation.easing;

// Configuración global de plugins
Chart.defaults.plugins.legend.display = true;
Chart.defaults.plugins.legend.position = 'bottom';
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(22, 27, 34, 0.95)';
Chart.defaults.plugins.tooltip.titleColor = '#f0f6fc';
Chart.defaults.plugins.tooltip.bodyColor = '#8b949e';
Chart.defaults.plugins.tooltip.borderColor = '#30363d';
Chart.defaults.plugins.tooltip.borderWidth = 1;
Chart.defaults.plugins.tooltip.cornerRadius = 8;

// Exportar configuración para otros módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = window.YEGA_CONFIG;
}

// Configuración de colores temáticos
const THEME_COLORS = {
    dark: {
        primary: '#6366f1',
        secondary: '#8b5cf6',
        accent: '#06b6d4',
        success: '#10b981',
        warning: '#f59e0b',
        error: '#ef4444',
        background: '#0d1117',
        surface: '#161b22',
        text: '#f0f6fc',
        textSecondary: '#8b949e',
        border: '#30363d'
    },
    light: {
        primary: '#6366f1',
        secondary: '#8b5cf6',
        accent: '#06b6d4',
        success: '#10b981',
        warning: '#f59e0b',
        error: '#ef4444',
        background: '#ffffff',
        surface: '#f6f8fa',
        text: '#24292f',
        textSecondary: '#656d76',
        border: '#d0d7de'
    }
};

// Aplicar colores según el tema
function updateChartColors(theme = 'dark') {
    const colors = THEME_COLORS[theme];
    
    Chart.defaults.color = colors.text;
    Chart.defaults.borderColor = colors.border;
    Chart.defaults.plugins.tooltip.backgroundColor = colors.surface + 'f0';
    Chart.defaults.plugins.tooltip.titleColor = colors.text;
    Chart.defaults.plugins.tooltip.bodyColor = colors.textSecondary;
    Chart.defaults.plugins.tooltip.borderColor = colors.border;
    
    // Actualizar configuración global
    window.YEGA_CONFIG.CHART_CONFIG.colors = colors;
}

// Detectar tema inicial
const isDarkTheme = document.body.classList.contains('dark-theme');
updateChartColors(isDarkTheme ? 'dark' : 'light');

// Escuchar cambios de tema
document.addEventListener('themeChanged', (e) => {
    updateChartColors(e.detail.theme);
});

// Log de configuración (solo en desarrollo)
if (window.YEGA_CONFIG.DEV_CONFIG.enableDebug) {
    console.log('🚀 Yega Dashboard Config loaded:', window.YEGA_CONFIG);
}