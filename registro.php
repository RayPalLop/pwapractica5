<?php 
include 'includes/config.php';
include 'includes/header.php'; 

// Si ya está logueado, redirigir
if (isset($_SESSION['usuario_id'])) {
    header('Location: /');
    exit();
}
?>

<div class="register-container">
    <div class="register-card">
        <div class="register-header">
            <h2><i class="fas fa-user-plus"></i> Crear Cuenta</h2>
            <p>Completa el formulario para registrarte</p>
        </div>
        
        <!-- Contenedor para mensajes de error/éxito -->
        <div id="registroMessage" class="mb-3"></div>

        <form id="registerForm" class="needs-validation" novalidate>
            <!-- Campo Nombre -->
            <div class="form-group">
                <label for="fullName">Nombre Completo</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="fullName" class="form-control" required>
                </div>
                <div class="invalid-feedback">
                    Por favor ingresa tu nombre completo
                </div>
            </div>
            
            <!-- Campo Email -->
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" class="form-control" required>
                </div>
                <div class="invalid-feedback">
                    Por favor ingresa un email válido
                </div>
            </div>
            
            <!-- Campo Contraseña -->
            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" class="form-control" minlength="8" required>
                    <button type="button" class="toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength">
                    <div class="strength-bar">
                        <div class="strength-level" style="width: 0%"></div>
                    </div>
                    <span class="strength-text">Seguridad: <span>Débil</span></span>
                </div>
                <div class="invalid-feedback">
                    La contraseña debe tener al menos 8 caracteres
                </div>
            </div>
            
            <!-- Campo Confirmar Contraseña -->
            <div class="form-group">
                <label for="confirmPassword">Confirmar Contraseña</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirmPassword" class="form-control" required>
                </div>
                <div class="invalid-feedback">
                    Las contraseñas no coinciden
                </div>
            </div>
            
            <button type="submit" class="register-btn">
                <i class="fas fa-user-plus"></i> Registrarse
            </button>
            
            <div class="login-link">
                ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>
            </div>
        </form>
    </div>
</div>

<script>
// ...existing code...
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const nombre = document.getElementById('fullName').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

        fetch('/Ejercicios_PHP/pwapractica5/includes/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'registro',
                nombre: nombre,
                email: email,
                password: password
            })
        })
        .then(async response => {
            try {
                const data = await response.json();
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    document.getElementById('registroMessage').innerHTML = `
                        <div class="alert alert-danger">${data.message}</div>
                    `;
                }
            } catch (e) {
                document.getElementById('registroMessage').innerHTML = `
                    <div class="alert alert-danger">Error inesperado del servidor.</div>
                `;
            }
        });
    });
// ...existing code...
</script>

<?php include 'includes/footer.php'; ?>