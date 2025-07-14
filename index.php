<?php
include 'includes/config.php'; // Incluir config para la sesión y la BD

// Si el usuario ya está logueado, redirigirlo a su dashboard
if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_roles'])) {
    $roles = $_SESSION['usuario_roles'];

    if (in_array('Administrador', $roles)) {
        header("Location: " . BASE_URL . "/admin/dashboard.php");
        exit();
    } elseif (in_array('Gerente de proyecto', $roles)) {
        header("Location: " . BASE_URL . "/gerente/dashboard.php");
        exit();
    } elseif (in_array('Miembro del equipo', $roles)) {
        header("Location: " . BASE_URL . "/miembro/dashboard.php");
        exit();
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto text-center">
            <h1 class="display-4 mb-4">Sistema de Gestión de Tareas</h1>
            <p class="lead mb-5">Una solución completa para administrar proyectos y tareas en equipo con diferentes niveles de acceso.</p>
            <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                <a href="login.php" class="btn btn-primary btn-lg px-4 gap-3">Iniciar Sesión</a>
                <a href="registro.php" class="btn btn-outline-secondary btn-lg px-4">Registrarse</a>
            </div>
        </div>
    </div>
    <div class="row mt-5">
        <div class="col-lg-4 mb-4">
            <a href="<?php echo BASE_URL; ?>/login.php" class="text-decoration-none text-dark">
                <div class="card h-100 shadow feature-card card-link">
                    <div class="card-body text-center">
                        <i class="fas fa-user-shield fa-3x mb-3 text-primary"></i>
                        <h3>Administrador</h3>
                        <p class="text-muted">Gestiona usuarios, asigna roles y supervisa todos los proyectos.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-4 mb-4">
            <a href="<?php echo BASE_URL; ?>/login.php" class="text-decoration-none text-dark">
                <div class="card h-100 shadow feature-card card-link">
                    <div class="card-body text-center">
                        <i class="fas fa-user-tie fa-3x mb-3 text-success"></i>
                        <h3>Gerente de Proyecto</h3>
                        <p class="text-muted">Crea y administra proyectos, asigna tareas y monitorea el progreso.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-4 mb-4">
            <a href="<?php echo BASE_URL; ?>/login.php" class="text-decoration-none text-dark">
                <div class="card h-100 shadow feature-card card-link">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x mb-3 text-info"></i>
                        <h3>Miembro del Equipo</h3>
                        <p class="text-muted">Visualiza y actualiza el estado de las tareas asignadas.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<script>
    // Animación simple para las cards
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.feature-card').forEach((card, i) => {
            setTimeout(() => {
                card.style.opacity = 1;
                card.style.transform = 'translateY(0)';
                card.style.transition = 'all 0.6s';
            }, 200 * i);
        });
    });
</script>

<?php include 'includes/footer.php'; ?>