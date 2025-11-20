<?php
// ajax/filtros.php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/funciones.php';

$q = $_GET['q'] ?? '';
$nivel = $_GET['nivel'] ?? '';

$sql = "SELECT id_equipo, codigo_equipo, nombre, marca, serie, estado, nivel FROM equipos WHERE 1=1 ";
$params = []; $types = '';

if($q !== ''){
    $sql .= " AND (codigo_equipo LIKE ? OR nombre LIKE ? OR marca LIKE ? OR serie LIKE ?)";
    $like = "%$q%";
    $params = array_merge($params, [$like,$like,$like,$like]);
    $types .= 'ssss';
}
if($nivel !== ''){
    $sql .= " AND nivel = ?";
    $params[] = $nivel; $types .= 's';
}
$stmt = $conn->prepare($sql);
if($stmt === false){ echo json_encode([]); exit; }
if(count($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$out = [];
while($r = $res->fetch_assoc()) $out[] = $r;
header('Content-Type: application/json; charset=utf-8');
echo json_encode($out);
