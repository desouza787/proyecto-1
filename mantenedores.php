<?php
// mantenedores.php - Final (parte 1/3)
// Integrado para la BD real (cmrj2) con $conn desde db.php
session_start();
require_once "db.php";               // debe exponer $conn (mysqli)
require_once "includes/header.php";  // tu header (nav/aside)
require_once "includes/funciones.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$usuario_actual = $_SESSION['usuario'];
$rol = $_SESSION['rol'] ?? 'Usuario';

// -------------------------
// Manejo de acciones POST (CRUD)
// -------------------------
$toast = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $entity = $_POST['entity'] ?? '';

    // Helper: safe param
    function p($k) { return $_POST[$k] ?? null; }

    try {
        // --- MARCA ---
        if ($entity === 'marca') {
            if ($action === 'add') {
                $nombre = trim(p('nombre'));
                if (!$nombre) throw new Exception("Nombre requerido.");
                $stmt = $conn->prepare("INSERT INTO marcas (nombre) VALUES (?)");
                $stmt->bind_param("s",$nombre); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Marca agregada."];
            } elseif ($action === 'edit') {
                $id = intval(p('id_marca')); $nombre = trim(p('nombre'));
                if (!$id) throw new Exception("ID inválido.");
                $stmt = $conn->prepare("UPDATE marcas SET nombre=? WHERE id_marca=?");
                $stmt->bind_param("si",$nombre,$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Marca actualizada."];
            } elseif ($action === 'delete') {
                $id = intval(p('id_marca'));
                $c = $conn->query("SELECT COUNT(*) as c FROM modelos WHERE id_marca=$id")->fetch_assoc()['c'] ?? 0;
                if ($c>0) throw new Exception("No se puede eliminar. Tiene modelos asociados ($c).");
                $stmt = $conn->prepare("DELETE FROM marcas WHERE id_marca=?"); $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Marca eliminada."];
            }
        }

        // --- MODELO ---
        if ($entity === 'modelo') {
            if ($action === 'add') {
                $id_marca = intval(p('id_marca')); $nombre = trim(p('nombre'));
                if (!$id_marca || !$nombre) throw new Exception("Marca y nombre son requeridos.");
                $stmt = $conn->prepare("INSERT INTO modelos (id_marca,nombre) VALUES (?,?)");
                $stmt->bind_param("is",$id_marca,$nombre); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Modelo agregado."];
            } elseif ($action === 'edit') {
                $id = intval(p('id_modelo')); $id_marca = intval(p('id_marca')); $nombre = trim(p('nombre'));
                if (!$id) throw new Exception("ID inválido.");
                $stmt = $conn->prepare("UPDATE modelos SET id_marca=?, nombre=? WHERE id_modelo=?");
                $stmt->bind_param("isi",$id_marca,$nombre,$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Modelo actualizado."];
            } elseif ($action === 'delete') {
                $id = intval(p('id_modelo'));
                $stmt = $conn->prepare("DELETE FROM modelos WHERE id_modelo=?"); $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Modelo eliminado."];
            }
        }

        // --- CATEGORIA ---
        if ($entity === 'categoria') {
            if ($action === 'add') {
                $nombre = trim(p('nombre')); $desc = trim(p('descripcion'));
                $stmt = $conn->prepare("INSERT INTO categorias (nombre_categoria, descripcion) VALUES (?,?)");
                $stmt->bind_param("ss",$nombre,$desc); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Categoría agregada."];
            } elseif ($action === 'edit') {
                $id = intval(p('id_categoria')); $nombre = trim(p('nombre')); $desc = trim(p('descripcion'));
                $stmt = $conn->prepare("UPDATE categorias SET nombre_categoria=?, descripcion=? WHERE id_categoria=?");
                $stmt->bind_param("ssi",$nombre,$desc,$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Categoría actualizada."];
            } elseif ($action === 'delete') {
                $id = intval(p('id_categoria'));
                $c = $conn->query("SELECT COUNT(*) as c FROM equipos WHERE id_categoria=$id")->fetch_assoc()['c'] ?? 0;
                if ($c>0) throw new Exception("No se puede eliminar. Tiene equipos asociados ($c).");
                $stmt = $conn->prepare("DELETE FROM categorias WHERE id_categoria=?"); $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Categoría eliminada."];
            }
        }

        // --- UBICACION ---
        if ($entity === 'ubicacion') {
            if ($action === 'add') {
                $nivel = trim(p('nivel')); $nombre = trim(p('nombre')); $desc = trim(p('descripcion'));
                $stmt = $conn->prepare("INSERT INTO ubicaciones (nivel,nombre,descripcion) VALUES (?,?,?)");
                $stmt->bind_param("sss",$nivel,$nombre,$desc); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Ubicación agregada."];
            } elseif ($action === 'edit') {
                $id = intval(p('id_ubicacion')); $nivel = trim(p('nivel')); $nombre = trim(p('nombre')); $desc = trim(p('descripcion'));
                $stmt = $conn->prepare("UPDATE ubicaciones SET nivel=?, nombre=?, descripcion=? WHERE id_ubicacion=?");
                $stmt->bind_param("sssi",$nivel,$nombre,$desc,$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Ubicación actualizada."];
            } elseif ($action === 'delete') {
                $id = intval(p('id_ubicacion'));
                $c = $conn->query("SELECT COUNT(*) as c FROM equipos WHERE id_ubicacion=$id")->fetch_assoc()['c'] ?? 0;
                if ($c>0) throw new Exception("No se puede eliminar. Tiene equipos asociados ($c).");
                $stmt = $conn->prepare("DELETE FROM ubicaciones WHERE id_ubicacion=?"); $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Ubicación eliminada."];
            }
        }

        // --- DEPARTAMENTO ---
        if ($entity === 'departamento') {
            if ($action === 'add') {
                $nombre = trim(p('nombre')); $tipo = trim(p('tipo')); $desc = trim(p('descripcion'));
                $stmt = $conn->prepare("INSERT INTO departamentos (nombre_dep,tipo,descripcion) VALUES (?,?,?)");
                $stmt->bind_param("sss",$nombre,$tipo,$desc); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Departamento agregado."];
            } elseif ($action === 'edit') {
                $id = intval(p('id_dep')); $nombre = trim(p('nombre')); $tipo = trim(p('tipo')); $desc = trim(p('descripcion'));
                $stmt = $conn->prepare("UPDATE departamentos SET nombre_dep=?, tipo=?, descripcion=? WHERE id_dep=?");
                $stmt->bind_param("sssi",$nombre,$tipo,$desc,$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Departamento actualizado."];
            } elseif ($action === 'delete') {
                $id = intval(p('id_dep'));
                $c = $conn->query("SELECT COUNT(*) as c FROM equipos WHERE id_dep=$id")->fetch_assoc()['c'] ?? 0;
                if ($c>0) throw new Exception("No se puede eliminar. Tiene equipos asociados ($c).");
                $stmt = $conn->prepare("DELETE FROM departamentos WHERE id_dep=?"); $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Departamento eliminado."];
            }
        }

        // --- USUARIOS ---
        if ($entity === 'usuario' || $entity === 'usuarios') { // aceptar cualquiera de los dos si llega
            if ($action === 'add') {
                $nombre = trim(p('nombre')); $email = trim(p('email')); $password = trim(p('password')); $rolnuevo = trim(p('rol'));
                if (!$nombre || !$email || !$password) throw new Exception("Todos los campos son requeridos.");
                // Optional: check email unique
                $chk = $conn->prepare("SELECT id FROM usuarios WHERE email=? LIMIT 1");
                $chk->bind_param("s",$email); $chk->execute(); $chk->store_result();
                if ($chk->num_rows) { $chk->close(); throw new Exception("Email ya registrado."); }
                $chk->close();

                $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO usuarios (nombre,email,password,rol) VALUES (?,?,?,?)");
                $stmt->bind_param("ssss",$nombre,$email,$pass_hash,$rolnuevo); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Usuario creado."];
            } elseif ($action === 'edit') {
                $id = intval(p('id')); $nombre = trim(p('nombre')); $email = trim(p('email')); $rolnuevo = trim(p('rol'));
                $sql = "UPDATE usuarios SET nombre=?, email=?, rol=? WHERE id=?";
                $stmt = $conn->prepare($sql); $stmt->bind_param("sssi",$nombre,$email,$rolnuevo,$id); $stmt->execute(); $stmt->close();
                if (!empty(p('password'))) {
                    $pass_hash = password_hash(p('password'), PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE usuarios SET password=? WHERE id=?"); $stmt->bind_param("si",$pass_hash,$id); $stmt->execute(); $stmt->close();
                }
                $toast = ['type'=>'success','msg'=>"Usuario actualizado."];
            } elseif ($action === 'delete') {
                $id = intval(p('id'));
                if ($id == ($_SESSION['id'] ?? 0)) throw new Exception("No puedes eliminar tu propia cuenta.");
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE id=?"); $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Usuario eliminado."];
            }
        }

    } catch (Exception $ex) {
        $toast = ['type'=>'error','msg'=>$ex->getMessage()];
    }

    $_SESSION['mto_toast'] = $toast;
    header("Location: mantenedores.php");
    exit;
}

// Recuperar toast guardado luego del redirect
if (isset($_SESSION['mto_toast'])) {
    $toast = $_SESSION['mto_toast'];
    unset($_SESSION['mto_toast']);
}

// -------------------------
// Lecturas necesarias para listados y selects
// -------------------------
$marcas = $conn->query("SELECT id_marca,nombre FROM marcas ORDER BY nombre ASC");
$modelos = $conn->query("SELECT id_modelo,id_marca,nombre FROM modelos ORDER BY nombre ASC");
$categorias = $conn->query("SELECT id_categoria,nombre_categoria,descripcion FROM categorias ORDER BY nombre_categoria ASC");
$ubicaciones = $conn->query("SELECT id_ubicacion,nivel,nombre FROM ubicaciones ORDER BY nombre ASC");
$departamentos = $conn->query("SELECT id_dep,nombre_dep FROM departamentos ORDER BY nombre_dep ASC");
$sqlUsuarios = "SELECT id, nombre, email, rol FROM usuarios";
$usuarios = $conn->query($sqlUsuarios);
if (!$usuarios) {
    die("ERROR SQL USUARIOS: " . $conn->error);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Mantenedores — Sistema</title>

  <!-- Bootstrap + DataTables + SweetAlert + FontAwesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>

  <!-- Custom styles & animations -->
  <style>
    :root{
      --accent:#12b886;
      --accent-2:#0ea5a4;
      --card-bg: linear-gradient(180deg, rgba(255,255,255,0.85), rgba(250,250,250,0.95));
      --glass: rgba(255,255,255,0.6);
      --soft-shadow: 0 8px 20px rgba(18,184,134,0.08);
    }

    body { background: linear-gradient(180deg,#f4f7fb,#eef6f4); font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; color:#263238; }

    /* Header shimmer */
    .page-head {
      padding: 18px;
      border-radius: 12px;
      background: linear-gradient(90deg, rgba(18,184,134,0.12), rgba(14,165,164,0.04));
      box-shadow: var(--soft-shadow);
      display:flex; gap:12px; align-items:center;
      transition: transform .22s ease, box-shadow .22s ease;
    }
    .page-head:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(18,184,134,0.12); }

    .shimmer {
      background: linear-gradient(90deg, rgba(255,255,255,0.2), rgba(255,255,255,0.6), rgba(255,255,255,0.2));
      background-size: 200% 100%;
      animation: shimmer 2.5s linear infinite;
      border-radius:8px;
    }
    @keyframes shimmer { from { background-position: 200% 0 } to { background-position: -200% 0 } }

    .card-animated { transition: transform .18s ease, box-shadow .18s ease; border-radius: 12px; background: var(--card-bg); }
    .card-animated:hover { transform: translateY(-6px); box-shadow: 0 18px 48px rgba(16,24,40,0.08); }

    .fab {
      position: fixed; right: 28px; bottom: 28px; z-index: 1100;
      background: linear-gradient(135deg,var(--accent),var(--accent-2));
      color:white; width:56px; height:56px; border-radius: 999px;
      display:flex; align-items:center; justify-content:center; box-shadow: 0 10px 30px rgba(16,24,40,0.18);
      cursor:pointer; transition: transform .15s ease;
    }
    .fab:active { transform: scale(.96); }

    /* table row fade-in */
    table.dataTable tbody tr { opacity: 0; transform: translateY(6px); transition: all .35s ease; }
    table.dataTable.loaded tbody tr { opacity: 1; transform: translateY(0); }

    /* subtle badge */
    .badge-soft { background: rgba(18,184,134,0.12); color:var(--accent); border-radius:999px; padding:.36rem .6rem; font-weight:600; }

    /* modal animations */
    .modal .modal-dialog { transition: transform .28s cubic-bezier(.2,.9,.3,1), opacity .2s ease; transform: translateY(24px); opacity: 0; }
    .modal.show .modal-dialog { transform: translateY(0); opacity: 1; }

    /* input focus */
    .form-control:focus { box-shadow: 0 6px 18px rgba(14,165,164,0.08); border-color: var(--accent); outline: none; }

    /* small helpers */
    .micro { font-size: .82rem; color:#6b7280; }
    .section-title { font-weight:700; letter-spacing:.2px; }

    /* animated empty placeholder */
    .empty-card { min-height:80px; display:flex; align-items:center; justify-content:center; color:#9ca3af; font-style:italic; }

    /* responsive tweaks */
    @media (max-width:720px){
      .page-head { flex-direction:column; align-items:flex-start; gap:8px; }
    }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-head w-100 card-animated p-3 d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h4 mb-0"><i class="fa-solid fa-tools text-success"></i> <span class="section-title">Mantenedores</span></h1>
        <div class="micro">Gestiona usuarios, marcas, modelos, categorías, ubicaciones y departamentos</div>
      </div>
      <div class="text-end">
        <div class="badge-soft shimmer">Sistema — <strong><?= htmlspecialchars($usuario_actual) ?></strong></div>
        <div class="micro mt-1">Base: <code class="micro">cmrj2</code></div>
      </div>
    </div>
  </div>

  <?php if($toast): ?>
    <div id="mtoToast" data-type="<?= htmlspecialchars($toast['type']) ?>" data-msg="<?= htmlspecialchars($toast['msg']) ?>"></div>
  <?php endif; ?>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-3" id="tabsMto" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabUsuarios">👤 Usuarios</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabRoles">🔐 Roles</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabMarcas">🏷 Marcas</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabModelos">💻 Modelos</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabCategorias">🗂 Categorías</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabUbicaciones">🧭 Ubicaciones</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabDepartamentos">🏢 Departamentos</a></li>
  </ul>

  <div class="tab-content">
    <!-- Usuarios -->
    <div class="tab-pane fade show active" id="tabUsuarios">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Usuarios</h5>
        <div>
          <button class="btn btn-outline-secondary me-2" onclick="document.location.reload();"><i class="fa-solid fa-sync"></i></button>
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalUsuario" data-mode="add"><i class="fa-solid fa-user-plus"></i> Nuevo usuario</button>
        </div>
      </div>

      <div class="card card-animated p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div><strong>Listado</strong> <span class="micro">— administra cuentas</span></div>
          <div><input id="searchUsuarios" class="form-control form-control-sm" placeholder="Buscar usuario..." style="width:220px"></div>
        </div>

        <table id="dtUsuarios" class="table table-sm table-striped display" style="width:100%">
          <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php while($r = $usuarios->fetch_assoc()): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><?= htmlspecialchars($r['nombre']) ?></td>
              <td><?= htmlspecialchars($r['email']) ?></td>
              <td><?= htmlspecialchars($r['rol']) ?></td>
              <td>
                <button class="btn btn-sm btn-outline-primary btn-edit-user" data-id="<?= $r['id'] ?>" data-nombre="<?= htmlspecialchars($r['nombre']) ?>" data-email="<?= htmlspecialchars($r['email']) ?>" data-rol="<?= htmlspecialchars($r['rol']) ?>"><i class="fa-solid fa-pen-to-square"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-del" data-entity="usuario" data-id="<?= $r['id'] ?>"><i class="fa-solid fa-trash"></i></button>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Roles -->
    <div class="tab-pane fade" id="tabRoles">
      <div class="card card-animated p-3 mb-3">
        <div class="d-flex justify-content-between">
          <div>
            <h6 class="mb-0">Roles y Permisos</h6>
            <div class="micro">Edítalo desde SQL o pide UI dedicada</div>
          </div>
          <div>
            <button id="btn-refresh-roles" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-sync"></i> Refrescar</button>
          </div>
        </div>
        <hr/>
        <table id="dtRoles" class="table table-sm table-striped">
          <thead><tr><th>ID</th><th>Rol</th><th>Módulo</th><th>Ver</th><th>Editar</th><th>Eliminar</th></tr></thead>
          <tbody>
            <?php
            $rp = $conn->query("SELECT * FROM roles_permiso ORDER BY id_permiso DESC");
            if($rp && $rp->num_rows){
                while($row = $rp->fetch_assoc()){
                    echo "<tr><td>{$row['id_permiso']}</td><td>".htmlspecialchars($row['rol'])."</td><td>".htmlspecialchars($row['modulo'])."</td><td>".($row['puede_ver']? '✓':'✕')."</td><td>".($row['puede_editar']? '✓':'✕')."</td><td>".($row['puede_eliminar']? '✓':'✕')."</td></tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='empty-card'>No hay permisos configurados.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MARCAS -->
    <div class="tab-pane fade" id="tabMarcas">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Marcas</h5>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalMarca" data-mode="add"><i class="fa-solid fa-plus"></i> Nueva marca</button>
      </div>
      <div class="card card-animated p-3 mb-3">
        <table id="dtMarcas" class="table table-sm table-striped display">
          <thead><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php $q = $conn->query("SELECT id_marca,nombre FROM marcas ORDER BY nombre ASC"); while($rw = $q->fetch_assoc()): ?>
            <tr>
              <td><?= $rw['id_marca'] ?></td>
              <td><?= htmlspecialchars($rw['nombre']) ?></td>
              <td>
                <button class="btn btn-sm btn-outline-primary btn-edit-marca" data-id="<?= $rw['id_marca'] ?>" data-nombre="<?= htmlspecialchars($rw['nombre']) ?>"><i class="fa-solid fa-pen"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-del" data-entity="marca" data-id="<?= $rw['id_marca'] ?>"><i class="fa-solid fa-trash"></i></button>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODELOS -->
    <div class="tab-pane fade" id="tabModelos">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Modelos</h5>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalModelo" data-mode="add"><i class="fa-solid fa-plus"></i> Nuevo modelo</button>
      </div>
      <div class="card card-animated p-3 mb-3">
        <table id="dtModelos" class="table table-sm table-striped display">
          <thead><tr><th>ID</th><th>Marca</th><th>Modelo</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php
          $q = $conn->query("SELECT m.id_modelo,m.nombre, ma.nombre AS marca, m.id_marca FROM modelos m LEFT JOIN marcas ma ON m.id_marca=ma.id_marca ORDER BY ma.nombre, m.nombre");
          while($rw = $q->fetch_assoc()):
          ?>
            <tr>
              <td><?= $rw['id_modelo'] ?></td>
              <td><?= htmlspecialchars($rw['marca']) ?></td>
              <td><?= htmlspecialchars($rw['nombre']) ?></td>
              <td>
                <button class="btn btn-sm btn-outline-primary btn-edit-modelo" data-id="<?= $rw['id_modelo'] ?>" data-id_marca="<?= intval($rw['id_marca'] ?? 0) ?>" data-nombre="<?= htmlspecialchars($rw['nombre']) ?>"><i class="fa-solid fa-pen"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-del" data-entity="modelo" data-id="<?= $rw['id_modelo'] ?>"><i class="fa-solid fa-trash"></i></button>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CATEGORIAS -->
    <div class="tab-pane fade" id="tabCategorias">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Categorías</h5>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCategoria" data-mode="add"><i class="fa-solid fa-plus"></i> Nueva categoría</button>
      </div>
      <div class="card card-animated p-3 mb-3">
        <table id="dtCategorias" class="table table-sm table-striped display">
          <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php $q = $conn->query("SELECT id_categoria,nombre_categoria,descripcion FROM categorias ORDER BY nombre_categoria"); while($rw = $q->fetch_assoc()): ?>
            <tr>
              <td><?= $rw['id_categoria'] ?></td>
              <td><?= htmlspecialchars($rw['nombre_categoria']) ?></td>
              <td><?= htmlspecialchars($rw['descripcion']) ?></td>
              <td>
                <button class="btn btn-sm btn-outline-primary btn-edit-categoria" data-id="<?= $rw['id_categoria'] ?>" data-nombre="<?= htmlspecialchars($rw['nombre_categoria']) ?>" data-desc="<?= htmlspecialchars($rw['descripcion']) ?>"><i class="fa-solid fa-pen"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-del" data-entity="categoria" data-id="<?= $rw['id_categoria'] ?>"><i class="fa-solid fa-trash"></i></button>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- UBICACIONES -->
    <div class="tab-pane fade" id="tabUbicaciones">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Ubicaciones</h5>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalUbicacion" data-mode="add"><i class="fa-solid fa-plus"></i> Nueva ubicación</button>
      </div>
      <div class="card card-animated p-3 mb-3">
        <table id="dtUbicaciones" class="table table-sm table-striped display">
          <thead><tr><th>ID</th><th>Nivel</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php $q = $conn->query("SELECT id_ubicacion,nivel,nombre,descripcion FROM ubicaciones ORDER BY nombre"); while($rw = $q->fetch_assoc()): ?>
            <tr>
              <td><?= $rw['id_ubicacion'] ?></td>
              <td><?= htmlspecialchars($rw['nivel']) ?></td>
              <td><?= htmlspecialchars($rw['nombre']) ?></td>
              <td><?= htmlspecialchars($rw['descripcion']) ?></td>
              <td>
                <button class="btn btn-sm btn-outline-primary btn-edit-ubicacion" data-id="<?= $rw['id_ubicacion'] ?>" data-nivel="<?= htmlspecialchars($rw['nivel']) ?>" data-nombre="<?= htmlspecialchars($rw['nombre']) ?>" data-desc="<?= htmlspecialchars($rw['descripcion']) ?>"><i class="fa-solid fa-pen"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-del" data-entity="ubicacion" data-id="<?= $rw['id_ubicacion'] ?>"><i class="fa-solid fa-trash"></i></button>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DEPARTAMENTOS -->
    <div class="tab-pane fade" id="tabDepartamentos">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Departamentos</h5>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalDepartamento" data-mode="add"><i class="fa-solid fa-plus"></i> Nuevo departamento</button>
      </div>
      <div class="card card-animated p-3 mb-3">
        <table id="dtDepartamentos" class="table table-sm table-striped display">
          <thead><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Descripción</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php $q = $conn->query("SELECT id_dep,nombre_dep,tipo,descripcion FROM departamentos ORDER BY nombre_dep"); while($rw = $q->fetch_assoc()): ?>
            <tr>
              <td><?= $rw['id_dep'] ?></td>
              <td><?= htmlspecialchars($rw['nombre_dep']) ?></td>
              <td><?= htmlspecialchars($rw['tipo']) ?></td>
              <td><?= htmlspecialchars($rw['descripcion']) ?></td>
              <td>
                <button class="btn btn-sm btn-outline-primary btn-edit-dep" data-id="<?= $rw['id_dep'] ?>" data-nombre="<?= htmlspecialchars($rw['nombre_dep']) ?>" data-tipo="<?= htmlspecialchars($rw['tipo']) ?>" data-desc="<?= htmlspecialchars($rw['descripcion']) ?>"><i class="fa-solid fa-pen"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-del" data-entity="departamento" data-id="<?= $rw['id_dep'] ?>"><i class="fa-solid fa-trash"></i></button>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div> <!-- tab-content -->
</div> <!-- container -->
<!-- ============================
     Modales (solo entidades reales)
     ============================ -->

<!-- Modal Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formUsuario">
      <input type="hidden" name="entity" value="usuario">
      <input type="hidden" name="action" id="usuario_action" value="add">
      <input type="hidden" name="id" id="usuario_id" value="">
      <div class="modal-header"><h5 class="modal-title">Usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nombre</label><input name="nombre" id="usuario_nombre" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Email</label><input name="email" id="usuario_email" type="email" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Rol</label><input name="rol" id="usuario_rol" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Contraseña (solo para nuevo o cambiar)</label><input name="password" id="usuario_password" type="password" class="form-control"></div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> Guardar</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button></div>
    </form>
  </div>
</div>

<!-- Modal Marca -->
<div class="modal fade" id="modalMarca" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formMarca">
      <input type="hidden" name="entity" value="marca">
      <input type="hidden" name="action" id="marca_action" value="add">
      <input type="hidden" name="id_marca" id="marca_id" value="">
      <div class="modal-header"><h5 class="modal-title">Marca</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nombre</label><input name="nombre" id="marca_nombre" class="form-control" required></div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-success">Guardar</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button></div>
    </form>
  </div>
</div>

<!-- Modal Modelo -->
<div class="modal fade" id="modalModelo" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formModelo">
      <input type="hidden" name="entity" value="modelo">
      <input type="hidden" name="action" id="modelo_action" value="add">
      <input type="hidden" name="id_modelo" id="modelo_id" value="">
      <div class="modal-header"><h5 class="modal-title">Modelo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Marca</label>
          <select name="id_marca" id="modelo_id_marca" class="form-control" required>
            <option value="">-- Seleccione --</option>
            <?php $m = $conn->query("SELECT id_marca,nombre FROM marcas ORDER BY nombre"); while($op = $m->fetch_assoc()): ?>
              <option value="<?= $op['id_marca'] ?>"><?= htmlspecialchars($op['nombre']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Nombre</label><input name="nombre" id="modelo_nombre" class="form-control" required></div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-success">Guardar</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button></div>
    </form>
  </div>
</div>

<!-- Modal Categoria -->
<div class="modal fade" id="modalCategoria" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formCategoria">
      <input type="hidden" name="entity" value="categoria">
      <input type="hidden" name="action" id="categoria_action" value="add">
      <input type="hidden" name="id_categoria" id="categoria_id" value="">
      <div class="modal-header"><h5 class="modal-title">Categoría</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nombre</label><input name="nombre" id="categoria_nombre" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Descripción</label><textarea name="descripcion" id="categoria_desc" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-success">Guardar</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button></div>
    </form>
  </div>
</div>

<!-- Modal Ubicacion -->
<div class="modal fade" id="modalUbicacion" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formUbicacion">
      <input type="hidden" name="entity" value="ubicacion">
      <input type="hidden" name="action" id="ubicacion_action" value="add">
      <input type="hidden" name="id_ubicacion" id="ubicacion_id" value="">
      <div class="modal-header"><h5 class="modal-title">Ubicación</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nivel</label><input name="nivel" id="ubicacion_nivel" class="form-control"></div>
        <div class="mb-2"><label class="form-label">Nombre</label><input name="nombre" id="ubicacion_nombre" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Descripción</label><textarea name="descripcion" id="ubicacion_desc" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-success">Guardar</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button></div>
    </form>
  </div>
</div>

<!-- Modal Departamento -->
<div class="modal fade" id="modalDepartamento" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formDepartamento">
      <input type="hidden" name="entity" value="departamento">
      <input type="hidden" name="action" id="dep_action" value="add">
      <input type="hidden" name="id_dep" id="dep_id" value="">
      <div class="modal-header"><h5 class="modal-title">Departamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nombre</label><input name="nombre" id="dep_nombre" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Tipo</label><input name="tipo" id="dep_tipo" class="form-control"></div>
        <div class="mb-2"><label class="form-label">Descripción</label><textarea name="descripcion" id="dep_desc" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-success">Guardar</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button></div>
    </form>
  </div>
</div>

<!-- Floating action button -->
<div class="fab" id="fabQuick" title="Atajos">
  <i class="fa-solid fa-magic"></i>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function(){

  
  // Mark tables as loaded (for animation)
  setTimeout(()=> $('table.display').addClass('loaded'), 300);

  // Toast from server
  const tdiv = document.getElementById('mtoToast');
  if(tdiv){
    const type = tdiv.dataset.type, msg = tdiv.dataset.msg;
    if(type === 'success') Swal.fire({icon:'success',text:msg,toast:true,position:'top-end',timer:2200,showConfirmButton:false});
    else Swal.fire({icon:'error',text:msg,toast:true,position:'top-end',timer:3500,showConfirmButton:false});
  }

  // quick search for usuarios
  $('#searchUsuarios').on('input', function(){
    $('#dtUsuarios').DataTable().search(this.value).draw();
  });

  // Generic delete confirmation
  $('.btn-del').on('click', function(){
    const ent = $(this).data('entity');
    const id = $(this).data('id');
    Swal.fire({
      title: 'Confirmar eliminación',
      html: `<div>¿Eliminar <strong>${ent}</strong> <span class="text-muted">#${id}</span>? <br><small>Esta acción no se puede revertir.</small></div>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then(res=>{
      if(res.isConfirmed){
        const f = $('<form method="POST"></form>');
        f.append(`<input type="hidden" name="entity" value="${ent}">`);
        f.append(`<input type="hidden" name="action" value="delete">`);
        let idname = 'id';
        if(ent === 'marca') idname = 'id_marca';
        else if(ent === 'modelo') idname = 'id_modelo';
        else if(ent === 'categoria') idname = 'id_categoria';
        else if(ent === 'ubicacion') idname = 'id_ubicacion';
        else if(ent === 'departamento') idname = 'id_dep';
        else if(ent === 'usuario') idname = 'id';
        f.append(`<input type="hidden" name="${idname}" value="${id}">`);
        $('body').append(f); f.submit();
      }
    });
  });

  // Modal actions: Usuario
  $('#modalUsuario').on('show.bs.modal', function(e){
    const btn = $(e.relatedTarget);
    const mode = btn.data('mode') || 'add';
    $('#usuario_action').val(mode === 'add' ? 'add' : 'edit');
    if(mode === 'add'){
      $('#usuario_id').val(''); $('#usuario_nombre').val(''); $('#usuario_email').val(''); $('#usuario_rol').val(''); $('#usuario_password').val('');
    }
  });
  $('.btn-edit-user').on('click', function(){
    $('#usuario_id').val($(this).data('id'));
    $('#usuario_nombre').val($(this).data('nombre'));
    $('#usuario_email').val($(this).data('email'));
    $('#usuario_rol').val($(this).data('rol'));
    $('#usuario_action').val('edit'); $('#usuario_password').val('');
    $('#modalUsuario').modal('show');
  });

  // Marca modal
  $('#modalMarca').on('show.bs.modal', function(e){ const mode = $(e.relatedTarget).data('mode') || 'add'; $('#marca_action').val(mode==='add'?'add':'edit'); if(mode==='add'){ $('#marca_id').val(''); $('#marca_nombre').val(''); } });
  $('.btn-edit-marca').on('click', function(){ $('#marca_id').val($(this).data('id')); $('#marca_nombre').val($(this).data('nombre')); $('#marca_action').val('edit'); $('#modalMarca').modal('show'); });

  // Modelo modal
  $('#modalModelo').on('show.bs.modal', function(e){ const mode = $(e.relatedTarget).data('mode') || 'add'; $('#modelo_action').val(mode==='add'?'add':'edit'); if(mode==='add'){ $('#modelo_id').val(''); $('#modelo_id_marca').val(''); $('#modelo_nombre').val(''); } });
  $('.btn-edit-modelo').on('click', function(){ $('#modelo_id').val($(this).data('id')); $('#modelo_id_marca').val($(this).data('id_marca')); $('#modelo_nombre').val($(this).data('nombre')); $('#modelo_action').val('edit'); $('#modalModelo').modal('show'); });

  // Categoria modal
  $('#modalCategoria').on('show.bs.modal', function(e){ const mode = $(e.relatedTarget).data('mode') || 'add'; $('#categoria_action').val(mode==='add'?'add':'edit'); if(mode==='add'){ $('#categoria_id').val(''); $('#categoria_nombre').val(''); $('#categoria_desc').val(''); } });
  $('.btn-edit-categoria').on('click', function(){ $('#categoria_id').val($(this).data('id')); $('#categoria_nombre').val($(this).data('nombre')); $('#categoria_desc').val($(this).data('desc')); $('#categoria_action').val('edit'); $('#modalCategoria').modal('show'); });

  // Ubicacion modal
  $('#modalUbicacion').on('show.bs.modal', function(e){ const mode = $(e.relatedTarget).data('mode') || 'add'; $('#ubicacion_action').val(mode==='add'?'add':'edit'); if(mode==='add'){ $('#ubicacion_id').val(''); $('#ubicacion_nivel').val(''); $('#ubicacion_nombre').val(''); $('#ubicacion_desc').val(''); } });
  $('.btn-edit-ubicacion').on('click', function(){ $('#ubicacion_id').val($(this).data('id')); $('#ubicacion_nivel').val($(this).data('nivel')); $('#ubicacion_nombre').val($(this).data('nombre')); $('#ubicacion_desc').val($(this).data('desc')); $('#ubicacion_action').val('edit'); $('#modalUbicacion').modal('show'); });

  // Departamento modal
  $('#modalDepartamento').on('show.bs.modal', function(e){ const mode = $(e.relatedTarget).data('mode') || 'add'; $('#dep_action').val(mode==='add'?'add':'edit'); if(mode==='add'){ $('#dep_id').val(''); $('#dep_nombre').val(''); $('#dep_tipo').val(''); $('#dep_desc').val(''); } });
  $('.btn-edit-dep').on('click', function(){ $('#dep_id').val($(this).data('id')); $('#dep_nombre').val($(this).data('nombre')); $('#dep_tipo').val($(this).data('tipo')); $('#dep_desc').val($(this).data('desc')); $('#dep_action').val('edit'); $('#modalDepartamento').modal('show'); });

  // Floating quick actions
  $('#fabQuick').on('click', function(){
    Swal.fire({
      title: 'Atajos rápidos',
      html: '<button class="btn btn-success m-1" id="goUser">Nuevo Usuario</button><button class="btn btn-primary m-1" id="goMarca">Nueva Marca</button>',
      showConfirmButton: false,
      showCloseButton: true,
    });
    $(document).on('click','#goUser', function(){ $('#modalUsuario').modal('show'); Swal.close(); });
    $(document).on('click','#goMarca', function(){ $('#modalMarca').modal('show'); Swal.close(); });
  });

  // Prevent double submit (UX)
  $('form').on('submit', function(){ $(this).find('button[type=submit]').prop('disabled',true); });

  // small highlight when switching tabs
  $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    $(e.target).closest('.page-head').addClass('animated-highlight');
    setTimeout(()=> $(e.target).closest('.page-head').removeClass('animated-highlight'), 900);
  });

});
</script>

<?php
require_once "includes/footer.php";
?>
