<?php
session_start();
require '../sql/conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_nombre = $_SESSION['user_nombre'];
$user_tipo = $_SESSION['user_tipo'];

// Procesar formularios
$message = '';
$message_type = '';

// Procesar solicitud de notebooks
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['solicitar_notebooks'])) {
    $cantidad = intval($_POST['cantidad_notebooks']);
    $aula = trim($_POST['aula']);
    $profesor = trim($_POST['profesor_encargado']);
    $fecha_uso = $_POST['fecha_uso'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];
    $proposito = trim($_POST['proposito']);
    
    if ($cantidad > 0 && $cantidad <= 20 && !empty($aula) && !empty($profesor)) {
        $stmt = $conn->prepare("INSERT INTO solicitudes_notebooks (usuario_id, cantidad_solicitada, aula_solicitante, profesor_encargado, fecha_uso, hora_inicio, hora_fin, proposito) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssss", $user_id, $cantidad, $aula, $profesor, $fecha_uso, $hora_inicio, $hora_fin, $proposito);
        
        if ($stmt->execute()) {
            $message = "✅ Solicitud de notebooks enviada correctamente. Será revisada por un administrador.";
            $message_type = 'success';
        } else {
            $message = "❌ Error al enviar la solicitud: " . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    } else {
        $message = "❌ Por favor completa todos los campos correctamente. La cantidad máxima es 20 notebooks.";
        $message_type = 'error';
    }
}

// Procesar solicitud de recursos TIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['solicitar_recurso'])) {
    $recurso_id = intval($_POST['recurso_id']);
    $cantidad = intval($_POST['cantidad_recurso']);
    $fecha_uso = $_POST['fecha_uso_recurso'];
    $proposito = trim($_POST['proposito_recurso']);
    
    if ($recurso_id > 0 && $cantidad > 0 && !empty($fecha_uso)) {
        // Verificar stock disponible
        $stmt = $conn->prepare("SELECT stock_disponible, nombre FROM recursos_tic WHERE id = ?");
        $stmt->bind_param("i", $recurso_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $recurso = $result->fetch_assoc();
        $stmt->close();
        
        if ($recurso && $cantidad <= $recurso['stock_disponible']) {
            $stmt = $conn->prepare("INSERT INTO solicitudes_recursos (usuario_id, recurso_id, cantidad_solicitada, fecha_uso, proposito) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $user_id, $recurso_id, $cantidad, $fecha_uso, $proposito);
            
            if ($stmt->execute()) {
                $message = "✅ Solicitud de {$recurso['nombre']} enviada correctamente.";
                $message_type = 'success';
            } else {
                $message = "❌ Error al enviar la solicitud: " . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = "❌ Stock insuficiente o recurso no disponible.";
            $message_type = 'error';
        }
    } else {
        $message = "❌ Por favor completa todos los campos correctamente.";
        $message_type = 'error';
    }
}

// Procesar solicitud de robótica
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['solicitar_robotica'])) {
    $kit_id = intval($_POST['kit_id']);
    $cantidad = intval($_POST['cantidad_kit']);
    $fecha_uso = $_POST['fecha_uso_kit'];
    $proposito = trim($_POST['proposito_kit']);
    
    if ($kit_id > 0 && $cantidad > 0 && !empty($fecha_uso)) {
        // Verificar stock disponible
        $stmt = $conn->prepare("SELECT stock_disponible, nombre FROM kits_robotica WHERE id = ?");
        $stmt->bind_param("i", $kit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $kit = $result->fetch_assoc();
        $stmt->close();
        
        if ($kit && $cantidad <= $kit['stock_disponible']) {
            $stmt = $conn->prepare("INSERT INTO solicitudes_robotica (usuario_id, kit_id, cantidad_solicitada, fecha_uso, proposito) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $user_id, $kit_id, $cantidad, $fecha_uso, $proposito);
            
            if ($stmt->execute()) {
                $message = "✅ Solicitud de {$kit['nombre']} enviada correctamente.";
                $message_type = 'success';
            } else {
                $message = "❌ Error al enviar la solicitud: " . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = "❌ Stock insuficiente o kit no disponible.";
            $message_type = 'error';
        }
    } else {
        $message = "❌ Por favor completa todos los campos correctamente.";
        $message_type = 'error';
    }
}

// Obtener estadísticas
$solicitudes_pendientes = 0;
$solicitudes_aprobadas = 0;

$queries = [
    "SELECT COUNT(*) as count FROM solicitudes_notebooks WHERE usuario_id = ? AND estado = 'pendiente'",
    "SELECT COUNT(*) as count FROM solicitudes_recursos WHERE usuario_id = ? AND estado = 'pendiente'",
    "SELECT COUNT(*) as count FROM solicitudes_robotica WHERE usuario_id = ? AND estado = 'pendiente'"
];

foreach ($queries as $query) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $solicitudes_pendientes += $result->fetch_assoc()['count'];
    $stmt->close();
}

$queries_aprobadas = [
    "SELECT COUNT(*) as count FROM solicitudes_notebooks WHERE usuario_id = ? AND estado = 'aprobada'",
    "SELECT COUNT(*) as count FROM solicitudes_recursos WHERE usuario_id = ? AND estado = 'aprobada'",
    "SELECT COUNT(*) as count FROM solicitudes_robotica WHERE usuario_id = ? AND estado = 'aprobada'"
];

foreach ($queries_aprobadas as $query) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $solicitudes_aprobadas += $result->fetch_assoc()['count'];
    $stmt->close();
}

// Obtener recursos disponibles
$recursos = [];
$stmt = $conn->prepare("SELECT r.*, c.nombre as categoria_nombre FROM recursos_tic r LEFT JOIN categorias_recursos c ON r.categoria_id = c.id WHERE r.stock_disponible > 0 AND r.estado = 'Disponible'");
$stmt->execute();
$recursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtener kits de robótica disponibles
$kits_robotica = [];
$stmt = $conn->prepare("SELECT * FROM kits_robotica WHERE stock_disponible > 0 AND estado = 'Disponible'");
$stmt->execute();
$kits_robotica = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GesTIC</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo-section">
                <div class="logo">
                    <img src="../img/logo-sin-fondo.png" alt="GesTIC-logo">
                </div>
            </div>
            
            <div class="nav-section">
                <button class="nav-item active" onclick="showSection('inicio')">
                    <i class="fas fa-home"></i>
                    Inicio
                </button>
                <button class="nav-item" onclick="showSection('notebooks')">
                    <i class="fas fa-laptop"></i>
                    Solicitar Notebooks
                </button>
                <button class="nav-item" onclick="showSection('recursos')">
                    <i class="fas fa-microchip"></i>
                    Recursos TIC
                </button>
                <button class="nav-item" onclick="showSection('robotica')">
                    <i class="fas fa-robot"></i>
                    Kits de Robótica
                </button>
                <button class="nav-item" onclick="showSection('estado')">
                    <i class="fas fa-clipboard-list"></i>
                    Mis Solicitudes
                </button>
                <?php if ($user_tipo === 'administrativo' || $user_tipo === 'directivo'): ?>
                <div id="gestion-usuarios" class="section">
                    <div class="form-container">
                        <h2 class="form-title">
                            <i class="fas fa-users-cog"></i>
                            Gestión de Usuarios
                        </h2>
        
                        <?php
                        // Obtener todos los usuarios
                        $usuarios = [];
                        $stmt = $conn->prepare("SELECT id, nombre, apellido, email, tipo, grado_seccion, materia, activo, fecha_registro FROM usuarios ORDER BY fecha_registro DESC");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $usuarios = $result->fetch_all(MYSQLI_ASSOC);
                        $stmt->close();
        
                        // Procesar eliminación de usuario
                        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar_usuario'])) {
                            $usuario_a_eliminar = intval($_POST['usuario_id']);
                            
                            // No permitir eliminarse a sí mismo
                            if ($usuario_a_eliminar != $user_id) {
                                $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                                $stmt->bind_param("i", $usuario_a_eliminar);
                                
                                if ($stmt->execute()) {
                                    $message = "✅ Usuario eliminado correctamente.";
                                    $message_type = 'success';
                                    // Recargar la lista
                                    header("Location: dashboard.php?section=gestion-usuarios");
                                    exit();
                                } else {
                                    $message = "❌ Error al eliminar el usuario: " . $conn->error;
                                    $message_type = 'error';
                                }
                                $stmt->close();
                            } else {
                                $message = "❌ No puedes eliminarte a ti mismo.";
                                $message_type = 'error';
                            }
                        }
                        ?>
                        
                        <div class="users-table-container">
                            <div class="table-header">
                                <h3>Usuarios Registrados (<?php echo count($usuarios); ?>)</h3>
                                <div class="table-actions">
                                    <input type="text" id="searchUsers" placeholder="Buscar usuarios..." class="search-input">
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="users-table">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Email</th>
                                            <th>Tipo</th>
                                            <th>Grado/Sección</th>
                                            <th>Materia</th>
                                            <th>Fecha Registro</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                            <td>
                                                <span class="user-type-badge <?php echo $usuario['tipo']; ?>">
                                                    <?php echo ucfirst($usuario['tipo']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($usuario['grado_seccion'] ?: 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['materia'] ?: 'N/A'); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $usuario['activo'] ? 'active' : 'inactive'; ?>">
                                                    <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($usuario['id'] != $user_id): ?>
                                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este usuario? Esta acción no se puede deshacer.');">
                                                    <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                    <button type="submit" name="eliminar_usuario" class="btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <span class="text-muted">Tú</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <a href="../auth/logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    Cerrar Sesión
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="welcome-section">
                    <h1>Bienvenido, <?php echo htmlspecialchars($user_nombre); ?></h1>
                    <p>Sistema de Gestión de Recursos TIC - <?php echo ucfirst($user_tipo); ?></p>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Sección Inicio -->
            <div id="inicio" class="section active">
                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $solicitudes_pendientes; ?></div>
                        <div class="stat-label">Solicitudes Pendientes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $solicitudes_aprobadas; ?></div>
                        <div class="stat-label">Solicitudes Aprobadas</div>
                    </div>
                </div>

                <!-- Tarjetas de Acceso Rápido -->
                <div class="cards-grid">
                    <div class="card" onclick="showSection('notebooks')">
                        <div class="card-icon">
                            <i class="fas fa-laptop"></i>
                        </div>
                        <h3>Solicitar Notebooks</h3>
                        <p>Solicita notebooks para tus clases (máximo 20 unidades por solicitud)</p>
                    </div>

                    <div class="card" onclick="showSection('recursos')">
                        <div class="card-icon">
                            <i class="fas fa-microchip"></i>
                        </div>
                        <h3>Recursos TIC</h3>
                        <p>Solicita Arduino, Raspberry Pi, sensores y componentes electrónicos</p>
                    </div>

                    <div class="card" onclick="showSection('robotica')">
                        <div class="card-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <h3>Kits de Robótica</h3>
                        <p>Solicita kits completos para proyectos de robótica e IoT</p>
                    </div>
                </div>
            </div>

            <!-- Sección Solicitud de Notebooks -->
            <div id="notebooks" class="section">
                <div class="form-container">
                    <h2 class="form-title">
                        <i class="fas fa-laptop"></i>
                        Solicitud de Notebooks
                    </h2>
                    
                    <form method="POST" action="">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="cantidad_notebooks">Cantidad de Notebooks *</label>
                                <input type="number" id="cantidad_notebooks" name="cantidad_notebooks" class="form-control" min="1" max="20" required>
                                <small style="color: #666; font-size: 12px;">Máximo 20 notebooks por solicitud</small>
                            </div>

                            <div class="form-group">
                                <label for="aula">Aula/Laboratorio *</label>
                                <input type="text" id="aula" name="aula" class="form-control" placeholder="Ej: Laboratorio A, Aula 205" required>
                            </div>

                            <div class="form-group">
                                <label for="profesor_encargado">Profesor Encargado *</label>
                                <input type="text" id="profesor_encargado" name="profesor_encargado" class="form-control" placeholder="Nombre del profesor responsable" required>
                            </div>

                            <div class="form-group">
                                <label for="fecha_uso">Fecha de Uso *</label>
                                <input type="date" id="fecha_uso" name="fecha_uso" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="hora_inicio">Hora Inicio *</label>
                                <input type="time" id="hora_inicio" name="hora_inicio" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="hora_fin">Hora Fin *</label>
                                <input type="time" id="hora_fin" name="hora_fin" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="proposito">Propósito de la Solicitud</label>
                            <textarea id="proposito" name="proposito" class="form-control" rows="3" placeholder="Describe para qué utilizarás los notebooks..."></textarea>
                        </div>

                        <button type="submit" name="solicitar_notebooks" class="btn btn-block">
                            <i class="fas fa-paper-plane"></i>
                            Enviar Solicitud
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sección Recursos TIC -->
            <div id="recursos" class="section">
                <div class="form-container">
                    <h2 class="form-title">
                        <i class="fas fa-microchip"></i>
                        Solicitud de Recursos TIC
                    </h2>
                    
                    <form method="POST" action="" id="formRecursos">
                        <div class="form-group">
                            <label>Selecciona un Recurso *</label>
                            <div class="resource-list">
                                <?php foreach ($recursos as $recurso): ?>
                                    <div class="resource-item" onclick="selectRecurso(<?php echo $recurso['id']; ?>, '<?php echo htmlspecialchars($recurso['nombre']); ?>')" id="recurso-<?php echo $recurso['id']; ?>">
                                        <div class="resource-name"><?php echo htmlspecialchars($recurso['nombre']); ?></div>
                                        <div class="resource-details"><?php echo htmlspecialchars($recurso['categoria_nombre']); ?></div>
                                        <div class="resource-details"><?php echo htmlspecialchars($recurso['descripcion']); ?></div>
                                        <div class="resource-stock">Stock disponible: <?php echo $recurso['stock_disponible']; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="recurso_id" id="recurso_id" required>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="cantidad_recurso">Cantidad *</label>
                                <input type="number" id="cantidad_recurso" name="cantidad_recurso" class="form-control" min="1" required>
                            </div>

                            <div class="form-group">
                                <label for="fecha_uso_recurso">Fecha de Uso *</label>
                                <input type="date" id="fecha_uso_recurso" name="fecha_uso_recurso" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="proposito_recurso">Propósito del Uso</label>
                            <textarea id="proposito_recurso" name="proposito_recurso" class="form-control" rows="3" placeholder="Describe para qué utilizarás este recurso..."></textarea>
                        </div>

                        <button type="submit" name="solicitar_recurso" class="btn btn-block">
                            <i class="fas fa-paper-plane"></i>
                            Enviar Solicitud
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sección Kits de Robótica -->
            <div id="robotica" class="section">
                <div class="form-container">
                    <h2 class="form-title">
                        <i class="fas fa-robot"></i>
                        Solicitud de Kits de Robótica
                    </h2>
                    
                    <form method="POST" action="" id="formRobotica">
                        <div class="form-group">
                            <label>Selecciona un Kit *</label>
                            <div class="resource-list">
                                <?php foreach ($kits_robotica as $kit): ?>
                                    <div class="resource-item" onclick="selectKit(<?php echo $kit['id']; ?>, '<?php echo htmlspecialchars($kit['nombre']); ?>')" id="kit-<?php echo $kit['id']; ?>">
                                        <div class="resource-name"><?php echo htmlspecialchars($kit['nombre']); ?></div>
                                        <div class="resource-details"><?php echo htmlspecialchars($kit['descripcion']); ?></div>
                                        <div class="resource-details">Componentes: <?php echo htmlspecialchars($kit['componentes']); ?></div>
                                        <div class="resource-stock">Stock disponible: <?php echo $kit['stock_disponible']; ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="kit_id" id="kit_id" required>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="cantidad_kit">Cantidad *</label>
                                <input type="number" id="cantidad_kit" name="cantidad_kit" class="form-control" min="1" required>
                            </div>

                            <div class="form-group">
                                <label for="fecha_uso_kit">Fecha de Uso *</label>
                                <input type="date" id="fecha_uso_kit" name="fecha_uso_kit" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="proposito_kit">Propósito del Uso</label>
                            <textarea id="proposito_kit" name="proposito_kit" class="form-control" rows="3" placeholder="Describe para qué utilizarás este kit..."></textarea>
                        </div>

                        <button type="submit" name="solicitar_robotica" class="btn btn-block">
                            <i class="fas fa-paper-plane"></i>
                            Enviar Solicitud
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sección Estado de Solicitudes -->
            <div id="estado" class="section">
                <div class="form-container">
                    <h2 class="form-title">
                        <i class="fas fa-clipboard-list"></i>
                        Mis Solicitudes
                    </h2>
                    <p>Próximamente podrás ver el estado de todas tus solicitudes aquí.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Navegación entre secciones
        function showSection(sectionId) {
            // Ocultar todas las secciones
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Mostrar la sección seleccionada
            document.getElementById(sectionId).classList.add('active');
            
            // Actualizar navegación activa
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Selección de recursos TIC
        function selectRecurso(id, nombre) {
            document.querySelectorAll('.resource-item').forEach(item => {
                item.classList.remove('selected');
            });
            document.getElementById('recurso-' + id).classList.add('selected');
            document.getElementById('recurso_id').value = id;
            
            // Actualizar cantidad máxima
            const stock = parseInt(document.querySelector('#recurso-' + id + ' .resource-stock').textContent.match(/\d+/)[0]);
            document.getElementById('cantidad_recurso').max = stock;
        }

        // Selección de kits de robótica
        function selectKit(id, nombre) {
            document.querySelectorAll('.resource-item').forEach(item => {
                item.classList.remove('selected');
            });
            document.getElementById('kit-' + id).classList.add('selected');
            document.getElementById('kit_id').value = id;
            
            // Actualizar cantidad máxima
            const stock = parseInt(document.querySelector('#kit-' + id + ' .resource-stock').textContent.match(/\d+/)[0]);
            document.getElementById('cantidad_kit').max = stock;
        }

        // Validación de fechas
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('input[type="date"]').forEach(input => {
                input.min = today;
            });
        });
    </script>
</body>
</html>