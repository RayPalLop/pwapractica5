// LOGIN
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        fetch('/Ejercicios_PHP/pwapractica5/includes/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'login',
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
                    document.getElementById('loginMessage').innerHTML = `
                        <div class="alert alert-danger">${data.message}</div>
                    `;
                }
            } catch (e) {
                document.getElementById('loginMessage').innerHTML = `
                    <div class="alert alert-danger">Error inesperado del servidor.</div>
                `;
            }
        });
    });
}

// REGISTRO
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const nombre = document.getElementById('fullName').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword') ? document.getElementById('confirmPassword').value : password;

        if (password !== confirmPassword) {
            document.getElementById('confirmPassword').classList.add('is-invalid');
            return;
        }

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
}