<?php
// ===================== ERRORES =====================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ===================== CONEXIÓN =====================
require_once "db.php";

// ===================== FPDF =====================
if (!file_exists("libs/fpdf/fpdf.php")) {
    die("ERROR: FPDF no se encuentra en libs/fpdf/fpdf.php");
}
require_once "libs/fpdf/fpdf.php";

// ===================== FILTROS =====================
$estado = $_GET['estado'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$ubicacion = $_GET['ubicacion'] ?? '';
$departamento = $_GET['departamento'] ?? '';

$where = [];
if($estado) $where[] = "e.estado='" . $conn->real_escape_string($estado) . "'";
if($categoria) $where[] = "c.nombre_categoria='" . $conn->real_escape_string($categoria) . "'";
if($ubicacion) $where[] = "u.nombre='" . $conn->real_escape_string($ubicacion) . "'";
if($departamento) $where[] = "d.nombre_dep='" . $conn->real_escape_string($departamento) . "'";
$whereSql = count($where) ? "WHERE ".implode(" AND ", $where) : "";

// ===================== CONSULTA =====================
$sql = "SELECT e.codigo_equipo, e.marca, e.modelo, e.serie, e.estado, 
               d.nombre_dep, c.nombre_categoria, u.nombre as ubicacion
        FROM equipos e
        LEFT JOIN departamentos d ON e.id_dep=d.id_dep
        LEFT JOIN categorias c ON e.id_categoria=c.id_categoria
        LEFT JOIN ubicaciones u ON e.id_ubicacion=u.id_ubicacion
        $whereSql
        ORDER BY e.id_equipo DESC";

$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta SQL: " . $conn->error);
}

// ===================== PDF =====================
class PDF extends FPDF {
    // Encabezado
    function Header() {
        // Logo (coloca tu logo en 'img/logo.png')
        if(file_exists('img/logo.png')) {
            $this->Image('img/logo.png',10,6,30);
        }
        // Título
        $this->SetFont('Arial','B',16);
        $this->Cell(0,10,'Reporte de Equipos',0,1,'C');
        // Fecha
        $this->SetFont('Arial','',10);
        $this->Cell(0,10,'Fecha: '.date('d/m/Y H:i'),0,1,'C');
        $this->Ln(5);
    }

    // Pie de página
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF('L','mm','A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','B',12);

// Cabeceras
$pdf->SetFillColor(200,200,200); // Gris claro
$pdf->Cell(35,10,'Codigo',1,0,'C',true);
$pdf->Cell(35,10,'Marca',1,0,'C',true);
$pdf->Cell(35,10,'Modelo',1,0,'C',true);
$pdf->Cell(35,10,'Serie',1,0,'C',true);
$pdf->Cell(30,10,'Estado',1,0,'C',true);
$pdf->Cell(40,10,'Departamento',1,0,'C',true);
$pdf->Cell(40,10,'Categoria',1,0,'C',true);
$pdf->Cell(40,10,'Ubicacion',1,1,'C',true);

// Datos
$pdf->SetFont('Arial','',10);
if ($result->num_rows == 0) {
    $pdf->Cell(330,10,'No se encontraron registros',1,0,'C');
} else {
    while($data = $result->fetch_assoc()){
        $pdf->Cell(35,10,$data['codigo_equipo'],1);
        $pdf->Cell(35,10,$data['marca'],1);
        $pdf->Cell(35,10,$data['modelo'],1);
        $pdf->Cell(35,10,$data['serie'],1);
        $pdf->Cell(30,10,$data['estado'],1);
        $pdf->Cell(40,10,$data['nombre_dep'],1);
        $pdf->Cell(40,10,$data['nombre_categoria'],1);
        $pdf->Cell(40,10,$data['ubicacion'],1);
        $pdf->Ln();
    }
}

// ===================== DESCARGAR =====================
$pdf->Output('D','equipos.pdf');
exit;
