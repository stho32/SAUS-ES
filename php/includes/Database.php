<?php
declare(strict_types=1);

class Database {
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct() {
        $config = require __DIR__ . '/../config.php';
        $db = $config['db'];
        
        try {
            $this->connection = new PDO(
                "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}",
                $db['user'],
                $db['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException("Verbindungsfehler: " . $e->getMessage());
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }
    
    // Verhindere Klonen der Instanz
    private function __clone() {}
    
    // Verhindere Unserialisierung der Instanz
    public function __wakeup() {
        throw new RuntimeException("Cannot unserialize singleton");
    }
}
