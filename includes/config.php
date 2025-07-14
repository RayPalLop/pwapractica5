<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// ... tu código PHP
?>


<?php
session_start();

define('BASE_URL', '/Ejercicios_PHP/pwapractica5');

$host = 'localhost';
$dbname = 'gestion_tareas';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

function tieneRol($rolRequerido) {
    if (!isset($_SESSION['usuario_id'])) {
        // Redirigir a la página de login usando la constante BASE_URL
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
    
    // Verificar si el rol requerido está en el array de roles de la sesión.
    // Esto es mucho más eficiente que consultar la base de datos en cada página.
    if (isset($_SESSION['usuario_roles']) && is_array($_SESSION['usuario_roles'])) {
        $tieneRol = in_array($rolRequerido, $_SESSION['usuario_roles']);
        // Log temporal para depuración
        error_log("Usuario " . $_SESSION['usuario_nombre'] . " tiene roles: " . implode(", ", $_SESSION['usuario_roles']) . ".  ¿Tiene rol '$rolRequerido'? " . ($tieneRol ? "Sí" : "No"));
        return $tieneRol;
    } else {
        // Log temporal para depuración
        error_log("No se encontraron roles para el usuario " . ($_SESSION['usuario_nombre'] ?? 'ID: ' . $_SESSION['usuario_id']) . " en la sesión.");
    }
    return false;
}
?>