<?php
class Database {
    private $localhost = "mysql.railway.internal";
    private $puerto = "41946";
    private $database = "railway"; // Cámbialo si tu nueva BD se llama diferente
    private $username = "root";
    private $password = "tIdYJBctwsMnBZKVmojqvzqReSbQzkdc";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host={$this->localhost};port={$this->puerto};dbname={$this->database};charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
