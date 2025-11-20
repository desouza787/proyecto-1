<?php
require_once "db.php";
require_once "libs/fpdf/fpdf.php";

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10,'Listado de Reparaciones',0,1,'C');
        $this->Ln(5);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',10);

// --- FILTROS ---
$where = [];
$f_equipo  = $_GET['f_equipo'] ?? '';
$f_tecnico = $_GET['f_tecnico'] ?? '';
$f_estado  = $_GET['f_estado'] ?? '';

if ($f_equipo)  $where[] = "(e.codigo_equipo LIKE '%".$conn->real_escape_string($f_equipo)."%' OR e.marca LIKE '%".$conn->real_escape_string($f_equipo)."%' OR e.modelo LIKE '%".$conn->real_escape_string($f_equipo)."%')";
if ($f_tecnico) $where[] = "r.tecnico LIKE '%".$conn->real_escape_string($f_tecnico)."'%";
if ($f_estado)  $where[] = "r.estado='".$conn->real_escape_string($f_estado)."'";

$whereSQL = count($where) ? "WHERE ".implode(" AND ", $where) : "";

// --- CONSULTA ---
$res = $conn->query("
    SELECT r.id_reparacion, e.codigo_equipo, e.marca, e.modelo,
           r.problema, r.tecnico, r.fecha_inicio, r.fecha_fin, r.solucion, r.estado, r.usuario_registra
    FROM reparaciones r
    LEFT JOIN equipos e ON e.id_equipo = r.id_equipo
    $whereSQL
    ORDER BY r.fecha_inicio DESC
");

// --- CABECERA DE TABLA ---
$pdf->SetFont('Arial','B',10);
$pdf->Cell(10,8,'ID',1);
$pdf->Cell(40,8,'Equipo',1);
$pdf->Cell(40,8,'Problema',1);
$pdf->Cell(30,8,'Técnico',1);
$pdf->Cell(25,8,'Inicio',1);
$pdf->Cell(25,8,'Fin',1);
$pdf->Cell(25,8,'Estado',1);
$pdf->Ln();

// --- DATOS ---
$pdf->SetFont('Arial','',9);
while($r=$res->fetch_assoc()){
    $equipo = $r['codigo_equipo'].'-'.$r['marca'].' '.$r['modelo'];
    $pdf->Cell(10,6,$r['id_reparacion'],1);
    $pdf->Cell(40,6,substr($equipo,0,20),1);
    $pdf->Cell(40,6,substr($r['problema'],0,20),1);
    $pdf->Cell(30,6,substr($r['tecnico'],0,15),1);
    $pdf->Cell(25,6,$r['fecha_inicio'],1);
    $pdf->Cell(25,6,$r['fecha_fin'] ?: '-',1);
    $pdf->Cell(25,6,$r['estado'],1);
    $pdf->Ln();
}

$pdf->Output('D','reparaciones.pdf');
