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
                // Obtener el ID del usuario recién registrado
                $user_id = $stmt->insert_id;
                
                // Crear sesión automáticamente después del registro
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_nombre'] = $nombre . ' ' . $apellido;
                $_SESSION['user_tipo'] = $tipo;
                $_SESSION['logged_in'] = true;
                
                // Redirigir al dashboard
                header("Location: ../pagina/dashboard.php");
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
    <title>Registro - GesTIC</title>
    <link rel="stylesheet" href="../css/registro.css">
</head>
<body>
    <img src="../img/Logo-sin_fondo.png" class="logo" alt="logo">
    <div class="container">
        <div class="box">
            <h2 style="text-align: center; color: #333; margin-bottom: 30px;">Registro de Usuario</h2>
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form class="form" method="POST" action="">
                <div class="form-group">
                    <div class="flex-column">
                        <label>Nombre</label>
                    </div>
                    <div class="inputForm">
                        <input type="text" name="nombre" class="input" placeholder="Ingresa tu nombre" value="<?php echo $_POST['nombre'] ?? ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="flex-column">
                        <label>Apellido</label>
                    </div>
                    <div class="inputForm">
                        <input type="text" name="apellido" class="input" placeholder="Ingresa tu apellido" value="<?php echo $_POST['apellido'] ?? ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="flex-column">
                        <label>Email</label>
                    </div>
                    <div class="inputForm">
                        <input type="email" name="email" class="input" placeholder="Ingresa tu email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="flex-column">
                        <label>Contraseña</label>
                    </div>
                    <div class="inputForm">
                        <input type="password" name="password" class="input" placeholder="Crea una contraseña" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="flex-column">
                        <label>Confirmar Contraseña</label>
                    </div>
                    <div class="inputForm">
                        <input type="password" name="confirm_password" class="input" placeholder="Confirma tu contraseña" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="flex-column">
                        <label>Tipo de Usuario</label>
                    </div>
                    <div class="inputForm">
                        <select name="tipo" required onchange="mostrarCamposAdicionales(this.value)" style="width: 90%; border: none; outline: none; background: transparent;">
                            <option value="">Seleccionar...</option>
                            <option value="alumno">Alumno</option>
                            <option value="profesor">Profesor</option>
                            <option value="administrativo">Administrativo</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="gradoField" style="display: none;">
                    <div class="flex-column">
                        <label>Grado/Sección</label>
                    </div>
                    <div class="inputForm">
                        <input type="text" name="grado_seccion" class="input" placeholder="Ej: 4to 2da, 5to 4ta">
                    </div>
                </div>

                <div class="form-group" id="materiaField" style="display: none;">
                    <div class="flex-column">
                        <label>Materia (para profesores)</label>
                    </div>
                    <div class="inputForm">
                        <input type="text" name="materia" class="input" placeholder="Ej: Robótica, Programación">
                    </div>
                </div>

                <button type="submit" class="button-submit">Registrarse</button>
            </form>

            <p class="p">¿Ya tienes cuenta? <a href="login.php" class="span">Inicia sesión aquí</a></p>
        </div>
    </div>

    <script>
        function mostrarCamposAdicionales(tipo) {
            document.getElementById('gradoField').style.display = tipo === 'alumno' ? 'block' : 'none';
            document.getElementById('materiaField').style.display = tipo === 'profesor' ? 'block' : 'none';
        }
    </script>
</body>
</html>