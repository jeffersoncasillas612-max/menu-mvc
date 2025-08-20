<?php
// config/database.php
declare(strict_types=1);

// 1) Autoload robusto (soporta ejecución desde cualquier ruta)
$rootPath = dirname(__DIR__);              // .../MenuMVC
$autoload1 = $rootPath . '/vendor/autoload.php';
$autoload2 = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload1)) {
    require_once $autoload1;
} elseif (file_exists($autoload2)) {
    require_once $autoload2;
}

// 2) Cargar .env si existe (solo local)
if (file_exists($rootPath . '/.env') && class_exists(\Dotenv\Dotenv::class)) {
    $dotenv = Dotenv\Dotenv::createImmutable($rootPath);
    $dotenv->load();
}

// 3) Helper para leer variables de entorno con fallback
function env(string $key, ?string $default = null): ?string {
    $v = getenv($key);
    if ($v === false || $v === '') {
        $v = $_ENV[$key] ?? $default;
    }
    return $v;
}

class Database {
    private string $localhost;
    private string $puerto;
    private string $database;
    private string $username;
    private string $password;
    public ?PDO $conn = null;

    public function __construct() {
        // 4) Leer variables
        $this->localhost = env('DB_HOST', 'localhost') ?? 'localhost';
        $this->puerto    = env('DB_PORT', '3306') ?? '3306';
        $this->database  = env('DB_DATABASE', '') ?? '';
        $this->username  = env('DB_USERNAME', '') ?? '';
        $this->password  = env('DB_PASSWORD', '') ?? '';

        // 5) Diagnóstico útil si algo viene vacío (solo cuando hay .env)
        $rootPath = dirname(__DIR__);
        if (file_exists($rootPath . '/.env')) {
            $faltan = [];
            foreach (['DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME'] as $k) {
                if (env($k, '') === '') $faltan[] = $k;
            }
            if (!empty($faltan)) {
                // Mensaje claro para depurar en local
                echo "⚠️ Variables faltantes en .env: " . implode(', ', $faltan) . "<br>";
                echo "Ruta .env: " . htmlspecialchars($rootPath . '/.env') . "<br>";
            }
        }
    }

    public function getConnection(): ?PDO {
        try {
            $dsn = "mysql:host={$this->localhost};port={$this->puerto};dbname={$this->database};charset=utf8";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Error de conexión: " . $e->getMessage();
            $this->conn = null;
        }
        return $this->conn;
    }
}
