<?php
include '../includes/config.php';
// La función tieneRol ya se encarga de verificar la sesión y redirigir si es necesario.
tieneRol('Administrador');
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
                        <a class="nav-link text-white" href="usuarios.php">
                            <i class="fas fa-users me-2"></i>Gestión de Usuarios
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
                <h1 class="h2">Dashboard de Administrador</h1>
            </div>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Usuarios</h5>
                            <p class="card-text display-4" id="totalUsuarios">0</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Proyectos</h5>
                            <p class="card-text display-4" id="totalProyectos">0</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Tareas</h5>
                            <p class="card-text display-4" id="totalTareas">0</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Últimos usuarios registrados -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Últimos Usuarios Registrados</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Fecha Registro</th>
                                    <th>Roles</th>
                                </tr>
                            </thead>
                            <tbody id="ultimosUsuarios">
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
    fetch('../includes/funciones.php?action=obtenerEstadisticasAdmin')
        .then(response => response.json())
        .then(data => {
            document.getElementById('totalUsuarios').textContent = data.totalUsuarios;
            document.getElementById('totalProyectos').textContent = data.totalProyectos;
            document.getElementById('totalTareas').textContent = data.totalTareas;
        });

    // Cargar últimos usuarios
    fetch('../includes/funciones.php?action=obtenerUltimosUsuarios')
        .then(response => response.json())
        .then(data => {
            const tabla = document.getElementById('ultimosUsuarios');
            tabla.innerHTML = '';
            
            data.forEach(usuario => {
                let roles = usuario.roles.map(r => r.nombre).join(', ');
                
                tabla.innerHTML += `
                    <tr>
                        <td>${usuario.id}</td>
                        <td>${usuario.nombre}</td>
                        <td>${usuario.email}</td>
                        <td>${new Date(usuario.fecha_creacion).toLocaleDateString()}</td>
                        <td>${roles}</td>
                    </tr>
                `;
            });
        });
});
</script>

<?php include '../includes/footer.php'; ?>