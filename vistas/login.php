<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    include_once __DIR__ . '/../config.php';
    header("Location: " . URL_BASE . "vistas/dashboard_view.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login | AutoGestión</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/config.js"></script>
    <script src="../assets/js/validators.js"></script>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-car"></i>
                <h1>AutoGestión</h1>
                <p>Inicia sesión en tu taller</p>
            </div>
            <form id="login-form">
                <div class="form-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" placeholder="Correo electrónico" required>
                </div>
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" placeholder="Contraseña" required>
                </div>
                <button type="submit" class="btn-login">Ingresar</button>
            </form>
            <div class="auth-footer">
                <a href="#" id="olvide-pass">¿Olvidaste tu contraseña?</a>
            </div>
        </div>
    </div>

    <!-- Modal para recuperación -->
    <div id="modal-recuperacion" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 400px;">
            <span class="close">&times;</span>
            <h2>Recuperar contraseña</h2>
            <p>Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>
            <input type="email" id="recuperacion-email" placeholder="Tu correo electrónico" style="width:100%; padding:10px; margin:15px 0; border-radius:40px; border:1px solid #ccc;">
            <button id="btn-enviar-recuperacion" class="btn-primary">Enviar enlace</button>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modal-recuperacion');
        const closeModal = modal.querySelector('.close');
        const olvideLink = document.getElementById('olvide-pass');
        const loginForm = document.getElementById('login-form');

        olvideLink.addEventListener('click', (e) => {
            e.preventDefault();
            modal.style.display = 'flex';
        });
        closeModal.addEventListener('click', () => modal.style.display = 'none');
        window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });

        document.getElementById('btn-enviar-recuperacion').addEventListener('click', async () => {
            const emailEl = document.getElementById('recuperacion-email');
            Validators.clearError(emailEl);
            const email = Validators.sanitize(emailEl.value);
            if (!Validators.required(email) || !Validators.email(email)) {
                Validators.showError(emailEl, 'Correo inválido');
                return;
            }
            try {
                const res = await apiRequest('auth/solicitar-recuperacion', { method: 'POST', body: JSON.stringify({ email }) });
                if (res.success) {
                    Swal.fire('Éxito', res.message, 'success');
                    modal.style.display = 'none';
                } else {
                    Swal.fire('Error', res.error || 'No se pudo procesar', 'error');
                }
            } catch (err) {
                console.error('Recuperación de contraseña error:', err);
                Swal.fire('Error', err.message || 'Error al enviar solicitud', 'error');
            }
        });

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const emailEl = document.getElementById('email');
            const passEl = document.getElementById('password');
            Validators.clearError(emailEl);
            Validators.clearError(passEl);
            const email = Validators.sanitize(emailEl.value);
            const password = passEl.value || '';
            if (!Validators.required(email) || !Validators.email(email)) { Validators.showError(emailEl, 'Email inválido'); emailEl.focus(); return; }
            if (!Validators.required(password) || password.length < 6) { Validators.showError(passEl, 'Contraseña inválida (mín 6 caracteres)'); passEl.focus(); return; }
            try {
                const res = await apiRequest('auth/login', { method: 'POST', body: JSON.stringify({ email, password }) });
                if (res.success) {
                        window.location.href = BASE_URL + 'vistas/dashboard_view.php';
                } else {
                    Swal.fire('Error', res.error || 'Credenciales incorrectas', 'error');
                }
            } catch (err) {
                Swal.fire('Error', err.message || 'Error de conexión', 'error');
            }
        });
    </script>
</body>
</html>