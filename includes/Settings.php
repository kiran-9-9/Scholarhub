<?php
class Settings {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function get($key, $default = null) {
        $stmt = $this->pdo->prepare("SELECT value FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['value'] : $default;
    }

    public function set($key, $value) {
        $stmt = $this->pdo->prepare("REPLACE INTO settings (`key`, `value`) VALUES (?, ?)");
        return $stmt->execute([$key, $value]);
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT `key`, `value` FROM settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    }
}
?> 