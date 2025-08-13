<?php
class Database {
    private $localhost = "localhost";
    private $puerto = "3306";
    private $database = "hospital"; // Cámbialo si tu nueva BD se llama diferente
    private $username = "root";
    private $password = "";
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
