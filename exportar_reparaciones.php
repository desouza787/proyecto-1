<?php
require_once "db.php";
require_once "vendor/autoload.php"; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// -------------------------------------------------------------
// FILTROS
// -------------------------------------------------------------
$where = [];

$f_equipo  = $_GET['f_equipo']  ?? '';
$f_tecnico = $_GET['f_tecnico'] ?? '';
$f_estado  = $_GET['f_estado']  ?? '';

if ($f_equipo) {
    $f = $conn->real_escape_string($f_equipo);
    $where[] = "(e.codigo_equipo LIKE '%$f%' 
                 OR e.marca LIKE '%$f%' 
                 OR e.modelo LIKE '%$f%')";
}

if ($f_tecnico) {
    $f = $conn->real_escape_string($f_tecnico);
    $where[] = "r.tecnico LIKE '%$f%'";
}

if ($f_estado) {
    $f = $conn->real_escape_string($f_estado);
    $where[] = "r.estado='$f'";
}

$whereSQL = count($where) ? "WHERE ".implode(" AND ", $where) : "";

// -------------------------------------------------------------
// CONSULTA
// -------------------------------------------------------------
$sql = "
    SELECT 
        r.id_reparacion,
        e.codigo_equipo,
        e.marca,
        e.modelo,
        r.problema,
        r.tecnico,
        r.fecha_inicio,
        r.fecha_fin,
        r.solucion,
        r.estado,
        r.usuario_registra
    FROM reparaciones r
    LEFT JOIN equipos e ON e.id_equipo = r.id_equipo
    $whereSQL
    ORDER BY r.fecha_inicio DESC
";

$result = $conn->query($sql);

// -------------------------------------------------------------
// CREAR EXCEL
// -------------------------------------------------------------
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Reparaciones");

// Encabezados
$headers = [
    "ID",
    "Equipo",
    "Problema",
    "Técnico",
    "Fecha inicio",
    "Fecha fin",
    "Solución",
    "Estado",
    "Usuario registra"
];

$col = "A";

foreach ($headers as $head) {
    $sheet->setCellValue($col . "1", $head);
    $sheet->getStyle($col . "1")->getFont()->setBold(true);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $col++;
}

// -------------------------------------------------------------
// LLENAR DATOS
// -------------------------------------------------------------
$rowNum = 2;

while ($r = $result->fetch_assoc()) {

    $equipo     = $r['codigo_equipo'] . " - " . $r['marca'] . " " . $r['modelo'];
    $fechaFin   = $r['fecha_fin'] ?: "-";
    $solucion   = $r['solucion'] ?: "-";

    $sheet->setCellValue("A$rowNum", $r['id_reparacion']);
    $sheet->setCellValue("B$rowNum", $equipo);
    $sheet->setCellValue("C$rowNum", $r['problema']);
    $sheet->setCellValue("D$rowNum", $r['tecnico']);
    $sheet->setCellValue("E$rowNum", $r['fecha_inicio']);
    $sheet->setCellValue("F$rowNum", $fechaFin);
    $sheet->setCellValue("G$rowNum", $solucion);
    $sheet->setCellValue("H$rowNum", $r['estado']);
    $sheet->setCellValue("I$rowNum", $r['usuario_registra']);

    $rowNum++;
}

// -------------------------------------------------------------
// DESCARGA DEL ARCHIVO
// -------------------------------------------------------------
$filename = "reparaciones_" . date("Ymd_His") . ".xlsx";

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
