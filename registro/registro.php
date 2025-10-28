<?php
session_start();
require '../sql/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $tipo = $_POST['tipo'];
    $grado_seccion = trim($_POST['grado_seccion'] ?? '');
    $materia = trim($_POST['materia'] ?? '');

    // Validaciones
    $errors = [];
    
    if ($password !== $confirm_password) {
        $errors[] = "Las contraseñas no coinciden";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    if (empty($errors)) {
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors[] = "Este email ya está registrado";
        } else {
            // Hash de la contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar usuario
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, apellido, email, password, tipo, grado_seccion, materia) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $nombre, $apellido, $email, $password_hash, $tipo, $grado_seccion, $materia);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Registro exitoso. Ahora puedes iniciar sesión.";
                header("Location: ../login/login.php");
                exit();
            } else {
                $errors[] = "Error al registrar el usuario: " . $conn->error;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Inventario</title>
    <link rel="stylesheet" href="css/registro.css">
</head>
<body>
    <div class="container">
        <h2>Registro de Usuario</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Nombre:</label>
                <input type="text" name="nombre" value="<?php echo $_POST['nombre'] ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Apellido:</label>
                <input type="text" name="apellido" value="<?php echo $_POST['apellido'] ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Contraseña:</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Confirmar Contraseña:</label>
                <input type="password" name="confirm_password" required>
            </div>

            <div class="form-group">
                <label>Tipo de Usuario:</label>
                <select name="tipo" required onchange="mostrarCamposAdicionales(this.value)">
                    <option value="">Seleccionar...</option>
                    <option value="alumno">Alumno</option>
                    <option value="profesor">Profesor</option>
                    <option value="directivo">Directivo</option>
                    <option value="administrativo">Administrativo</option>
                </select>
            </div>

            <div class="form-group" id="gradoField" style="display: none;">
                <label>Grado/Sección:</label>
                <input type="text" name="grado_seccion" placeholder="Ej: 4to A, 5to B">
            </div>

            <div class="form-group" id="materiaField" style="display: none;">
                <label>Materia (para profesores):</label>
                <input type="text" name="materia" placeholder="Ej: Robótica, Programación">
            </div>

            <button type="submit">Registrarse</button>
        </form>

        <p>¿Ya tienes cuenta? <a href="../inicio_sesion/login.php">Inicia sesión aquí</a></p>
    </div>

    <script>
        function mostrarCamposAdicionales(tipo) {
            document.getElementById('gradoField').style.display = tipo === 'alumno' ? 'block' : 'none';
            document.getElementById('materiaField').style.display = tipo === 'profesor' ? 'block' : 'none';
        }
    </script>
</body>
</html>