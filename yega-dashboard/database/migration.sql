-- Migración inicial para Yega Dashboard
-- Base de datos MySQL para almacenar datos de GitHub

CREATE DATABASE IF NOT EXISTS yega_dashboard 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE yega_dashboard;

-- Tabla de repositorios
CREATE TABLE IF NOT EXISTS repositories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    owner VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    language VARCHAR(100),
    stars INT DEFAULT 0,
    forks INT DEFAULT 0,
    open_issues INT DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_owner (owner),
    INDEX idx_name (name),
    INDEX idx_stars (stars),
    INDEX idx_updated (updated_at)
) ENGINE=InnoDB;

-- Tabla de issues
CREATE TABLE IF NOT EXISTS issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repository_id INT NOT NULL,
    number INT NOT NULL,
    title TEXT NOT NULL,
    state ENUM('open', 'closed') NOT NULL,
    body LONGTEXT,
    author VARCHAR(255) NOT NULL,
    labels JSON,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    
    FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_repo_issue (repository_id, number),
    INDEX idx_state (state),
    INDEX idx_author (author),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Tabla de pull requests
CREATE TABLE IF NOT EXISTS pull_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repository_id INT NOT NULL,
    number INT NOT NULL,
    title TEXT NOT NULL,
    state ENUM('open', 'closed', 'merged') NOT NULL,
    body LONGTEXT,
    author VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    merged_at DATETIME NULL,
    closed_at DATETIME NULL,
    
    FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_repo_pr (repository_id, number),
    INDEX idx_state (state),
    INDEX idx_author (author),
    INDEX idx_created (created_at),
    INDEX idx_merged (merged_at)
) ENGINE=InnoDB;

-- Tabla de commits
CREATE TABLE IF NOT EXISTS commits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repository_id INT NOT NULL,
    sha VARCHAR(40) UNIQUE NOT NULL,
    message TEXT NOT NULL,
    author VARCHAR(255) NOT NULL,
    date DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE,
    INDEX idx_author (author),
    INDEX idx_date (date),
    INDEX idx_repository (repository_id)
) ENGINE=InnoDB;

-- Tabla de contenido README
CREATE TABLE IF NOT EXISTS readme_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repository_id INT UNIQUE NOT NULL,
    content LONGTEXT NOT NULL,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insertar datos iniciales de configuración
INSERT IGNORE INTO repositories (name, owner, full_name, description, created_at, updated_at) VALUES
('Yega-Ecosistema', 'SebastianVernisMora', 'SebastianVernisMora/Yega-Ecosistema', 'Ecosistema principal de Yega', NOW(), NOW()),
('Yega-API', 'SebastianVernisMora', 'SebastianVernisMora/Yega-API', 'API backend de Yega', NOW(), NOW()),
('Yega-Tienda', 'SebastianVernisMora', 'SebastianVernisMora/Yega-Tienda', 'Aplicación de tienda Yega', NOW(), NOW()),
('Yega-Repartidor', 'SebastianVernisMora', 'SebastianVernisMora/Yega-Repartidor', 'Aplicación para repartidores', NOW(), NOW()),
('Yega-Cliente', 'SebastianVernisMora', 'SebastianVernisMora/Yega-Cliente', 'Aplicación cliente de Yega', NOW(), NOW());

-- Vistas útiles para reportes
CREATE OR REPLACE VIEW repository_summary AS
SELECT 
    r.id,
    r.name,
    r.owner,
    r.full_name,
    r.stars,
    r.forks,
    r.open_issues,
    COUNT(DISTINCT i.id) as total_issues,
    COUNT(DISTINCT CASE WHEN i.state = 'open' THEN i.id END) as open_issues_count,
    COUNT(DISTINCT pr.id) as total_prs,
    COUNT(DISTINCT CASE WHEN pr.state = 'open' THEN pr.id END) as open_prs_count,
    COUNT(DISTINCT CASE WHEN pr.state = 'merged' THEN pr.id END) as merged_prs_count,
    COUNT(DISTINCT c.id) as total_commits,
    r.updated_at
FROM repositories r
LEFT JOIN issues i ON r.id = i.repository_id
LEFT JOIN pull_requests pr ON r.id = pr.repository_id  
LEFT JOIN commits c ON r.id = c.repository_id
GROUP BY r.id;

-- Índices adicionales para optimización
CREATE INDEX idx_issues_state_created ON issues(state, created_at);
CREATE INDEX idx_prs_state_created ON pull_requests(state, created_at);
CREATE INDEX idx_commits_repo_date ON commits(repository_id, date);

-- Trigger para actualizar updated_at en repositories
DELIMITER //
CREATE TRIGGER update_repo_timestamp 
    AFTER INSERT ON issues
    FOR EACH ROW
BEGIN
    UPDATE repositories 
    SET updated_at = CURRENT_TIMESTAMP 
    WHERE id = NEW.repository_id;
END//
DELIMITER ;

DELIMITER //
CREATE TRIGGER update_repo_timestamp_pr 
    AFTER INSERT ON pull_requests
    FOR EACH ROW
BEGIN
    UPDATE repositories 
    SET updated_at = CURRENT_TIMESTAMP 
    WHERE id = NEW.repository_id;
END//
DELIMITER ;