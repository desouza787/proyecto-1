<?php
require_once "db.php";
require_once "libs/fpdf/fpdf.php";

// Consulta sin filtros
$sql = "SELECT e.codigo_equipo, e.marca, e.modelo, e.serie, e.estado, 
               d.nombre_dep, c.nombre_categoria, u.nombre as ubicacion
        FROM equipos e
        LEFT JOIN departamentos d ON e.id_dep=d.id_dep
        LEFT JOIN categorias c ON e.id_categoria=c.id_categoria
        LEFT JOIN ubicaciones u ON e.id_ubicacion=u.id_ubicacion
        ORDER BY e.id_equipo DESC";

$result = $conn->query($sql);

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',12);

// Cabeceras
$pdf->Cell(30,10,'Codigo',1);
$pdf->Cell(30,10,'Marca',1);
$pdf->Cell(30,10,'Modelo',1);
$pdf->Cell(30,10,'Serie',1);
$pdf->Cell(25,10,'Estado',1);
$pdf->Cell(30,10,'Departamento',1);
$pdf->Cell(30,10,'Categoria',1);
$pdf->Cell(25,10,'Ubicacion',1);
$pdf->Ln();

$pdf->SetFont('Arial','',10);
while($data = $result->fetch_assoc()){
    $pdf->Cell(30,10,$data['codigo_equipo'],1);
    $pdf->Cell(30,10,$data['marca'],1);
    $pdf->Cell(30,10,$data['modelo'],1);
    $pdf->Cell(30,10,$data['serie'],1);
    $pdf->Cell(25,10,$data['estado'],1);
    $pdf->Cell(30,10,$data['nombre_dep'],1);
    $pdf->Cell(30,10,$data['nombre_categoria'],1);
    $pdf->Cell(25,10,$data['ubicacion'],1);
    $pdf->Ln();
}

$pdf->Output('D','todos_los_equipos.pdf');
exit;
