<?php 
include '../includes/config.php';
// La función tieneRol ya se encarga de verificar la sesión y redirigir si es necesario.
tieneRol('Administrador');
include '../includes/header.php'; 

// Verificación redundante, pero útil para depurar si hay problemas de sesión
if (!isset($_SESSION['usuario_roles']) || !in_array('Administrador', $_SESSION['usuario_roles'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>No tienes permiso para acceder a esta página.</div></div>";
    include '../includes/footer.php';
    exit();
}
?>

<div class="container mt-4">
    <h2>Gestión de Usuarios</h2>
    
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#nuevoUsuarioModal">
        Agregar Nuevo Usuario
    </button>
    
    <table class="table table-striped" id="tablaUsuarios">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Roles</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <!-- Datos se cargarán via AJAX -->
        </tbody>
    </table>
</div>

<!-- Modal para nuevo usuario -->
<div class="modal fade" id="nuevoUsuarioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevoUsuario">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Roles</label>
                        <select class="form-select" name="roles[]" multiple required>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM roles");
                            while ($rol = $stmt->fetch()) {
                                echo "<option value='{$rol['id']}'>{$rol['nombre']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarUsuario()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Cargar usuarios al abrir la página
document.addEventListener('DOMContentLoaded', function() {
    cargarUsuarios();
});

function cargarUsuarios() {
    fetch('../includes/funciones.php?action=obtenerUsuarios')
        .then(response => response.json())
        .then(data => {
            const tabla = document.querySelector('#tablaUsuarios tbody');
            tabla.innerHTML = '';
            
            data.forEach(usuario => {
                let roles = usuario.roles.map(r => r.nombre).join(', ');
                
                tabla.innerHTML += `
                    <tr>
                        <td>${usuario.id}</td>
                        <td>${usuario.nombre}</td>
                        <td>${usuario.email}</td>
                        <td>${roles}</td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editarUsuario(${usuario.id})">Editar</button>
                            <button class="btn btn-sm btn-danger" onclick="eliminarUsuario(${usuario.id})">Eliminar</button>
                        </td>
                    </tr>
                `;
            });
        });
}

function guardarUsuario() {
    const form = document.getElementById('formNuevoUsuario');
    const formData = new FormData(form);
    
    fetch('../includes/funciones.php?action=crearUsuario', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#nuevoUsuarioModal').modal('hide');
            cargarUsuarios();
        } else {
            alert(data.message);
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>