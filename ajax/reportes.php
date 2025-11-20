<?php
require_once "../db.php";

$nivel = $_GET['nivel'] ?? '';
$estado = $_GET['estado'] ?? '';
$fecha = $_GET['fecha'] ?? '';

$where = [];
if ($nivel) $where[] = "nivel='$nivel'";
if ($estado) $where[] = "estado='$estado'";
if ($fecha) $where[] = "DATE(fecha_ingreso)='$fecha'";

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// -----------------------------
// 📋 Equipos filtrados
// -----------------------------
$equipos = [];
$res = $conn->query("SELECT id_equipo, codigo_equipo, marca, modelo, nivel, estado, DATE_FORMAT(fecha_ingreso,'%Y-%m-%d') fecha_ingreso 
                     FROM equipos $where_sql 
                     ORDER BY fecha_ingreso DESC");
while ($r = $res->fetch_assoc()) $equipos[] = $r;

// -----------------------------
// 📊 Gráfico: Equipos por Estado
// -----------------------------
$chartEstados = [];
$res = $conn->query("SELECT estado, COUNT(*) c FROM equipos $where_sql GROUP BY estado");
while ($r = $res->fetch_assoc()) $chartEstados[$r['estado']] = (int)$r['c'];

// -----------------------------
// 📊 Gráfico: Equipos por Nivel
// -----------------------------
$chartNiveles = [];
$res = $conn->query("SELECT nivel, COUNT(*) c FROM equipos $where_sql GROUP BY nivel");
while ($r = $res->fetch_assoc()) $chartNiveles[$r['nivel']] = (int)$r['c'];

// -----------------------------
// 📈 Gráfico: Movimientos (historial_estados)
// -----------------------------
$chartHistorial = [];
$res = $conn->query("
    SELECT DATE(fecha) as dia, COUNT(*) as total 
    FROM historial_estados 
    GROUP BY DATE(fecha) 
    ORDER BY dia ASC
");
while ($r = $res->fetch_assoc()) {
    $chartHistorial[$r['dia']] = (int)$r['total'];
}

// -----------------------------
// 📊 Estadísticas rápidas
// -----------------------------
$stats = [
    'total'       => 0,
    'uso'         => 0,
    'reparacion'  => 0,
    'baja'        => 0
];

$res = $conn->query("SELECT estado, COUNT(*) c FROM equipos");
while ($r = $res->fetch_assoc()) {
    $stats['total'] += $r['c'];
    switch ($r['estado']) {
        case 'En uso': $stats['uso'] = (int)$r['c']; break;
        case 'En reparación': $stats['reparacion'] = (int)$r['c']; break;
        case 'Dado de baja': $stats['baja'] = (int)$r['c']; break;
    }
}

// -----------------------------
// 📦 Salida JSON final
// -----------------------------
echo json_encode([
    'equipos'       => $equipos,
    'chartEstados'  => $chartEstados,
    'chartNiveles'  => $chartNiveles,
    'chartHistorial'=> $chartHistorial,
    'stats'         => $stats
]);
