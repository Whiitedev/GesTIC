<?php
session_start();
require '../sql/conexion.php';

// Verificar permisos de administrador
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['user_tipo'] !== 'administrativo') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

if (!isset($_GET['code']) || empty($_GET['code'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Código no proporcionado']);
    exit();
}

$code = trim($_GET['code']);

// Buscar en recursos TIC
$stmt = $conn->prepare("SELECT id, nombre, 'recurso' as tipo FROM recursos_tic WHERE codigo_barras = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $item = $result->fetch_assoc();
    $stmt->close();
    echo json_encode([
        'exists' => true,
        'id' => $item['id'],
        'nombre' => $item['nombre'],
        'tipo' => $item['tipo']
    ]);
    exit();
}
$stmt->close();

// Buscar en kits de robótica
$stmt = $conn->prepare("SELECT id, nombre, 'kit' as tipo FROM kits_robotica WHERE codigo_barras = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $item = $result->fetch_assoc();
    $stmt->close();
    echo json_encode([
        'exists' => true,
        'id' => $item['id'],
        'nombre' => $item['nombre'],
        'tipo' => $item['tipo']
    ]);
    exit();
}
$stmt->close();

// Código no encontrado
echo json_encode([
    'exists' => false
]);
?>