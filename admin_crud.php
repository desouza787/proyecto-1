<?php
session_start();
require_once "db.php";
require_once "includes/header.php";
require_once "includes/funciones.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

//////////////////////
// --- ANEXOS (antes Departamentos) ---
//////////////////////
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_anexo'])) {
    $nombre_dep = $conn->real_escape_string($_POST['nombre_dep']);
    $tipo = $conn->real_escape_string($_POST['tipo']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $id_dep = intval($_POST['id_dep'] ?? 0);

    if ($id_dep > 0) {
        $sql = "UPDATE departamentos SET nombre_dep='$nombre_dep', tipo='$tipo', descripcion='$descripcion' WHERE id_dep=$id_dep";
    } else {
        $sql = "INSERT INTO departamentos (nombre_dep, tipo, descripcion) VALUES ('$nombre_dep','$tipo','$descripcion')";
    }

    if (!$conn->query($sql)) die("Error al guardar anexo: " . $conn->error);
    header("Location: admin_crud.php");
    exit;
}

if (isset($_GET['eliminar_anexo'])) {
    $id = intval($_GET['eliminar_anexo']);
    $conn->query("DELETE FROM departamentos WHERE id_dep=$id");
    header("Location: admin_crud.php");
    exit;
}

//////////////////////
// --- UBICACIONES ---
//////////////////////
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_ubicacion'])) {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $nivel = $conn->real_escape_string($_POST['nivel']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $id_ubicacion = intval($_POST['id_ubicacion'] ?? 0);

    if ($id_ubicacion > 0) {
        $sql = "UPDATE ubicaciones SET nombre='$nombre', nivel='$nivel', descripcion='$descripcion' WHERE id_ubicacion=$id_ubicacion";
    } else {
        $sql = "INSERT INTO ubicaciones (nombre, nivel, descripcion) VALUES ('$nombre','$nivel','$descripcion')";
    }

    if (!$conn->query($sql)) die("Error al guardar ubicación: " . $conn->error);
    header("Location: admin_crud.php");
    exit;
}

if (isset($_GET['eliminar_ubicacion'])) {
    $id = intval($_GET['eliminar_ubicacion']);
    $conn->query("DELETE FROM ubicaciones WHERE id_ubicacion=$id");
    header("Location: admin_crud.php");
    exit;
}

//////////////////////
// --- CATEGORIAS ---
//////////////////////
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_categoria'])) {
    $nombre = $conn->real_escape_string($_POST['nombre_categoria']);
    $descripcion = $conn->real_escape_string($_POST['descripcion_categoria']);
    $id_categoria = intval($_POST['id_categoria'] ?? 0);

    if ($id_categoria > 0) {
        $sql = "UPDATE categorias SET nombre_categoria='$nombre', descripcion='$descripcion' WHERE id_categoria=$id_categoria";
    } else {
        $sql = "INSERT INTO categorias (nombre_categoria, descripcion) VALUES ('$nombre','$descripcion')";
    }

    if (!$conn->query($sql)) die("Error al guardar categoría: " . $conn->error);
    header("Location: admin_crud.php");
    exit;
}

if (isset($_GET['eliminar_categoria'])) {
    $id = intval($_GET['eliminar_categoria']);
    $conn->query("DELETE FROM categorias WHERE id_categoria=$id");
    header("Location: admin_crud.php");
    exit;
}

// Consultas
$anexos = $conn->query("SELECT * FROM departamentos ORDER BY id_dep ASC");
$ubicaciones = $conn->query("SELECT * FROM ubicaciones ORDER BY id_ubicacion ASC");
$categorias = $conn->query("SELECT * FROM categorias ORDER BY id_categoria ASC");
?>

<div class="container mt-4">
  <h2 class="mb-4 text-center text-primary"><i class="fa-solid fa-gear"></i> Administración General</h2>

  <ul class="nav nav-tabs" id="crudTabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#anexoTab">Anexos</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ubiTab">Ubicaciones</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#catTab">Categorías</a></li>
  </ul>

  <div class="tab-content mt-4">
    <!-- ANEXOS -->
    <div class="tab-pane fade show active" id="anexoTab">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Gestión de Anexos</h5>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAnexo">+ Nuevo</button>
      </div>
      <table class="table table-bordered table-hover">
        <thead class="table-dark"><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Descripción</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php while($d = $anexos->fetch_assoc()): ?>
          <tr>
            <td><?= $d['id_dep'] ?></td>
            <td><?= e($d['nombre_dep']) ?></td>
            <td><?= e($d['tipo']) ?></td>
            <td><?= e($d['descripcion']) ?></td>
            <td>
              <button class="btn btn-primary btn-sm" 
                      data-bs-toggle="modal" 
                      data-bs-target="#modalAnexo"
                      data-id="<?= $d['id_dep'] ?>"
                      data-nombre="<?= e($d['nombre_dep']) ?>"
                      data-tipo="<?= e($d['tipo']) ?>"
                      data-desc="<?= e($d['descripcion']) ?>">✏️</button>
              <a href="?eliminar_anexo=<?= $d['id_dep'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar anexo?')">🗑️</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- UBICACIONES -->
    <div class="tab-pane fade" id="ubiTab">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Gestión de Ubicaciones</h5>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalUbicacion">+ Nueva</button>
      </div>
      <table class="table table-bordered table-hover">
        <thead class="table-dark"><tr><th>ID</th><th>Nombre</th><th>Nivel</th><th>Descripción</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php while($u = $ubicaciones->fetch_assoc()): ?>
          <tr>
            <td><?= $u['id_ubicacion'] ?></td>
            <td><?= e($u['nombre']) ?></td>
            <td><?= e($u['nivel']) ?></td>
            <td><?= e($u['descripcion']) ?></td>
            <td>
              <button class="btn btn-primary btn-sm" 
                      data-bs-toggle="modal" 
                      data-bs-target="#modalUbicacion"
                      data-id="<?= $u['id_ubicacion'] ?>"
                      data-nombre="<?= e($u['nombre']) ?>"
                      data-nivel="<?= e($u['nivel']) ?>"
                      data-desc="<?= e($u['descripcion']) ?>">✏️</button>
              <a href="?eliminar_ubicacion=<?= $u['id_ubicacion'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar ubicación?')">🗑️</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <!-- CATEGORIAS -->
    <div class="tab-pane fade" id="catTab">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Gestión de Categorías</h5>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCategoria">+ Nueva</button>
      </div>
      <table class="table table-bordered table-hover">
        <thead class="table-dark"><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php while($c = $categorias->fetch_assoc()): ?>
          <tr>
            <td><?= $c['id_categoria'] ?></td>
            <td><?= e($c['nombre_categoria']) ?></td>
            <td><?= e($c['descripcion']) ?></td>
            <td>
              <button class="btn btn-primary btn-sm" 
                      data-bs-toggle="modal" 
                      data-bs-target="#modalCategoria"
                      data-id="<?= $c['id_categoria'] ?>"
                      data-nombre="<?= e($c['nombre_categoria']) ?>"
                      data-desc="<?= e($c['descripcion']) ?>">✏️</button>
              <a href="?eliminar_categoria=<?= $c['id_categoria'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar categoría?')">🗑️</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODALES -->
<!-- ANEXO -->
<div class="modal fade" id="modalAnexo" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="">
      <div class="modal-header"><h5 class="modal-title">Anexo</h5></div>
      <div class="modal-body">
        <input type="hidden" name="id_dep" id="dep_id">
        <div class="mb-3"><label>Nombre</label><input type="text" name="nombre_dep" id="dep_nombre" class="form-control" required></div>
        <div class="mb-3"><label>Tipo</label><input type="text" name="tipo" id="dep_tipo" class="form-control"></div>
        <div class="mb-3"><label>Descripción</label><textarea name="descripcion" id="dep_desc" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="guardar_anexo" class="btn btn-success">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- UBICACION -->
<div class="modal fade" id="modalUbicacion" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="">
      <div class="modal-header"><h5 class="modal-title">Ubicación</h5></div>
      <div class="modal-body">
        <input type="hidden" name="id_ubicacion" id="ubi_id">
        <div class="mb-3"><label>Nombre</label><input type="text" name="nombre" id="ubi_nombre" class="form-control" required></div>
        <div class="mb-3"><label>Nivel</label><input type="text" name="nivel" id="ubi_nivel" class="form-control"></div>
        <div class="mb-3"><label>Descripción</label><textarea name="descripcion" id="ubi_desc" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="guardar_ubicacion" class="btn btn-success">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- CATEGORIA -->
<div class="modal fade" id="modalCategoria" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="">
      <div class="modal-header"><h5 class="modal-title">Categoría</h5></div>
      <div class="modal-body">
        <input type="hidden" name="id_categoria" id="cat_id">
        <div class="mb-3"><label>Nombre</label><input type="text" name="nombre_categoria" id="cat_nombre" class="form-control" required></div>
        <div class="mb-3"><label>Descripción</label><textarea name="descripcion_categoria" id="cat_desc" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="guardar_categoria" class="btn btn-success">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('show.bs.modal', function (e) {
  let btn = e.relatedTarget;
  if (!btn) return;

  // ANEXO
  if (btn.dataset.id && e.target.id === 'modalAnexo') {
    document.getElementById('dep_id').value = btn.dataset.id;
    document.getElementById('dep_nombre').value = btn.dataset.nombre;
    document.getElementById('dep_tipo').value = btn.dataset.tipo;
    document.getElementById('dep_desc').value = btn.dataset.desc;
  }

  // UBICACION
  if (btn.dataset.id && e.target.id === 'modalUbicacion') {
    document.getElementById('ubi_id').value = btn.dataset.id;
    document.getElementById('ubi_nombre').value = btn.dataset.nombre;
    document.getElementById('ubi_nivel').value = btn.dataset.nivel;
    document.getElementById('ubi_desc').value = btn.dataset.desc;
  }

  // CATEGORIA
  if (btn.dataset.id && e.target.id === 'modalCategoria') {
    document.getElementById('cat_id').value = btn.dataset.id;
    document.getElementById('cat_nombre').value = btn.dataset.nombre;
    document.getElementById('cat_desc').value = btn.dataset.desc;
  }
});
</script>

<?php require_once "includes/footer.php"; ?>
