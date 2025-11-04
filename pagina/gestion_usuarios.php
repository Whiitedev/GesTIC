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

// Cambiar tipo de usuario
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo-section">
                <div class="logo">
                    <i class="fas fa-laptop-code"></i>
                    GesTIC
                </div>
            </div>
            
            <div class="nav-section">
                <button class="nav-item" onclick="window.location.href='dashboard.php'">
                    <i class="fas fa-home"></i>
                    Volver al Dashboard
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
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="admin-header">
                <h1 class="admin-title">
                    <i class="fas fa-users"></i>
                    Gestión de Usuarios
                </h1>
                <p class="admin-subtitle">Administra los usuarios del sistema GesTIC</p>
            </div>

            <!-- Mensajes -->
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas -->
            <div class="stats-grid-admin">
                <div class="stat-card-admin">
                    <div class="stat-label-admin">Total Usuarios</div>
                    <div class="stat-number-admin"><?php echo $total_usuarios; ?></div>
                </div>
                <div class="stat-card-admin">
                    <div class="stat-label-admin">Usuarios Activos</div>
                    <div class="stat-number-admin"><?php echo $total_activos; ?></div>
                    
                </div>
                <div class="stat-card-admin">
                    <div class="stat-label-admin">Profesores</div>
                    <div class="stat-number-admin"><?php echo $contador_tipos['profesor']; ?></div>
                    
                </div>
                <div class="stat-card-admin">
                    <div class="stat-label-admin">Alumnos</div>
                    <div class="stat-number-admin"><?php echo $contador_tipos['alumno']; ?></div>
                </div>
            </div>

            <!-- Filtros de Búsqueda -->
            <div class="admin-card">
                <h3 class="card-title">
                    <i class="fas fa-search"></i>
                    Buscar Usuarios
                </h3>
                
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
            <div class="admin-card">
                <h3 class="card-title">
                    <i class="fas fa-list"></i>
                    Lista de Usuarios (<?php echo $total_usuarios; ?>)
                </h3>
                
                <?php if (empty($usuarios)): ?>
                    <div style="text-align: center; padding: 40px; color: #64748b;">
                        <i class="fas fa-users-slash" style="font-size: 3em; color: #94a3b8; margin-bottom: 15px;"></i>
                        <h3>No se encontraron usuarios</h3>
                        <p>Intenta con otros términos de búsqueda</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Usuario</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Tipo</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Información</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Estado</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Último Login</th>
                                    <th style="padding: 12px; text-align: left; font-weight: 600; color: #374151;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr style="border-bottom: 1px solid #e5e7eb; transition: background 0.3s ease;">
                                    <td style="padding: 12px;">
                                        <div style="font-weight: 600; color: #1e293b;">
                                            <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                                        </div>
                                        <div style="color: #64748b; font-size: 12px;">
                                            <?php echo htmlspecialchars($usuario['email']); ?>
                                        </div>
                                        <div style="color: #94a3b8; font-size: 11px;">
                                            Registro: <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?>
                                        </div>
                                    </td>
                                    
                                    <td style="padding: 12px;">
                                        <form method="POST" action="" style="display: flex; gap: 5px; align-items: center;">
                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                            <select name="nuevo_tipo" class="form-control" style="padding: 6px; font-size: 12px; min-width: 120px;" 
                                                    onchange="this.form.submit()" <?php echo $usuario['id'] == $user_id ? 'disabled' : ''; ?>>
                                                <option value="profesor" <?php echo $usuario['tipo'] === 'profesor' ? 'selected' : ''; ?>>Profesor</option>
                                                <option value="alumno" <?php echo $usuario['tipo'] === 'alumno' ? 'selected' : ''; ?>>Alumno</option>
                                                <option value="directivo" <?php echo $usuario['tipo'] === 'directivo' ? 'selected' : ''; ?>>Directivo</option>
                                                <option value="administrativo" <?php echo $usuario['tipo'] === 'administrativo' ? 'selected' : ''; ?>>Administrativo</option>
                                            </select>
                                            <button type="submit" name="cambiar_tipo" style="display: none;">Cambiar</button>
                                        </form>
                                    </td>
                                    
                                    <td style="padding: 12px;">
                                        <?php if ($usuario['tipo'] === 'alumno' && !empty($usuario['grado_seccion'])): ?>
                                            <div style="color: #475569; font-size: 12px;">
                                                <strong>Grado/Sección:</strong> <?php echo htmlspecialchars($usuario['grado_seccion']); ?>
                                            </div>
                                        <?php elseif ($usuario['tipo'] === 'profesor' && !empty($usuario['materia'])): ?>
                                            <div style="color: #475569; font-size: 12px;">
                                                <strong>Materia:</strong> <?php echo htmlspecialchars($usuario['materia']); ?>
                                            </div>
                                        <?php else: ?>
                                            <div style="color: #94a3b8; font-size: 12px;">
                                                Sin información adicional
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td style="padding: 12px;">
                                        <?php if ($usuario['activo']): ?>
                                            <span style="background: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                                <i class="fas fa-check-circle"></i> Activo
                                            </span>
                                        <?php else: ?>
                                            <span style="background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                                <i class="fas fa-times-circle"></i> Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td style="padding: 12px;">
                                        <?php if ($usuario['ultimo_login']): ?>
                                            <div style="color: #475569; font-size: 12px;">
                                                <?php echo date('d/m/Y', strtotime($usuario['ultimo_login'])); ?>
                                            </div>
                                            <div style="color: #64748b; font-size: 11px;">
                                                <?php echo date('H:i', strtotime($usuario['ultimo_login'])); ?>
                                            </div>
                                        <?php else: ?>
                                            <div style="color: #94a3b8; font-size: 12px;">
                                                Nunca ha ingresado
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td style="padding: 12px;">
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <?php if ($usuario['id'] != $user_id): ?>
                                                <?php if ($usuario['activo']): ?>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                        <button type="submit" name="accion_usuario" value="desactivar" class="btn" style="background: #f59e0b; padding: 6px 10px; font-size: 11px;">
                                                            <i class="fas fa-user-slash"></i> Desactivar
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                        <button type="submit" name="accion_usuario" value="activar" class="btn" style="background: #10b981; padding: 6px 10px; font-size: 11px;">
                                                            <i class="fas fa-user-check"></i> Activar
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color: #64748b; font-size: 11px; padding: 6px 10px;">
                                                    <i class="fas fa-info-circle"></i> Tú
                                                </span>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-secondary" style="padding: 6px 10px; font-size: 11px;" 
                                                    onclick="verDetallesUsuario(<?php echo $usuario['id']; ?>)">
                                                <i class="fas fa-eye"></i> Ver
                                            </button>
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

    <script>
        // Función para ver detalles del usuario (puedes expandir esto)
        function verDetallesUsuario(usuarioId) {
            alert('Detalles del usuario ID: ' + usuarioId + '\n\nEn una implementación completa, aquí se mostraría información detallada del usuario, historial de solicitudes, etc.');
        }

        // Confirmación para acciones críticas
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = e.submitter;
                    if (submitBtn && (submitBtn.value === 'desactivar' || submitBtn.value === 'activar')) {
                        const accion = submitBtn.value === 'desactivar' ? 'desactivar' : 'activar';
                        const confirmacion = confirm(`¿Estás seguro de que quieres ${accion} este usuario?`);
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