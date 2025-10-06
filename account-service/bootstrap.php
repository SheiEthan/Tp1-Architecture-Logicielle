<?php

// Bootstrap pour microservice CompteBancaire
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoloader simple pour les classes
spl_autoload_register(function ($class) {
    // Essayer de charger depuis shared/ d'abord
    $sharedFile = __DIR__ . '/shared/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($sharedFile)) {
        require_once $sharedFile;
        return;
    }

    // Puis depuis le répertoire local
    $localFile = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($localFile)) {
        require_once $localFile;
        return;
    }
});

// Configuration de base de données pour comptes
class DB {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            $host = $_ENV['DB_HOST'] ?? 'mysql_accounts';
            $db = $_ENV['DB_DATABASE'] ?? 'tp3_accounts';
            $user = $_ENV['DB_USERNAME'] ?? 'laravel';
            $pass = $_ENV['DB_PASSWORD'] ?? 'secret';

            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return self::$pdo;
    }
}

// Classes simulées Laravel pour compatibilité
class Request {
    private $data;

    public function __construct($data = []) {
        $this->data = $data;
    }

    public function input($key, $default = null) {
        return $this->data[$key] ?? $default;
    }
}

class JsonResponse {
    private $data;
    private $status;

    public function __construct($data, $status = 200) {
        $this->data = $data;
        $this->status = $status;
    }

    public function getContent() {
        http_response_code($this->status);
        return json_encode($this->data);
    }

    public function getStatusCode() {
        return $this->status;
    }
}

function response() {
    return new class {
        public function json($data, $status = 200) {
            return new JsonResponse($data, $status);
        }
    };
}

function app($class) {
    // Service locator simple
    if ($class === \Application\Ports\ICompteBancaireRepository::class) {
        return new \Persistence\PDOCompteBancaireRepository();
    }
    if ($class === \Application\Mediator\Mediator::class) {
        return new \Application\Mediator\Mediator();
    }
    throw new Exception("Service not found: $class");
}
