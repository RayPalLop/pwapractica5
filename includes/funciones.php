<?php
include 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$requestData = array_merge($_GET, $_POST);

switch ($action) {
    // Funciones existentes de autenticación y usuarios
    case 'obtenerUsuarios':
        $stmt = $pdo->query("
            SELECT u.id, u.nombre, u.email, 
                   GROUP_CONCAT(r.nombre SEPARATOR ', ') AS roles_str
            FROM usuarios u
            LEFT JOIN usuario_roles ur ON u.id = ur.usuario_id
            LEFT JOIN roles r ON ur.rol_id = r.id
            GROUP BY u.id
        ");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $resultado = array_map(function($usuario) {
            $usuario['roles'] = $usuario['roles_str'] ? 
                array_map('trim', explode(',', $usuario['roles_str'])) : [];
            unset($usuario['roles_str']);
            return $usuario;
        }, $usuarios);
        
        echo json_encode($resultado);
        break;
        
    case 'crearUsuario':
        $nombre = $requestData['nombre'];
        $email = $requestData['email'];
        $password = password_hash($requestData['password'], PASSWORD_DEFAULT);
        $roles = $requestData['roles'] ?? [];
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$nombre, $email, $password]);
            $usuarioId = $pdo->lastInsertId();
            
            foreach ($roles as $rolId) {
                $stmt = $pdo->prepare("INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (?, ?)");
                $stmt->execute([$usuarioId, $rolId]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // Funciones para tareas
    case 'obtenerTareas':
        $proyectoId = $requestData['proyecto_id'] ?? '';
        $estado = $requestData['estado'] ?? '';
        $usuarioId = $_SESSION['usuario_id'] ?? 0;
        
        $sql = "
            SELECT t.*, p.nombre AS proyecto_nombre, 
                   u.nombre AS asignado_nombre
            FROM tareas t
            JOIN proyectos p ON t.proyecto_id = p.id
            JOIN usuarios u ON t.asignado_id = u.id
            WHERE p.gerente_id = :gerente_id
        ";
        
        $params = ['gerente_id' => $usuarioId];
        
        if ($proyectoId) {
            $sql .= " AND t.proyecto_id = :proyecto_id";
            $params['proyecto_id'] = $proyectoId;
        }
        
        if ($estado) {
            $sql .= " AND t.estado = :estado";
            $params['estado'] = $estado;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($tareas);
        break;
        
    case 'obtenerMiembrosProyecto':
        $proyectoId = $requestData['proyecto_id'];
        
        $stmt = $pdo->prepare("
            SELECT u.id, u.nombre 
            FROM usuarios u
            JOIN usuario_roles ur ON u.id = ur.usuario_id
            JOIN roles r ON ur.rol_id = r.id
            WHERE r.nombre = 'Miembro del equipo'
        ");
        $stmt->execute();
        $miembros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($miembros);
        break;
        
    case 'crearTarea':
        $titulo = $requestData['titulo'];
        $descripcion = $requestData['descripcion'];
        $proyectoId = $requestData['proyecto_id'];
        $asignadoId = $requestData['asignado_id'];
        $prioridad = $requestData['prioridad'];
        $fechaVencimiento = $requestData['fecha_vencimiento'] ?? null;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tareas 
                (titulo, descripcion, proyecto_id, creador_id, asignado_id, prioridad, fecha_vencimiento)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $titulo, 
                $descripcion, 
                $proyectoId, 
                $_SESSION['usuario_id'], 
                $asignadoId, 
                $prioridad, 
                $fechaVencimiento
            ]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    case 'obtenerMisTareas':
        $estado = $requestData['estado'] ?? '';
        
        $sql = "
            SELECT t.*, p.nombre AS proyecto_nombre
            FROM tareas t
            JOIN proyectos p ON t.proyecto_id = p.id
            WHERE t.asignado_id = :asignado_id
        ";
        
        $params = ['asignado_id' => $_SESSION['usuario_id']];
        
        if ($estado) {
            $sql .= " AND t.estado = :estado";
            $params['estado'] = $estado;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($tareas);
        break;
        
    case 'actualizarEstadoTarea':
        $tareaId = $requestData['tarea_id'];
        $estado = $requestData['estado'];
        
        // Verificar permisos
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM tareas 
            WHERE id = ? AND (creador_id = ? OR asignado_id = ?)
        ");
        $stmt->execute([$tareaId, $_SESSION['usuario_id'], $_SESSION['usuario_id']]);
        $tienePermiso = $stmt->fetchColumn() > 0;
        
        if (!$tienePermiso) {
            echo json_encode(['success' => false, 'message' => 'No tienes permiso para actualizar esta tarea']);
            break;
        }
        
        $stmt = $pdo->prepare("UPDATE tareas SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $tareaId]);
        
        echo json_encode(['success' => true]);
        break;

    // Nuevas funciones para los dashboards
    case 'obtenerEstadisticasAdmin':
        // Total usuarios
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
        $totalUsuarios = $stmt->fetchColumn();
        
        // Total proyectos
        $stmt = $pdo->query("SELECT COUNT(*) FROM proyectos");
        $totalProyectos = $stmt->fetchColumn();
        
        // Total tareas
        $stmt = $pdo->query("SELECT COUNT(*) FROM tareas");
        $totalTareas = $stmt->fetchColumn();
        
        echo json_encode([
            'totalUsuarios' => $totalUsuarios,
            'totalProyectos' => $totalProyectos,
            'totalTareas' => $totalTareas
        ]);
        break;

    case 'obtenerUltimosUsuarios':
        $stmt = $pdo->query("
            SELECT u.id, u.nombre, u.email, u.fecha_creacion, 
                   GROUP_CONCAT(r.nombre SEPARATOR ', ') AS roles_str
            FROM usuarios u
            LEFT JOIN usuario_roles ur ON u.id = ur.usuario_id
            LEFT JOIN roles r ON ur.rol_id = r.id
            GROUP BY u.id
            ORDER BY u.fecha_creacion DESC
            LIMIT 5
        ");
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $resultado = array_map(function($usuario) {
            $usuario['roles'] = $usuario['roles_str'] ? 
                array_map('trim', explode(',', $usuario['roles_str'])) : [];
            unset($usuario['roles_str']);
            return $usuario;
        }, $usuarios);
        
        echo json_encode($resultado);
        break;

    case 'obtenerEstadisticasGerente':
        $gerenteId = $requestData['gerente_id'];
        
        // Total proyectos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM proyectos WHERE gerente_id = ?");
        $stmt->execute([$gerenteId]);
        $totalProyectos = $stmt->fetchColumn();
        
        // Tareas activas (no completadas)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM tareas t
            JOIN proyectos p ON t.proyecto_id = p.id
            WHERE p.gerente_id = ? AND t.estado != 'Completada'
        ");
        $stmt->execute([$gerenteId]);
        $tareasActivas = $stmt->fetchColumn();
        
        // Tareas completadas
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM tareas t
            JOIN proyectos p ON t.proyecto_id = p.id
            WHERE p.gerente_id = ? AND t.estado = 'Completada'
        ");
        $stmt->execute([$gerenteId]);
        $tareasCompletadas = $stmt->fetchColumn();
        
        echo json_encode([
            'totalProyectos' => $totalProyectos,
            'tareasActivas' => $tareasActivas,
            'tareasCompletadas' => $tareasCompletadas
        ]);
        break;

    case 'obtenerTareasPendientes':
        $gerenteId = $requestData['gerente_id'];
        
        $stmt = $pdo->prepare("
            SELECT t.id, t.titulo, t.prioridad, t.fecha_vencimiento,
                   p.nombre AS proyecto_nombre, u.nombre AS asignado_nombre
            FROM tareas t
            JOIN proyectos p ON t.proyecto_id = p.id
            JOIN usuarios u ON t.asignado_id = u.id
            WHERE p.gerente_id = ? AND t.estado != 'Completada'
            ORDER BY 
                CASE t.prioridad 
                    WHEN 'Alta' THEN 1 
                    WHEN 'Media' THEN 2 
                    WHEN 'Baja' THEN 3 
                    ELSE 4 
                END,
                t.fecha_vencimiento ASC
            LIMIT 10
        ");
        $stmt->execute([$gerenteId]);
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($tareas);
        break;

    case 'obtenerEstadisticasMiembro':
        $usuarioId = $requestData['usuario_id'];
        
        // Total tareas asignadas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tareas WHERE asignado_id = ?");
        $stmt->execute([$usuarioId]);
        $totalTareas = $stmt->fetchColumn();
        
        // Tareas completadas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tareas WHERE asignado_id = ? AND estado = 'Completada'");
        $stmt->execute([$usuarioId]);
        $tareasCompletadas = $stmt->fetchColumn();
        
        // Tareas pendientes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tareas WHERE asignado_id = ? AND estado != 'Completada'");
        $stmt->execute([$usuarioId]);
        $tareasPendientes = $stmt->fetchColumn();
        
        echo json_encode([
            'totalTareas' => $totalTareas,
            'tareasCompletadas' => $tareasCompletadas,
            'tareasPendientes' => $tareasPendientes
        ]);
        break;

    case 'obtenerMisTareasRecientes':
        $usuarioId = $requestData['usuario_id'];
        
        $stmt = $pdo->prepare("
            SELECT t.id, t.titulo, t.estado, t.prioridad, t.fecha_vencimiento,
                   p.nombre AS proyecto_nombre
            FROM tareas t
            JOIN proyectos p ON t.proyecto_id = p.id
            WHERE t.asignado_id = ?
            ORDER BY t.fecha_creacion DESC
            LIMIT 5
        ");
        $stmt->execute([$usuarioId]);
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($tareas);
        break;

    case 'obtenerProyectosGerente':
        $gerenteId = $requestData['gerente_id'];
        
        $stmt = $pdo->prepare("
            SELECT id, nombre, fecha_inicio, fecha_fin 
            FROM proyectos 
            WHERE gerente_id = ?
            ORDER BY fecha_inicio DESC
        ");
        $stmt->execute([$gerenteId]);
        $proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($proyectos);
        break;

    case 'crearProyecto':
        $nombre = $requestData['nombre'] ?? '';
        $descripcion = $requestData['descripcion'] ?? '';
        $fecha_inicio = $requestData['fecha_inicio'] ?? '';
        $fecha_fin = $requestData['fecha_fin'] ?? null;
        $gerente_id = $_SESSION['usuario_id'];

        if (empty($nombre) || empty($fecha_inicio)) {
            echo json_encode(['success' => false, 'message' => 'El nombre y la fecha de inicio son obligatorios.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO proyectos (nombre, descripcion, fecha_inicio, fecha_fin, gerente_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $fecha_inicio, $fecha_fin, $gerente_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al crear el proyecto: ' . $e->getMessage()]);
        }
        break;

    case 'obtenerProyecto':
        $id = $requestData['id'] ?? 0;
        $gerente_id = $_SESSION['usuario_id'];

        $stmt = $pdo->prepare("SELECT * FROM proyectos WHERE id = ? AND gerente_id = ?");
        $stmt->execute([$id, $gerente_id]);
        $proyecto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($proyecto) {
            echo json_encode(['success' => true, 'proyecto' => $proyecto]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Proyecto no encontrado o no tienes permiso.']);
        }
        break;

    case 'actualizarProyecto':
        $id = $requestData['proyecto_id'] ?? 0;
        $nombre = $requestData['nombre'] ?? '';
        $descripcion = $requestData['descripcion'] ?? '';
        $fecha_inicio = $requestData['fecha_inicio'] ?? '';
        $fecha_fin = $requestData['fecha_fin'] ?? null;
        $gerente_id = $_SESSION['usuario_id'];

        try {
            $stmt = $pdo->prepare("UPDATE proyectos SET nombre = ?, descripcion = ?, fecha_inicio = ?, fecha_fin = ? WHERE id = ? AND gerente_id = ?");
            $stmt->execute([$nombre, $descripcion, $fecha_inicio, $fecha_fin, $id, $gerente_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el proyecto: ' . $e->getMessage()]);
        }
        break;

    case 'eliminarProyecto':
        $id = $requestData['id'] ?? 0;
        $gerente_id = $_SESSION['usuario_id'];

        try {
            // Asegurarse de que solo el gerente del proyecto puede eliminarlo
            $stmt = $pdo->prepare("DELETE FROM proyectos WHERE id = ? AND gerente_id = ?");
            $stmt->execute([$id, $gerente_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el proyecto: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?>