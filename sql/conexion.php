<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventario";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

function verificarSesion() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Función para obtener datos del usuario
function obtenerUsuario($conn, $user_id) {
    $stmt = $conn->prepare("SELECT nombre, apellido, email, tipo, grado_seccion, materia FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($nombre, $apellido, $email, $tipo, $grado_seccion, $materia);
    $stmt->fetch();
    $stmt->close();
    
    return [
        'nombre_completo' => $nombre . ' ' . $apellido,
        'email' => $email,
        'tipo' => $tipo,
        'grado_seccion' => $grado_seccion,
        'materia' => $materia
    ];
}
?>