<?php
require_once '../config/auth.php';
startSession();

if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['usuario'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (authenticate($user, $pass)) {
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['user'] = $user;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | Asistente de Tareas</title>
    <link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-card">
        <div class="login-brand">
            <i data-lucide="lock"></i>
            <span>Asistente de Tareas</span>
        </div>
        <p class="login-subtitle">Inicia sesión para acceder al panel</p>

        <?php if ($error): ?>
            <div class="login-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" autocomplete="username" required value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>">

            <label for="password">Contraseña</label>
            <div class="password-field">
                <input type="password" id="password" name="password" autocomplete="current-password" required>
                <button type="button" class="toggle-password" id="togglePassword" aria-label="Mostrar u ocultar contraseña">
                    <i data-lucide="eye"></i>
                </button>
            </div>

            <button type="submit" class="btn-login">Ingresar</button>
        </form>
    </div>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        lucide.createIcons();
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        togglePassword.addEventListener('click', function () {
            const mostrar = passwordInput.type === 'password';
            passwordInput.type = mostrar ? 'text' : 'password';
            togglePassword.innerHTML = '<i data-lucide="' + (mostrar ? 'eye-off' : 'eye') + '"></i>';
            lucide.createIcons();
        });
    </script>
</body>
</html>
