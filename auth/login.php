<?php
session_start();
require '../sql/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Buscar usuario
    $stmt = $conn->prepare("SELECT id, nombre, apellido, password, tipo, activo FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $nombre, $apellido, $password_hash, $tipo, $activo);
    
    if ($stmt->fetch() && $activo) {
        if (password_verify($password, $password_hash)) {
            // Actualizar último login
            $update_stmt = $conn->prepare("UPDATE usuarios SET ultimo_login = CURRENT_TIMESTAMP WHERE id = ?");
            $update_stmt->bind_param("i", $id);
            $update_stmt->execute();
            $update_stmt->close();

            // Crear sesión
            $_SESSION['user_id'] = $id;
            $_SESSION['user_nombre'] = $nombre . ' ' . $apellido;
            $_SESSION['user_tipo'] = $tipo;
            $_SESSION['logged_in'] = true;

            // Redirigir al dashboard
            header("Location: ../pagina/dashboard.php");
            exit();
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "Usuario no encontrado o cuenta inactiva";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Inventario</title>
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    <h1 class="title">GesTIC</h1>
    
    <div class="container">
        <div class="box">
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <form class="form" method="POST" action="">
                <div class="flex-column">
                    <label>Email</label>
                </div>
                <div class="inputForm">
                    <input type="email" name="email" class="input" placeholder="Ingresa tu email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                </div>

                <div class="flex-column">
                    <label>Contraseña</label>
                </div>
                <div class="inputForm">
                    <input type="password" name="password" class="input" placeholder="Ingresa tu contraseña" required>
                </div>

                <div class="flex-row">
                    <div>
                        <input type="checkbox">
                        <label>Recordarme</label>
                    </div>
                    <span class="span">¿Olvidaste tu contraseña?</span>
                </div>

                <button type="submit" class="button-submit">Iniciar Sesión</button>
            </form>

            <p class="p">¿No tienes cuenta? <a href="registro.php" class="span">Regístrate aquí</a></p>
        </div>
    </div>
</body>
</html>