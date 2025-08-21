<?php

namespace YegaDashboard\Config;

class DatabaseConfig
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        $databaseUrl = $_ENV['DATABASE_URL'] ?? 'mysql://root:@localhost:3306/yega_dashboard';
        
        // Parse database URL
        $parsedUrl = parse_url($databaseUrl);
        
        $host = $parsedUrl['host'] ?? 'localhost';
        $port = $parsedUrl['port'] ?? 3306;
        $dbname = ltrim($parsedUrl['path'] ?? '/yega_dashboard', '/');
        $username = $parsedUrl['user'] ?? 'root';
        $password = $parsedUrl['pass'] ?? '';
        
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $this->connection = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (\PDOException $e) {
            throw new \Exception('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): \PDO
    {
        return $this->connection;
    }
}
