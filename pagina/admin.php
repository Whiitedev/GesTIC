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

// Procesar formularios de administración
$message = '';
$message_type = '';

// Agregar nuevo recurso TIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_recurso'])) {
    $nombre = trim($_POST['nombre_recurso']);
    $descripcion = trim($_POST['descripcion_recurso']);
    $categoria_id = intval($_POST['categoria_recurso']);
    $stock = intval($_POST['stock_recurso']);
    $ubicacion = trim($_POST['ubicacion_recurso']);
    $codigo_barras = trim($_POST['codigo_barras_recurso']);
    
    if (!empty($nombre) && $categoria_id > 0 && $stock > 0) {
        // Verificar si el código de barras ya existe
        if (!empty($codigo_barras)) {
            $stmt = $conn->prepare("SELECT id FROM recursos_tic WHERE codigo_barras = ?");
            $stmt->bind_param("s", $codigo_barras);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $message = "❌ El código de barras ya está en uso";
                $message_type = 'error';
                $stmt->close();
            } else {
                $stmt->close();
                // Insertar recurso
                $stmt = $conn->prepare("INSERT INTO recursos_tic (nombre, descripcion, categoria_id, stock_total, stock_disponible, ubicacion, codigo_barras, agregado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiiissi", $nombre, $descripcion, $categoria_id, $stock, $stock, $ubicacion, $codigo_barras, $user_id);
                
                if ($stmt->execute()) {
                    $message = "✅ Recurso TIC agregado correctamente";
                    $message_type = 'success';
                } else {
                    $message = "❌ Error al agregar el recurso: " . $conn->error;
                    $message_type = 'error';
                }
                $stmt->close();
            }
        } else {
            // Insertar sin código de barras
            $stmt = $conn->prepare("INSERT INTO recursos_tic (nombre, descripcion, categoria_id, stock_total, stock_disponible, ubicacion, agregado_por) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiisi", $nombre, $descripcion, $categoria_id, $stock, $stock, $ubicacion, $user_id);
            
            if ($stmt->execute()) {
                $message = "✅ Recurso TIC agregado correctamente";
                $message_type = 'success';
            } else {
                $message = "❌ Error al agregar el recurso: " . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } else {
        $message = "❌ Por favor completa todos los campos obligatorios";
        $message_type = 'error';
    }
}

// Agregar nuevo kit de robótica
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_kit'])) {
    $nombre = trim($_POST['nombre_kit']);
    $descripcion = trim($_POST['descripcion_kit']);
    $componentes = trim($_POST['componentes_kit']);
    $stock = intval($_POST['stock_kit']);
    $ubicacion = trim($_POST['ubicacion_kit']);
    $codigo_barras = trim($_POST['codigo_barras_kit']);
    
    if (!empty($nombre) && $stock > 0) {
        // Verificar si el código de barras ya existe
        if (!empty($codigo_barras)) {
            $stmt = $conn->prepare("SELECT id FROM kits_robotica WHERE codigo_barras = ?");
            $stmt->bind_param("s", $codigo_barras);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $message = "❌ El código de barras ya está en uso";
                $message_type = 'error';
                $stmt->close();
            } else {
                $stmt->close();
                // Insertar kit
                $stmt = $conn->prepare("INSERT INTO kits_robotica (nombre, descripcion, componentes, stock_total, stock_disponible, ubicacion, codigo_barras, agregado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssiissi", $nombre, $descripcion, $componentes, $stock, $stock, $ubicacion, $codigo_barras, $user_id);
                
                if ($stmt->execute()) {
                    $message = "✅ Kit de robótica agregado correctamente";
                    $message_type = 'success';
                } else {
                    $message = "❌ Error al agregar el kit: " . $conn->error;
                    $message_type = 'error';
                }
                $stmt->close();
            }
        } else {
            // Insertar sin código de barras
            $stmt = $conn->prepare("INSERT INTO kits_robotica (nombre, descripcion, componentes, stock_total, stock_disponible, ubicacion, agregado_por) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiisi", $nombre, $descripcion, $componentes, $stock, $stock, $ubicacion, $user_id);
            
            if ($stmt->execute()) {
                $message = "✅ Kit de robótica agregado correctamente";
                $message_type = 'success';
            } else {
                $message = "❌ Error al agregar el kit: " . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } else {
        $message = "❌ Por favor completa todos los campos obligatorios";
        $message_type = 'error';
    }
}

// Obtener estadísticas para el panel de admin
$total_recursos = 0;
$total_kits = 0;
$solicitudes_pendientes = 0;

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM recursos_tic");
$stmt->execute();
$total_recursos = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM kits_robotica");
$stmt->execute();
$total_kits = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM solicitudes_notebooks WHERE estado = 'pendiente'");
$stmt->execute();
$solicitudes_pendientes += $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM solicitudes_recursos WHERE estado = 'pendiente'");
$stmt->execute();
$solicitudes_pendientes += $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM solicitudes_robotica WHERE estado = 'pendiente'");
$stmt->execute();
$solicitudes_pendientes += $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Obtener categorías para el formulario
$categorias = [];
$stmt = $conn->prepare("SELECT * FROM categorias_recursos WHERE activo = TRUE");
$stmt->execute();
$categorias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - GesTIC</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
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
                <button class="nav-item active">
                    <i class="fas fa-qrcode"></i>
                    Escanear Códigos
                </button>
                <button class="nav-item" onclick="showModal('modalRecursos')">
                    <i class="fas fa-microchip"></i>
                    Agregar Recurso
                </button>
                <button class="nav-item" onclick="showModal('modalKits')">
                    <i class="fas fa-robot"></i>
                    Agregar Kit
                </button>
                <button class="nav-item" onclick="window.location.href='gestion_solicitudes.php'">
                    <i class="fas fa-clipboard-list"></i>
                    Gestionar Solicitudes
                </button>
                <button class="nav-item" onclick="window.location.href='gestion_usuarios.php'">
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
                    <i class="fas fa-cogs"></i>
                    Panel de Administración
                </h1>
                <p class="admin-subtitle">Gestión de recursos TIC y sistema de código de barras</p>
            </div>

            <!-- Mensajes -->
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas Rápidas -->
            <div class="stats-grid-admin">
                <div class="stat-card-admin">
                    <div class="stat-number-admin"><?php echo $total_recursos; ?></div>
                    <div class="stat-label-admin">Recursos TIC</div>
                </div>
                <div class="stat-card-admin">
                    <div class="stat-number-admin"><?php echo $total_kits; ?></div>
                    <div class="stat-label-admin">Kits de Robótica</div>
                </div>
                <div class="stat-card-admin">
                    <div class="stat-number-admin"><?php echo $solicitudes_pendientes; ?></div>
                    <div class="stat-label-admin">Solicitudes Pendientes</div>
                </div>
            </div>

            <!-- Grid de Funcionalidades -->
            <div class="admin-grid">
                <!-- Escáner de Códigos de Barras -->
                <div class="admin-card">
                    <h3 class="card-title">
                        <i class="fas fa-camera"></i>
                        Escáner de Códigos
                    </h3>
                    
                    <div class="scanner-section">
                        <div class="scanner-placeholder" onclick="initScanner()">
                            <div class="scanner-icon">
                                <i class="fas fa-barcode"></i>
                            </div>
                            <div class="scanner-text">
                                Haz clic para activar la cámara
                            </div>
                            <small style="color: #94a3b8;">Escanea códigos de barras para agregar recursos rápidamente</small>
                        </div>
                    </div>

                    <div class="manual-input">
                        <label>O ingresa el código manualmente:</label>
                        <div class="input-group">
                            <input type="text" id="codigo_manual" class="form-control" placeholder="Código de barras">
                            <button class="btn" onclick="processBarcode(document.getElementById('codigo_manual').value)">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="admin-card">
                    <h3 class="card-title">
                        <i class="fas fa-bolt"></i>
                        Acciones Rápidas
                    </h3>
                    
                    <div class="quick-actions">
                        <div class="quick-action-btn" onclick="showModal('modalRecursos')">
                            <i class="fas fa-microchip"></i>
                            Nuevo Recurso
                        </div>
                        <div class="quick-action-btn" onclick="showModal('modalKits')">
                            <i class="fas fa-robot"></i>
                            Nuevo Kit
                        </div>
                        <div class="quick-action-btn" onclick="window.location.href='#'">
                            <i class="fas fa-list"></i>
                            Ver Inventario
                        </div>
                        <div class="quick-action-btn" onclick="window.location.href='#'">
                            <i class="fas fa-chart-bar"></i>
                            Reportes
                        </div>
                    </div>

                    <div class="stats-grid-admin" style="margin-top: 25px;">
                        <div class="stat-card-admin">
                            <div class="stat-number-admin"><?php echo date('H:i'); ?></div>
                            <div class="stat-label-admin">Hora Actual</div>
                        </div>
                        <div class="stat-card-admin">
                            <div class="stat-number-admin"><?php echo date('d/m/Y'); ?></div>
                            <div class="stat-label-admin">Fecha</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Agregar Recurso TIC -->
    <div id="modalRecursos" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-microchip"></i>
                    Agregar Nuevo Recurso TIC
                </h3>
                <button class="close-modal" onclick="closeModal('modalRecursos')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Nombre del Recurso *</label>
                        <input type="text" name="nombre_recurso" class="form-control" placeholder="Ej: Arduino Uno, Raspberry Pi 4" required>
                    </div>

                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="descripcion_recurso" class="form-control" rows="2" placeholder="Descripción detallada del recurso..."></textarea>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Categoría *</label>
                            <select name="categoria_recurso" class="form-control" required>
                                <option value="">Seleccionar categoría...</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Stock Inicial *</label>
                            <input type="number" name="stock_recurso" class="form-control" min="1" value="1" required>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Ubicación</label>
                            <input type="text" name="ubicacion_recurso" class="form-control" placeholder="Ej: Laboratorio A, Armario 3">
                        </div>

                        <div class="form-group">
                            <label>Código de Barras</label>
                            <input type="text" name="codigo_barras_recurso" class="form-control" placeholder="Código único (opcional)">
                        </div>
                    </div>

                    <button type="submit" name="agregar_recurso" class="btn btn-block">
                        <i class="fas fa-plus"></i>
                        Agregar Recurso
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Agregar Kit de Robótica -->
    <div id="modalKits" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-robot"></i>
                    Agregar Nuevo Kit de Robótica
                </h3>
                <button class="close-modal" onclick="closeModal('modalKits')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Nombre del Kit *</label>
                        <input type="text" name="nombre_kit" class="form-control" placeholder="Ej: Kit Robot Seguidor, Kit Brazo Mecánico" required>
                    </div>

                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="descripcion_kit" class="form-control" rows="2" placeholder="Descripción del kit..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Componentes</label>
                        <textarea name="componentes_kit" class="form-control" rows="2" placeholder="Lista de componentes incluidos..."></textarea>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Stock Inicial *</label>
                            <input type="number" name="stock_kit" class="form-control" min="1" value="1" required>
                        </div>

                        <div class="form-group">
                            <label>Ubicación</label>
                            <input type="text" name="ubicacion_kit" class="form-control" placeholder="Ej: Armario Robótica, Estante 2">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Código de Barras</label>
                        <input type="text" name="codigo_barras_kit" class="form-control" placeholder="Código único (opcional)">
                    </div>

                    <button type="submit" name="agregar_kit" class="btn btn-block">
                        <i class="fas fa-plus"></i>
                        Agregar Kit
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    // =============================================
    // SISTEMA HÍBRIDO DE ESCÁNER
    // =============================================
    class BarcodeScanner {
        constructor() {
            this.barcodeBuffer = '';
            this.reading = false;
            this.timeout = null;
            this.html5QrcodeScanner = null;
        }
        
        // Inicializar escáner de cámara (HTML5 Barcode Reader)
        initCameraScanner() {
            const scannerPlaceholder = document.querySelector('.scanner-placeholder');
            
            scannerPlaceholder.innerHTML = `
                <div id="reader" style="width: 100%; height: 100%;"></div>
                <div style="text-align: center; margin-top: 10px;">
                    <button class="btn" onclick="barcodeScanner.stopCameraScanner()">
                        <i class="fas fa-stop"></i> Detener Cámara
                    </button>
                </div>
            `;

            this.html5QrcodeScanner = new Html5Qrcode("reader");
            
            const config = {
                fps: 10,
                qrbox: { width: 250, height: 150 }
            };

            this.html5QrcodeScanner.start(
                { facingMode: "environment" },
                config,
                (decodedText) => this.processScannedCode(decodedText),
                (error) => console.log("Escaneando...", error)
            ).catch(err => {
                alert("Error al iniciar la cámara: " + err);
                this.stopCameraScanner();
            });
        }
        
        // Inicializar detector de lector físico
        initPhysicalScanner() {
            document.addEventListener('keydown', (event) => {
                this.handlePhysicalScannerInput(event);
            });
            
            console.log("Detector de lector físico activado");
        }
        
        // Manejar entrada del lector físico
        handlePhysicalScannerInput(event) {
            // Los lectores físicos suelen enviar los datos rápidamente
            // Ignorar teclas de control excepto Enter
            if (event.key === 'Enter') {
                if (this.barcodeBuffer.length > 3) { // Mínimo 4 caracteres para ser un código válido
                    this.processScannedCode(this.barcodeBuffer);
                }
                this.barcodeBuffer = '';
                event.preventDefault();
                return;
            }
            
            // Solo capturar caracteres alfanuméricos y algunos símbolos comunes en códigos de barras
            if (event.key.length === 1 && event.key.match(/[a-zA-Z0-9\-_\.]/)) {
                this.barcodeBuffer += event.key;
                
                // Resetear el buffer después de un tiempo (para evitar acumulación)
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    this.barcodeBuffer = '';
                }, 500);
            }
        }
        
        // Procesar código escaneado (desde cámara o lector físico)
        processScannedCode(code) {
            console.log("Código escaneado:", code);
            
            // Mostrar notificación
            this.showMessage('🔍 Código detectado: ' + code, 'info');
            
            // Verificar en la base de datos
            this.checkBarcodeInDatabase(code);
        }
        
        // Verificar código en la base de datos
        checkBarcodeInDatabase(code) {
            fetch('check_barcode.php?code=' + encodeURIComponent(code))
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        this.showMessage('✅ ' + data.nombre + ' (Ya existe en el sistema)', 'success');
                        // Aquí podrías mostrar opciones para editar el recurso existente
                    } else {
                        this.showMessage('📦 Código nuevo detectado. Completa los datos del recurso:', 'success');
                        // Llenar automáticamente el campo de código en los modales
                        document.querySelector('input[name="codigo_barras_recurso"]').value = code;
                        document.querySelector('input[name="codigo_barras_kit"]').value = code;
                        // Mostrar modal para agregar nuevo recurso
                        this.showModal('modalRecursos');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.showMessage('❌ Error al verificar el código en la base de datos', 'error');
                });
        }
        
        stopCameraScanner() {
            if (this.html5QrcodeScanner) {
                this.html5QrcodeScanner.stop().catch(err => {
                    console.error("Error al detener cámara:", err);
                });
            }
            this.restoreScannerInterface();
        }
        
        restoreScannerInterface() {
            const scannerPlaceholder = document.querySelector('.scanner-placeholder');
            scannerPlaceholder.innerHTML = `
                <div class="scanner-mode-selector" style="margin-bottom: 15px;">
                    <button class="btn" onclick="barcodeScanner.initCameraScanner()" style="margin: 5px;">
                        <i class="fas fa-camera"></i> Usar Cámara
                    </button>
                    <button class="btn" onclick="barcodeScanner.initPhysicalScannerOnly()" style="margin: 5px;">
                        <i class="fas fa-keyboard"></i> Usar Lector Físico
                    </button>
                </div>
                <div class="scanner-icon">
                    <i class="fas fa-barcode"></i>
                </div>
                <div class="scanner-text">
                    Elige el modo de escaneo
                </div>
                <small style="color: #94a3b8;">
                    • <strong>Cámara:</strong> Escanea con la cámara del dispositivo<br>
                    • <strong>Lector Físico:</strong> Conecta tu lector USB y escanea
                </small>
            `;
        }
        
        // Modo solo lector físico (sin cámara)
        initPhysicalScannerOnly() {
            const scannerPlaceholder = document.querySelector('.scanner-placeholder');
            scannerPlaceholder.innerHTML = `
                <div class="scanner-icon" style="color: #10b981;">
                    <i class="fas fa-keyboard"></i>
                </div>
                <div class="scanner-text">
                    Lector Físico Activado
                </div>
                <div style="background: #f0fdf4; padding: 15px; border-radius: 8px; margin: 10px 0; border: 1px solid #10b981;">
                    <i class="fas fa-info-circle" style="color: #10b981;"></i>
                    <small><strong>Conecta tu lector USB y escanea un código de barras</strong></small>
                </div>
                <small style="color: #94a3b8;">
                    El sistema detectará automáticamente los códigos escaneados
                </small>
                <br>
                <button class="btn" onclick="barcodeScanner.restoreScannerInterface()" style="margin-top: 15px;">
                    <i class="fas fa-undo"></i> Cambiar Modo
                </button>
            `;
            
            this.initPhysicalScanner();
        }
        
        showMessage(message, type) {
            let messageDiv = document.getElementById('scanner-message');
            if (!messageDiv) {
                messageDiv = document.createElement('div');
                messageDiv.id = 'scanner-message';
                document.body.appendChild(messageDiv);
            }
            
            const styles = {
                success: 'background: #10b981;',
                error: 'background: #ef4444;',
                info: 'background: #3b82f6;'
            };
            
            messageDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                z-index: 10000;
                max-width: 300px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                ${styles[type] || styles.info}
            `;
            
            messageDiv.innerHTML = message;
            
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 5000);
        }
        
        showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
    }

    // =============================================
    // FUNCIONES GLOBALES Y CONFIGURACIÓN
    // =============================================

    // Inicializar el sistema híbrido
    const barcodeScanner = new BarcodeScanner();

    // Función principal de inicialización (reemplaza la antigua initScanner)
    function initScanner() {
        barcodeScanner.restoreScannerInterface();
    }

    // Funciones para modales (mantener las existentes)
    function showModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Cerrar modal al hacer clic fuera
    window.onclick = function(event) {
        document.querySelectorAll('.modal').forEach(modal => {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    }

    // Función para procesar código manual (entrada de texto)
    function processBarcode(code) {
        if (!code || code.trim() === '') {
            barcodeScanner.showMessage('Por favor ingresa un código de barras válido', 'error');
            return;
        }
        barcodeScanner.processScannedCode(code.trim());
        // Limpiar campo después de procesar
        document.getElementById('codigo_manual').value = '';
    }

    // Generar código de barras automático al enfocar
    document.addEventListener('DOMContentLoaded', function() {
        const barcodeFields = document.querySelectorAll('input[placeholder*="Código único"]');
        barcodeFields.forEach(field => {
            field.addEventListener('focus', function() {
                if (!this.value) {
                    // Generar código único simple
                    const timestamp = Date.now().toString(36);
                    const random = Math.random().toString(36).substr(2, 5);
                    this.value = 'GES-' + timestamp + '-' + random.toUpperCase();
                }
            });
        });

        // Inicializar interfaz de escáner al cargar la página
        barcodeScanner.restoreScannerInterface();
    });
</script>
</body>
</html>