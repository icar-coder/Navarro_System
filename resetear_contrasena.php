<?php
$token = $_GET['token'] ?? '';
if (empty($token)) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer contraseña</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/config.js"></script>
    <script src="assets/js/validators.js"></script>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-key"></i>
                <h1>Nueva contraseña</h1>
                <p>Ingresa tu nueva contraseña</p>
            </div>
            <form id="reset-form">
                <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" placeholder="Nueva contraseña" required>
                </div>
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" placeholder="Confirmar contraseña" required>
                </div>
                <button type="submit" class="btn-login">Restablecer contraseña</button>
            </form>
            <div class="auth-footer">
                <a href="login.php">Volver al login</a>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('reset-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const token = document.getElementById('token').value;
            const passEl = document.getElementById('password');
            const confEl = document.getElementById('confirm_password');
            Validators.clearError(passEl);
            Validators.clearError(confEl);
            const password = passEl.value || '';
            const confirm = confEl.value || '';
            if (!Validators.required(password) || password.length < 6) { Validators.showError(passEl, 'Contraseña mínima 6 caracteres'); passEl.focus(); return; }
            if (password !== confirm) { Validators.showError(confEl, 'Las contraseñas no coinciden'); confEl.focus(); return; }
            try {
                const res = await apiRequest('auth/resetear-password', { method: 'POST', body: JSON.stringify({ token, password }) });
                if (res.success) {
                    Swal.fire('Éxito', 'Contraseña actualizada. Ya puedes iniciar sesión.', 'success').then(() => {
                        window.location.href = 'login.php';
                    });
                } else {
                    Swal.fire('Error', res.error || 'No se pudo restablecer', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        });
    </script>
</body>
</html>