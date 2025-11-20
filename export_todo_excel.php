<?php
require_once "db.php";
require_once "vendor/autoload.php"; // Importante para usar PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Crear el documento Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Equipos");

// ENCABEZADOS
$headers = ["Codigo", "Marca", "Modelo", "Serie", "Estado", "Departamento", "Categoria", "Ubicacion"];
$col = 'A';

foreach ($headers as $header) {
    $sheet->setCellValue($col . "1", $header);
    $sheet->getStyle($col . "1")->getFont()->setBold(true); // Negrita
    $sheet->getColumnDimension($col)->setAutoSize(true); // Auto tamaño
    $col++;
}

// CONSULTA EXACTAMENTE IGUAL A TU ORIGINAL
$sql = "SELECT 
            e.codigo_equipo, e.marca, e.modelo, e.serie, e.estado,
            d.nombre_dep, c.nombre_categoria, u.nombre AS ubicacion
        FROM equipos e
        LEFT JOIN departamentos d ON e.id_dep=d.id_dep
        LEFT JOIN categorias c ON e.id_categoria=c.id_categoria
        LEFT JOIN ubicaciones u ON e.id_ubicacion=u.id_ubicacion
        ORDER BY e.id_equipo DESC";

$result = $conn->query($sql);

// Llenar filas
$rowNumber = 2;

while ($row = $result->fetch_assoc()) {

    $sheet->setCellValue("A" . $rowNumber, $row["codigo_equipo"]);
    $sheet->setCellValue("B" . $rowNumber, $row["marca"]);
    $sheet->setCellValue("C" . $rowNumber, $row["modelo"]);
    $sheet->setCellValue("D" . $rowNumber, $row["serie"]);
    $sheet->setCellValue("E" . $rowNumber, $row["estado"]);
    $sheet->setCellValue("F" . $rowNumber, $row["nombre_dep"]);
    $sheet->setCellValue("G" . $rowNumber, $row["nombre_categoria"]);
    $sheet->setCellValue("H" . $rowNumber, $row["ubicacion"]);

    $rowNumber++;
}

// Nombre del archivo (igual al tuyo, solo cambia extensión real)
$filename = "equipos_todos_" . date('Ymd_His') . ".xlsx";

// Encabezados para descargar sin errores
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

// Descargar XLSX
$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
?>
