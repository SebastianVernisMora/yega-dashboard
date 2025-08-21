// Dashboard principal para Yega - Funcionalidades específicas
class YegaDashboardCore {
    constructor() {
        this.repositories = [];
        this.currentRepo = null;
        this.modalState = {
            isOpen: false,
            currentTab: 'info',
            repository: null
        };
        this.viewMode = 'grid'; // 'grid' o 'list'
        this.pagination = {
            currentPage: 1,
            itemsPerPage: 12,
            totalItems: 0
        };
    }

    // Renderizado de repositorios
    renderRepositories() {
        const container = document.getElementById('repositoriesGrid');
        const listContainer = document.getElementById('repositoriesList');
        
        if (!container && !listContainer) {
            console.warn('Contenedores de repositorios no encontrados');
            return;
        }

        // Aplicar filtros
        const filteredRepos = this.getFilteredRepositories();
        const sortedRepos = this.sortRepositories(filteredRepos);
        
        // Paginación
        const startIndex = (this.pagination.currentPage - 1) * this.pagination.itemsPerPage;
        const endIndex = startIndex + this.pagination.itemsPerPage;
        const paginatedRepos = sortedRepos.slice(startIndex, endIndex);
        
        this.pagination.totalItems = sortedRepos.length;

        if (container) {
            this.renderRepositoryCards(container, paginatedRepos);
        }
        
        if (listContainer) {
            this.renderRepositoryList(listContainer, paginatedRepos);
        }
        
        this.renderPagination();
        this.updateRepositoryStats(sortedRepos);
    }

    renderRepositoryCards(container, repositories) {
        if (repositories.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No se encontraron repositorios</h3>
                    <p>Intenta ajustar los filtros de búsqueda</p>
                </div>
            `;
            return;
        }

        container.innerHTML = repositories.map(repo => this.createRepositoryCard(repo)).join('');
        
        // Agregar event listeners
        container.querySelectorAll('.repo-card').forEach((card, index) => {
            card.addEventListener('click', () => {
                this.openRepositoryModal(repositories[index]);
            });
        });
    }

    createRepositoryCard(repo) {
        const updatedDate = new Date(repo.updated_at || repo.pushed_at || Date.now());
        const timeAgo = this.getTimeAgo(updatedDate);
        const isActive = this.isRepositoryActive(repo);
        const languageClass = (repo.language || 'unknown').toLowerCase();
        
        // Calcular métricas de actividad
        const activityScore = this.calculateActivityScore(repo);
        const healthScore = this.calculateHealthScore(repo);
        
        return `
            <div class="repo-card" data-repo="${repo.name}">
                <div class="repo-header">
                    <div class="repo-title">
                        <a href="${repo.html_url}" class="repo-name" target="_blank" onclick="event.stopPropagation()">
                            <i class="fab fa-github"></i>
                            ${repo.name}
                        </a>
                        <div class="repo-badges">
                            ${repo.private ? '<span class="repo-badge private"><i class="fas fa-lock"></i> Privado</span>' : ''}
                            ${repo.archived ? '<span class="repo-badge archived"><i class="fas fa-archive"></i> Archivado</span>' : ''}
                            ${repo.fork ? '<span class="repo-badge fork"><i class="fas fa-code-branch"></i> Fork</span>' : ''}
                        </div>
                    </div>
                    <div class="repo-status ${isActive ? 'active' : 'inactive'}">
                        <i class="fas fa-circle"></i>
                        ${isActive ? 'Activo' : 'Inactivo'}
                    </div>
                </div>
                
                <div class="repo-description">
                    ${repo.description || 'Sin descripción disponible'}
                </div>
                
                <div class="repo-stats">
                    <div class="repo-stat">
                        <i class="fas fa-star"></i>
                        <span>${this.formatNumber(repo.stargazers_count || 0)}</span>
                    </div>
                    <div class="repo-stat">
                        <i class="fas fa-code-branch"></i>
                        <span>${this.formatNumber(repo.forks_count || 0)}</span>
                    </div>
                    <div class="repo-stat">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>${this.formatNumber(repo.open_issues_count || repo.open_issues || 0)}</span>
                    </div>
                    <div class="repo-stat">
                        <i class="fas fa-eye"></i>
                        <span>${this.formatNumber(repo.watchers_count || 0)}</span>
                    </div>
                </div>
                
                <div class="repo-metrics">
                    <div class="metric-bar">
                        <div class="metric-label">Actividad</div>
                        <div class="metric-progress">
                            <div class="metric-fill" style="width: ${activityScore}%"></div>
                        </div>
                        <div class="metric-value">${activityScore}%</div>
                    </div>
                    <div class="metric-bar">
                        <div class="metric-label">Salud</div>
                        <div class="metric-progress">
                            <div class="metric-fill health" style="width: ${healthScore}%"></div>
                        </div>
                        <div class="metric-value">${healthScore}%</div>
                    </div>
                </div>
                
                <div class="repo-footer">
                    <div class="repo-language">
                        ${repo.language ? `
                            <span class="language-dot ${languageClass}"></span>
                            <span>${repo.language}</span>
                        ` : '<span class="text-muted">Sin lenguaje</span>'}
                    </div>
                    <div class="repo-updated">
                        <i class="fas fa-clock"></i>
                        <span>${timeAgo}</span>
                    </div>
                </div>
                
                <div class="repo-actions">
                    <button class="btn btn-sm btn-secondary" onclick="event.stopPropagation(); window.open('${repo.html_url}', '_blank')">
                        <i class="fab fa-github"></i>
                        <span>Ver en GitHub</span>
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); this.closest('.repo-card').click()">
                        <i class="fas fa-info-circle"></i>
                        <span>Detalles</span>
                    </button>
                </div>
            </div>
        `;
    }

    // Filtrado y ordenamiento
    getFilteredRepositories() {
        let filtered = [...this.repositories];
        
        // Filtro de búsqueda
        if (this.filters.search) {
            const searchTerm = this.filters.search.toLowerCase();
            filtered = filtered.filter(repo => 
                repo.name.toLowerCase().includes(searchTerm) ||
                (repo.description && repo.description.toLowerCase().includes(searchTerm)) ||
                (repo.language && repo.language.toLowerCase().includes(searchTerm))
            );
        }
        
        // Filtro por tipo
        switch (this.filters.filterBy) {
            case 'active':
                filtered = filtered.filter(repo => this.isRepositoryActive(repo));
                break;
            case 'issues':
                filtered = filtered.filter(repo => (repo.open_issues_count || repo.open_issues || 0) > 0);
                break;
            case 'prs':
                filtered = filtered.filter(repo => (repo.open_prs || 0) > 0);
                break;
            case 'javascript':
            case 'python':
            case 'typescript':
            case 'vue':
                filtered = filtered.filter(repo => 
                    repo.language && repo.language.toLowerCase() === this.filters.filterBy
                );
                break;
        }
        
        return filtered;
    }

    sortRepositories(repositories) {
        return repositories.sort((a, b) => {
            switch (this.filters.sortBy) {
                case 'name':
                    return a.name.localeCompare(b.name);
                case 'stars':
                    return (b.stargazers_count || 0) - (a.stargazers_count || 0);
                case 'activity':
                    return this.calculateActivityScore(b) - this.calculateActivityScore(a);
                case 'issues':
                    return (b.open_issues_count || b.open_issues || 0) - (a.open_issues_count || a.open_issues || 0);
                case 'updated':
                    return new Date(b.updated_at || b.pushed_at || 0) - new Date(a.updated_at || a.pushed_at || 0);
                default:
                    return 0;
            }
        });
    }

    // Cálculos de métricas
    calculateActivityScore(repo) {
        const now = new Date();
        const lastUpdate = new Date(repo.updated_at || repo.pushed_at || 0);
        const daysSinceUpdate = (now - lastUpdate) / (1000 * 60 * 60 * 24);
        
        let score = 100;
        
        // Penalizar por inactividad
        if (daysSinceUpdate > 365) score -= 50;
        else if (daysSinceUpdate > 180) score -= 30;
        else if (daysSinceUpdate > 90) score -= 15;
        else if (daysSinceUpdate > 30) score -= 5;
        
        // Bonificar por actividad
        const stars = repo.stargazers_count || 0;
        const forks = repo.forks_count || 0;
        const issues = repo.open_issues_count || repo.open_issues || 0;
        
        score += Math.min(stars / 10, 20);
        score += Math.min(forks / 5, 15);
        score -= Math.min(issues / 2, 10);
        
        return Math.max(0, Math.min(100, Math.round(score)));
    }

    calculateHealthScore(repo) {
        let score = 100;
        
        // Verificar elementos de salud del repositorio
        if (!repo.description) score -= 10;
        if (!repo.homepage) score -= 5;
        if (repo.archived) score -= 30;
        if (!repo.language) score -= 10;
        
        const issues = repo.open_issues_count || repo.open_issues || 0;
        score -= Math.min(issues, 20);
        
        // Bonificar por popularidad
        const stars = repo.stargazers_count || 0;
        score += Math.min(stars / 20, 15);
        
        return Math.max(0, Math.min(100, Math.round(score)));
    }

    isRepositoryActive(repo) {
        const lastUpdate = new Date(repo.updated_at || repo.pushed_at || 0);
        const daysSinceUpdate = (Date.now() - lastUpdate) / (1000 * 60 * 60 * 24);
        return daysSinceUpdate < 90; // Activo si se actualizó en los últimos 90 días
    }

    // Modal de repositorio
    async openRepositoryModal(repository) {
        this.currentRepo = repository;
        this.modalState.isOpen = true;
        this.modalState.repository = repository;
        
        const modal = document.getElementById('repositoryModal');
        if (!modal) return;
        
        // Actualizar contenido del modal
        this.updateModalContent(repository);
        
        // Mostrar modal
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Cargar datos adicionales
        await this.loadRepositoryDetails(repository);
    }

    closeModal() {
        const modal = document.getElementById('repositoryModal');
        if (!modal) return;
        
        modal.classList.remove('show');
        document.body.style.overflow = '';
        
        this.modalState.isOpen = false;
        this.modalState.repository = null;
        this.currentRepo = null;
    }

    updateModalContent(repository) {
        // Actualizar header
        document.getElementById('modalRepoName').textContent = repository.name;
        document.getElementById('modalRepoDescription').textContent = 
            repository.description || 'Sin descripción disponible';
        
        // Actualizar stats
        document.getElementById('modalStars').textContent = this.formatNumber(repository.stargazers_count || 0);
        document.getElementById('modalForks').textContent = this.formatNumber(repository.forks_count || 0);
        document.getElementById('modalLanguage').textContent = repository.language || 'No especificado';
        document.getElementById('modalUpdated').textContent = this.getTimeAgo(new Date(repository.updated_at || repository.pushed_at));
        
        // Actualizar links
        const githubLink = document.getElementById('modalGithubLink');
        if (githubLink) {
            githubLink.href = repository.html_url;
        }
        
        const websiteLink = document.getElementById('modalWebsiteLink');
        if (websiteLink) {
            if (repository.homepage) {
                websiteLink.href = repository.homepage;
                websiteLink.style.display = 'inline-flex';
            } else {
                websiteLink.style.display = 'none';
            }
        }
    }

    async loadRepositoryDetails(repository) {
        try {
            // Cargar README
            this.loadRepositoryReadme(repository.name);
            
            // Cargar issues
            this.loadRepositoryIssues(repository.name);
            
            // Cargar PRs
            this.loadRepositoryPRs(repository.name);
            
        } catch (error) {
            console.error('Error cargando detalles del repositorio:', error);
        }
    }

    async loadRepositoryReadme(repoName) {
        const readmeContainer = document.getElementById('readmeContent');
        const loadingElement = document.querySelector('.readme-loading');
        
        if (!readmeContainer) return;
        
        try {
            loadingElement.style.display = 'block';
            readmeContainer.style.display = 'none';
            
            const response = await this.fetchData(`readme.php?repo=${encodeURIComponent(repoName)}`);
            
            if (response && response.content) {
                // Renderizar markdown
                const htmlContent = marked.parse(response.content);
                readmeContainer.innerHTML = htmlContent;
                
                // Highlight code blocks
                readmeContainer.querySelectorAll('pre code').forEach(block => {
                    hljs.highlightBlock(block);
                });
                
                // Actualizar links para abrir en nueva pestaña
                readmeContainer.querySelectorAll('a').forEach(link => {
                    if (link.href.startsWith('http')) {
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                    }
                });
            } else {
                readmeContainer.innerHTML = '<p class="text-muted">README no disponible</p>';
            }
            
        } catch (error) {
            console.error('Error cargando README:', error);
            readmeContainer.innerHTML = '<p class="text-error">Error cargando README</p>';
        } finally {
            loadingElement.style.display = 'none';
            readmeContainer.style.display = 'block';
        }
    }

    async loadRepositoryIssues(repoName) {
        const issuesContainer = document.getElementById('issuesList');
        if (!issuesContainer) return;
        
        try {
            const response = await this.fetchData(`issues.php?repo=${encodeURIComponent(repoName)}`);
            
            if (response && Array.isArray(response)) {
                this.renderIssues(issuesContainer, response);
            } else {
                issuesContainer.innerHTML = '<p class="text-muted">No hay issues disponibles</p>';
            }
            
        } catch (error) {
            console.error('Error cargando issues:', error);
            issuesContainer.innerHTML = '<p class="text-error">Error cargando issues</p>';
        }
    }

    async loadRepositoryPRs(repoName) {
        const prsContainer = document.getElementById('prsList');
        if (!prsContainer) return;
        
        try {
            const response = await this.fetchData(`prs.php?repo=${encodeURIComponent(repoName)}`);
            
            if (response && Array.isArray(response)) {
                this.renderPRs(prsContainer, response);
            } else {
                prsContainer.innerHTML = '<p class="text-muted">No hay pull requests disponibles</p>';
            }
            
        } catch (error) {
            console.error('Error cargando PRs:', error);
            prsContainer.innerHTML = '<p class="text-error">Error cargando pull requests</p>';
        }
    }

    renderIssues(container, issues) {
        if (issues.length === 0) {
            container.innerHTML = '<p class="text-muted">No hay issues para mostrar</p>';
            return;
        }
        
        container.innerHTML = issues.map(issue => `
            <div class="issue-item">
                <div class="issue-header">
                    <a href="${issue.html_url}" class="issue-title" target="_blank">
                        ${issue.title}
                    </a>
                    <span class="issue-state ${issue.state}">
                        ${issue.state}
                    </span>
                </div>
                <div class="issue-meta">
                    <span>
                        <i class="fas fa-hashtag"></i>
                        #${issue.number}
                    </span>
                    <span>
                        <i class="fas fa-user"></i>
                        ${issue.user.login}
                    </span>
                    <span>
                        <i class="fas fa-clock"></i>
                        ${this.getTimeAgo(new Date(issue.created_at))}
                    </span>
                    <span>
                        <i class="fas fa-comments"></i>
                        ${issue.comments} comentarios
                    </span>
                </div>
                ${issue.labels.length > 0 ? `
                    <div class="issue-labels">
                        ${issue.labels.map(label => `
                            <span class="issue-label" style="background-color: #${label.color}">
                                ${label.name}
                            </span>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
        `).join('');
    }

    renderPRs(container, prs) {
        if (prs.length === 0) {
            container.innerHTML = '<p class="text-muted">No hay pull requests para mostrar</p>';
            return;
        }
        
        container.innerHTML = prs.map(pr => `
            <div class="pr-item">
                <div class="pr-header">
                    <a href="${pr.html_url}" class="pr-title" target="_blank">
                        ${pr.title}
                    </a>
                    <span class="pr-state ${pr.merged_at ? 'merged' : pr.state}">
                        ${pr.merged_at ? 'merged' : pr.state}
                    </span>
                </div>
                <div class="pr-meta">
                    <span>
                        <i class="fas fa-hashtag"></i>
                        #${pr.number}
                    </span>
                    <span>
                        <i class="fas fa-user"></i>
                        ${pr.user.login}
                    </span>
                    <span>
                        <i class="fas fa-clock"></i>
                        ${this.getTimeAgo(new Date(pr.created_at))}
                    </span>
                    <span>
                        <i class="fas fa-comments"></i>
                        ${pr.comments || 0} comentarios
                    </span>
                    ${pr.additions !== undefined && pr.deletions !== undefined ? `
                        <span>
                            <i class="fas fa-code"></i>
                            +${pr.additions} -${pr.deletions}
                        </span>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    // Paginación
    renderPagination() {
        const container = document.getElementById('repositoriesPagination');
        if (!container) return;
        
        const totalPages = Math.ceil(this.pagination.totalItems / this.pagination.itemsPerPage);
        
        if (totalPages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let paginationHTML = '';
        
        // Botón anterior
        paginationHTML += `
            <button class="pagination-btn" ${this.pagination.currentPage === 1 ? 'disabled' : ''} 
                    onclick="window.yegaDashboard.goToPage(${this.pagination.currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
        
        // Números de página
        const startPage = Math.max(1, this.pagination.currentPage - 2);
        const endPage = Math.min(totalPages, startPage + 4);
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <button class="pagination-btn ${i === this.pagination.currentPage ? 'active' : ''}" 
                        onclick="window.yegaDashboard.goToPage(${i})">
                    ${i}
                </button>
            `;
        }
        
        // Botón siguiente
        paginationHTML += `
            <button class="pagination-btn" ${this.pagination.currentPage === totalPages ? 'disabled' : ''} 
                    onclick="window.yegaDashboard.goToPage(${this.pagination.currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
        
        // Información de paginación
        const startItem = (this.pagination.currentPage - 1) * this.pagination.itemsPerPage + 1;
        const endItem = Math.min(startItem + this.pagination.itemsPerPage - 1, this.pagination.totalItems);
        
        paginationHTML += `
            <div class="pagination-info">
                ${startItem}-${endItem} de ${this.pagination.totalItems}
            </div>
        `;
        
        container.innerHTML = paginationHTML;
    }

    goToPage(page) {
        const totalPages = Math.ceil(this.pagination.totalItems / this.pagination.itemsPerPage);
        
        if (page < 1 || page > totalPages) return;
        
        this.pagination.currentPage = page;
        this.renderRepositories();
        
        // Scroll to top
        document.querySelector('.repositories-list').scrollIntoView({ behavior: 'smooth' });
    }

    // Utilidades
    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'k';
        }
        return num.toString();
    }

    getTimeAgo(date) {
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
        const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
        const diffMinutes = Math.floor(diffTime / (1000 * 60));
        
        if (diffDays > 30) {
            const diffMonths = Math.floor(diffDays / 30);
            return `hace ${diffMonths} mes${diffMonths !== 1 ? 'es' : ''}`;
        } else if (diffDays > 0) {
            return `hace ${diffDays} día${diffDays !== 1 ? 's' : ''}`;
        } else if (diffHours > 0) {
            return `hace ${diffHours} hora${diffHours !== 1 ? 's' : ''}`;
        } else if (diffMinutes > 0) {
            return `hace ${diffMinutes} minuto${diffMinutes !== 1 ? 's' : ''}`;
        } else {
            return 'hace un momento';
        }
    }

    updateRepositoryStats(repositories) {
        const stats = {
            total: repositories.length,
            active: repositories.filter(repo => this.isRepositoryActive(repo)).length,
            withIssues: repositories.filter(repo => (repo.open_issues_count || repo.open_issues || 0) > 0).length,
            archived: repositories.filter(repo => repo.archived).length
        };
        
        // Actualizar elementos de stats si existen
        const elements = {
            total: document.getElementById('repoStatsTotal'),
            active: document.getElementById('repoStatsActive'),
            withIssues: document.getElementById('repoStatsIssues'),
            archived: document.getElementById('repoStatsArchived')
        };
        
        Object.keys(stats).forEach(key => {
            if (elements[key]) {
                elements[key].textContent = stats[key];
            }
        });
    }
}

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.YegaDashboardCore = YegaDashboardCore;
}