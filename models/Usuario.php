<?php
require_once __DIR__ . '/../config/database.php';


class Usuario {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function verificar($correo, $clave) {
        $sql = "SELECT * FROM usuarios WHERE usu_correo = :correo AND usu_estado = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificamos contraseña con hash SHA2
        if ($usuario && hash('sha256', $clave) === $usuario['usu_contrasena']) {
            return $usuario;
        }

        return false;
    }


    public function crear($nombre, $apellido, $correo, $cedula, $rol_id, $especialidad_id = null) {
        $contrasena = hash('sha256', $cedula); // Contraseña igual a la cédula encriptada
    
        $sql = "INSERT INTO usuarios 
                (usu_nombre, usu_apellido, usu_correo, usu_contrasena, usu_cedula, rol_id, especialidad_id, usu_primera_vez, usu_estado)
                VALUES 
                (:nombre, :apellido, :correo, :contrasena, :cedula, :rol_id, :especialidad_id, 1, 1)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':contrasena', $contrasena);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->bindParam(':rol_id', $rol_id);
        $stmt->bindParam(':especialidad_id', $especialidad_id);
    
        return $stmt->execute();
    }
    
    

    public function existeCedulaOCorreo($cedula, $correo) {
        $sql = "SELECT COUNT(*) AS total FROM usuarios WHERE usu_cedula = :cedula OR usu_correo = :correo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total'] > 0;
    }
    



    public function actualizarClave($id, $claveHash) {
        $sql = "UPDATE usuarios SET usu_contrasena = ?, usu_primera_vez = 0 WHERE usu_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$claveHash, $id]);
    }

    
    public function obtenerPorCorreo($correo) {
        $sql = "SELECT * FROM usuarios WHERE usu_correo = :correo";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function actualizarContrasenaYPrimeraVez($id, $nuevaHash, $primeraVez = false) {
        $sql = "UPDATE usuarios SET usu_contrasena = :clave, usu_primera_vez = :primera WHERE usu_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':clave', $nuevaHash);
        $stmt->bindParam(':primera', $primeraVez, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    
    public function guardarTokenRecuperacion($id, $token, $expira) {
        $sql = "UPDATE usuarios SET usu_token_recuperacion = ?, usu_token_expira = ? WHERE usu_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$token, $expira, $id]);
    }

    public function obtenerPorToken($token) {
        $sql = "SELECT * FROM usuarios 
                WHERE usu_token_recuperacion = :token 
                  AND usu_token_expira >= NOW() 
                  AND usu_estado = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    
    public function limpiarToken($id) {
        $sql = "UPDATE usuarios SET usu_token_recuperacion = NULL, usu_token_expira = NULL WHERE usu_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function obtenerCorreoPorCedula($cedula) {
        $sql = "SELECT usu_correo FROM usuarios WHERE usu_cedula = :cedula AND usu_estado = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['usu_correo'] : null;
    }
    
    
    
    
}
