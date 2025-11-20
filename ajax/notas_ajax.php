<?php
// ajax/notas_ajax.php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
session_start();

$id_equipo = intval($_POST['id_equipo'] ?? 0);
$comentario = trim($_POST['comentario'] ?? '');
$usuario = $_SESSION['usuario'] ?? 'Anonimo';

if($id_equipo <= 0 || $comentario === ''){
    echo json_encode(['ok'=>false,'error'=>'Datos inválidos']); exit;
}

$stmt = $conn->prepare("INSERT INTO comentarios (id_producto, usuario, comentario, fecha) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iss", $id_equipo, $usuario, $comentario);
$res = $stmt->execute();

echo json_encode(['ok'=> (bool)$res, 'id'=>$conn->insert_id]);
