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

// Procesar acciones sobre solicitudes
$message = '';
$message_type = '';

// Aprobar/rechazar solicitud
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_solicitud'])) {
    $tipo_solicitud = $_POST['tipo_solicitud'];
    $solicitud_id = intval($_POST['solicitud_id']);
    $accion = $_POST['accion'];
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    $tablas = [
        'notebooks' => 'solicitudes_notebooks',
        'recursos' => 'solicitudes_recursos', 
        'robotica' => 'solicitudes_robotica'
    ];
    
    if (isset($tablas[$tipo_solicitud])) {
        $tabla = $tablas[$tipo_solicitud];
        $estado = $accion === 'aprobar' ? 'aprobada' : 'rechazada';
        
        $stmt = $conn->prepare("UPDATE $tabla SET estado = ?, observaciones_admin = ?, fecha_aprobacion = NOW(), administrador_id = ? WHERE id = ?");
        $stmt->bind_param("ssii", $estado, $observaciones, $user_id, $solicitud_id);
        
        if ($stmt->execute()) {
            $message = "✅ Solicitud " . $estado . " correctamente";
            $message_type = 'success';
            
            // Si se aprueba, actualizar stock disponible
            if ($accion === 'aprobar' && $tipo_solicitud === 'recursos') {
                actualizarStockRecurso($conn, $solicitud_id);
            } elseif ($accion === 'aprobar' && $tipo_solicitud === 'robotica') {
                actualizarStockKit($conn, $solicitud_id);
            }
        } else {
            $message = "❌ Error al procesar la solicitud: " . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

function actualizarStockRecurso($conn, $solicitud_id) {
    $stmt = $conn->prepare("SELECT recurso_id, cantidad_solicitada FROM solicitudes_recursos WHERE id = ?");
    $stmt->bind_param("i", $solicitud_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $solicitud = $result->fetch_assoc();
    $stmt->close();
    
    if ($solicitud) {
        $stmt = $conn->prepare("UPDATE recursos_tic SET stock_disponible = stock_disponible - ? WHERE id = ? AND stock_disponible >= ?");
        $stmt->bind_param("iii", $solicitud['cantidad_solicitada'], $solicitud['recurso_id'], $solicitud['cantidad_solicitada']);
        $stmt->execute();
        $stmt->close();
    }
}

function actualizarStockKit($conn, $solicitud_id) {
    $stmt = $conn->prepare("SELECT kit_id, cantidad_solicitada FROM solicitudes_robotica WHERE id = ?");
    $stmt->bind_param("i", $solicitud_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $solicitud = $result->fetch_assoc();
    $stmt->close();
    
    if ($solicitud) {
        $stmt = $conn->prepare("UPDATE kits_robotica SET stock_disponible = stock_disponible - ? WHERE id = ? AND stock_disponible >= ?");
        $stmt->bind_param("iii", $solicitud['cantidad_solicitada'], $solicitud['kit_id'], $solicitud['cantidad_solicitada']);
        $stmt->execute();
        $stmt->close();
    }
}

// Obtener solicitudes pendientes
$solicitudes_notebooks = [];
$solicitudes_recursos = [];
$solicitudes_robotica = [];

// Solicitudes de notebooks
$stmt = $conn->prepare("
    SELECT sn.*, u.nombre, u.apellido, u.email, u.tipo as usuario_tipo 
    FROM solicitudes_notebooks sn 
    JOIN usuarios u ON sn.usuario_id = u.id 
    WHERE sn.estado = 'pendiente'
    ORDER BY sn.fecha_solicitud DESC
");
$stmt->execute();
$solicitudes_notebooks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Solicitudes de recursos
$stmt = $conn->prepare("
    SELECT sr.*, u.nombre, u.apellido, u.email, r.nombre as recurso_nombre, r.stock_disponible
    FROM solicitudes_recursos sr 
    JOIN usuarios u ON sr.usuario_id = u.id 
    JOIN recursos_tic r ON sr.recurso_id = r.id 
    WHERE sr.estado = 'pendiente'
    ORDER BY sr.fecha_solicitud DESC
");
$stmt->execute();
$solicitudes_recursos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Solicitudes de robótica
$stmt = $conn->prepare("
    SELECT srob.*, u.nombre, u.apellido, u.email, kr.nombre as kit_nombre, kr.stock_disponible
    FROM solicitudes_robotica srob 
    JOIN usuarios u ON srob.usuario_id = u.id 
    JOIN kits_robotica kr ON srob.kit_id = kr.id 
    WHERE srob.estado = 'pendiente'
    ORDER BY srob.fecha_solicitud DESC
");
$stmt->execute();
$solicitudes_robotica = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Solicitudes - GesTIC</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/gestion_solicitudes.css">
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
                <button class="nav-item active">
                    <i class="fas fa-clipboard-list"></i>
                    Gestionar Solicitudes
                </button>
                <button class="nav-item" onclick="window.location.href='gestion_usuarios.php'">
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
                    <h1>Gestión de Solicitudes</h1>
                    <p>Revisa y gestiona todas las solicitudes pendientes del sistema</p>
                </div>

                <!-- Estadísticas Rápidas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($solicitudes_notebooks); ?></div>
                        <div class="stat-label">Solicitudes de Notebooks</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($solicitudes_recursos); ?></div>
                        <div class="stat-label">Solicitudes de Recursos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($solicitudes_robotica); ?></div>
                        <div class="stat-label">Solicitudes de Robótica</div>
                    </div>
                </div>

                <!-- Pestañas de Solicitudes -->
                <div class="form-container">
                    <div class="tabs-header">
                        <button class="tab-button active" onclick="showTab('notebooks')">
                            <i class="fas fa-laptop"></i> 
                            Notebooks 
                            <span class="tab-counter"><?php echo count($solicitudes_notebooks); ?></span>
                        </button>
                        <button class="tab-button" onclick="showTab('recursos')">
                            <i class="fas fa-microchip"></i> 
                            Recursos TIC 
                            <span class="tab-counter"><?php echo count($solicitudes_recursos); ?></span>
                        </button>
                        <button class="tab-button" onclick="showTab('robotica')">
                            <i class="fas fa-robot"></i> 
                            Robótica 
                            <span class="tab-counter"><?php echo count($solicitudes_robotica); ?></span>
                        </button>
                    </div>

                    <!-- Tab: Solicitudes de Notebooks -->
                    <div id="tab-notebooks" class="tab-content active">
                        <?php if (empty($solicitudes_notebooks)): ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <h3>No hay solicitudes pendientes de notebooks</h3>
                                <p>Todas las solicitudes de notebooks han sido procesadas</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($solicitudes_notebooks as $solicitud): ?>
                            <div class="solicitud-item">
                                <div class="solicitud-header">
                                    <div class="solicitud-info">
                                        <h4>
                                            <i class="fas fa-laptop"></i> 
                                            Solicitud de <?php echo $solicitud['cantidad_solicitada']; ?> notebooks
                                        </h4>
                                        <p>
                                            <strong>Solicitante:</strong> <?php echo $solicitud['nombre'] . ' ' . $solicitud['apellido']; ?> 
                                            (<?php echo $solicitud['usuario_tipo']; ?>)<br>
                                            <strong>Fecha de uso:</strong> <?php echo $solicitud['fecha_uso']; ?> 
                                            de <?php echo $solicitud['hora_inicio']; ?> a <?php echo $solicitud['hora_fin']; ?><br>
                                            <strong>Aula:</strong> <?php echo $solicitud['aula_solicitante']; ?> 
                                            | <strong>Profesor:</strong> <?php echo $solicitud['profesor_encargado']; ?>
                                        </p>
                                        <?php if (!empty($solicitud['proposito'])): ?>
                                            <p class="proposito">
                                                <strong>Propósito:</strong> <?php echo $solicitud['proposito']; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="solicitud-meta">
                                        <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?>
                                    </div>
                                </div>
                                
                                <form method="POST" action="" class="solicitud-actions">
                                    <input type="hidden" name="tipo_solicitud" value="notebooks">
                                    <input type="hidden" name="solicitud_id" value="<?php echo $solicitud['id']; ?>">
                                    
                                    <input type="text" name="observaciones" placeholder="Observaciones (opcional)" 
                                           class="solicitud-observaciones">
                                    
                                    <button type="submit" name="accion_solicitud" value="aprobar" class="btn" style="background: #10b981;">
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                    
                                    <button type="submit" name="accion_solicitud" value="rechazar" class="btn" style="background: #ef4444;">
                                        <i class="fas fa-times"></i> Rechazar
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Tab: Solicitudes de Recursos TIC -->
                    <div id="tab-recursos" class="tab-content">
                        <?php if (empty($solicitudes_recursos)): ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <h3>No hay solicitudes pendientes de recursos TIC</h3>
                                <p>Todas las solicitudes de recursos TIC han sido procesadas</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($solicitudes_recursos as $solicitud): ?>
                            <div class="solicitud-item recursos">
                                <div class="solicitud-header">
                                    <div class="solicitud-info">
                                        <h4>
                                            <i class="fas fa-microchip"></i> 
                                            <?php echo $solicitud['recurso_nombre']; ?>
                                        </h4>
                                        <p>
                                            <strong>Solicitante:</strong> <?php echo $solicitud['nombre'] . ' ' . $solicitud['apellido']; ?><br>
                                            <strong>Cantidad:</strong> <?php echo $solicitud['cantidad_solicitada']; ?> 
                                            | <strong>Stock disponible:</strong> <?php echo $solicitud['stock_disponible']; ?><br>
                                            <strong>Fecha de uso:</strong> <?php echo $solicitud['fecha_uso']; ?>
                                        </p>
                                        <?php if (!empty($solicitud['proposito'])): ?>
                                            <p class="proposito">
                                                <strong>Propósito:</strong> <?php echo $solicitud['proposito']; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="solicitud-meta">
                                        <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?>
                                    </div>
                                </div>
                                
                                <form method="POST" action="" class="solicitud-actions">
                                    <input type="hidden" name="tipo_solicitud" value="recursos">
                                    <input type="hidden" name="solicitud_id" value="<?php echo $solicitud['id']; ?>">
                                    
                                    <input type="text" name="observaciones" placeholder="Observaciones (opcional)" 
                                           class="solicitud-observaciones">
                                    
                                    <button type="submit" name="accion_solicitud" value="aprobar" class="btn" 
                                            style="background: #10b981;" 
                                            <?php echo ($solicitud['cantidad_solicitada'] > $solicitud['stock_disponible']) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                    
                                    <button type="submit" name="accion_solicitud" value="rechazar" class="btn" 
                                            style="background: #ef4444;">
                                        <i class="fas fa-times"></i> Rechazar
                                    </button>
                                </form>
                                
                                <?php if ($solicitud['cantidad_solicitada'] > $solicitud['stock_disponible']): ?>
                                    <div class="stock-insuficiente">
                                        <i class="fas fa-exclamation-triangle"></i> Stock insuficiente
                                    </div>
                                <?php else: ?>
                                    <div class="stock-ok">
                                        <i class="fas fa-check-circle"></i> Stock disponible
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Tab: Solicitudes de Robótica -->
                    <div id="tab-robotica" class="tab-content">
                        <?php if (empty($solicitudes_robotica)): ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <h3>No hay solicitudes pendientes de kits de robótica</h3>
                                <p>Todas las solicitudes de kits de robótica han sido procesadas</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($solicitudes_robotica as $solicitud): ?>
                            <div class="solicitud-item robotica">
                                <div class="solicitud-header">
                                    <div class="solicitud-info">
                                        <h4>
                                            <i class="fas fa-robot"></i> 
                                            <?php echo $solicitud['kit_nombre']; ?>
                                        </h4>
                                        <p>
                                            <strong>Solicitante:</strong> <?php echo $solicitud['nombre'] . ' ' . $solicitud['apellido']; ?><br>
                                            <strong>Cantidad:</strong> <?php echo $solicitud['cantidad_solicitada']; ?> 
                                            | <strong>Stock disponible:</strong> <?php echo $solicitud['stock_disponible']; ?><br>
                                            <strong>Fecha de uso:</strong> <?php echo $solicitud['fecha_uso']; ?>
                                        </p>
                                        <?php if (!empty($solicitud['proposito'])): ?>
                                            <p class="proposito">
                                                <strong>Propósito:</strong> <?php echo $solicitud['proposito']; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="solicitud-meta">
                                        <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?>
                                    </div>
                                </div>
                                
                                <form method="POST" action="" class="solicitud-actions">
                                    <input type="hidden" name="tipo_solicitud" value="robotica">
                                    <input type="hidden" name="solicitud_id" value="<?php echo $solicitud['id']; ?>">
                                    
                                    <input type="text" name="observaciones" placeholder="Observaciones (opcional)" 
                                           class="solicitud-observaciones">
                                    
                                    <button type="submit" name="accion_solicitud" value="aprobar" class="btn" 
                                            style="background: #10b981;"
                                            <?php echo ($solicitud['cantidad_solicitada'] > $solicitud['stock_disponible']) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                    
                                    <button type="submit" name="accion_solicitud" value="rechazar" class="btn" 
                                            style="background: #ef4444;">
                                        <i class="fas fa-times"></i> Rechazar
                                    </button>
                                </form>
                                
                                <?php if ($solicitud['cantidad_solicitada'] > $solicitud['stock_disponible']): ?>
                                    <div class="stock-insuficiente">
                                        <i class="fas fa-exclamation-triangle"></i> Stock insuficiente
                                    </div>
                                <?php else: ?>
                                    <div class="stock-ok">
                                        <i class="fas fa-check-circle"></i> Stock disponible
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Funcionalidad de pestañas
        function showTab(tabName) {
            // Ocultar todas las pestañas
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Mostrar la pestaña seleccionada
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Actualizar botones activos
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            event.target.classList.add('active');
        }
    </script>
</body>
</html>