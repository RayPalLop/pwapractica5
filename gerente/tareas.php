<?php 
include '../includes/config.php';
if (!tieneRol('Gerente de proyecto')) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}
include '../includes/header.php'; 

// Obtener proyectos del gerente actual
$stmt = $pdo->prepare("SELECT * FROM proyectos WHERE gerente_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$proyectos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Gestión de Tareas</h2>
    
    <div class="row mb-3">
        <div class="col-md-4">
            <select class="form-select" id="filtroProyecto">
                <option value="">Todos los proyectos</option>
                <?php foreach ($proyectos as $proyecto): ?>
                    <option value="<?= $proyecto['id'] ?>"><?= $proyecto['nombre'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <select class="form-select" id="filtroEstado">
                <option value="">Todos los estados</option>
                <option value="Pendiente">Pendiente</option>
                <option value="En progreso">En progreso</option>
                <option value="Completada">Completada</option>
            </select>
        </div>
        <div class="col-md-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevaTareaModal">
                Nueva Tarea
            </button>
        </div>
    </div>
    
    <table class="table table-striped" id="tablaTareas">
        <thead>
            <tr>
                <th>Título</th>
                <th>Proyecto</th>
                <th>Asignado a</th>
                <th>Estado</th>
                <th>Prioridad</th>
                <th>Vencimiento</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <!-- Datos se cargarán via AJAX -->
        </tbody>
    </table>
</div>

<!-- Modal para nueva tarea -->
<div class="modal fade" id="nuevaTareaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevaTarea">
                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" class="form-control" name="titulo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Proyecto</label>
                        <select class="form-select" name="proyecto_id" required>
                            <?php foreach ($proyectos as $proyecto): ?>
                                <option value="<?= $proyecto['id'] ?>"><?= $proyecto['nombre'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Asignar a</label>
                        <select class="form-select" name="asignado_id" required>
                            <!-- Se llenará con AJAX -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prioridad</label>
                        <select class="form-select" name="prioridad" required>
                            <option value="Baja">Baja</option>
                            <option value="Media" selected>Media</option>
                            <option value="Alta">Alta</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de vencimiento</label>
                        <input type="date" class="form-control" name="fecha_vencimiento">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarTarea()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Cargar tareas al abrir la página
document.addEventListener('DOMContentLoaded', function() {
    cargarTareas();
    
    // Cargar miembros del equipo cuando se selecciona un proyecto
    document.querySelector('select[name="proyecto_id"]').addEventListener('change', function() {
        cargarMiembrosProyecto(this.value);
    });
});

function cargarTareas() {
    const proyectoId = document.getElementById('filtroProyecto').value;
    const estado = document.getElementById('filtroEstado').value;
    
    fetch(`../includes/funciones.php?action=obtenerTareas&proyecto_id=${proyectoId}&estado=${estado}`)
        .then(response => response.json())
        .then(data => {
            const tabla = document.querySelector('#tablaTareas tbody');
            tabla.innerHTML = '';
            
            data.forEach(tarea => {
                tabla.innerHTML += `
                    <tr>
                        <td>${tarea.titulo}</td>
                        <td>${tarea.proyecto_nombre}</td>
                        <td>${tarea.asignado_nombre}</td>
                        <td>
                            <select class="form-select form-select-sm" onchange="actualizarEstadoTarea(${tarea.id}, this.value)">
                                <option value="Pendiente" ${tarea.estado === 'Pendiente' ? 'selected' : ''}>Pendiente</option>
                                <option value="En progreso" ${tarea.estado === 'En progreso' ? 'selected' : ''}>En progreso</option>
                                <option value="Completada" ${tarea.estado === 'Completada' ? 'selected' : ''}>Completada</option>
                            </select>
                        </td>
                        <td>${tarea.prioridad}</td>
                        <td>${tarea.fecha_vencimiento || '-'}</td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="editarTarea(${tarea.id})">Editar</button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarTarea(${tarea.id})">Eliminar</button>
                        </td>
                    </tr>
                `;
            });
        });
}

function cargarMiembrosProyecto(proyectoId) {
    fetch(`../includes/funciones.php?action=obtenerMiembrosProyecto&proyecto_id=${proyectoId}`)
        .then(response => response.json())
        .then(data => {
            const select = document.querySelector('select[name="asignado_id"]');
            select.innerHTML = '';
            
            data.forEach(miembro => {
                select.innerHTML += `<option value="${miembro.id}">${miembro.nombre}</option>`;
            });
        });
}

function guardarTarea() {
    const form = document.getElementById('formNuevaTarea');
    const formData = new FormData(form);
    
    fetch('../includes/funciones.php?action=crearTarea', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#nuevaTareaModal').modal('hide');
            cargarTareas();
        } else {
            alert(data.message);
        }
    });
}

function actualizarEstadoTarea(tareaId, estado) {
    fetch('../includes/funciones.php?action=actualizarEstadoTarea', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            tarea_id: tareaId,
            estado: estado
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert(data.message);
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>