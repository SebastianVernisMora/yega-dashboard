// Dashboard JavaScript para Yega Ecosistema
class YegaDashboard {
    constructor() {
        this.apiBase = '/api';
        this.repositories = [];
        this.currentSection = 'overview';
        this.charts = {};
        
        this.init();
    }

    async init() {
        console.log('Inicializando Yega Dashboard...');
        
        // Configurar navegación
        this.setupNavigation();
        
        // Cargar datos iniciales
        await this.loadInitialData();
        
        // Configurar eventos
        this.setupEventListeners();
        
        // Inicializar gráficos
        this.initializeCharts();
        
        console.log('Dashboard inicializado correctamente');
    }

    setupNavigation() {
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Remover clase active de todos los links
                navLinks.forEach(l => l.classList.remove('active'));
                
                // Agregar clase active al link clickeado
                link.classList.add('active');
                
                // Obtener la sección
                const section = link.dataset.section;
                this.showSection(section);
            });
        });
    }

    showSection(sectionName) {
        // Ocultar todas las secciones
        const sections = document.querySelectorAll('.content-section');
        sections.forEach(section => section.classList.remove('active'));
        
        // Mostrar la sección seleccionada
        const targetSection = document.getElementById(`${sectionName}-section`);
        if (targetSection) {
            targetSection.classList.add('active');
            this.currentSection = sectionName;
            
            // Cargar datos específicos de la sección
            this.loadSectionData(sectionName);
        }
    }

    async loadInitialData() {
        try {
            // Cargar repositorios
            const reposResponse = await fetch(`${this.apiBase}/repositories`);
            const reposData = await reposResponse.json();
            
            if (reposData.success) {
                this.repositories = reposData.data;
                this.updateRepositoriesFilter();
                this.loadOverviewData();
            }
            
            // Cargar estadísticas generales
            const statsResponse = await fetch(`${this.apiBase}/stats/overview`);
            const statsData = await statsResponse.json();
            
            if (statsData.success) {
                this.updateHeaderStats(statsData.data);
                this.updateOverviewCards(statsData.data);
            }
            
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
            this.showError('Error cargando datos del dashboard');
        }
    }

    updateHeaderStats(stats) {
        const totalRepos = stats.length;
        const totalIssues = stats.reduce((sum, repo) => sum + parseInt(repo.total_issues), 0);
        const totalPRs = stats.reduce((sum, repo) => sum + parseInt(repo.total_prs), 0);
        
        document.getElementById('total-repos').textContent = totalRepos;
        document.getElementById('total-issues').textContent = totalIssues;
        document.getElementById('total-prs').textContent = totalPRs;
        
        // Efectos neón en los números
        this.animateNumbers();
    }

    updateOverviewCards(stats) {
        const repoMapping = {
            'Yega-Ecosistema': 'ecosistema-stats',
            'Yega-API': 'api-stats',
            'Yega-Tienda': 'tienda-stats',
            'Yega-Repartidor': 'repartidor-stats',
            'Yega-Cliente': 'cliente-stats'
        };
        
        stats.forEach(repo => {
            const elementId = repoMapping[repo.name];
            if (elementId) {
                const element = document.getElementById(elementId);
                if (element) {
                    element.innerHTML = this.createRepoStatsHTML(repo);
                }
            }
        });
    }

    createRepoStatsHTML(repo) {
        return `
            <div class="repo-stats">
                <div class="stat-row">
                    <i class="fas fa-star neon-green"></i>
                    <span>${repo.stars} Stars</span>
                </div>
                <div class="stat-row">
                    <i class="fas fa-code-branch neon-cyan"></i>
                    <span>${repo.forks} Forks</span>
                </div>
                <div class="stat-row">
                    <i class="fas fa-exclamation-circle neon-pink"></i>
                    <span>${repo.open_issues} Issues Abiertos</span>
                </div>
                <div class="stat-row">
                    <i class="fas fa-code-branch neon-purple"></i>
                    <span>${repo.total_prs} Pull Requests</span>
                </div>
                <button class="repo-readme-btn" onclick="dashboard.showReadme('${repo.name}')">
                    <i class="fas fa-book"></i> Ver README
                </button>
            </div>
        `;
    }

    updateRepositoriesFilter() {
        const repoFilter = document.getElementById('repo-filter');
        
        // Limpiar opciones existentes excepto "Todos"
        while (repoFilter.children.length > 1) {
            repoFilter.removeChild(repoFilter.lastChild);
        }
        
        // Agregar repositorios
        this.repositories.forEach(repo => {
            const option = document.createElement('option');
            option.value = repo.name;
            option.textContent = repo.name;
            repoFilter.appendChild(option);
        });
    }

    async loadSectionData(section) {
        switch (section) {
            case 'repositories':
                this.loadRepositoriesSection();
                break;
            case 'issues':
                this.loadIssuesSection();
                break;
            case 'pulls':
                this.loadPullsSection();
                break;
            case 'activity':
                this.loadActivitySection();
                break;
        }
    }

    loadRepositoriesSection() {
        const grid = document.getElementById('repositories-grid');
        
        grid.innerHTML = this.repositories.map(repo => `
            <div class="neon-card repository-card">
                <div class="card-header">
                    <i class="fas fa-folder neon-cyan"></i>
                    <h3>${repo.name}</h3>
                </div>
                <div class="card-content">
                    <p class="repo-description">${repo.description || 'Sin descripción'}</p>
                    <div class="repo-meta">
                        <span class="meta-item">
                            <i class="fas fa-code"></i>
                            ${repo.language || 'N/A'}
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-star neon-green"></i>
                            ${repo.stars}
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-code-branch neon-purple"></i>
                            ${repo.forks}
                        </span>
                    </div>
                    <div class="repo-actions">
                        <button class="action-btn" onclick="dashboard.showReadme('${repo.name}')">
                            <i class="fas fa-book"></i> README
                        </button>
                        <button class="action-btn" onclick="dashboard.viewIssues('${repo.name}')">
                            <i class="fas fa-bug"></i> Issues (${repo.open_issues})
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    async loadIssuesSection() {
        const tbody = document.querySelector('#issues-table tbody');
        tbody.innerHTML = '<tr><td colspan="5" class="loading-cell"><div class="loading-spinner"></div></td></tr>';
        
        try {
            const allIssues = [];
            
            for (const repo of this.repositories) {
                const response = await fetch(`${this.apiBase}/repository/${repo.owner}/${repo.name}/issues`);
                const data = await response.json();
                
                if (data.success && data.data) {
                    data.data.forEach(issue => {
                        issue.repo_name = repo.name;
                        allIssues.push(issue);
                    });
                }
            }
            
            tbody.innerHTML = allIssues.map(issue => `
                <tr>
                    <td><span class="repo-tag">${issue.repo_name}</span></td>
                    <td class="issue-title">${issue.title}</td>
                    <td><span class="status-badge status-${issue.state}">${issue.state}</span></td>
                    <td>${issue.author}</td>
                    <td>${this.formatDate(issue.created_at)}</td>
                </tr>
            `).join('');
            
        } catch (error) {
            console.error('Error cargando issues:', error);
            tbody.innerHTML = '<tr><td colspan="5" class="error-cell">Error cargando issues</td></tr>';
        }
    }

    async loadPullsSection() {
        const tbody = document.querySelector('#pulls-table tbody');
        tbody.innerHTML = '<tr><td colspan="5" class="loading-cell"><div class="loading-spinner"></div></td></tr>';
        
        try {
            const allPRs = [];
            
            for (const repo of this.repositories) {
                const response = await fetch(`${this.apiBase}/repository/${repo.owner}/${repo.name}/pulls`);
                const data = await response.json();
                
                if (data.success && data.data) {
                    data.data.forEach(pr => {
                        pr.repo_name = repo.name;
                        allPRs.push(pr);
                    });
                }
            }
            
            tbody.innerHTML = allPRs.map(pr => `
                <tr>
                    <td><span class="repo-tag">${pr.repo_name}</span></td>
                    <td class="pr-title">${pr.title}</td>
                    <td><span class="status-badge status-${pr.state}">${pr.state}</span></td>
                    <td>${pr.author}</td>
                    <td>${this.formatDate(pr.created_at)}</td>
                </tr>
            `).join('');
            
        } catch (error) {
            console.error('Error cargando pull requests:', error);
            tbody.innerHTML = '<tr><td colspan="5" class="error-cell">Error cargando PRs</td></tr>';
        }
    }

    initializeCharts() {
        // Configuración global para Chart.js con tema neón
        Chart.defaults.color = '#cccccc';
        Chart.defaults.borderColor = '#333333';
        Chart.defaults.backgroundColor = 'rgba(255, 255, 255, 0.1)';
        
        this.createIssuesChart();
    }

    createIssuesChart() {
        const ctx = document.getElementById('issuesChart');
        if (!ctx) return;
        
        // Datos simulados - se actualizarán con datos reales
        const data = {
            labels: ['Ecosistema', 'API', 'Tienda', 'Repartidor', 'Cliente'],
            datasets: [{
                label: 'Issues Abiertos',
                data: [5, 8, 3, 12, 7],
                backgroundColor: [
                    'rgba(255, 0, 127, 0.7)',   // Rosa neón
                    'rgba(0, 255, 255, 0.7)',   // Cian neón
                    'rgba(138, 43, 226, 0.7)',  // Morado neón
                    'rgba(50, 255, 50, 0.7)',   // Verde neón
                    'rgba(255, 20, 147, 0.7)'   // Rosa neón claro
                ],
                borderColor: [
                    '#ff007f',
                    '#00ffff',
                    '#8a2be2',
                    '#32ff32',
                    '#ff1493'
                ],
                borderWidth: 2
            }]
        };
        
        this.charts.issues = new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#cccccc',
                            font: {
                                family: 'Rajdhani'
                            }
                        }
                    }
                }
            }
        });
    }

    async showReadme(repoName) {
        const modal = document.getElementById('readme-modal');
        const modalTitle = document.getElementById('modal-title');
        const readmeContent = document.getElementById('readme-content');
        
        modalTitle.textContent = `README - ${repoName}`;
        readmeContent.innerHTML = '<div class="loading-spinner"></div>';
        modal.style.display = 'block';
        
        try {
            const repo = this.repositories.find(r => r.name === repoName);
            const response = await fetch(`${this.apiBase}/repository/${repo.owner}/${repo.name}/readme`);
            const data = await response.json();
            
            if (data.success && data.data) {
                readmeContent.innerHTML = `<pre class="readme-text">${data.data}</pre>`;
            } else {
                readmeContent.innerHTML = '<p class="error-text">No se pudo cargar el README</p>';
            }
            
        } catch (error) {
            console.error('Error cargando README:', error);
            readmeContent.innerHTML = '<p class="error-text">Error cargando README</p>';
        }
    }

    closeReadmeModal() {
        const modal = document.getElementById('readme-modal');
        modal.style.display = 'none';
    }

    async syncData() {
        const syncBtn = document.querySelector('.sync-btn');
        const originalText = syncBtn.innerHTML;
        
        syncBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sincronizando...';
        syncBtn.disabled = true;
        
        try {
            const response = await fetch('/sync.php', { method: 'POST' });
            
            if (response.ok) {
                this.showSuccess('Sincronización completada');
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                this.showError('Error en la sincronización');
            }
            
        } catch (error) {
            console.error('Error en sincronización:', error);
            this.showError('Error de conexión');
        } finally {
            setTimeout(() => {
                syncBtn.innerHTML = originalText;
                syncBtn.disabled = false;
            }, 3000);
        }
    }

    setupEventListeners() {
        // Filtros
        document.getElementById('status-filter').addEventListener('change', () => {
            this.applyFilters();
        });
        
        document.getElementById('repo-filter').addEventListener('change', () => {
            this.applyFilters();
        });
        
        // Modal
        document.getElementById('readme-modal').addEventListener('click', (e) => {
            if (e.target.id === 'readme-modal') {
                this.closeReadmeModal();
            }
        });
    }

    applyFilters() {
        const statusFilter = document.getElementById('status-filter').value;
        const repoFilter = document.getElementById('repo-filter').value;
        
        // Implementar lógica de filtros
        console.log('Aplicando filtros:', { statusFilter, repoFilter });
    }

    animateNumbers() {
        const numbers = document.querySelectorAll('.stat-value');
        numbers.forEach(num => {
            num.classList.add('pulse-animation');
            setTimeout(() => {
                num.classList.remove('pulse-animation');
            }, 2000);
        });
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    loadOverviewData() {
        // Cargar datos específicos para la vista de overview
        console.log('Cargando datos de overview...');
    }

    loadActivitySection() {
        // Implementar gráficos de actividad
        console.log('Cargando sección de actividad...');
    }

    viewIssues(repoName) {
        // Cambiar a la sección de issues y filtrar por repo
        document.querySelector('[data-section="issues"]').click();
        document.getElementById('repo-filter').value = repoName;
        this.applyFilters();
    }
}

// Funciones globales para eventos
function closeReadmeModal() {
    dashboard.closeReadmeModal();
}

function syncData() {
    dashboard.syncData();
}

// Inicializar dashboard cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.dashboard = new YegaDashboard();
});

// Estilos adicionales para elementos dinámicos
const additionalStyles = `
.repo-stats {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.stat-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-secondary);
}

.repo-readme-btn, .action-btn {
    background: transparent;
    border: 1px solid var(--neon-cyan);
    color: var(--neon-cyan);
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    margin-top: 1rem;
    width: 100%;
}

.repo-readme-btn:hover, .action-btn:hover {
    background: var(--neon-cyan-shadow);
    box-shadow: 0 0 10px var(--neon-cyan-shadow);
}

.repo-description {
    color: var(--text-secondary);
    margin-bottom: 1rem;
    line-height: 1.5;
}

.repo-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.repo-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    flex: 1;
    margin-top: 0;
}

.repo-tag {
    background: var(--neon-purple-shadow);
    color: var(--neon-purple);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.issue-title, .pr-title {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.loading-cell, .error-cell {
    text-align: center;
    padding: 2rem;
    color: var(--text-muted);
}

.error-text {
    color: var(--neon-pink);
    text-align: center;
}

.readme-text {
    color: var(--text-secondary);
    line-height: 1.6;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 2rem;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    z-index: 3000;
    animation: slideIn 0.3s ease;
}

.notification.success {
    background: var(--neon-green-shadow);
    border: 1px solid var(--neon-green);
}

.notification.error {
    background: var(--neon-pink-shadow);
    border: 1px solid var(--neon-pink);
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
`;

// Agregar estilos al document
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);