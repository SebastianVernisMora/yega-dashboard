// Gestión del timeline de actividad
class TimelineManager {
    constructor(dashboard) {
        this.dashboard = dashboard;
        this.currentPeriod = '7';
        this.currentType = 'all';
        this.currentPage = 1;
        this.itemsPerPage = 20;
        this.isLoading = false;
        this.hasMore = true;
        this.setupInfiniteScroll();
    }

    setupInfiniteScroll() {
        const container = document.getElementById('timelineContainer');
        if (!container) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && this.hasMore && !this.isLoading) {
                    this.loadMoreItems();
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '100px'
        });

        // Observar el elemento de carga
        const loadingElement = document.getElementById('timelineLoading');
        if (loadingElement) {
            observer.observe(loadingElement);
        }
    }

    async loadTimelineData(period = '7', type = 'all', reset = true) {
        this.currentPeriod = period;
        this.currentType = type;
        
        if (reset) {
            this.currentPage = 1;
            this.hasMore = true;
        }

        this.isLoading = true;
        this.showTimelineLoading();

        try {
            const endpoint = `${this.dashboard.config.ENDPOINTS?.timeline || 'timeline.php'}?period=${period}&type=${type}&page=${this.currentPage}&limit=${this.itemsPerPage}`;
            const response = await this.dashboard.fetchData(endpoint);
            
            let timelineData = Array.isArray(response) ? response : response.timeline || [];
            
            // Datos de fallback si no hay datos reales
            if (!timelineData.length && this.currentPage === 1) {
                timelineData = this.generateFallbackData(period, type);
            }

            if (reset) {
                this.dashboard.data.timeline = timelineData;
            } else {
                this.dashboard.data.timeline = [...this.dashboard.data.timeline, ...timelineData];
            }

            this.hasMore = timelineData.length === this.itemsPerPage;
            this.renderTimeline(reset);
            
        } catch (error) {
            console.error('Error loading timeline:', error);
            if (this.currentPage === 1) {
                this.dashboard.data.timeline = this.generateFallbackData(period, type);
                this.renderTimeline(true);
            }
        } finally {
            this.isLoading = false;
            this.hideTimelineLoading();
        }
    }

    async loadMoreItems() {
        if (this.isLoading || !this.hasMore) return;
        
        this.currentPage++;
        await this.loadTimelineData(this.currentPeriod, this.currentType, false);
    }

    generateFallbackData(period, type) {
        const events = [];
        const now = new Date();
        const periodDays = parseInt(period);
        
        const eventTypes = type === 'all' ? ['commit', 'issue', 'pr', 'release'] : [type];
        const repositories = ['yega', 'yega-cli', 'yega-desktop', 'yega-web', 'yega-docs'];
        
        const eventTemplates = {
            commit: [
                'Actualización del modelo de IA principal',
                'Mejoras en el rendimiento del sistema',
                'Corrección de errores menores',
                'Implementación de nuevas características',
                'Optimización de algoritmos'
            ],
            issue: [
                'Problema con la configuración de memoria',
                'Error en la interfaz de usuario',
                'Fallo en la sincronización de datos',
                'Incompatibilidad con versiones anteriores',
                'Problema de rendimiento en dispositivos móviles'
            ],
            pr: [
                'Nueva interfaz para configuración avanzada',
                'Implementación de sistema de notificaciones',
                'Mejoras en la documentación',
                'Actualización de dependencias',
                'Refactorización del código base'
            ],
            release: [
                'Lanzamiento de versión v2.1.0',
                'Actualización menor v1.5.3',
                'Versión beta v3.0.0-beta.1',
                'Hotfix v2.0.2',
                'Release candidate v2.2.0-rc.1'
            ]
        };

        // Generar eventos para el período especificado
        for (let i = 0; i < periodDays * 2; i++) {
            const eventType = eventTypes[Math.floor(Math.random() * eventTypes.length)];
            const repository = repositories[Math.floor(Math.random() * repositories.length)];
            const templates = eventTemplates[eventType];
            const title = templates[Math.floor(Math.random() * templates.length)];
            
            const date = new Date(now);
            date.setDate(date.getDate() - Math.floor(Math.random() * periodDays));
            date.setHours(Math.floor(Math.random() * 24));
            date.setMinutes(Math.floor(Math.random() * 60));

            events.push({
                id: i + 1,
                type: eventType,
                title,
                repository,
                created_at: date.toISOString(),
                description: this.generateEventDescription(eventType, title),
                author: this.generateAuthor(),
                url: `https://github.com/yegaai/${repository}`
            });
        }

        // Ordenar por fecha descendente
        return events.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    }

    generateEventDescription(type, title) {
        const descriptions = {
            commit: [
                'Mejoras significativas en la funcionalidad core del sistema.',
                'Optimización de procesos internos para mejor rendimiento.',
                'Corrección de errores reportados por la comunidad.',
                'Implementación de características solicitadas por usuarios.'
            ],
            issue: [
                'Problema identificado que requiere atención inmediata.',
                'Error reportado por múltiples usuarios en producción.',
                'Incompatibilidad detectada en ciertos entornos.',
                'Fallo intermitente que afecta la experiencia del usuario.'
            ],
            pr: [
                'Propuesta de mejora para la funcionalidad existente.',
                'Nueva característica desarrollada por el equipo.',
                'Refactorización para mejorar la mantenibilidad del código.',
                'Actualización de documentación y ejemplos.'
            ],
            release: [
                'Nueva versión estable con múltiples mejoras.',
                'Actualización de seguridad y corrección de errores.',
                'Versión beta para pruebas de la comunidad.',
                'Lanzamiento con nuevas características experimentales.'
            ]
        };

        const typeDescriptions = descriptions[type] || descriptions.commit;
        return typeDescriptions[Math.floor(Math.random() * typeDescriptions.length)];
    }

    generateAuthor() {
        const authors = [
            'yegaai-dev',
            'carlos-rodriguez',
            'ana-martinez',
            'luis-garcia',
            'maria-lopez',
            'david-santos',
            'elena-torres'
        ];
        return authors[Math.floor(Math.random() * authors.length)];
    }

    renderTimeline(reset = true) {
        const container = document.getElementById('timelineContainer');
        if (!container) return;

        if (reset) {
            container.innerHTML = '';
        }

        const timeline = this.dashboard.data.timeline || [];
        
        if (!timeline.length) {
            container.innerHTML = this.dashboard.renderEmptyState('eventos en el timeline', 'clock');
            return;
        }

        const newItems = reset ? timeline : timeline.slice(-this.itemsPerPage);
        
        newItems.forEach(event => {
            const item = this.createTimelineItem(event);
            container.appendChild(item);
        });

        // Animar nuevos elementos
        if (!reset) {
            const newElements = container.querySelectorAll('.timeline-item:nth-last-child(-n+' + newItems.length + ')');
            newElements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    el.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }
    }

    createTimelineItem(event) {
        const item = document.createElement('div');
        item.className = 'timeline-item';
        item.dataset.eventId = event.id;
        
        item.innerHTML = `
            <div class="timeline-item-header">
                <div class="timeline-item-info">
                    <h4>${this.dashboard.escapeHtml(event.title)}</h4>
                    <p class="timeline-item-repo">
                        <i class="fab fa-github"></i>
                        ${this.dashboard.escapeHtml(event.repository)}
                    </p>
                </div>
                <div class="timeline-item-meta">
                    <div class="timeline-item-type ${event.type}">
                        <i class="fas fa-${this.getEventIcon(event.type)}"></i>
                        ${this.getEventTypeName(event.type)}
                    </div>
                    <span class="timeline-item-date">${this.formatEventDate(event.created_at)}</span>
                </div>
            </div>
            ${event.description ? `
                <div class="timeline-item-description">
                    <p>${this.dashboard.escapeHtml(event.description)}</p>
                </div>
            ` : ''}
            ${event.author ? `
                <div class="timeline-item-footer">
                    <span class="timeline-item-author">
                        <i class="fas fa-user"></i>
                        ${this.dashboard.escapeHtml(event.author)}
                    </span>
                    ${event.url ? `
                        <a href="${event.url}" target="_blank" class="timeline-item-link">
                            <i class="fas fa-external-link-alt"></i>
                            Ver en GitHub
                        </a>
                    ` : ''}
                </div>
            ` : ''}
        `;

        // Agregar click handler para expandir/contraer
        const header = item.querySelector('.timeline-item-header');
        header.addEventListener('click', () => {
            item.classList.toggle('expanded');
        });

        return item;
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
        return names[type] || type.charAt(0).toUpperCase() + type.slice(1);
    }

    formatEventDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffMins < 1) {
            return 'hace un momento';
        } else if (diffMins < 60) {
            return `hace ${diffMins} minuto${diffMins > 1 ? 's' : ''}`;
        } else if (diffHours < 24) {
            return `hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`;
        } else if (diffDays < 7) {
            return `hace ${diffDays} día${diffDays > 1 ? 's' : ''}`;
        } else {
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }

    showTimelineLoading() {
        const loading = document.getElementById('timelineLoading');
        if (loading) {
            loading.classList.add('show');
        }
    }

    hideTimelineLoading() {
        const loading = document.getElementById('timelineLoading');
        if (loading) {
            loading.classList.remove('show');
        }
    }

    setPeriodFilter(period) {
        // Actualizar botones activos
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-period="${period}"]`)?.classList.add('active');
        
        this.loadTimelineData(period, this.currentType, true);
    }

    setTypeFilter(type) {
        // Actualizar botones activos
        document.querySelectorAll('.type-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-type="${type}"]`)?.classList.add('active');
        
        this.loadTimelineData(this.currentPeriod, type, true);
    }

    exportTimeline(format) {
        const timeline = this.dashboard.data.timeline;
        if (!timeline?.length) {
            this.dashboard.showNotification('No hay datos para exportar', 'warning');
            return;
        }

        try {
            let content, filename, mimeType;
            const timestamp = new Date().toISOString().split('T')[0];
            
            if (format === 'json') {
                content = JSON.stringify({
                    exported_at: new Date().toISOString(),
                    period: this.currentPeriod,
                    type: this.currentType,
                    total_events: timeline.length,
                    events: timeline
                }, null, 2);
                filename = `yega-timeline-${timestamp}.json`;
                mimeType = 'application/json';
            } else if (format === 'csv') {
                const headers = ['Fecha', 'Tipo', 'Repositorio', 'Título', 'Descripción', 'Autor'];
                const rows = timeline.map(event => [
                    this.formatEventDate(event.created_at),
                    this.getEventTypeName(event.type),
                    event.repository || '',
                    event.title || '',
                    event.description || '',
                    event.author || ''
                ]);
                
                content = [headers, ...rows]
                    .map(row => row.map(cell => `"${(cell || '').toString().replace(/"/g, '""')}"`).join(','))
                    .join('\n');
                
                filename = `yega-timeline-${timestamp}.csv`;
                mimeType = 'text/csv;charset=utf-8';
            }
            
            this.downloadFile(content, filename, mimeType);
            this.dashboard.showNotification(`Timeline exportado como ${format.toUpperCase()}`, 'success');
            
        } catch (error) {
            this.dashboard.showNotification('Error al exportar timeline', 'error');
            console.error('Export error:', error);
        }
    }

    downloadFile(content, filename, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Limpiar URL objeto
        setTimeout(() => URL.revokeObjectURL(url), 100);
    }

    refreshTimeline() {
        this.loadTimelineData(this.currentPeriod, this.currentType, true);
    }

    clearTimeline() {
        this.dashboard.data.timeline = [];
        const container = document.getElementById('timelineContainer');
        if (container) {
            container.innerHTML = this.dashboard.renderEmptyState('eventos en el timeline', 'clock');
        }
    }

    // Búsqueda en timeline
    searchTimeline(query) {
        const container = document.getElementById('timelineContainer');
        const items = container.querySelectorAll('.timeline-item');
        
        if (!query.trim()) {
            items.forEach(item => {
                item.style.display = 'block';
            });
            return;
        }

        const searchTerm = query.toLowerCase();
        let visibleCount = 0;
        
        items.forEach(item => {
            const title = item.querySelector('h4')?.textContent.toLowerCase() || '';
            const description = item.querySelector('.timeline-item-description p')?.textContent.toLowerCase() || '';
            const repository = item.querySelector('.timeline-item-repo')?.textContent.toLowerCase() || '';
            
            const isVisible = title.includes(searchTerm) || 
                            description.includes(searchTerm) || 
                            repository.includes(searchTerm);
            
            item.style.display = isVisible ? 'block' : 'none';
            if (isVisible) visibleCount++;
        });

        // Mostrar mensaje si no hay resultados
        if (visibleCount === 0) {
            const emptyState = document.createElement('div');
            emptyState.className = 'timeline-search-empty';
            emptyState.innerHTML = this.dashboard.renderEmptyState('eventos que coincidan con la búsqueda', 'search');
            container.appendChild(emptyState);
        } else {
            // Remover mensaje de estado vacío si existe
            const emptyState = container.querySelector('.timeline-search-empty');
            if (emptyState) {
                emptyState.remove();
            }
        }
    }
}

// Exportar para uso global
window.TimelineManager = TimelineManager;