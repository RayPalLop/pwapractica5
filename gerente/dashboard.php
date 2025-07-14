<?php
include '../includes/config.php';
if (!tieneRol('Gerente de proyecto')) {
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active text-white" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="proyectos.php">
                            <i class="fas fa-project-diagram me-2"></i>Mis Proyectos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="tareas.php">
                            <i class="fas fa-tasks me-2"></i>Gestión de Tareas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="<?php echo BASE_URL; ?>/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard de Gerente</h1>
            </div>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Mis Proyectos</h5>
                            <p class="card-text display-4" id="totalProyectos">0</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Tareas Activas</h5>
                            <p class="card-text display-4" id="tareasActivas">0</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Tareas Completadas</h5>
                            <p class="card-text display-4" id="tareasCompletadas">0</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tareas pendientes -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Tareas Pendientes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Proyecto</th>
                                    <th>Asignado a</th>
                                    <th>Prioridad</th>
                                    <th>Vencimiento</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tareasPendientes">
                                <!-- Datos cargados por AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cargar estadísticas
    fetch(`../includes/funciones.php?action=obtenerEstadisticasGerente&gerente_id=<?= $_SESSION['usuario_id'] ?>`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('totalProyectos').textContent = data.totalProyectos;
            document.getElementById('tareasActivas').textContent = data.tareasActivas;
            document.getElementById('tareasCompletadas').textContent = data.tareasCompletadas;
        });

    // Cargar tareas pendientes
    fetch(`../includes/funciones.php?action=obtenerTareasPendientes&gerente_id=<?= $_SESSION['usuario_id'] ?>`)
        .then(response => response.json())
        .then(data => {
            const tabla = document.getElementById('tareasPendientes');
            tabla.innerHTML = '';
            
            data.forEach(tarea => {
                tabla.innerHTML += `
                    <tr>
                        <td>${tarea.titulo}</td>
                        <td>${tarea.proyecto_nombre}</td>
                        <td>${tarea.asignado_nombre}</td>
                        <td><span class="badge ${getPriorityBadgeClass(tarea.prioridad)}">${tarea.prioridad}</span></td>
                        <td>${tarea.fecha_vencimiento ? new Date(tarea.fecha_vencimiento).toLocaleDateString() : '-'}</td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="editarTarea(${tarea.id})">Editar</button>
                        </td>
                    </tr>
                `;
            });
        });
});

function getPriorityBadgeClass(prioridad) {
    switch(prioridad) {
        case 'Alta': return 'bg-danger';
        case 'Media': return 'bg-warning text-dark';
        case 'Baja': return 'bg-success';
        default: return 'bg-secondary';
    }
}
</script>

<?php include '../includes/footer.php'; ?>