<?php
use Dotenv\Dotenv;

class Database {
    private $localhost;
    private $puerto;
    private $database;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // 1. Cargar dotenv solo si existe archivo local
        $envPath = __DIR__ . '/../'; // ajusta la ruta según dónde esté tu .env
        if (file_exists($envPath . '.env')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            $dotenv = Dotenv::createImmutable($envPath);
            $dotenv->load();
        }

        // 2. Leer variables (si no existen, Render ya las inyecta desde su panel)
        $this->localhost = getenv("DB_HOST");
        $this->puerto    = getenv("DB_PORT");
        $this->database  = getenv("DB_DATABASE");
        $this->username  = getenv("DB_USERNAME");
        $this->password  = getenv("DB_PASSWORD");
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->localhost};port={$this->puerto};dbname={$this->database};charset=utf8";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }

        return $this->conn;
    }
}
