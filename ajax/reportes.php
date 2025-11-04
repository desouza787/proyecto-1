<?php
require_once "../db.php";

$nivel = $_GET['nivel'] ?? '';
$estado = $_GET['estado'] ?? '';
$fecha = $_GET['fecha'] ?? '';

$where = [];
if($nivel) $where[] = "nivel='$nivel'";
if($estado) $where[] = "estado='$estado'";
if($fecha) $where[] = "DATE(fecha_ingreso)='$fecha'";

$where_sql = $where ? "WHERE ".implode(" AND ", $where) : "";

// Equipos filtrados
$equipos = [];
$res = $conn->query("SELECT id_equipo, codigo_equipo, nombre, marca, nivel, estado, DATE_FORMAT(fecha_ingreso,'%Y-%m-%d') fecha_ingreso FROM equipos $where_sql");
while($r = $res->fetch_assoc()) $equipos[] = $r;

// Datos para gráficos
$chartEstados = [];
$res = $conn->query("SELECT estado, COUNT(*) c FROM equipos $where_sql GROUP BY estado");
while($r = $res->fetch_assoc()) $chartEstados[$r['estado']] = (int)$r['c'];

$chartNiveles = [];
$res = $conn->query("SELECT nivel, COUNT(*) c FROM equipos $where_sql GROUP BY nivel");
while($r = $res->fetch_assoc()) $chartNiveles[$r['nivel']] = (int)$r['c'];

echo json_encode(['equipos'=>$equipos,'chartEstados'=>$chartEstados,'chartNiveles'=>$chartNiveles]);
