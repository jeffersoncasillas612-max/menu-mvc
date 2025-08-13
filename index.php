<?php

// ------------------------------------------------------------
// ENRUTADOR PRINCIPAL CON CIFRADO DE CONTROLADOR, ACCIÓN, ID Y VISTA
// ------------------------------------------------------------

// Función para decodificar base64 de forma segura
function decodificar($valor) {
    $decodificado = base64_decode($valor, true);
    return $decodificado !== false ? $decodificado : null;
}

// Función para validar nombres (controlador / acción)
function esNombreValido($nombre) {
    return preg_match('/^[a-zA-Z_]+$/', $nombre);
}

// ✅ 1. Si viene vista cifrada tipo: ?vista=cm9sZXMvbGlzdGFyLnBocA==
if (isset($_GET['vista'])) {
    $vista = decodificar($_GET['vista']);  // ej: roles/listar.php
    if ($vista && file_exists("views/$vista")) {
        include "views/$vista";
        exit();
    } else {
        echo "❌ Vista no encontrada: <strong>views/$vista</strong>";
        exit();
    }
}

// ✅ 2. Si vienen controlador y acción cifrados tipo: ?c=cm9s&a=ZWRpdGFy&id=NA==
$controller = isset($_GET['c']) ? decodificar($_GET['c']) : ($_GET['controller'] ?? 'login');
$action     = isset($_GET['a']) ? decodificar($_GET['a']) : ($_GET['action'] ?? 'form');
$idCifrado  = $_GET['id'] ?? null;
$id         = $idCifrado ? decodificar($idCifrado) : null;

// ✅ Validación básica del nombre del controlador y acción
if (!esNombreValido($controller) || !esNombreValido($action)) {
    echo "❌ Parámetros inválidos.";
    exit();
}

// ✅ 3. Lógica para login directo sin controlador
if ($controller === 'login' && $action === 'form') {
    require_once 'views/login.php';
    exit();
}

// ✅ 4. Ruta y existencia del archivo del controlador
$controllerFile = "controllers/" . ucfirst($controller) . "Controller.php";

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $className = ucfirst($controller) . "Controller";
    $obj = new $className();

    // Verificar que el método exista en el controlador
    if (method_exists($obj, $action)) {
        // Ejecutar acción con o sin parámetro ID
        if ($id !== null) {
            $obj->$action($id);
        } else {
            $obj->$action();
        }
    } else {
        echo "❌ Acción '$action' no encontrada en el controlador '$className'.";
    }
} else {
    echo "❌ Controlador '$controllerFile' no encontrado.";
}
