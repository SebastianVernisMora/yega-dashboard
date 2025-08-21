-- =============================================================================
-- Scripts de Base de Datos - Dashboard Yega
-- =============================================================================
-- Scripts SQL para configurar la base de datos MySQL del Dashboard Yega
-- =============================================================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS yega_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario
CREATE USER IF NOT EXISTS 'yega_user'@'localhost' IDENTIFIED BY 'yega_password_change_me';
CREATE USER IF NOT EXISTS 'yega_user'@'%' IDENTIFIED BY 'yega_password_change_me';

-- Otorgar permisos
GRANT ALL PRIVILEGES ON yega_dashboard.* TO 'yega_user'@'localhost';
GRANT ALL PRIVILEGES ON yega_dashboard.* TO 'yega_user'@'%';
GRANT SELECT ON performance_schema.* TO 'yega_user'@'localhost';
GRANT SELECT ON performance_schema.* TO 'yega_user'@'%';
FLUSH PRIVILEGES;

-- Usar la base de datos
USE yega_dashboard;

-- =============================================================================
-- Tablas principales (si no usas Prisma)
-- =============================================================================

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    github_id VARCHAR(255) UNIQUE,
    avatar_url TEXT,
    access_token TEXT,
    refresh_token TEXT,
    name VARCHAR(255),
    bio TEXT,
    location VARCHAR(255),
    company VARCHAR(255),
    blog VARCHAR(255),
    public_repos INT DEFAULT 0,
    public_gists INT DEFAULT 0,
    followers INT DEFAULT 0,
    following INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    last_sync_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_github_id (github_id),
    INDEX idx_is_active (is_active),
    INDEX idx_last_sync (last_sync_at)
) ENGINE=InnoDB;

-- Tabla de repositorios
CREATE TABLE IF NOT EXISTS repositories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    github_id VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    description TEXT,
    private BOOLEAN DEFAULT FALSE,
    fork BOOLEAN DEFAULT FALSE,
    stars_count INT DEFAULT 0,
    forks_count INT DEFAULT 0,
    watchers_count INT DEFAULT 0,
    language VARCHAR(100),
    size INT DEFAULT 0,
    default_branch VARCHAR(100) DEFAULT 'main',
    topics JSON,
    license JSON,
    has_issues BOOLEAN DEFAULT TRUE,
    has_projects BOOLEAN DEFAULT TRUE,
    has_wiki BOOLEAN DEFAULT TRUE,
    has_pages BOOLEAN DEFAULT FALSE,
    archived BOOLEAN DEFAULT FALSE,
    disabled BOOLEAN DEFAULT FALSE,
    clone_url TEXT,
    ssh_url TEXT,
    html_url TEXT,
    homepage VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    pushed_at TIMESTAMP NULL,
    
    user_id INT NOT NULL,
    
    INDEX idx_github_id (github_id),
    INDEX idx_user_id (user_id),
    INDEX idx_name (name),
    INDEX idx_language (language),
    INDEX idx_private (private),
    INDEX idx_fork (fork),
    INDEX idx_stars (stars_count),
    INDEX idx_updated (updated_at),
    INDEX idx_pushed (pushed_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de commits
CREATE TABLE IF NOT EXISTS commits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sha VARCHAR(40) UNIQUE NOT NULL,
    message TEXT,
    author_name VARCHAR(255),
    author_email VARCHAR(255),
    author_date TIMESTAMP,
    committer_name VARCHAR(255),
    committer_email VARCHAR(255),
    committer_date TIMESTAMP,
    additions INT DEFAULT 0,
    deletions INT DEFAULT 0,
    changed_files INT DEFAULT 0,
    url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    repository_id INT NOT NULL,
    user_id INT NOT NULL,
    
    INDEX idx_sha (sha),
    INDEX idx_repository_id (repository_id),
    INDEX idx_user_id (user_id),
    INDEX idx_author_email (author_email),
    INDEX idx_author_date (author_date),
    INDEX idx_committer_date (committer_date),
    
    FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de issues
CREATE TABLE IF NOT EXISTS issues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    github_id INT UNIQUE NOT NULL,
    number INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    body TEXT,
    state ENUM('open', 'closed') DEFAULT 'open',
    labels JSON,
    assignees JSON,
    milestone JSON,
    comments_count INT DEFAULT 0,
    author_username VARCHAR(255),
    author_avatar_url TEXT,
    html_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    
    repository_id INT NOT NULL,
    user_id INT NOT NULL,
    
    INDEX idx_github_id (github_id),
    INDEX idx_repository_id (repository_id),
    INDEX idx_user_id (user_id),
    INDEX idx_number (number),
    INDEX idx_state (state),
    INDEX idx_created (created_at),
    INDEX idx_updated (updated_at),
    
    FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de pull requests
CREATE TABLE IF NOT EXISTS pull_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    github_id INT UNIQUE NOT NULL,
    number INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    body TEXT,
    state ENUM('open', 'closed', 'merged') DEFAULT 'open',
    draft BOOLEAN DEFAULT FALSE,
    mergeable BOOLEAN DEFAULT TRUE,
    merged BOOLEAN DEFAULT FALSE,
    merge_commit_sha VARCHAR(40),
    base_ref VARCHAR(255),
    head_ref VARCHAR(255),
    additions INT DEFAULT 0,
    deletions INT DEFAULT 0,
    changed_files INT DEFAULT 0,
    commits_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    review_comments_count INT DEFAULT 0,
    author_username VARCHAR(255),
    author_avatar_url TEXT,
    html_url TEXT,
    diff_url TEXT,
    patch_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    merged_at TIMESTAMP NULL,
    
    repository_id INT NOT NULL,
    user_id INT NOT NULL,
    
    INDEX idx_github_id (github_id),
    INDEX idx_repository_id (repository_id),
    INDEX idx_user_id (user_id),
    INDEX idx_number (number),
    INDEX idx_state (state),
    INDEX idx_merged (merged),
    INDEX idx_created (created_at),
    INDEX idx_updated (updated_at),
    
    FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de actividades
CREATE TABLE IF NOT EXISTS activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    metadata JSON,
    repository_name VARCHAR(255),
    target_type VARCHAR(50), -- 'commit', 'issue', 'pull_request', etc.
    target_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    user_id INT NOT NULL,
    repository_id INT,
    
    INDEX idx_user_id (user_id),
    INDEX idx_repository_id (repository_id),
    INDEX idx_type (type),
    INDEX idx_action (action),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created (created_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabla de sesiones (opcional si usas database sessions)
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de configuraciones
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key_name VARCHAR(255) UNIQUE NOT NULL,
    value TEXT,
    type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (key_name),
    INDEX idx_public (is_public)
) ENGINE=InnoDB;

-- Tabla de tokens de API
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    abilities JSON,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    user_id INT NOT NULL,
    
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_last_used (last_used_at),
    INDEX idx_expires (expires_at),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================================
-- Datos iniciales
-- =============================================================================

-- Configuraciones por defecto
INSERT INTO settings (key_name, value, type, description, is_public) VALUES
('app_name', 'Yega Dashboard', 'string', 'Nombre de la aplicación', true),
('app_version', '1.0.0', 'string', 'Versión de la aplicación', true),
('github_sync_interval', '300', 'integer', 'Intervalo de sincronización con GitHub en segundos', false),
('github_batch_size', '50', 'integer', 'Tamaño de lote para sincronización', false),
('github_timeout', '30', 'integer', 'Timeout para requests a GitHub API', false),
('max_repositories_per_user', '1000', 'integer', 'Máximo de repositorios por usuario', false),
('enable_auto_sync', 'true', 'boolean', 'Habilitar sincronización automática', false),
('cache_ttl', '3600', 'integer', 'TTL del cache en segundos', false)
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- =============================================================================
-- Vistas útiles
-- =============================================================================

-- Vista de estadísticas de usuario
CREATE OR REPLACE VIEW user_stats AS
SELECT 
    u.id,
    u.username,
    u.email,
    COUNT(DISTINCT r.id) as repositories_count,
    COUNT(DISTINCT c.id) as commits_count,
    COUNT(DISTINCT i.id) as issues_count,
    COUNT(DISTINCT pr.id) as pull_requests_count,
    SUM(r.stars_count) as total_stars,
    SUM(r.forks_count) as total_forks,
    MAX(c.committer_date) as last_commit_date,
    u.created_at as user_created_at
FROM users u
LEFT JOIN repositories r ON u.id = r.user_id
LEFT JOIN commits c ON u.id = c.user_id
LEFT JOIN issues i ON u.id = i.user_id
LEFT JOIN pull_requests pr ON u.id = pr.user_id
GROUP BY u.id, u.username, u.email, u.created_at;

-- Vista de actividad reciente
CREATE OR REPLACE VIEW recent_activity AS
SELECT 
    a.id,
    a.type,
    a.action,
    a.description,
    a.repository_name,
    a.created_at,
    u.username,
    u.avatar_url,
    r.name as repo_name,
    r.language
FROM activities a
JOIN users u ON a.user_id = u.id
LEFT JOIN repositories r ON a.repository_id = r.id
ORDER BY a.created_at DESC;

-- Vista de repositorios populares
CREATE OR REPLACE VIEW popular_repositories AS
SELECT 
    r.id,
    r.name,
    r.full_name,
    r.description,
    r.language,
    r.stars_count,
    r.forks_count,
    r.watchers_count,
    r.html_url,
    u.username as owner_username,
    u.avatar_url as owner_avatar,
    r.created_at,
    r.updated_at,
    COUNT(DISTINCT c.id) as commits_count,
    COUNT(DISTINCT i.id) as issues_count,
    COUNT(DISTINCT pr.id) as pull_requests_count
FROM repositories r
JOIN users u ON r.user_id = u.id
LEFT JOIN commits c ON r.id = c.repository_id
LEFT JOIN issues i ON r.id = i.repository_id
LEFT JOIN pull_requests pr ON r.id = pr.repository_id
WHERE r.private = FALSE
GROUP BY r.id, r.name, r.full_name, r.description, r.language, 
         r.stars_count, r.forks_count, r.watchers_count, r.html_url,
         u.username, u.avatar_url, r.created_at, r.updated_at
ORDER BY r.stars_count DESC, r.forks_count DESC;

-- =============================================================================
-- Procedimientos almacenados útiles
-- =============================================================================

DELIMITER //

-- Procedimiento para limpiar datos antiguos
CREATE PROCEDURE CleanOldData(IN days_to_keep INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Limpiar actividades antiguas
    DELETE FROM activities 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    
    -- Limpiar sesiones expiradas
    DELETE FROM sessions 
    WHERE last_activity < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY));
    
    -- Limpiar tokens expirados
    DELETE FROM api_tokens 
    WHERE expires_at IS NOT NULL AND expires_at < NOW();
    
    COMMIT;
END //

-- Procedimiento para estadísticas de usuario
CREATE PROCEDURE GetUserStatistics(IN user_id INT)
BEGIN
    SELECT 
        u.username,
        u.email,
        u.created_at as joined_date,
        COUNT(DISTINCT r.id) as total_repositories,
        COUNT(DISTINCT CASE WHEN r.private = 0 THEN r.id END) as public_repositories,
        COUNT(DISTINCT CASE WHEN r.fork = 1 THEN r.id END) as forked_repositories,
        SUM(r.stars_count) as total_stars,
        SUM(r.forks_count) as total_forks,
        COUNT(DISTINCT c.id) as total_commits,
        COUNT(DISTINCT i.id) as total_issues,
        COUNT(DISTINCT pr.id) as total_pull_requests,
        COUNT(DISTINCT CASE WHEN pr.merged = 1 THEN pr.id END) as merged_pull_requests,
        (
            SELECT GROUP_CONCAT(DISTINCT r2.language SEPARATOR ', ')
            FROM repositories r2 
            WHERE r2.user_id = u.id AND r2.language IS NOT NULL
        ) as languages_used
    FROM users u
    LEFT JOIN repositories r ON u.id = r.user_id
    LEFT JOIN commits c ON u.id = c.user_id
    LEFT JOIN issues i ON u.id = i.user_id
    LEFT JOIN pull_requests pr ON u.id = pr.user_id
    WHERE u.id = user_id
    GROUP BY u.id, u.username, u.email, u.created_at;
END //

DELIMITER ;

-- =============================================================================
-- Triggers para mantener datos actualizados
-- =============================================================================

-- Trigger para actualizar updated_at en usuarios
DROP TRIGGER IF EXISTS update_users_timestamp;
CREATE TRIGGER update_users_timestamp
    BEFORE UPDATE ON users
    FOR EACH ROW
    SET NEW.updated_at = CURRENT_TIMESTAMP;

-- Trigger para crear actividad cuando se crea un commit
DROP TRIGGER IF EXISTS create_commit_activity;
CREATE TRIGGER create_commit_activity
    AFTER INSERT ON commits
    FOR EACH ROW
    INSERT INTO activities (type, action, description, repository_name, target_type, target_id, user_id, repository_id)
    SELECT 
        'commit',
        'created',
        CONCAT('Commit: ', SUBSTRING(NEW.message, 1, 100)),
        r.full_name,
        'commit',
        NEW.sha,
        NEW.user_id,
        NEW.repository_id
    FROM repositories r WHERE r.id = NEW.repository_id;

-- =============================================================================
-- Índices para optimización
-- =============================================================================

-- Índices compuestos para consultas comunes
CREATE INDEX idx_repo_user_updated ON repositories(user_id, updated_at);
CREATE INDEX idx_commit_repo_date ON commits(repository_id, committer_date);
CREATE INDEX idx_activity_user_date ON activities(user_id, created_at);
CREATE INDEX idx_issue_repo_state ON issues(repository_id, state);
CREATE INDEX idx_pr_repo_state ON pull_requests(repository_id, state);

-- Índice de texto completo para búsquedas
ALTER TABLE repositories ADD FULLTEXT(name, description);
ALTER TABLE issues ADD FULLTEXT(title, body);
ALTER TABLE pull_requests ADD FULLTEXT(title, body);

SELECT 'Base de datos configurada exitosamente' as status;