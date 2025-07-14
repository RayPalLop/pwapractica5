<?php 
include '../includes/config.php';

// La función tieneRol() ya redirige si el usuario no ha iniciado sesión.
// Aquí, manejamos el caso específico en que el usuario SÍ está logueado, pero NO tiene el rol correcto.
$rolRequerido = 'Gerente de proyecto';
if (!tieneRol($rolRequerido)) {
    // Si la función devuelve false, significa que el usuario está logueado pero no tiene el rol.
    // Mostramos un mensaje de error claro en lugar de simplemente redirigir.
    include '../includes/header.php';
    echo "<div class='container mt-5'><div class='alert alert-danger'><h4><i class='fas fa-exclamation-triangle'></i> Acceso Denegado</h4><p>No tienes los permisos necesarios para acceder a esta página. Se requiere el rol de '<strong>" . htmlspecialchars($rolRequerido) . "</strong>'.</p><p>Por favor, inicia sesión con una cuenta que tenga los permisos adecuados.</p><a href='" . BASE_URL . "/logout.php' class='btn btn-warning'>Cerrar Sesión</a> <a href='" . BASE_URL . "/index.php' class='btn btn-primary'>Volver al Inicio</a></div></div>";
    include '../includes/footer.php';
    exit();
}

include '../includes/header.php'; 
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Mis Proyectos</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#proyectoModal" onclick="abrirModalNuevo()">
            <i class="fas fa-plus"></i> Nuevo Proyecto
        </button>
    </div>
    
    <table class="table table-striped table-hover" id="tablaProyectos">
        <thead>
            <tr>
                <th>Nombre del Proyecto</th>
                <th>Fecha de Inicio</th>
                <th>Fecha de Fin</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <!-- Los datos se cargarán aquí vía AJAX -->
        </tbody>
    </table>
</div>

<!-- Modal para Crear/Editar Proyecto -->
<div class="modal fade" id="proyectoModal" tabindex="-1" aria-labelledby="proyectoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="proyectoModalLabel">Nuevo Proyecto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formProyecto">
                    <input type="hidden" id="proyecto_id" name="proyecto_id">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Proyecto</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarProyecto()">Guardar Proyecto</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    cargarProyectos();
});

function cargarProyectos() {
    fetch(`../includes/funciones.php?action=obtenerProyectosGerente&gerente_id=<?= $_SESSION['usuario_id'] ?>`)
        .then(response => response.json())
        .then(data => {
            const tabla = document.querySelector('#tablaProyectos tbody');
            tabla.innerHTML = '';
            data.forEach(proyecto => {
                tabla.innerHTML += `
                    <tr>
                        <td>${proyecto.nombre}</td>
                        <td>${new Date(proyecto.fecha_inicio).toLocaleDateString()}</td>
                        <td>${proyecto.fecha_fin ? new Date(proyecto.fecha_fin).toLocaleDateString() : 'N/A'}</td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="abrirModalEditar(${proyecto.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarProyecto(${proyecto.id})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        });
}

function abrirModalNuevo() {
    document.getElementById('formProyecto').reset();
    document.getElementById('proyecto_id').value = '';
    document.getElementById('proyectoModalLabel').textContent = 'Nuevo Proyecto';
}

function abrirModalEditar(id) {
    fetch(`../includes/funciones.php?action=obtenerProyecto&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const proyecto = data.proyecto;
                document.getElementById('proyecto_id').value = proyecto.id;
                document.getElementById('nombre').value = proyecto.nombre;
                document.getElementById('descripcion').value = proyecto.descripcion;
                document.getElementById('fecha_inicio').value = proyecto.fecha_inicio;
                document.getElementById('fecha_fin').value = proyecto.fecha_fin;
                document.getElementById('proyectoModalLabel').textContent = 'Editar Proyecto';
                new bootstrap.Modal(document.getElementById('proyectoModal')).show();
            } else {
                alert(data.message);
            }
        });
}

function guardarProyecto() {
    const form = document.getElementById('formProyecto');
    const formData = new FormData(form);
    const action = formData.get('proyecto_id') ? 'actualizarProyecto' : 'crearProyecto';

    fetch(`../includes/funciones.php?action=${action}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('proyectoModal')).hide();
            cargarProyectos();
        } else {
            alert(data.message);
        }
    });
}

function eliminarProyecto(id) {
    if (confirm('¿Estás seguro de que quieres eliminar este proyecto? Esto también eliminará todas las tareas asociadas.')) {
        const formData = new FormData();
        formData.append('id', id);

        fetch('../includes/funciones.php?action=eliminarProyecto', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cargarProyectos();
            } else {
                alert(data.message);
            }
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>