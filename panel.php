<?php
// panel.php - Archivo único, CRUD completo unificado
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}
require 'db.php'; // Debe definir $conn (mysqli)

$usuario = $_SESSION['usuario'];
$flash = "";

// -----------------------------
// Función: generar código automático de producto
// -----------------------------
function generarCodigoProducto(){
    global $conn;
    $sql = "SELECT codigo FROM productos ORDER BY id_producto DESC LIMIT 1";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0){
        $row = $result->fetch_assoc();
        $num = (int) filter_var($row['codigo'], FILTER_SANITIZE_NUMBER_INT) + 1;
    } else {
        $num = 1;
    }
    return "PROD-" . str_pad($num,4,"0",STR_PAD_LEFT);
}

// -----------------------------
// Procesamiento de acciones (todos apuntan a este archivo)
// - action values: create_dep, edit_dep, delete_dep,
//                  create_cat, edit_cat, delete_cat,
//                  create_prod, edit_prod, delete_prod,
//                  update_stock, delete_stock,
//                  create_act, edit_act, delete_act,
//                  create_alert, delete_alert
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // ----- DEPARTAMENTOS -----
    if ($action === 'create_dep') {
        $nombre = trim($_POST['nombre_dep'] ?? '');
        $estilo = $_POST['estilo_dep'] ?? 'Básica';
        $descripcion = trim($_POST['descripcion_dep'] ?? '');
        $stmt = $conn->prepare("INSERT INTO departamentos (nombre_dep, estilo, descripcion) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $estilo, $descripcion);
        $stmt->execute();
        header("Location: panel.php");
        exit();
    }

    if ($action === 'edit_dep') {
        $id = intval($_POST['id_dep'] ?? 0);
        $nombre = trim($_POST['nombre_dep'] ?? '');
        $estilo = $_POST['estilo_dep'] ?? 'Básica';
        $descripcion = trim($_POST['descripcion_dep'] ?? '');
        $stmt = $conn->prepare("UPDATE departamentos SET nombre_dep=?, estilo=?, descripcion=? WHERE id_dep=?");
        $stmt->bind_param("sssi", $nombre, $estilo, $descripcion, $id);
        $stmt->execute();
        header("Location: panel.php");
        exit();
    }

    if ($action === 'delete_dep') {
        $id = intval($_POST['id_dep'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM departamentos WHERE id_dep=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: panel.php");
        exit();
    }

    // ----- CATEGORÍAS -----
    if ($action === 'create_cat') {
        $nombre = trim($_POST['nombre_cat'] ?? '');
        $descripcion = trim($_POST['descripcion_cat'] ?? '');
        $stmt = $conn->prepare("INSERT INTO categorias (nombre_categoria, descripcion) VALUES (?, ?)");
        $stmt->bind_param("ss", $nombre, $descripcion);
        $stmt->execute();
        header("Location: panel.php");
        exit();
    }

    if ($action === 'edit_cat') {
        $id = intval($_POST['id_cat'] ?? 0);
        $nombre = trim($_POST['nombre_cat'] ?? '');
        $descripcion = trim($_POST['descripcion_cat'] ?? '');
        $stmt = $conn->prepare("UPDATE categorias SET nombre_categoria=?, descripcion=? WHERE id_categoria=?");
        $stmt->bind_param("ssi", $nombre, $descripcion, $id);
        $stmt->execute();
        header("Location: panel.php");
        exit();
    }

    if ($action === 'delete_cat') {
        $id = intval($_POST['id_cat'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM categorias WHERE id_categoria=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: panel.php");
        exit();
    }

    // ----- PRODUCTOS -----
    if ($action === 'create_prod') {
        // Note: codigo se ignora si el campo viene vacío y se genera siempre del lado servidor
        $codigo = generarCodigoProducto();
        $nombre = trim($_POST['nombre_prod'] ?? '');
        $id_categoria = intval($_POST['id_categoria_prod'] ?? 0);
        $id_dep = intval($_POST['id_dep_prod'] ?? 0);
        $precio = floatval($_POST['precio_prod'] ?? 0);
        $descripcion = trim($_POST['descripcion_prod'] ?? '');
        // Insert producto
        $stmt = $conn->prepare("INSERT INTO productos (codigo, nombre, id_categoria, id_dep, precio, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiids", $codigo, $nombre, $id_categoria, $id_dep, $precio, $descripcion);
        $stmt->execute();
        // Si vino cantidad para stock, insertar/actualizar stock
        $cantidad = isset($_POST['cantidad_prod']) ? intval($_POST['cantidad_prod']) : null;
        if ($cantidad !== null) {
            $inserted_id = $conn->insert_id;
            if ($inserted_id) {
                $sstmt = $conn->prepare("INSERT INTO stock (id_producto, cantidad) VALUES (?, ?) ON DUPLICATE KEY UPDATE cantidad=VALUES(cantidad)");
                $sstmt->bind_param("ii", $inserted_id, $cantidad);
                $sstmt->execute();
            }
        }
        header("Location: panel.php");
        exit();
    }

    if ($action === 'edit_prod') {
        $id = intval($_POST['id_prod'] ?? 0);
        $nombre = trim($_POST['nombre_prod'] ?? '');
        $id_categoria = intval($_POST['id_categoria_prod'] ?? 0);
        $id_dep = intval($_POST['id_dep_prod'] ?? 0);
        $precio = floatval($_POST['precio_prod'] ?? 0);
        $descripcion = trim($_POST['descripcion_prod'] ?? '');
        $stmt = $conn->prepare("UPDATE productos SET nombre=?, id_categoria=?, id_dep=?, precio=?, descripcion=? WHERE id_producto=?");
        $stmt->bind_param("siidsi", $nombre, $id_categoria, $id_dep, $precio, $descripcion, $id);
        $stmt->execute();
        // actualizar stock si envian cantidad
        if (isset($_POST['cantidad_prod'])) {
            $cantidad = intval($_POST['cantidad_prod']);
            // Verificar si existe stock
            $res = $conn->query("SELECT id_stock FROM stock WHERE id_producto=$id");
            if ($res && $res->num_rows > 0) {
                $sstmt = $conn->prepare("UPDATE stock SET cantidad=? WHERE id_producto=?");
                $sstmt->bind_param("ii", $cantidad, $id);
                $sstmt->execute();
            } else {
                $sstmt = $conn->prepare("INSERT INTO stock (id_producto, cantidad) VALUES (?, ?)");
                $sstmt->bind_param("ii", $id, $cantidad);
                $sstmt->execute();
            }
        }
        header("Location: panel.php");
        exit();
    }

    if ($action === 'delete_prod') {
        $id = intval($_POST['id_prod'] ?? 0);
        // borrar stock asociado
        $stmt = $conn->prepare("DELETE FROM stock WHERE id_producto=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        // borrar producto
        $pstmt = $conn->prepare("DELETE FROM productos WHERE id_producto=?");
        $pstmt->bind_param("i", $id);
        $pstmt->execute();
        header("Location: panel.php");
        exit();
    }

    // ----- STOCK -----
    if ($action === 'update_stock') {
        $id_producto = intval($_POST['id_producto_stock'] ?? 0);
        $cantidad = intval($_POST['cantidad_stock'] ?? 0);
        // comprobar si existe
        $res = $conn->query("SELECT id_stock FROM stock WHERE id_producto=$id_producto");
        if ($res && $res->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE stock SET cantidad=? WHERE id_producto=?");
            $stmt->bind_param("ii", $cantidad, $id_producto);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO stock (id_producto, cantidad) VALUES (?, ?)");
            $stmt->bind_param("ii", $id_producto, $cantidad);
            $stmt->execute();
        }
        header("Location: panel.php");
        exit();
    }

    if ($action === 'delete_stock') {
        $id = intval($_POST['id_stock'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM stock WHERE id_stock=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: panel.php");
        exit();
    }

    // ----- ACTIVOS -----
    if ($action === 'create_act') {
        $codigo = trim($_POST['codigo_act'] ?? '');
        $nombre = trim($_POST['nombre_act'] ?? '');
        $id_categoria = intval($_POST['id_categoria_act'] ?? 0);
        $fecha_ingreso = $_POST['fecha_ingreso_act'] ?? null;
        $descripcion = trim($_POST['descripcion_act'] ?? '');
        $stmt = $conn->prepare("INSERT INTO activos (codigo, nombre, id_categoria, fecha_ingreso, descripcion) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $codigo, $nombre, $id_categoria, $fecha_ingreso, $descripcion);
        $stmt->execute();
        header("Location: panel.php");
        exit();
    }

    if ($action === 'edit_act') {
        $id = intval($_POST['id_act'] ?? 0);
        $nombre = trim($_POST['nombre_act'] ?? '');
        $id_categoria = intval($_POST['id_categoria_act'] ?? 0);
        $fecha_ingreso = $_POST['fecha_ingreso_act'] ?? null;
        $descripcion = trim($_POST['descripcion_act'] ?? '');
        $stmt = $conn->prepare("UPDATE activos SET nombre=?, id_categoria=?, fecha_ingreso=?, descripcion=? WHERE id=?");
        $stmt->bind_param("sissi", $nombre, $id_categoria, $fecha_ingreso, $descripcion, $id);
        $stmt->execute();
        header("Location: panel.php");
        exit();
    }

    if ($action === 'delete_act') {
        $id = intval($_POST['id_act'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM activos WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: panel.php");
        exit();
    }

    // ----- ALERTAS -----
    if ($action === 'create_alert') {
        $id_producto = intval($_POST['id_producto_alert'] ?? 0);
        $mensaje = trim($_POST['mensaje_alert'] ?? '');
        $fecha = $_POST['fecha_alert'] ?? date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO alertas_stock (id_producto, mensaje, fecha_alerta) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $id_producto, $mensaje, $fecha);
        $stmt->execute();
        header("Location: panel.php");
        exit();
    }

    if ($action === 'delete_alert') {
        $id = intval($_POST['id_alert'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM alertas_stock WHERE id_alerta=?");
        // table column named id_alerta per earlier code
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: panel.php");
        exit();
    }
}

// -----------------------------
// CONSULTAS PARA RENDERIZAR LA PÁGINA
// -----------------------------
$departamentos = $conn->query("SELECT * FROM departamentos ORDER BY id_dep DESC");
$categorias = $conn->query("SELECT * FROM categorias ORDER BY id_categoria DESC");
$productos = $conn->query("
    SELECT p.*, c.nombre_categoria, d.nombre_dep, s.cantidad, s.id_stock
    FROM productos p
    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
    LEFT JOIN departamentos d ON p.id_dep = d.id_dep
    LEFT JOIN stock s ON p.id_producto = s.id_producto
    ORDER BY p.id_producto DESC
");
$stocks = $conn->query("
    SELECT s.id_stock, p.nombre as producto, s.cantidad
    FROM stock s
    LEFT JOIN productos p ON s.id_producto = p.id_producto
    ORDER BY s.id_stock DESC
");
$activos = $conn->query("
    SELECT a.*, c.nombre_categoria
    FROM activos a
    LEFT JOIN categorias c ON a.id_categoria = c.id_categoria
    ORDER BY a.id DESC
");
$alertas = $conn->query("
    SELECT al.*, p.nombre as producto
    FROM alertas_stock al
    LEFT JOIN productos p ON al.id_producto = p.id_producto
    ORDER BY al.fecha_alerta DESC
");

// Estadísticas
$total_productos = $conn->query("SELECT COUNT(*) as n FROM productos")->fetch_assoc()['n'] ?? 0;
$total_activos = $conn->query("SELECT COUNT(*) as n FROM activos")->fetch_assoc()['n'] ?? 0;
$total_departamentos = $conn->query("SELECT COUNT(*) as n FROM departamentos")->fetch_assoc()['n'] ?? 0;
$total_alertas = $conn->query("SELECT COUNT(*) as n FROM alertas_stock")->fetch_assoc()['n'] ?? 0;
$stock_bajo = $conn->query("SELECT COUNT(*) as n FROM stock WHERE cantidad < 10")->fetch_assoc()['n'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Panel - Inventario</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
/* ====== ESTILOS INTERNOS ====== */
:root{
  --accent:#1565c0;
  --accent-dark:#0d47a1;
}
body {
  background: #f1f5f9;
  font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
}
.navbar {
  background: linear-gradient(90deg,var(--accent),var(--accent-dark));
}
.header-brand { color: white; }
.card-stats { border-radius: 12px; box-shadow: 0 6px 18px rgba(11,42,73,0.06); padding: 14px; }
.small-muted { font-size: 0.9rem; color: #6b7280; }
.table-fixed { table-layout: fixed; width: 100%; font-size: 0.95rem; }
.table-hover tbody tr:hover { background-color: #f8fafc; transition: .15s; }
.form-control, .form-select { border-radius: 8px; padding: 10px; background: #fff; border: 1px solid #e7eef7; }
.modal-content { border-radius: 10px; }
.modal-header { background-color: #0d6efd; color: #fff; }
.modal-footer { background: #f1f5f9; }
.btn-outline-secondary, .btn-outline-danger { border-radius: 6px; }
@media (max-width:767px){ .table-fixed th, .table-fixed td{font-size:0.82rem;} }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg">
  <div class="container-fluid px-4">
    <a class="navbar-brand header-brand" href="#"><i class="fa-solid fa-school"></i> Panel Inventario</a>
    <div class="d-flex align-items-center">
      <span class="text-white me-3">Hola, <?= htmlspecialchars($usuario) ?></span>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
    </div>
  </div>
</nav>

<div class="container my-4">
  <!-- Cards -->
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card card-stats">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0">Departamentos</h6>
            <h3 class="mb-0"><?= $total_departamentos ?></h3>
            <p class="small-muted mb-0">Registrados</p>
          </div>
          <div class="ms-3 display-6 text-white"><i class="fa-solid fa-building" style="color:var(--accent-dark)"></i></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-stats">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0">Productos</h6>
            <h3 class="mb-0"><?= $total_productos ?></h3>
            <p class="small-muted mb-0">Registrados</p>
          </div>
          <div class="ms-3 display-6 text-white"><i class="fa-solid fa-box" style="color:var(--accent-dark)"></i></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-stats">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0">Stock bajo</h6>
            <h3 class="mb-0"><?= $stock_bajo ?></h3>
            <p class="small-muted mb-0">Productos &lt; 10 unidades</p>
          </div>
          <div class="ms-3 display-6 text-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card card-stats">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0">Alertas</h6>
            <h3 class="mb-0"><?= $total_alertas ?></h3>
            <p class="small-muted mb-0">Pendientes</p>
          </div>
          <div class="ms-3 display-6 text-warning"><i class="fa-solid fa-bell"></i></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Nav pills -->
  <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
    <li class="nav-item"><button class="nav-link active" id="pills-dep-tab" data-bs-toggle="pill" data-bs-target="#pills-dep" type="button">Departamentos</button></li>
    <li class="nav-item"><button class="nav-link" id="pills-cat-tab" data-bs-toggle="pill" data-bs-target="#pills-cat" type="button">Categorías</button></li>
    <li class="nav-item"><button class="nav-link" id="pills-prod-tab" data-bs-toggle="pill" data-bs-target="#pills-prod" type="button">Productos</button></li>
    <li class="nav-item"><button class="nav-link" id="pills-stock-tab" data-bs-toggle="pill" data-bs-target="#pills-stock" type="button">Stock</button></li>
    <li class="nav-item"><button class="nav-link" id="pills-act-tab" data-bs-toggle="pill" data-bs-target="#pills-act" type="button">Activos</button></li>
    <li class="nav-item"><button class="nav-link" id="pills-alert-tab" data-bs-toggle="pill" data-bs-target="#pills-alert" type="button">Alertas</button></li>
  </ul>

  <div class="tab-content" id="pills-tabContent">
    <!-- ========== DEPARTAMENTOS TAB ========== -->
    <div class="tab-pane fade show active" id="pills-dep" role="tabpanel">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Departamentos</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateDep"><i class="fa-solid fa-plus"></i> Nuevo</button>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-fixed">
          <thead class="table-light">
            <tr><th>ID</th><th>Nombre</th><th>session</th><th>Descripción</th><th>Acciones</th></tr>
          </thead>
          <tbody>
            <?php if ($departamentos && $departamentos->num_rows > 0): ?>
              <?php $departamentos->data_seek(0); while($d = $departamentos->fetch_assoc()): ?>
                <tr>
                  <td><?= $d['id_dep'] ?></td>
                  <td><?= htmlspecialchars($d['nombre_dep']) ?></td>
                  
<td><?= htmlspecialchars($d['nombre_dep']) ?></td>


                  <td><?= htmlspecialchars($d['descripcion']) ?></td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-outline-secondary" onclick='openEditDep(<?= json_encode($d, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick='openDeleteDep(<?= $d['id_dep'] ?>)'><i class="fa-solid fa-trash"></i></button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="5" class="text-center text-muted">No hay departamentos registrados</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ========== CATEGORÍAS TAB ========== -->
    <div class="tab-pane fade" id="pills-cat" role="tabpanel">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Categorías</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateCat"><i class="fa-solid fa-plus"></i> Nuevo</button>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-fixed">
          <thead class="table-light"><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php if ($categorias && $categorias->num_rows > 0): ?>
              <?php $categorias->data_seek(0); while($c = $categorias->fetch_assoc()): ?>
                <tr>
                  <td><?= $c['id_categoria'] ?></td>
                  <td><?= htmlspecialchars($c['nombre_categoria']) ?></td>
                  <td><?= htmlspecialchars($c['descripcion']) ?></td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-outline-secondary" onclick='openEditCat(<?= json_encode($c, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick='openDeleteCat(<?= $c['id_categoria'] ?>)'><i class="fa-solid fa-trash"></i></button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="4" class="text-center text-muted">Sin categorías registradas</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ========== PRODUCTOS TAB ========== -->
    <div class="tab-pane fade" id="pills-prod" role="tabpanel">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Productos</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateProd"><i class="fa-solid fa-plus"></i> Nuevo</button>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-striped align-middle text-center">
          <thead class="table-light">
            <tr><th>ID</th><th>Código</th><th>Nombre</th><th>Categoría</th><th>Departamento</th><th>Precio</th><th>Stock</th><th>Acciones</th></tr>
          </thead>
          <tbody>
            <?php if ($productos && $productos->num_rows > 0): ?>
              <?php $productos->data_seek(0); while($p = $productos->fetch_assoc()): ?>
                <tr>
                  <td><?= $p['id_producto'] ?></td>
                  <td><?= htmlspecialchars($p['codigo']) ?></td>
                  <td><?= htmlspecialchars($p['nombre']) ?></td>
                  <td><?= htmlspecialchars($p['nombre_categoria']) ?></td>
                  <td><?= htmlspecialchars($p['nombre_dep']) ?></td>
                  <td>$<?= number_format($p['precio'],2) ?></td>
                  <td><?= $p['cantidad'] ?? 0 ?></td>
                  <td>
                    <button class="btn btn-sm btn-outline-secondary" onclick='openEditProd(<?= json_encode($p, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick='openDeleteProd(<?= $p['id_producto'] ?>)'><i class="fa-solid fa-trash"></i></button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="8" class="text-center text-muted">Sin productos registrados</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ========== STOCK TAB ========== -->
    <div class="tab-pane fade" id="pills-stock" role="tabpanel">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Stock</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUpdateStock"><i class="fa-solid fa-plus"></i> Actualizar</button>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-fixed">
          <thead class="table-light"><tr><th>ID</th><th>Producto</th><th>Cantidad</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php if ($stocks && $stocks->num_rows > 0): ?>
              <?php $stocks->data_seek(0); while($s = $stocks->fetch_assoc()): ?>
                <tr>
                  <td><?= $s['id_stock'] ?></td>
                  <td><?= htmlspecialchars($s['producto']) ?></td>
                  <td><?= $s['cantidad'] ?></td>
                  <td><button class="btn btn-sm btn-outline-danger" onclick='openDeleteStock(<?= $s['id_stock'] ?>)'><i class="fa-solid fa-trash"></i></button></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="4" class="text-center text-muted">Sin registros de stock</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ========== ACTIVOS TAB ========== -->
    <div class="tab-pane fade" id="pills-act" role="tabpanel">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Activos</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateAct"><i class="fa-solid fa-plus"></i> Nuevo</button>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-fixed">
          <thead class="table-light"><tr><th>ID</th><th>Código</th><th>Nombre</th><th>Categoría</th><th>Descripción</th><th>Fecha Ingreso</th><th>Acciones</th></tr></thead>
          <tbody>
            <?php if ($activos && $activos->num_rows > 0): ?>
              <?php $activos->data_seek(0); while($a = $activos->fetch_assoc()): ?>
                <tr>
                  <td><?= $a['id'] ?></td>
                  <td><?= htmlspecialchars($a['codigo']) ?></td>
                  <td><?= htmlspecialchars($a['nombre']) ?></td>
                  <td><?= htmlspecialchars($a['nombre_categoria']) ?></td>
                  <td><?= htmlspecialchars($a['descripcion']) ?></td>
                  <td><?= htmlspecialchars($a['fecha_ingreso']) ?></td>
                  <td>
                    <button class="btn btn-sm btn-outline-secondary" onclick='openEditAct(<?= json_encode($a, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick='openDeleteAct(<?= $a['id'] ?>)'><i class="fa-solid fa-trash"></i></button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="7" class="text-center text-muted">Sin activos registrados</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ========== ALERTAS TAB ========== -->
    <div class="tab-pane fade" id="pills-alert" role="tabpanel">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Alertas de Stock</h5>
        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateAlert"><i class="fa-solid fa-bell"></i> Nueva</button>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-fixed">
          <thead class="table-light"><tr><th>ID</th><th>Producto</th><th>Mensaje</th><th>Fecha</th><th>Acción</th></tr></thead>
          <tbody>
            <?php if ($alertas && $alertas->num_rows > 0): ?>
              <?php $alertas->data_seek(0); while($al = $alertas->fetch_assoc()): ?>
                <tr>
                  <td><?= $al['id_alerta'] ?></td>
                  <td><?= htmlspecialchars($al['producto']) ?></td>
                  <td><?= htmlspecialchars($al['mensaje']) ?></td>
                  <td><?= htmlspecialchars($al['fecha_alerta']) ?></td>
                  <td><button class="btn btn-sm btn-outline-danger" onclick='openDeleteAlert(<?= $al['id_alerta'] ?>)'><i class="fa-solid fa-trash"></i></button></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="5" class="text-center text-muted">Sin alertas registradas</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div> <!-- end tab-content -->
</div> <!-- end container -->

<!-- ============================
     MODALES (todos apuntan a panel.php)
     - Usamos name="action" para procesar arriba
============================ -->

<!-- ---------- Departamentos: Create / Edit / Delete ---------- -->
<div class="modal fade" id="modalCreateDep" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Nuevo Departamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="create_dep">
    <div class="mb-3"><label class="form-label">Nombre</label>
      <input type="text" class="form-control" name="nombre_dep" required placeholder="Ej: Biblioteca"></div>
    <div class="mb-3"><label class="form-label">Session</label>
      <select class="form-select" name="estilo_dep" required>
        <option value="Básica">Básica</option>
        <option value="Media">Media</option>
        <option value="Épica">Épica</option>
      </select>
    </div>
    <div class="mb-3"><label class="form-label">Descripción</label>
      <textarea class="form-control" name="descripcion_dep" rows="3"></textarea>
    </div>
  </div>
  <div class="modal-footer"><button class="btn btn-success" type="submit">Guardar</button></div>
</form></div></div>

<div class="modal fade" id="modalEditDep" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Editar Departamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="edit_dep">
    <input type="hidden" name="id_dep" id="edit_dep_id">
    <div class="mb-3"><label class="form-label">Nombre</label><input type="text" class="form-control" name="nombre_dep" id="edit_dep_nombre" required></div>
    <div class="mb-3"><label class="form-label">Session</label>
      <select class="form-select" name="estilo_dep" id="edit_dep_estilo" required>
        <option value="Básica">Básica</option>
        <option value="Media">Media</option>
        <option value="Épica">Épica</option>
      </select>
    </div>
    <div class="mb-3"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion_dep" id="edit_dep_descripcion" rows="3"></textarea></div>
  </div>
  <div class="modal-footer"><button class="btn btn-success" type="submit">Actualizar</button></div>
</form></div></div>

<div class="modal fade" id="modalDeleteDep" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Eliminar Departamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="delete_dep">
    <input type="hidden" name="id_dep" id="del_dep_id">
    <p>¿Desea eliminar este departamento?</p>
  </div>
  <div class="modal-footer"><button class="btn btn-danger" type="submit">Eliminar</button></div>
</form></div></div>

<!-- ---------- Categorías: Create / Edit / Delete ---------- -->
<div class="modal fade" id="modalCreateCat" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Nueva Categoría</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="create_cat">
    <div class="mb-3"><label class="form-label">Nombre</label><input class="form-control" name="nombre_cat" required></div>
    <div class="mb-3"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion_cat"></textarea></div>
  </div>
  <div class="modal-footer"><button class="btn btn-success" type="submit">Guardar</button></div>
</form></div></div>

<div class="modal fade" id="modalEditCat" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Editar Categoría</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="edit_cat">
    <input type="hidden" name="id_cat" id="edit_cat_id">
    <div class="mb-3"><label class="form-label">Nombre</label><input class="form-control" name="nombre_cat" id="edit_cat_nombre" required></div>
    <div class="mb-3"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion_cat" id="edit_cat_descripcion"></textarea></div>
  </div>
  <div class="modal-footer"><button class="btn btn-success" type="submit">Actualizar</button></div>
</form></div></div>

<div class="modal fade" id="modalDeleteCat" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Eliminar Categoría</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="delete_cat">
    <input type="hidden" name="id_cat" id="del_cat_id">
    <p>¿Desea eliminar esta categoría?</p>
  </div>
  <div class="modal-footer"><button class="btn btn-danger" type="submit">Eliminar</button></div>
</form></div></div>

<!-- ---------- Productos: Create / Edit / Delete ---------- -->
<div class="modal fade" id="modalCreateProd" tabindex="-1"><div class="modal-dialog modal-lg"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Nuevo Producto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="create_prod">
    <div class="row g-2">
      <div class="col-md-6"><label class="form-label">Código</label><input class="form-control" name="codigo_prod" value="<?= generarCodigoProducto() ?>" readonly></div>
      <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control" name="nombre_prod" required></div>
      <div class="col-md-6"><label class="form-label">Categoría</label>
        <select class="form-select" name="id_categoria_prod" required>
          <option value="">Seleccione...</option>
          <?php $categorias->data_seek(0); while($c = $categorias->fetch_assoc()): ?>
            <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-6"><label class="form-label">Departamento</label>
        <select class="form-select" name="id_dep_prod" required>
          <option value="">Seleccione...</option>
          <?php $departamentos->data_seek(0); while($d = $departamentos->fetch_assoc()): ?>
            <option value="<?= $d['id_dep'] ?>"><?= htmlspecialchars($d['nombre_dep']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-6"><label class="form-label">Precio</label><input type="number" step="0.01" class="form-control" name="precio_prod" required></div>
      <div class="col-md-6"><label class="form-label">Stock inicial</label><input type="number" class="form-control" name="cantidad_prod" value="0" required></div>
      <div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion_prod"></textarea></div>
    </div>
  </div>
  <div class="modal-footer"><button class="btn btn-success" type="submit">Guardar</button></div>
</form></div></div>

<div class="modal fade" id="modalEditProd" tabindex="-1"><div class="modal-dialog modal-lg"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Editar Producto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="edit_prod">
    <input type="hidden" name="id_prod" id="edit_prod_id">
    <div class="row g-2">
      <div class="col-md-6"><label class="form-label">Código</label><input class="form-control" id="edit_prod_codigo" name="codigo_prod" readonly></div>
      <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control" id="edit_prod_nombre" name="nombre_prod" required></div>
      <div class="col-md-6"><label class="form-label">Categoría</label>
        <select class="form-select" id="edit_prod_categoria" name="id_categoria_prod" required>
          <option value="">Seleccione...</option>
          <?php $categorias->data_seek(0); while($c = $categorias->fetch_assoc()): ?>
            <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-6"><label class="form-label">Departamento</label>
        <select class="form-select" id="edit_prod_dep" name="id_dep_prod" required>
          <option value="">Seleccione...</option>
          <?php $departamentos->data_seek(0); while($d = $departamentos->fetch_assoc()): ?>
            <option value="<?= $d['id_dep'] ?>"><?= htmlspecialchars($d['nombre_dep']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-6"><label class="form-label">Precio</label><input type="number" step="0.01" class="form-control" id="edit_prod_precio" name="precio_prod" required></div>
      <div class="col-md-6"><label class="form-label">Stock</label><input type="number" class="form-control" id="edit_prod_cantidad" name="cantidad_prod" required></div>
      <div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" id="edit_prod_descripcion" name="descripcion_prod"></textarea></div>
    </div>
  </div>
  <div class="modal-footer"><button class="btn btn-success" type="submit">Actualizar</button></div>
</form></div></div>

<div class="modal fade" id="modalDeleteProd" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Eliminar Producto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="delete_prod">
    <input type="hidden" name="id_prod" id="del_prod_id">
    <p>¿Desea eliminar este producto?</p>
  </div>
  <div class="modal-footer"><button class="btn btn-danger" type="submit">Eliminar</button></div>
</form></div></div>

<!-- ---------- Stock: Update / Delete ---------- -->
<div class="modal fade" id="modalUpdateStock" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Actualizar Stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="update_stock">
    <div class="mb-3"><label class="form-label">Producto</label>
      <select class="form-select" name="id_producto_stock" required>
        <option value="">Seleccione...</option>
        <?php $productos->data_seek(0); while($p = $productos->fetch_assoc()): ?>
          <option value="<?= $p['id_producto'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="mb-3"><label class="form-label">Cantidad</label><input type="number" class="form-control" name="cantidad_stock" required></div>
  </div>
  <div class="modal-footer"><button class="btn btn-success" type="submit">Guardar</button></div>
</form></div></div>

<div class="modal fade" id="modalDeleteStock" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Eliminar Stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="delete_stock">
    <input type="hidden" name="id_stock" id="del_stock_id">
    <p>¿Desea eliminar este registro de stock?</p>
  </div>
  <div class="modal-footer"><button class="btn btn-danger" type="submit">Eliminar</button></div>
</form></div></div>

<!-- ---------- Activos: Create / Edit / Delete ---------- -->
<div class="modal fade" id="modalCreateAct" tabindex="-1"><div class="modal-dialog modal-lg"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Nuevo Activo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="create_act">
    <div class="row g-2">
      <div class="col-md-6"><label class="form-label">Código</label><input class="form-control" name="codigo_act" required></div>
      <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control" name="nombre_act" required></div>
      <div class="col-md-6"><label class="form-label">Categoría</label>
        <select class="form-select" name="id_categoria_act" required>
          <option value="">Seleccione...</option>
          <?php $categorias->data_seek(0); while($c = $categorias->fetch_assoc()): ?>
            <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-6"><label class="form-label">Fecha Ingreso</label><input type="date" class="form-control" name="fecha_ingreso_act" required></div>
      <div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion_act"></textarea></div>
    </div>
  </div>
  <div class="modal-footer"><button class="btn btn-success" type="submit">Guardar</button></div>
</form></div></div>

<div class="modal fade" id="modalEditAct" tabindex="-1"><div class="modal-dialog modal-lg"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Editar Activo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="edit_act">
    <input type="hidden" name="id_act" id="edit_act_id">
    <div class="row g-2">
      <div class="col-md-6"><label class="form-label">Código</label><input class="form-control" id="edit_act_codigo" name="codigo_act" readonly></div>
      <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control" id="edit_act_nombre" name="nombre_act" required></div>
      <div class="col-md-6"><label class="form-label">Categoría</label>
        <select class="form-select" id="edit_act_categoria" name="id_categoria_act" required>
          <option value="">Seleccione...</option>
          <?php $categorias->data_seek(0); while($c = $categorias->fetch_assoc()): ?>
            <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="col-md-6"><label class="form-label">Fecha Ingreso</label><input class="form-control" id="edit_act_fecha" name="fecha_ingreso_act" type="date" required></div>
      <div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" id="edit_act_descripcion" name="descripcion_act"></textarea></div>
    </div>
  </div>
  <div class="modal-footer"><button class="btn btn-success" type="submit">Actualizar</button></div>
</form></div></div>

<div class="modal fade" id="modalDeleteAct" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Eliminar Activo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="delete_act">
    <input type="hidden" name="id_act" id="del_act_id">
    <p>¿Desea eliminar este activo?</p>
  </div>
  <div class="modal-footer"><button class="btn btn-danger" type="submit">Eliminar</button></div>
</form></div></div>

<!-- ---------- Alertas: Create / Delete ---------- -->
<div class="modal fade" id="modalCreateAlert" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Nueva Alerta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="create_alert">
    <div class="mb-3"><label class="form-label">Producto</label>
      <select class="form-select" name="id_producto_alert" required>
        <option value="">Seleccione...</option>
        <?php $productos->data_seek(0); while($pp = $productos->fetch_assoc()): ?>
          <option value="<?= $pp['id_producto'] ?>"><?= htmlspecialchars($pp['nombre']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="mb-3"><label class="form-label">Mensaje</label><textarea class="form-control" name="mensaje_alert" required></textarea></div>
    <div class="mb-3"><label class="form-label">Fecha</label><input class="form-control" type="datetime-local" name="fecha_alert"></div>
  </div>
  <div class="modal-footer"><button class="btn btn-warning" type="submit">Crear</button></div>
</form></div></div>

<div class="modal fade" id="modalDeleteAlert" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="panel.php">
  <div class="modal-header"><h5 class="modal-title">Eliminar Alerta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <input type="hidden" name="action" value="delete_alert">
    <input type="hidden" name="id_alert" id="del_alert_id">
    <p>¿Desea eliminar esta alerta?</p>
  </div>
  <div class="modal-footer"><button class="btn btn-danger" type="submit">Eliminar</button></div>
</form></div></div>

<!-- =========================
     SCRIPTS JS (abrir modales y rellenar)
     - Evita errores de comillas con JSON_HEX_* en el backend
========================= -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Departamentos
function openEditDep(data){
  document.getElementById('edit_dep_id').value = data.id_dep;
  document.getElementById('edit_dep_nombre').value = data.nombre_dep;
  document.getElementById('edit_dep_estilo').value = data.estilo;
  document.getElementById('edit_dep_descripcion').value = data.descripcion;
  new bootstrap.Modal(document.getElementById('modalEditDep')).show();
}
function openDeleteDep(id){
  document.getElementById('del_dep_id').value = id;
  new bootstrap.Modal(document.getElementById('modalDeleteDep')).show();
}

// Categorías
function openEditCat(data){
  document.getElementById('edit_cat_id').value = data.id_categoria;
  document.getElementById('edit_cat_nombre').value = data.nombre_categoria;
  document.getElementById('edit_cat_descripcion').value = data.descripcion;
  new bootstrap.Modal(document.getElementById('modalEditCat')).show();
}
function openDeleteCat(id){
  document.getElementById('del_cat_id').value = id;
  new bootstrap.Modal(document.getElementById('modalDeleteCat')).show();
}

// Productos
function openEditProd(data){
  document.getElementById('edit_prod_id').value = data.id_producto;
  document.getElementById('edit_prod_codigo').value = data.codigo;
  document.getElementById('edit_prod_nombre').value = data.nombre;
  document.getElementById('edit_prod_precio').value = data.precio;
  document.getElementById('edit_prod_cantidad').value = data.cantidad ?? 0;
  document.getElementById('edit_prod_descripcion').value = data.descripcion;
  document.getElementById('edit_prod_categoria').value = data.id_categoria;
  document.getElementById('edit_prod_dep').value = data.id_dep;
  new bootstrap.Modal(document.getElementById('modalEditProd')).show();
}
function openDeleteProd(id){
  document.getElementById('del_prod_id').value = id;
  new bootstrap.Modal(document.getElementById('modalDeleteProd')).show();
}

// Stock
function openDeleteStock(id){
  document.getElementById('del_stock_id').value = id;
  new bootstrap.Modal(document.getElementById('modalDeleteStock')).show();
}

// Activos
function openEditAct(data){
  document.getElementById('edit_act_id').value = data.id;
  document.getElementById('edit_act_codigo').value = data.codigo;
  document.getElementById('edit_act_nombre').value = data.nombre;
  document.getElementById('edit_act_categoria').value = data.id_categoria;
  document.getElementById('edit_act_fecha').value = data.fecha_ingreso;
  document.getElementById('edit_act_descripcion').value = data.descripcion;
  new bootstrap.Modal(document.getElementById('modalEditAct')).show();
}
function openDeleteAct(id){
  document.getElementById('del_act_id').value = id;
  new bootstrap.Modal(document.getElementById('modalDeleteAct')).show();
}

// Alertas
function openDeleteAlert(id){
  document.getElementById('del_alert_id').value = id;
  new bootstrap.Modal(document.getElementById('modalDeleteAlert')).show();
}
</script>
</body>
</html>
