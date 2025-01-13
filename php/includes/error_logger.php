<?php
declare(strict_types=1);

class ErrorLogger {
    private static ?ErrorLogger $instance = null;
    private string $logFile;
    
    private function __construct() {
        // Logdatei außerhalb des Web-Root-Verzeichnisses
        $this->logFile = dirname(__DIR__, 2) . '/logs/error.log';
        
        // Erstelle logs-Verzeichnis, falls es nicht existiert
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public static function getInstance(): ErrorLogger {
        if (self::$instance === null) {
            self::$instance = new ErrorLogger();
        }
        return self::$instance;
    }
    
    public function logError(string $message, ?Throwable $exception = null): void {
        $timestamp = date('Y-m-d H:i:s');
        $errorMessage = "[$timestamp] $message";
        
        if ($exception) {
            $errorMessage .= "\nException: " . $exception->getMessage();
            $errorMessage .= "\nFile: " . $exception->getFile() . ":" . $exception->getLine();
            $errorMessage .= "\nTrace:\n" . $exception->getTraceAsString();
        }
        
        $errorMessage .= "\nURL: " . $_SERVER['REQUEST_URI'];
        $errorMessage .= "\nIP: " . $_SERVER['REMOTE_ADDR'];
        $errorMessage .= "\nUser Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
        $errorMessage .= "\n" . str_repeat('-', 80) . "\n";
        
        error_log($errorMessage, 3, $this->logFile);
    }
    
    public function getLogPath(): string {
        return $this->logFile;
    }
}
