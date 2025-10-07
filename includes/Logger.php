<?php
class Logger {
    private static $instance = null;
    private $logFile;
    private $logLevel;

    private function __construct() {
        $this->logFile = LOG_DIR . '/app.log';
        $this->logLevel = LOG_LEVEL;
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function log($message, $level = 'INFO', $context = []) {
        $levels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];
        
        if ($levels[$level] < $levels[$this->logLevel]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = sprintf(
            "[%s] [%s] %s %s\n",
            $timestamp,
            $level,
            $message,
            !empty($context) ? json_encode($context) : ''
        );

        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    public function debug($message, $context = []) {
        $this->log($message, 'DEBUG', $context);
    }

    public function info($message, $context = []) {
        $this->log($message, 'INFO', $context);
    }

    public function warning($message, $context = []) {
        $this->log($message, 'WARNING', $context);
    }

    public function error($message, $context = []) {
        $this->log($message, 'ERROR', $context);
    }

    public function logException(\Throwable $e, $context = []) {
        $this->error(sprintf(
            "%s: %s in %s:%d\nStack trace:\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ), $context);
    }

    public function logUserActivity($userId, $action, $details = []) {
        $context = array_merge(['user_id' => $userId], $details);
        $this->info("User Activity: $action", $context);
    }

    public function logSecurityEvent($event, $details = []) {
        $this->warning("Security Event: $event", $details);
    }

    public function logDatabaseQuery($query, $params = [], $executionTime = null) {
        $context = [
            'query' => $query,
            'params' => $params,
            'execution_time' => $executionTime
        ];
        $this->debug("Database Query", $context);
    }
} 