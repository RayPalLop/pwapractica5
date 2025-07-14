<?php
include 'config.php';

header('Content-Type: application/json');

// Validar que la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Acción no especificada.']);
    exit;
}

switch ($action) {
    case 'login':
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Email y contraseña son requeridos.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            // Regenerar el ID de sesión para prevenir ataques de fijación de sesión.
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email'] = $usuario['email'];

            // Obtener roles del usuario
            $stmt = $pdo->prepare("SELECT r.nombre FROM roles r JOIN usuario_roles ur ON r.id = ur.rol_id WHERE ur.usuario_id = ?");
            $stmt->execute([$usuario['id']]);
            $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $_SESSION['usuario_roles'] = $roles;

            // Redirigir según el rol
            $redirect = BASE_URL . '/index.php'; // valor por defecto
            if (in_array('Administrador', $roles)) {
                $redirect = BASE_URL . '/admin/dashboard.php';
            } elseif (in_array('Gerente de proyecto', $roles)) {
                $redirect = BASE_URL . '/gerente/dashboard.php';
            } elseif (in_array('Miembro del equipo', $roles)) {
                $redirect = BASE_URL . '/miembro/dashboard.php';
            }

            echo json_encode(['success' => true, 'redirect' => $redirect]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas.']);
        }
        break;

    case 'registro':
        $nombre = trim($data['nombre'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        // Validaciones básicas
        if (empty($nombre) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'El formato del email no es válido.']);
            exit;
        }
        if (strlen($password) < 8) {
            echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.']);
            exit;
        }

        // Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este correo electrónico ya está registrado.']);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            // Insertar nuevo usuario
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $email, $hashedPassword]);
            $usuarioId = $pdo->lastInsertId();

            // Asignar rol por defecto "Miembro del equipo"
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'Miembro del equipo'");
            $stmt->execute();
            $rol = $stmt->fetch();
            if (!$rol) {
                throw new Exception("El rol por defecto 'Miembro del equipo' no existe.");
            }
            $rolId = $rol['id'];

            $stmt = $pdo->prepare("INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (?, ?)");
            $stmt->execute([$usuarioId, $rolId]);

            $pdo->commit();

            // Iniciar sesión automáticamente después del registro
            $_SESSION['usuario_id'] = $usuarioId;
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_email'] = $email;
            $_SESSION['usuario_roles'] = ['Miembro del equipo'];

            echo json_encode([
                'success' => true,
                'message' => 'Registro exitoso. Redirigiendo...',
                'redirect' => BASE_URL . '/miembro/dashboard.php'
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log($e->getMessage()); // Guardar error real para depuración
            echo json_encode(['success' => false, 'message' => 'Error al registrar el usuario.']);
        }
        break;

    case 'logout':
        // Limpiar todas las variables de sesión.
        $_SESSION = array();

        // Borrar la cookie de sesión del navegador.
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Finalmente, destruir la sesión en el servidor.
        session_destroy();
        echo json_encode(['success' => true, 'redirect' => BASE_URL . '/login.php']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
        break;
}
?>
