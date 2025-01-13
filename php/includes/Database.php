<?php
declare(strict_types=1);

require_once __DIR__ . '/error_logger.php';

class Database {
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private ErrorLogger $logger;
    
    private function __construct() {
        $this->logger = ErrorLogger::getInstance();
        $this->connect();
    }
    
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    private function connect(): void {
        try {
            $config = require __DIR__ . '/../config.php';
            $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
            
            $this->connection = new PDO(
                $dsn,
                $config['db']['user'],
                $config['db']['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            $this->logger->logError("Datenbankverbindungsfehler", $e);
            throw new Exception("Datenbankverbindung konnte nicht hergestellt werden");
        }
    }
    
    public function getConnection(): PDO {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    public function executeQuery(string $query, array $params = []): PDOStatement {
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logger->logError("SQL-Ausführungsfehler: $query", $e);
            throw new Exception("Datenbankabfrage konnte nicht ausgeführt werden");
        }
    }
    
    // Verhindere Klonen der Instanz
    private function __clone() {}
    
    // Verhindere Unserialisierung der Instanz
    public function __wakeup() {
        throw new RuntimeException("Cannot unserialize singleton");
    }
}
