<?php
if(session_status() === PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['usuario'])){
    header("Location: index.php");
    exit;
}

require_once 'db.php';
require_once 'includes/funciones.php';

$user = $_SESSION['usuario'];
$rol  = $_SESSION['rol'] ?? 'Docente';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Panel Inventario</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<style>
body{background:#f6f8fa;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial;}
aside{min-height:100vh;border-right:1px solid #e9eef6;}
.card-stats{border-radius:10px;box-shadow:0 6px 18px rgba(11,42,73,0.04);padding:14px;}
.table-fixed{table-layout:fixed;width:100%;}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="/registro_inventario/dashboard.php"><i class="fa-solid fa-school"></i> Inventario</a>
    <div class="ms-auto d-flex align-items-center text-white">
      <div class="me-3"><?= e($user) ?> (<?= e($rol) ?>)</div>
      <a class="btn btn-outline-light btn-sm" href="/registro_inventario/logout.php">Salir</a>
    </div>
  </div>
</nav>

<div class="d-flex">

  <aside class="p-3 bg-white shadow-sm" style="width:260px; min-height:100vh; border-right:1px solid #e9eef6;">

    <style>
      .nav-link {
        color: #333;
        border-radius: 10px;
        padding: 10px 14px;
        font-weight: 500;
        transition: all 0.2s ease-in-out;
      }
      .nav-link:hover {
        background-color: #f1f4ff;
        color: #0d6efd;
        transform: translateX(4px);
      }
      .nav-link.active {
        background-color: #0d6efd;
        color: white;
        font-weight: 600;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
      }
      .nav-link i {
        width: 20px;
        text-align: center;
      }
      .aside-header {
        text-align: center;
        margin-bottom: 20px;
      }
      .aside-header i {
        font-size: 2rem;
        color: #0d6efd;
      }
      .aside-header h5 {
        font-weight: bold;
        color: #0d6efd;
        margin-top: 5px;
      }
      hr {
        margin: 0.8rem 0;
      }
    </style>

    <div class="aside-header">
      <i class="fa-solid fa-laptop-code"></i>
      <h5>Panel Inventario</h5>
      <hr>
    </div>

    <ul class="nav nav-pills flex-column gap-1">
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 <?= basename($_SERVER['PHP_SELF'])==='dashboard.php'?'active':'' ?>" href="dashboard.php">
          <i class="fa-solid fa-chart-line"></i> <span>Dashboard</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 <?= basename($_SERVER['PHP_SELF'])==='equipos.php'?'active':'' ?>" href="equipos.php">
          <i class="fa-solid fa-desktop"></i> <span>Equipos</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 <?= basename($_SERVER['PHP_SELF'])==='reparaciones.php'?'active':'' ?>" href="reparaciones.php">
          <i class="fa-solid fa-screwdriver-wrench"></i> <span>Reparaciones</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 <?= basename($_SERVER['PHP_SELF'])==='admin_crud.php'?'active':'' ?>" href="admin_crud.php">
          <i class="fa-solid fa-building-columns"></i> <span>Departamentos y Ubicaciones</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 <?= basename($_SERVER['PHP_SELF'])==='historial.php'?'active':'' ?>" href="historial.php">
          <i class="fa-solid fa-clock-rotate-left"></i> <span>Historial</span>
        </a>
      </li>

      <?php if($rol === 'Administrador'): ?>
      <li class="nav-item">
        <a class="nav-link d-flex align-items-center gap-2 <?= basename($_SERVER['PHP_SELF'])==='usuarios.php'?'active':'' ?>" href="usuarios.php">
          <i class="fa-solid fa-users-gear"></i> <span>Usuarios</span>
        </a>
      </li>
      <?php endif; ?>
    </ul>
  </aside>


  <main class="flex-fill p-3">
