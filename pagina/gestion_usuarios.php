<?php
session_start();
require '../sql/conexion.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['user_tipo'] !== 'administrativo') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_nombre = $_SESSION['user_nombre'];

// Procesar acciones sobre usuarios
$message = '';
$message_type = '';

// Activar/Desactivar usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_usuario'])) {
    $usuario_id = intval($_POST['usuario_id']);
    $accion = $_POST['accion'];
    
    // No permitir desactivarse a sí mismo
    if ($usuario_id == $user_id) {
        $message = "❌ No puedes desactivar tu propia cuenta";
        $message_type = 'error';
    } else {
        $nuevo_estado = $accion === 'activar' ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
        $stmt->bind_param("ii", $nuevo_estado, $usuario_id);
        
        if ($stmt->execute()) {
            $estado_texto = $accion === 'activar' ? 'activada' : 'desactivada';
            $message = "✅ Cuenta " . $estado_texto . " correctamente";
            $message_type = 'success';
        } else {
            $message = "❌ Error al actualizar el usuario: " . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Cambiar tipo de usuario - CORREGIDO: Verificar si existe la clave antes de acceder
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar_tipo'])) {
    $usuario_id = intval($_POST['usuario_id']);
    $nuevo_tipo = $_POST['nuevo_tipo'];
    
    // No permitir cambiarse el tipo a sí mismo
    if ($usuario_id == $user_id) {
        $message = "❌ No puedes cambiar tu propio tipo de usuario";
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET tipo = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_tipo, $usuario_id);
        
        if ($stmt->execute()) {
            $message = "✅ Tipo de usuario actualizado correctamente";
            $message_type = 'success';
        } else {
            $message = "❌ Error al actualizar el tipo de usuario: " . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Buscar usuarios
$busqueda = '';
$filtro_tipo = '';
$usuarios = [];

// Construir consulta base
$query = "SELECT id, nombre, apellido, email, tipo, grado_seccion, materia, activo, fecha_registro, ultimo_login FROM usuarios WHERE 1=1";
$params = [];
$types = '';

if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
    $busqueda = trim($_GET['busqueda']);
    $query .= " AND (nombre LIKE ? OR apellido LIKE ? OR email LIKE ?)";
    $search_term = "%$busqueda%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= 'sss';
}

if (isset($_GET['tipo']) && !empty($_GET['tipo'])) {
    $filtro_tipo = $_GET['tipo'];
    $query .= " AND tipo = ?";
    $params[] = $filtro_tipo;
    $types .= 's';
}

$query .= " ORDER BY fecha_registro DESC";

// Ejecutar consulta
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$usuarios = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Estadísticas
$total_usuarios = count($usuarios);
$usuarios_activos = array_filter($usuarios, function($user) {
    return $user['activo'] == 1;
});
$total_activos = count($usuarios_activos);

// Contar por tipo
$contador_tipos = [
    'profesor' => 0,
    'alumno' => 0,
    'directivo' => 0,
    'administrativo' => 0
];

foreach ($usuarios as $usuario) {
    if (isset($contador_tipos[$usuario['tipo']])) {
        $contador_tipos[$usuario['tipo']]++;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - GesTIC</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/gestion_usuarios.css">
    <link rel="apple-touch-icon" sizes="57x57" href="../img/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="../img/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="../img/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="../img/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="../img/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="../img/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="../img/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="../img/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../img/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="../img/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="../img/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../img/favicon/favicon-16x16.png">
    <link rel="manifest" href="../img/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="../img/favicon/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <div class="dashboard-container">
        <!-- Header Horizontal -->
        <header class="header-nav">
            <div class="nav-top">
                <div class="logo-section">
                    <div>
                        <img src="../img/Logo-sin_fondo.png" class="logo" alt="GesTIC-logo">
                    </div>
                </div>
                
                <div class="user-info">
                    <span><?php echo htmlspecialchars($user_nombre); ?> (Admin)</span>
                    <a href="../auth/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar Sesión
                    </a>
                </div>
            </div>

            <!-- Navegación Horizontal -->
            <nav class="nav-horizontal">
                <button class="nav-item" onclick="window.location.href='dashboard.php'">
                    <i class="fas fa-arrow-left"></i>
                    Volver al Dashboard
                </button>
                <button class="nav-item" onclick="window.location.href='admin.php'">
                    <i class="fas fa-cogs"></i>
                    Panel Admin
                </button>
                <button class="nav-item" onclick="window.location.href='admin.php'">
                    <i class="fas fa-qrcode"></i>
                    Escanear Códigos
                </button>
                <button class="nav-item" onclick="window.location.href='admin.php'">
                    <i class="fas fa-microchip"></i>
                    Agregar Recurso
                </button>
                <button class="nav-item" onclick="window.location.href='admin.php'">
                    <i class="fas fa-robot"></i>
                    Agregar Kit
                </button>
                <button class="nav-item" onclick="window.location.href='gestion_solicitudes.php'">
                    <i class="fas fa-clipboard-list"></i>
                    Gestionar Solicitudes
                </button>
                <button class="nav-item active">
                    <i class="fas fa-users"></i>
                    Gestionar Usuarios
                </button>
            </nav>
        </header>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Mensajes -->
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Sección Principal -->
            <div class="section active">
                <div class="welcome-section">
                    <h1>Gestión de Usuarios</h1>
                    <p>Administra los usuarios del sistema GesTIC</p>
                </div>

                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_usuarios; ?></div>
                        <div class="stat-label">Total Usuarios</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_activos; ?></div>
                        <div class="stat-label">Usuarios Activos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $contador_tipos['profesor']; ?></div>
                        <div class="stat-label">Profesores</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $contador_tipos['alumno']; ?></div>
                        <div class="stat-label">Alumnos</div>
                    </div>
                </div>

                <!-- Filtros de Búsqueda -->
                <div class="form-container">
                    <h2 class="form-title">
                        <i class="fas fa-search"></i>
                        Buscar Usuarios
                    </h2>
                    
                    <form method="GET" action="">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Buscar por nombre, apellido o email:</label>
                                <input type="text" name="busqueda" class="form-control" placeholder="Ej: Juan Pérez, juan@email.com" value="<?php echo htmlspecialchars($busqueda); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Filtrar por tipo:</label>
                                <select name="tipo" class="form-control">
                                    <option value="">Todos los tipos</option>
                                    <option value="profesor" <?php echo $filtro_tipo === 'profesor' ? 'selected' : ''; ?>>Profesor</option>
                                    <option value="alumno" <?php echo $filtro_tipo === 'alumno' ? 'selected' : ''; ?>>Alumno</option>
                                    <option value="directivo" <?php echo $filtro_tipo === 'directivo' ? 'selected' : ''; ?>>Directivo</option>
                                    <option value="administrativo" <?php echo $filtro_tipo === 'administrativo' ? 'selected' : ''; ?>>Administrativo</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button type="submit" class="btn">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='gestion_usuarios.php'">
                                <i class="fas fa-undo"></i> Limpiar Filtros
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lista de Usuarios -->
                <div class="form-container">
                    <h2 class="form-title">
                        <i class="fas fa-list"></i>
                        Lista de Usuarios (<?php echo $total_usuarios; ?>)
                    </h2>
                    
                    <?php if (empty($usuarios)): ?>
                        <div class="empty-state-usuarios">
                            <i class="fas fa-users-slash"></i>
                            <h3>No se encontraron usuarios</h3>
                            <p>Intenta con otros términos de búsqueda</p>
                        </div>
                    <?php else: ?>
                        <div class="tabla-container">
                            <table class="tabla-usuarios">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Tipo</th>
                                        <th>Información</th>
                                        <th>Estado</th>
                                        <th>Último Login</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td class="usuario-info">
                                            <div class="usuario-nombre">
                                                <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                                                <?php if ($usuario['id'] == $user_id): ?>
                                                    <span class="badge-tu-cuenta">Tú</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="usuario-email">
                                                <?php echo htmlspecialchars($usuario['email']); ?>
                                            </div>
                                            <div class="usuario-registro">
                                                Registro: <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <form method="POST" action="" class="form-tipo-usuario">
                                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                <input type="hidden" name="cambiar_tipo" value="1">
                                                <select name="nuevo_tipo" class="select-tipo" onchange="this.form.submit()" 
                                                        <?php echo $usuario['id'] == $user_id ? 'disabled' : ''; ?>>
                                                    <option value="profesor" <?php echo $usuario['tipo'] === 'profesor' ? 'selected' : ''; ?>>Profesor</option>
                                                    <option value="alumno" <?php echo $usuario['tipo'] === 'alumno' ? 'selected' : ''; ?>>Alumno</option>
                                                    <option value="directivo" <?php echo $usuario['tipo'] === 'directivo' ? 'selected' : ''; ?>>Directivo</option>
                                                    <option value="administrativo" <?php echo $usuario['tipo'] === 'administrativo' ? 'selected' : ''; ?>>Administrativo</option>
                                                </select>
                                            </form>
                                        </td>
                                        
                                        <td>
                                            <?php if ($usuario['tipo'] === 'alumno' && !empty($usuario['grado_seccion'])): ?>
                                                <div class="info-adicional">
                                                    <strong>Grado/Sección:</strong> <?php echo htmlspecialchars($usuario['grado_seccion']); ?>
                                                </div>
                                            <?php elseif ($usuario['tipo'] === 'profesor' && !empty($usuario['materia'])): ?>
                                                <div class="info-adicional">
                                                    <strong>Materia:</strong> <?php echo htmlspecialchars($usuario['materia']); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="info-vacia">
                                                    Sin información adicional
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td>
                                            <?php if ($usuario['activo']): ?>
                                                <span class="badge-estado badge-activo">
                                                    <i class="fas fa-check-circle"></i> Activo
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-estado badge-inactivo">
                                                    <i class="fas fa-times-circle"></i> Inactivo
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td>
                                            <?php if ($usuario['ultimo_login']): ?>
                                                <div class="ultimo-login">
                                                    <?php echo date('d/m/Y', strtotime($usuario['ultimo_login'])); ?>
                                                </div>
                                                <div class="ultimo-login-fecha">
                                                    <?php echo date('H:i', strtotime($usuario['ultimo_login'])); ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="nunca-login">
                                                    Nunca ha ingresado
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td>
                                            <div class="acciones-usuario">
                                                <?php if ($usuario['id'] != $user_id): ?>
                                                    <?php if ($usuario['activo']): ?>
                                                        <form method="POST" action="">
                                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                            <input type="hidden" name="accion" value="desactivar">
                                                            <button type="submit" name="accion_usuario" value="1" class="btn-accion btn-desactivar">
                                                                <i class="fas fa-user-slash"></i> Desactivar
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" action="">
                                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                            <input type="hidden" name="accion" value="activar">
                                                            <button type="submit" name="accion_usuario" value="1" class="btn-accion btn-activar">
                                                                <i class="fas fa-user-check"></i> Activar
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="btn-accion" disabled style="background: #f3f4f6; color: #6b7280;">
                                                        <i class="fas fa-info-circle"></i> Tú
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Confirmación para acciones críticas
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = e.submitter;
                    if (submitBtn && submitBtn.classList.contains('btn-desactivar')) {
                        const confirmacion = confirm('¿Estás seguro de que quieres desactivar este usuario?');
                        if (!confirmacion) {
                            e.preventDefault();
                        }
                    } else if (submitBtn && submitBtn.classList.contains('btn-activar')) {
                        const confirmacion = confirm('¿Estás seguro de que quieres activar este usuario?');
                        if (!confirmacion) {
                            e.preventDefault();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>