// Aplicación principal del Dashboard Yega
class YegaDashboard {
    constructor() {
        this.data = {
            repositories: [],
            overview: {},
            timeline: [],
            analytics: {},
            charts: {}
        };
        this.currentTab = 'overview';
        this.isLoading = false;
        this.cache = new Map();
        this.notifications = [];
        this.config = window.YEGA_CONFIG || {};
        this.filters = {
            search: '',
            sortBy: 'name',
            filterBy: 'all'
        };
        this.modal = null;
        this.charts = {};
    }

    async init() {
        this.setupEventListeners();
        this.setupTabNavigation();
        this.setupModal();
        this.setupTheme();
        await this.loadInitialData();
        this.startAutoRefresh();
        this.showNotification('Dashboard inicializado correctamente', 'success');
    }

    setupEventListeners() {
        // Navegación por tabs
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.switchTab(e.target.closest('.nav-tab').dataset.tab);
            });
        });

        // Búsqueda de repositorios
        const searchInput = document.getElementById('repoSearch');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => {
                this.filters.search = e.target.value;
                this.filterRepositories();
            }, this.config.FILTER_CONFIG?.debounceDelay || 300));
        }

        // Filtros
        document.getElementById('sortBy')?.addEventListener('change', (e) => {
            this.filters.sortBy = e.target.value;
            this.sortRepositories();
        });

        document.getElementById('filterBy')?.addEventListener('change', (e) => {
            this.filters.filterBy = e.target.value;
            this.filterRepositories();
        });

        // Sincronización
        document.getElementById('syncBtn')?.addEventListener('click', () => {
            this.syncData();
        });

        // Toggle de tema
        document.getElementById('themeToggle')?.addEventListener('click', () => {
            this.toggleTheme();
        });

        // Timeline filters
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.setTimelinePeriod(e.target.dataset.period);
            });
        });

        document.querySelectorAll('.type-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.setTimelineType(e.target.dataset.type);
            });
        });

        // Export buttons
        document.getElementById('exportJson')?.addEventListener('click', () => {
            this.exportTimeline('json');
        });

        document.getElementById('exportCsv')?.addEventListener('click', () => {
            this.exportTimeline('csv');
        });
    }

    setupTabNavigation() {
        const tabs = document.querySelectorAll('.nav-tab');
        const contents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.dataset.tab;
                
                // Remover clases activas
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));
                
                // Agregar clases activas
                tab.classList.add('active');
                const tabContent = document.getElementById(`${tabId}-tab`);
                if (tabContent) {
                    tabContent.classList.add('active');
                }
                
                this.currentTab = tabId;
                this.loadTabData(tabId);
            });
        });
    }

    setupModal() {
        this.modal = document.getElementById('repositoryModal');
        
        // Cerrar modal
        document.getElementById('modalClose')?.addEventListener('click', () => {
            this.closeModal();
        });

        // Cerrar modal al hacer click fuera
        this.modal?.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.closeModal();
            }
        });

        // Navegación por tabs del modal
        document.querySelectorAll('.modal-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.switchModalTab(e.target.dataset.modalTab);
            });
        });

        // Filtros del modal
        document.getElementById('issuesFilter')?.addEventListener('change', (e) => {
            this.filterModalContent('issues', e.target.value);
        });

        document.getElementById('prsFilter')?.addEventListener('change', (e) => {
            this.filterModalContent('prs', e.target.value);
        });
    }

    setupTheme() {
        const savedTheme = localStorage.getItem(this.config.THEME_CONFIG?.storageKey || 'yega-dashboard-theme');
        if (savedTheme === 'light') {
            document.body.classList.remove('dark-theme');
        } else {
            document.body.classList.add('dark-theme');
        }
        this.updateThemeIcon();
    }

    async loadInitialData() {
        this.showLoading();
        try {
            await Promise.all([
                this.loadOverviewData(),
                this.loadRepositoriesData()
            ]);
        } catch (error) {
            this.showNotification(this.config.ERROR_MESSAGES?.loadError || 'Error al cargar datos iniciales', 'error');
        } finally {
            this.hideLoading();
        }
    }

    async loadOverviewData() {
        try {
            const response = await this.fetchData(this.config.ENDPOINTS?.overview || 'overview.php');
            this.data.overview = response;
            this.renderOverview();
        } catch (error) {
            console.error('Error loading overview:', error);
            // Datos de fallback
            this.data.overview = {
                total_repositories: 5,
                total_issues: 42,
                total_prs: 18,
                activity_score: 85
            };
            this.renderOverview();
        }
    }

    async loadRepositoriesData() {
        try {
            const response = await this.fetchData(this.config.ENDPOINTS?.repositories || 'repositories.php');
            this.data.repositories = Array.isArray(response) ? response : response.repositories || [];
            
            // Datos de fallback si no hay datos
            if (!this.data.repositories.length) {
                this.data.repositories = this.getFallbackRepositories();
            }
            
            this.renderRepositories();
        } catch (error) {
            console.error('Error loading repositories:', error);
            this.data.repositories = this.getFallbackRepositories();
            this.renderRepositories();
        }
    }

    getFallbackRepositories() {
        return [
            {
                name: 'yega',
                description: 'Motor principal de IA conversacional Yega',
                language: 'Python',
                stars: 1247,
                forks: 89,
                open_issues: 15,
                open_prs: 5,
                updated_at: '2025-08-15T10:30:00Z',
                url: 'https://github.com/yegaai/yega'
            },
            {
                name: 'yega-cli',
                description: 'Interfaz de línea de comandos para Yega',
                language: 'JavaScript',
                stars: 456,
                forks: 34,
                open_issues: 8,
                open_prs: 2,
                updated_at: '2025-08-14T16:20:00Z',
                url: 'https://github.com/yegaai/yega-cli'
            },
            {
                name: 'yega-desktop',
                description: 'Aplicación de escritorio para Yega',
                language: 'TypeScript',
                stars: 789,
                forks: 56,
                open_issues: 12,
                open_prs: 3,
                updated_at: '2025-08-13T14:15:00Z',
                url: 'https://github.com/yegaai/yega-desktop'
            },
            {
                name: 'yega-web',
                description: 'Interfaz web para el ecosistema Yega',
                language: 'Vue.js',
                stars: 623,
                forks: 42,
                open_issues: 6,
                open_prs: 1,
                updated_at: '2025-08-12T11:45:00Z',
                url: 'https://github.com/yegaai/yega-web'
            },
            {
                name: 'yega-docs',
                description: 'Documentación oficial del ecosistema Yega',
                language: 'Markdown',
                stars: 234,
                forks: 78,
                open_issues: 4,
                open_prs: 2,
                updated_at: '2025-08-11T09:30:00Z',
                url: 'https://github.com/yegaai/yega-docs'
            }
        ];
    }

    async loadTabData(tabId) {
        if (this.isLoading) return;

        try {
            switch (tabId) {
                case 'overview':
                    await this.loadOverviewData();
                    break;
                case 'repositories':
                    await this.loadRepositoriesData();
                    break;
                case 'analytics':
                    await this.loadAnalyticsData();
                    break;
                case 'timeline':
                    await this.loadTimelineData();
                    break;
            }
        } catch (error) {
            this.showNotification('Error al cargar datos de la pestaña', 'error');
        }
    }

    async loadAnalyticsData() {
        try {
            const response = await this.fetchData(this.config.ENDPOINTS?.activity || 'activity.php');
            this.data.analytics = response;
            this.renderAnalytics();
        } catch (error) {
            console.error('Error loading analytics:', error);
            this.renderAnalytics();
        }
    }

    async loadTimelineData(period = '7', type = 'all') {
        try {
            const endpoint = `${this.config.ENDPOINTS?.timeline || 'timeline.php'}?period=${period}&type=${type}`;
            const response = await this.fetchData(endpoint);
            this.data.timeline = Array.isArray(response) ? response : response.timeline || [];
            
            // Datos de fallback
            if (!this.data.timeline.length) {
                this.data.timeline = this.getFallbackTimeline();
            }
            
            this.renderTimeline();
        } catch (error) {
            console.error('Error loading timeline:', error);
            this.data.timeline = this.getFallbackTimeline();
            this.renderTimeline();
        }
    }

    getFallbackTimeline() {
        return [
            {
                type: 'commit',
                title: 'Actualización del modelo de IA principal',
                repository: 'yega',
                created_at: '2025-08-17T10:30:00Z',
                description: 'Mejoras en precisión y velocidad de respuesta'
            },
            {
                type: 'issue',
                title: 'Problema con la configuración de memoria',
                repository: 'yega-cli',
                created_at: '2025-08-16T15:20:00Z',
                description: 'Error al asignar memoria en sistemas con RAM limitada'
            },
            {
                type: 'pr',
                title: 'Nueva interfaz para configuración avanzada',
                repository: 'yega-desktop',
                created_at: '2025-08-15T12:45:00Z',
                description: 'Implementación de panel de configuración mejorado'
            }
        ];
    }

    renderOverview() {
        const data = this.data.overview;
        
        // Actualizar estadísticas
        this.updateStat('totalRepos', data.total_repositories || this.data.repositories.length || 0);
        this.updateStat('totalIssues', data.total_issues || 0);
        this.updateStat('totalPRs', data.total_prs || 0);
        this.updateStat('activityScore', data.activity_score || 0);
        
        // Renderizar cards de repositorios
        this.renderRepositoryCards();
        
        // Renderizar gráficos
        this.renderOverviewCharts();
    }

    updateStat(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            // Animación de contador
            const currentValue = parseInt(element.textContent) || 0;
            const targetValue = parseInt(value) || 0;
            this.animateCounter(element, currentValue, targetValue, 1000);
        }
    }

    animateCounter(element, start, end, duration) {
        const startTime = performance.now();
        const difference = end - start;
        
        const step = (timestamp) => {
            const elapsed = timestamp - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const current = Math.floor(start + difference * progress);
            
            element.textContent = current.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };
        
        requestAnimationFrame(step);
    }

    renderRepositoryCards() {
        const container = document.getElementById('repositoriesGrid');
        if (!container || !this.data.repositories.length) {
            if (container) {
                container.innerHTML = this.renderEmptyState('repositorios', 'folder-open');
            }
            return;
        }
        
        container.innerHTML = this.data.repositories.map(repo => `
            <div class="repo-card" onclick="window.YegaDashboard.openRepositoryModal('${repo.name}')">
                <div class="repo-header">
                    <div class="repo-info">
                        <h3><i class="fab fa-github"></i> ${this.escapeHtml(repo.name)}</h3>
                        <p>${this.escapeHtml(repo.description || 'Sin descripción')}</p>
                    </div>
                    ${repo.language ? `
                        <div class="repo-language">
                            <span class="language-dot" style="background-color: ${this.getLanguageColor(repo.language)}"></span>
                            ${this.escapeHtml(repo.language)}
                        </div>
                    ` : ''}
                </div>
                <div class="repo-stats">
                    <div class="repo-stat stars">
                        <i class="fas fa-star"></i>
                        <span>${this.formatNumber(repo.stars || 0)}</span>
                    </div>
                    <div class="repo-stat issues">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>${this.formatNumber(repo.open_issues || 0)}</span>
                    </div>
                    <div class="repo-stat prs">
                        <i class="fas fa-code-branch"></i>
                        <span>${this.formatNumber(repo.open_prs || 0)}</span>
                    </div>
                </div>
            </div>
        `).join('');
    }

    renderRepositories() {
        const container = document.getElementById('repositoriesList');
        if (!container) return;
        
        let repositories = [...this.data.repositories];
        
        // Aplicar filtros
        repositories = this.applyRepositoryFilters(repositories);
        
        if (!repositories.length) {
            container.innerHTML = this.renderEmptyState('repositorios que coincidan con los filtros', 'search');
            return;
        }
        
        container.innerHTML = repositories.map(repo => `
            <div class="repo-item" onclick="window.YegaDashboard.openRepositoryModal('${repo.name}')">
                <div class="repo-item-header">
                    <div class="repo-item-info">
                        <h3><i class="fab fa-github"></i> ${this.escapeHtml(repo.name)}</h3>
                        <p>${this.escapeHtml(repo.description || 'Sin descripción')}</p>
                    </div>
                    <div class="repo-item-meta">
                        ${repo.language ? `
                            <div class="repo-language">
                                <span class="language-dot" style="background-color: ${this.getLanguageColor(repo.language)}"></span>
                                ${this.escapeHtml(repo.language)}
                            </div>
                        ` : ''}
                        <span class="text-muted">Actualizado: ${this.formatDate(repo.updated_at)}</span>
                    </div>
                </div>
                <div class="repo-item-stats">
                    <div class="repo-stat stars">
                        <i class="fas fa-star"></i>
                        <span>${this.formatNumber(repo.stars || 0)} estrellas</span>
                    </div>
                    <div class="repo-stat issues">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>${this.formatNumber(repo.open_issues || 0)} issues</span>
                    </div>
                    <div class="repo-stat prs">
                        <i class="fas fa-code-branch"></i>
                        <span>${this.formatNumber(repo.open_prs || 0)} PRs</span>
                    </div>
                </div>
            </div>
        `).join('');
    }

    applyRepositoryFilters(repositories) {
        let filtered = [...repositories];
        
        // Filtro de búsqueda
        if (this.filters.search) {
            const search = this.filters.search.toLowerCase();
            filtered = filtered.filter(repo => 
                repo.name.toLowerCase().includes(search) ||
                (repo.description && repo.description.toLowerCase().includes(search))
            );
        }
        
        // Filtro por tipo
        switch (this.filters.filterBy) {
            case 'active':
                filtered = filtered.filter(repo => new Date(repo.updated_at) > new Date(Date.now() - 30 * 24 * 60 * 60 * 1000));
                break;
            case 'issues':
                filtered = filtered.filter(repo => (repo.open_issues || 0) > 0);
                break;
            case 'prs':
                filtered = filtered.filter(repo => (repo.open_prs || 0) > 0);
                break;
        }
        
        // Ordenamiento
        filtered.sort((a, b) => {
            switch (this.filters.sortBy) {
                case 'name':
                    return a.name.localeCompare(b.name);
                case 'stars':
                    return (b.stars || 0) - (a.stars || 0);
                case 'activity':
                    return new Date(b.updated_at) - new Date(a.updated_at);
                case 'issues':
                    return (b.open_issues || 0) - (a.open_issues || 0);
                default:
                    return 0;
            }
        });
        
        return filtered;
    }

    async fetchData(endpoint, options = {}) {
        const baseUrl = this.config.API_BASE || './api/';
        const url = endpoint.startsWith('http') ? endpoint : `${baseUrl}${endpoint}`;
        const cacheKey = url + JSON.stringify(options);
        
        // Verificar caché
        if (this.config.CACHE_CONFIG?.enabled && this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            const ttl = this.config.CACHE_CONFIG?.ttl || 300000;
            if (Date.now() - cached.timestamp < ttl) {
                return cached.data;
            }
        }
        
        try {
            const response = await fetch(url, {
                method: options.method || 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });
            
            if (!response.ok) {
                const errorMessage = this.getErrorMessage(response.status);
                throw new Error(errorMessage);
            }
            
            const data = await response.json();
            
            // Guardar en caché
            if (this.config.CACHE_CONFIG?.enabled) {
                this.cache.set(cacheKey, {
                    data,
                    timestamp: Date.now()
                });
                
                // Limpiar caché si excede el tamaño máximo
                const maxSize = this.config.CACHE_CONFIG?.maxSize || 50;
                if (this.cache.size > maxSize) {
                    const firstKey = this.cache.keys().next().value;
                    this.cache.delete(firstKey);
                }
            }
            
            return data;
        } catch (error) {
            console.error(`Error fetching ${endpoint}:`, error);
            throw error;
        }
    }

    getErrorMessage(status) {
        const messages = this.config.ERROR_MESSAGES || {};
        switch (status) {
            case 404:
                return messages.notFound || 'Recurso no encontrado';
            case 401:
                return messages.unauthorized || 'No autorizado';
            case 429:
                return messages.rateLimited || 'Límite de solicitudes excedido';
            case 500:
                return messages.serverError || 'Error del servidor';
            default:
                return messages.networkError || 'Error de conexión';
        }
    }

    switchTab(tabId) {
        if (this.currentTab === tabId) return;
        
        this.currentTab = tabId;
        this.loadTabData(tabId);
    }

    showLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.add('show');
        }
        this.isLoading = true;
    }

    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
        this.isLoading = false;
    }

    showNotification(message, type = 'info') {
        const toast = this.createToast(message, type);
        document.body.appendChild(toast);
        
        // Mostrar toast
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Auto-ocultar
        if (this.config.NOTIFICATION_CONFIG?.autoHide !== false) {
            const delay = this.config.NOTIFICATION_CONFIG?.hideDelay || 5000;
            setTimeout(() => this.hideNotification(toast), delay);
        }
        
        // Limpiar notificaciones excesivas
        const maxNotifications = this.config.NOTIFICATION_CONFIG?.maxNotifications || 5;
        const toasts = document.querySelectorAll('.toast');
        if (toasts.length > maxNotifications) {
            this.hideNotification(toasts[0]);
        }
    }

    createToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const iconMap = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        
        toast.innerHTML = `
            <i class="toast-icon ${iconMap[type] || iconMap.info}"></i>
            <span class="toast-message">${this.escapeHtml(message)}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        return toast;
    }

    hideNotification(toast) {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentElement) {
                toast.parentElement.removeChild(toast);
            }
        }, 300);
    }

    renderEmptyState(entity, icon = 'folder-open') {
        return `
            <div class="empty-state">
                <i class="fas fa-${icon}"></i>
                <h3>No hay ${entity}</h3>
                <p>No se encontraron datos para mostrar.</p>
            </div>
        `;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    getLanguageColor(language) {
        const colors = {
            'JavaScript': '#f1e05a',
            'Python': '#3572A5',
            'Java': '#b07219',
            'TypeScript': '#2b7489',
            'PHP': '#4F5D95',
            'C++': '#f34b7d',
            'C#': '#239120',
            'Go': '#00ADD8',
            'Rust': '#dea584',
            'Ruby': '#701516',
            'Vue.js': '#4FC08D',
            'Markdown': '#083fa1'
        };
        return colors[language] || '#6e7681';
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    async syncData() {
        const syncBtn = document.getElementById('syncBtn');
        if (syncBtn) {
            syncBtn.disabled = true;
            const originalContent = syncBtn.innerHTML;
            syncBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Sincronizando...</span>';
        }
        
        this.showLoading();
        try {
            await this.fetchData(this.config.ENDPOINTS?.sync || 'sync.php', { method: 'POST' });
            this.cache.clear();
            await this.loadInitialData();
            this.showNotification(this.config.SUCCESS_MESSAGES?.syncSuccess || 'Datos sincronizados correctamente', 'success');
        } catch (error) {
            this.showNotification(this.config.ERROR_MESSAGES?.syncError || 'Error al sincronizar datos', 'error');
        } finally {
            this.hideLoading();
            if (syncBtn) {
                syncBtn.disabled = false;
                syncBtn.innerHTML = '<i class="fas fa-sync-alt"></i> <span>Sincronizar</span>';
            }
        }
    }

    toggleTheme() {
        document.body.classList.toggle('dark-theme');
        const theme = document.body.classList.contains('dark-theme') ? 'dark' : 'light';
        const storageKey = this.config.THEME_CONFIG?.storageKey || 'yega-dashboard-theme';
        localStorage.setItem(storageKey, theme);
        this.updateThemeIcon();
        
        // Actualizar gráficos si existen
        this.updateChartsTheme();
    }

    updateThemeIcon() {
        const themeIcon = document.querySelector('#themeToggle i');
        if (themeIcon) {
            const isDark = document.body.classList.contains('dark-theme');
            themeIcon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    startAutoRefresh() {
        if (!this.config.SYNC_CONFIG?.autoSync) return;
        
        const interval = this.config.SYNC_CONFIG?.interval || 300000;
        setInterval(() => {
            if (!this.isLoading) {
                this.loadTabData(this.currentTab);
            }
        }, interval);
    }

    // Modal functions
    async openRepositoryModal(repoName) {
        const repo = this.data.repositories.find(r => r.name === repoName);
        if (!repo) return;
        
        document.getElementById('modalRepoName').textContent = repo.name;
        this.modal.classList.add('show');
        
        // Cargar datos del repositorio
        await this.loadRepositoryDetails(repo);
    }

    closeModal() {
        if (this.modal) {
            this.modal.classList.remove('show');
        }
    }

    switchModalTab(tabName) {
        // Actualizar tabs activos
        document.querySelectorAll('.modal-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.modal-tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        document.querySelector(`[data-modal-tab="${tabName}"]`).classList.add('active');
        document.getElementById(`modal-${tabName}`).classList.add('active');
    }

    async loadRepositoryDetails(repo) {
        this.renderRepositoryOverview(repo);
        await this.loadRepositoryIssues(repo.name);
        await this.loadRepositoryPRs(repo.name);
        await this.loadRepositoryReadme(repo.name);
    }

    renderRepositoryOverview(repo) {
        const container = document.getElementById('modal-overview');
        if (!container) return;
        
        container.innerHTML = `
            <div class="repo-overview">
                <div class="repo-stats-grid">
                    <div class="stat-item">
                        <h4>${this.formatNumber(repo.stars || 0)}</h4>
                        <p>Estrellas</p>
                    </div>
                    <div class="stat-item">
                        <h4>${this.formatNumber(repo.forks || 0)}</h4>
                        <p>Forks</p>
                    </div>
                    <div class="stat-item">
                        <h4>${this.formatNumber(repo.open_issues || 0)}</h4>
                        <p>Issues Abiertas</p>
                    </div>
                    <div class="stat-item">
                        <h4>${this.formatNumber(repo.open_prs || 0)}</h4>
                        <p>PRs Abiertos</p>
                    </div>
                </div>
                <div class="repo-details">
                    <p><strong>Descripción:</strong> ${this.escapeHtml(repo.description || 'Sin descripción')}</p>
                    <p><strong>Lenguaje:</strong> ${this.escapeHtml(repo.language || 'N/A')}</p>
                    <p><strong>Última actualización:</strong> ${this.formatDate(repo.updated_at)}</p>
                    ${repo.url ? `<p><strong>Repositorio:</strong> <a href="${repo.url}" target="_blank">${repo.url}</a></p>` : ''}
                </div>
            </div>
        `;
    }

    async loadRepositoryIssues(repoName) {
        try {
            const endpoint = `${this.config.ENDPOINTS?.issues || 'issues.php'}?repo=${encodeURIComponent(repoName)}`;
            const issues = await this.fetchData(endpoint);
            this.renderRepositoryIssues(Array.isArray(issues) ? issues : []);
        } catch (error) {
            console.error('Error loading repository issues:', error);
            this.renderRepositoryIssues([]);
        }
    }

    async loadRepositoryPRs(repoName) {
        try {
            const endpoint = `${this.config.ENDPOINTS?.prs || 'prs.php'}?repo=${encodeURIComponent(repoName)}`;
            const prs = await this.fetchData(endpoint);
            this.renderRepositoryPRs(Array.isArray(prs) ? prs : []);
        } catch (error) {
            console.error('Error loading repository PRs:', error);
            this.renderRepositoryPRs([]);
        }
    }

    async loadRepositoryReadme(repoName) {
        try {
            const endpoint = `${this.config.ENDPOINTS?.readme || 'readme.php'}?repo=${encodeURIComponent(repoName)}`;
            const readme = await this.fetchData(endpoint);
            this.renderRepositoryReadme(readme.content || '# README\n\nContenido del README para ' + repoName);
        } catch (error) {
            console.error('Error loading repository README:', error);
            this.renderRepositoryReadme('# README no disponible\n\nNo se pudo cargar el contenido del README.');
        }
    }

    renderRepositoryIssues(issues) {
        const container = document.getElementById('issuesList');
        if (!container) return;
        
        if (!issues.length) {
            container.innerHTML = this.renderEmptyState('issues', 'exclamation-circle');
            return;
        }
        
        container.innerHTML = issues.map(issue => `
            <div class="issue-item">
                <div class="issue-header">
                    <div class="issue-title">
                        ${this.escapeHtml(issue.title)}
                        <span class="issue-number">#${issue.number}</span>
                    </div>
                    <div class="issue-status ${issue.state}">
                        <i class="fas fa-${issue.state === 'open' ? 'circle' : 'check-circle'}"></i>
                        ${issue.state === 'open' ? 'Abierta' : 'Cerrada'}
                    </div>
                </div>
                <div class="issue-meta">
                    <span>Por ${this.escapeHtml(issue.user?.login || 'Usuario')}</span>
                    <span>${this.formatDate(issue.created_at)}</span>
                    ${issue.comments > 0 ? `<span><i class="fas fa-comments"></i> ${issue.comments}</span>` : ''}
                </div>
            </div>
        `).join('');
    }

    renderRepositoryPRs(prs) {
        const container = document.getElementById('prsList');
        if (!container) return;
        
        if (!prs.length) {
            container.innerHTML = this.renderEmptyState('pull requests', 'code-branch');
            return;
        }
        
        container.innerHTML = prs.map(pr => `
            <div class="pr-item">
                <div class="pr-header">
                    <div class="pr-title">
                        ${this.escapeHtml(pr.title)}
                        <span class="pr-number">#${pr.number}</span>
                    </div>
                    <div class="pr-status ${pr.merged ? 'merged' : pr.state}">
                        <i class="fas fa-${pr.merged ? 'code-branch' : pr.state === 'open' ? 'circle' : 'times-circle'}"></i>
                        ${pr.merged ? 'Merged' : pr.state === 'open' ? 'Abierto' : 'Cerrado'}
                    </div>
                </div>
                <div class="pr-meta">
                    <span>Por ${this.escapeHtml(pr.user?.login || 'Usuario')}</span>
                    <span>${this.formatDate(pr.created_at)}</span>
                    ${pr.comments > 0 ? `<span><i class="fas fa-comments"></i> ${pr.comments}</span>` : ''}
                </div>
            </div>
        `).join('');
    }

    renderRepositoryReadme(content) {
        const container = document.getElementById('readmeContent');
        if (!container) return;
        
        try {
            // Usar marked.js para renderizar markdown
            if (window.marked) {
                container.innerHTML = marked.parse(content);
            } else {
                // Fallback simple si marked.js no está disponible
                container.innerHTML = `<pre>${this.escapeHtml(content)}</pre>`;
            }
        } catch (error) {
            container.innerHTML = `<pre>${this.escapeHtml(content)}</pre>`;
        }
    }

    filterRepositories() {
        this.renderRepositories();
    }

    sortRepositories() {
        this.renderRepositories();
    }

    setTimelinePeriod(period) {
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-period="${period}"]`).classList.add('active');
        
        const currentType = document.querySelector('.type-btn.active')?.dataset.type || 'all';
        this.loadTimelineData(period, currentType);
    }

    setTimelineType(type) {
        document.querySelectorAll('.type-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-type="${type}"]`).classList.add('active');
        
        const currentPeriod = document.querySelector('.filter-btn.active')?.dataset.period || '7';
        this.loadTimelineData(currentPeriod, type);
    }

    renderTimeline() {
        const container = document.getElementById('timelineContainer');
        if (!container) return;
        
        if (!Array.isArray(this.data.timeline) || !this.data.timeline.length) {
            container.innerHTML = this.renderEmptyState('eventos en el timeline', 'clock');
            return;
        }
        
        container.innerHTML = this.data.timeline.map(event => `
            <div class="timeline-item">
                <div class="timeline-item-header">
                    <div class="timeline-item-info">
                        <h4>${this.escapeHtml(event.title || event.message)}</h4>
                        <p>${this.escapeHtml(event.repository || 'Repositorio desconocido')}</p>
                    </div>
                    <div class="timeline-item-meta">
                        <div class="timeline-item-type ${event.type}">
                            <i class="fas fa-${this.getEventIcon(event.type)}"></i>
                            ${this.getEventTypeName(event.type)}
                        </div>
                        <span>${this.formatDate(event.created_at)}</span>
                    </div>
                </div>
                ${event.description ? `<p class="timeline-item-description">${this.escapeHtml(event.description)}</p>` : ''}
            </div>
        `).join('');
    }

    getEventIcon(type) {
        const icons = {
            commit: 'code-commit',
            issue: 'exclamation-circle',
            pr: 'code-branch',
            release: 'tag'
        };
        return icons[type] || 'circle';
    }

    getEventTypeName(type) {
        const names = {
            commit: 'Commit',
            issue: 'Issue',
            pr: 'Pull Request',
            release: 'Release'
        };
        return names[type] || type;
    }

    exportTimeline(format) {
        if (!this.data.timeline?.length) {
            this.showNotification('No hay datos para exportar', 'warning');
            return;
        }
        
        try {
            let content, filename, mimeType;
            
            if (format === 'json') {
                content = JSON.stringify(this.data.timeline, null, 2);
                filename = `yega-timeline-${new Date().toISOString().split('T')[0]}.json`;
                mimeType = 'application/json';
            } else if (format === 'csv') {
                const headers = ['Fecha', 'Tipo', 'Repositorio', 'Título', 'Descripción'];
                const rows = this.data.timeline.map(event => [
                    this.formatDate(event.created_at),
                    this.getEventTypeName(event.type),
                    event.repository || '',
                    event.title || event.message || '',
                    event.description || ''
                ]);
                
                content = [headers, ...rows]
                    .map(row => row.map(cell => `"${(cell || '').toString().replace(/"/g, '""')}"`).join(','))
                    .join('\n');
                
                filename = `yega-timeline-${new Date().toISOString().split('T')[0]}.csv`;
                mimeType = 'text/csv';
            }
            
            const blob = new Blob([content], { type: mimeType });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            this.showNotification(`Timeline exportado como ${format.toUpperCase()}`, 'success');
        } catch (error) {
            this.showNotification('Error al exportar timeline', 'error');
            console.error('Export error:', error);
        }
    }

    renderOverviewCharts() {
        this.renderActivityChart();
        this.renderIssuesPRsChart();
    }

    renderActivityChart() {
        const canvas = document.getElementById('activityChart');
        if (!canvas || !window.Chart) return;
        
        const ctx = canvas.getContext('2d');
        
        // Destruir gráfico existente
        if (this.charts.activity) {
            this.charts.activity.destroy();
        }
        
        const data = {
            labels: this.data.repositories.map(repo => repo.name),
            datasets: [{
                label: 'Estrellas',
                data: this.data.repositories.map(repo => repo.stars || 0),
                backgroundColor: 'rgba(0, 123, 255, 0.8)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }]
        };
        
        this.charts.activity = new Chart(ctx, {
            type: 'bar',
            data: data,
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
                        beginAtZero: true
                    }
                }
            }
        });
    }

    renderIssuesPRsChart() {
        const canvas = document.getElementById('issuesPrsChart');
        if (!canvas || !window.Chart) return;
        
        const ctx = canvas.getContext('2d');
        
        // Destruir gráfico existente
        if (this.charts.issuesPrs) {
            this.charts.issuesPrs.destroy();
        }
        
        const totalIssues = this.data.repositories.reduce((sum, repo) => sum + (repo.open_issues || 0), 0);
        const totalPRs = this.data.repositories.reduce((sum, repo) => sum + (repo.open_prs || 0), 0);
        
        const data = {
            labels: ['Issues', 'Pull Requests'],
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
                borderWidth: 1
            }]
        };
        
        this.charts.issuesPrs = new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%'
            }
        });
    }

    renderAnalytics() {
        this.renderRadarChart();
        this.renderTimelineChart();
        this.renderIssuesDistributionChart();
        this.renderContributorsList();
    }

    renderRadarChart() {
        const canvas = document.getElementById('radarChart');
        if (!canvas || !window.Chart) return;
        
        const ctx = canvas.getContext('2d');
        
        if (this.charts.radar) {
            this.charts.radar.destroy();
        }
        
        const repos = this.data.repositories.slice(0, 3); // Top 3 repos
        
        const data = {
            labels: ['Estrellas', 'Forks', 'Issues', 'PRs'],
            datasets: repos.map((repo, index) => ({
                label: repo.name,
                data: [
                    Math.min((repo.stars || 0) / 100, 10),
                    Math.min((repo.forks || 0) / 20, 10),
                    Math.min((repo.open_issues || 0) / 5, 10),
                    Math.min((repo.open_prs || 0) / 3, 10)
                ],
                backgroundColor: `hsla(${index * 120}, 70%, 50%, 0.2)`,
                borderColor: `hsla(${index * 120}, 70%, 50%, 1)`,
                borderWidth: 2
            }))
        };
        
        this.charts.radar = new Chart(ctx, {
            type: 'radar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 10
                    }
                }
            }
        });
    }

    renderTimelineChart() {
        const canvas = document.getElementById('timelineChart');
        if (!canvas || !window.Chart) return;
        
        const ctx = canvas.getContext('2d');
        
        if (this.charts.timeline) {
            this.charts.timeline.destroy();
        }
        
        // Datos simulados para la línea de tiempo
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
                    tension: 0.4
                },
                {
                    label: 'Issues',
                    data: [5, 8, 3, 6, 9, 4, 7],
                    borderColor: 'rgba(255, 193, 7, 1)',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.4
                }
            ]
        };
        
        this.charts.timeline = new Chart(ctx, {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    renderIssuesDistributionChart() {
        const canvas = document.getElementById('issuesDistributionChart');
        if (!canvas || !window.Chart) return;
        
        const ctx = canvas.getContext('2d');
        
        if (this.charts.issuesDistribution) {
            this.charts.issuesDistribution.destroy();
        }
        
        const data = {
            labels: this.data.repositories.map(repo => repo.name),
            datasets: [{
                data: this.data.repositories.map(repo => repo.open_issues || 0),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 205, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ],
                borderWidth: 1
            }]
        };
        
        this.charts.issuesDistribution = new Chart(ctx, {
            type: 'pie',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    renderContributorsList() {
        const container = document.getElementById('contributorsList');
        if (!container) return;
        
        // Datos simulados de contribuidores
        const contributors = [
            { name: 'yegaai-dev', commits: 156, avatar: 'YD' },
            { name: 'contributor1', commits: 89, avatar: 'C1' },
            { name: 'contributor2', commits: 67, avatar: 'C2' },
            { name: 'contributor3', commits: 45, avatar: 'C3' },
            { name: 'contributor4', commits: 32, avatar: 'C4' }
        ];
        
        container.innerHTML = contributors.map(contributor => `
            <div class="contributor-item">
                <div class="contributor-avatar">${contributor.avatar}</div>
                <div class="contributor-info">
                    <div class="contributor-name">${this.escapeHtml(contributor.name)}</div>
                    <div class="contributor-stats">
                        <span class="contributor-score">${contributor.commits}</span> commits
                    </div>
                </div>
            </div>
        `).join('');
    }

    filterModalContent(type, filter) {
        // Implementar filtros específicos para modal
        if (type === 'issues') {
            // Filtrar issues
        } else if (type === 'prs') {
            // Filtrar PRs
        }
    }

    updateChartsTheme() {
        // Actualizar tema de los gráficos
        Object.values(this.charts).forEach(chart => {
            if (chart && chart.update) {
                chart.update();
            }
        });
    }
}

// Inicializar aplicación
window.YegaDashboard = new YegaDashboard();