<?php
require_once "db.php";
require_once "vendor/autoload.php"; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// -------------------------
//  FILTROS RECIBIDOS
// -------------------------
$estado      = $conn->real_escape_string($_GET['estado'] ?? '');
$categoria   = $conn->real_escape_string($_GET['categoria'] ?? '');
$ubicacion   = $conn->real_escape_string($_GET['ubicacion'] ?? '');
$departamento= $conn->real_escape_string($_GET['departamento'] ?? '');

// -------------------------
//  WHERE DINÁMICO
// -------------------------
$where = [];

if ($estado)        $where[] = "e.estado='$estado'";
if ($categoria)     $where[] = "c.nombre_categoria='$categoria'";
if ($ubicacion)     $where[] = "u.nombre='$ubicacion'";
if ($departamento)  $where[] = "d.nombre_dep='$departamento'";

$whereSql = count($where) ? "WHERE ".implode(" AND ", $where) : "";

// -------------------------
//  CONSULTA PRINCIPAL
// -------------------------
$sql = "
    SELECT 
        e.codigo_equipo, e.marca, e.modelo, e.serie, e.estado,
        d.nombre_dep, c.nombre_categoria, u.nombre AS ubicacion
    FROM equipos e
    LEFT JOIN departamentos d ON e.id_dep = d.id_dep
    LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
    LEFT JOIN ubicaciones u ON e.id_ubicacion = u.id_ubicacion
    $whereSql
    ORDER BY e.id_equipo DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Error en la consulta SQL: " . $conn->error);
}

// -------------------------
//  CREAR EXCEL
// -------------------------
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Cabeceras corregidas
$headers = [
    'Código', 
    'Marca', 
    'Modelo', 
    'Serie', 
    'Estado', 
    'Departamento', 
    'Categoría', 
    'Ubicación'
];

$col = 'A';
foreach ($headers as $h) {
    $sheet->setCellValue($col.'1', $h);
    $col++;
}

// -------------------------
//  LLENAR TABLA
// -------------------------
$row = 2;

while ($data = $result->fetch_assoc()) {
    $sheet->setCellValue('A'.$row, $data['codigo_equipo']);
    $sheet->setCellValue('B'.$row, $data['marca']);
    $sheet->setCellValue('C'.$row, $data['modelo']);
    $sheet->setCellValue('D'.$row, $data['serie']);
    $sheet->setCellValue('E'.$row, $data['estado']);
    $sheet->setCellValue('F'.$row, $data['nombre_dep']);
    $sheet->setCellValue('G'.$row, $data['nombre_categoria']);
    $sheet->setCellValue('H'.$row, $data['ubicacion']);
    $row++;
}

// -------------------------
//  DESCARGA DEL ARCHIVO
// -------------------------
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="equipos.xlsx"');
header('Cache-Control: max-age=0');
header('Expires: 0');
header('Pragma: public');
header('Content-Transfer-Encoding: binary');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
