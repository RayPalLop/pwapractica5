<?php 
include '../includes/config.php';
if (!tieneRol('Miembro del equipo')) {
    header('Location: /login.php');
    exit();
}
include '../includes/header.php'; 
?>

<div class="container mt-4">
    <h2>Mis Tareas</h2>
    
    <div class="row mb-3">
        <div class="col-md-4">
            <select class="form-select" id="filtroEstado">
                <option value="">Todos los estados</option>
                <option value="Pendiente">Pendiente</option>
                <option value="En progreso">En progreso</option>
                <option value="Completada">Completada</option>
            </select>
        </div>
    </div>
    
    <table class="table table-striped" id="tablaTareas">
        <thead>
            <tr>
                <th>Título</th>
                <th>Proyecto</th>
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

<script>
// Cargar tareas al abrir la página
document.addEventListener('DOMContentLoaded', function() {
    cargarTareas();
});

function cargarTareas() {
    const estado = document.getElementById('filtroEstado').value;
    
    fetch(`../includes/funciones.php?action=obtenerMisTareas&estado=${estado}`)
        .then(response => response.json())
        .then(data => {
            const tabla = document.querySelector('#tablaTareas tbody');
            tabla.innerHTML = '';
            
            data.forEach(tarea => {
                tabla.innerHTML += `
                    <tr>
                        <td>${tarea.titulo}</td>
                        <td>${tarea.proyecto_nombre}</td>
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
                            <button class="btn btn-sm btn-info" onclick="verDetallesTarea(${tarea.id})">Detalles</button>
                        </td>
                    </tr>
                `;
            });
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

function verDetallesTarea(tareaId) {
    // Implementar modal con detalles de la tarea
    alert(`Mostrar detalles de la tarea ${tareaId}`);
}
</script>

<?php include '../includes/footer.php'; ?>