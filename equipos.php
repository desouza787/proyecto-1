<?php
// equipos.php (MEJORADO - interfaz moderna, modo oscuro, toasts)
// Mantiene tu lógica original; agrega UI y mejoras sin romper funciones.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "db.php";
require_once "includes/funciones.php";
require_once "includes/header.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

// ---------- AJAX para generar código dinámico ----------
if (isset($_GET['accion']) && $_GET['accion'] === 'generar_codigo') {
    ob_clean();
    $nivel = $_GET['nivel'] ?? '';
    $id_dep = intval($_GET['id_dep'] ?? 0);
    $id_categoria = intval($_GET['id_categoria'] ?? 0);

    if ($nivel && $id_dep && $id_categoria) {
        echo generarCodigoInterno($conn, $nivel, $id_dep, $id_categoria);
    } else {
        echo '';
    }
    exit;
}

// ---------- Consultas para selects ----------
$anexos = $conn->query("SELECT id_dep, nombre_dep FROM departamentos ORDER BY nombre_dep ASC")->fetch_all(MYSQLI_ASSOC);
$categorias = $conn->query("SELECT id_categoria, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC")->fetch_all(MYSQLI_ASSOC);
$ubicaciones = $conn->query("SELECT id_ubicacion, nombre FROM ubicaciones ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);

// ---------- Procesar formulario Agregar Equipo ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_equipo'])) {
    $nivel = $_POST['nivel'] ?? '';
    $id_dep = intval($_POST['id_dep'] ?? 0);
    $id_categoria = intval($_POST['id_categoria'] ?? 0);
    $id_ubicacion = intval($_POST['id_ubicacion'] ?? 0);
    $estado = $_POST['estado'] ?? 'En uso';
    $marca = $conn->real_escape_string($_POST['marca'] ?? '');
    $modelo = $conn->real_escape_string($_POST['modelo'] ?? '');
    $serie = $conn->real_escape_string($_POST['serie'] ?? '');
    $procesador = $conn->real_escape_string($_POST['procesador'] ?? '');
    $ram = $conn->real_escape_string($_POST['ram'] ?? '');
    $disco = $conn->real_escape_string($_POST['disco'] ?? '');
    $so = $conn->real_escape_string($_POST['so'] ?? '');
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    $ultima_revision = !empty($_POST['ultima_revision']) ? $conn->real_escape_string($_POST['ultima_revision']) : NULL;

    $imagen_path = NULL;
    if (!empty($_FILES['imagen']['name'])) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $imagen_path = "uploads/" . uniqid() . ".$ext";
        move_uploaded_file($_FILES['imagen']['tmp_name'], $imagen_path);
    }

    // ---------- Generar código antes de insertar ----------
    $codigo_equipo = generarCodigoInterno($conn, $nivel, $id_dep, $id_categoria);
    unset($_POST['codigo_equipo']);

    if (empty($codigo_equipo)) {
        die("Error: código no generado correctamente.");
    }

    $stmt_check = $conn->prepare("SELECT id_equipo FROM equipos WHERE codigo_equipo=? LIMIT 1");
    $stmt_check->bind_param("s", $codigo_equipo);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    $stmt_check->close();

    if ($res_check && $res_check->num_rows > 0) {
        die("Error: el código '$codigo_equipo' ya existe en la base de datos.");
    }

    // ---------- Insertar equipo ----------
    $stmt = $conn->prepare("INSERT INTO equipos 
        (codigo_equipo, marca, modelo, serie, procesador, ram, disco, so, estado, id_dep, id_categoria, id_ubicacion, nivel, observaciones, imagen, ultima_revision)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $stmt->bind_param(
        "sssssssssiiissss",
        $codigo_equipo,
        $marca,
        $modelo,
        $serie,
        $procesador,
        $ram,
        $disco,
        $so,
        $estado,
        $id_dep,
        $id_categoria,
        $id_ubicacion,
        $nivel,
        $observaciones,
        $imagen_path,
        $ultima_revision
    );

    if (!$stmt->execute()) {
        die("Error al agregar equipo: " . $stmt->error);
    }

    $id_equipo = $conn->insert_id;
    $usuario = $_SESSION['usuario'] ?? 'Sistema';
    $detalle = "Equipo agregado al sistema";
    $conn->query("INSERT INTO historial_estados (id_equipo, estado_anterior, estado_nuevo, usuario, fecha, detalle)
                  VALUES ($id_equipo,'N/A','$estado','$usuario',NOW(),'$detalle')");

    $stmt->close();

    // --- Flash toast para notificación al volver a la página ---
    $_SESSION['toast'] = ['type' => 'success', 'msg' => '✅ Equipo agregado correctamente', 'timeout' => 4000];

    header("Location: equipos.php");
    exit;
}
?>

<!-- ===========================
     ESTILOS LOCALES (diseño moderno, dark mode)
     =========================== -->
<style>
:root{
  --bg:#f6fbff;
  --card:#ffffff;
  --muted:#6c757d;
  --accent:#4b9cdb;
  --text:#15202b;
  --glass: rgba(255,255,255,0.7);
}
body.dark-mode {
  --bg:#071122;
  --card:#071829;
  --muted:#9aa6b2;
  --accent:#5eb4ff;
  --text:#e6eef8;
  --glass: rgba(255,255,255,0.03);
}
.page-wrap { background: linear-gradient(180deg,var(--bg), #eef6ff); padding: 28px 0; min-height:80vh; transition: background 300ms ease; }
.card-soft { background: var(--card); border-radius: 12px; box-shadow: 0 6px 30px rgba(2,6,23,0.06); color:var(--text); }
.header-compact { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; }
.controls { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.controls .filter { display:flex; gap:8px; align-items:center; }
.summary-cards .card { border-radius:12px; transition: transform .18s ease; }
.summary-cards .card:hover { transform: translateY(-6px); box-shadow:0 18px 40px rgba(15,23,34,0.08); }

/* table */
.table thead th { background: linear-gradient(90deg, rgba(0,0,0,0.03), rgba(0,0,0,0.02)); }
.table tbody tr { transition: background 160ms ease, transform 120ms ease; }
.table tbody tr:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(2,6,23,0.04); background: var(--glass); }

/* form modal */
.modal .modal-content { border-radius:12px; overflow:hidden; }
.form-section { padding:12px; border-radius:8px; background: linear-gradient(180deg, rgba(0,0,0,0.02), rgba(0,0,0,0.01)); }

/* image preview */
.img-preview { width:80px; height:80px; object-fit:cover; border-radius:8px; border:1px solid rgba(0,0,0,0.06); }

/* theme toggle */
.theme-btn { border-radius: 8px; padding:8px 10px; background:var(--card); border:1px solid rgba(0,0,0,0.04); cursor:pointer; box-shadow:0 4px 14px rgba(2,6,23,0.03); }

/* toast location */
#app-toast { position: fixed; right: 20px; bottom: 20px; z-index: 12000; }

/* responsive tweaks */
@media (max-width: 767px) {
  .header-compact { flex-direction:column; align-items:flex-start; gap:8px; }
}
</style>

<div class="page-wrap">
  <div class="container">
    <!-- HEADER -->
    <div class="header-compact">
      <div>
        <h3 class="mb-0">💻 Gestión de Equipos</h3>
        <div class="small-muted">Administra, filtra y exporta tu inventario de equipos</div>
      </div>

      <div class="d-flex gap-2 align-items-center">
        <button id="theme-toggle" class="theme-btn" title="Modo oscuro / claro">🌙</button>
        <button class="btn btn-outline-secondary" id="btn-help" title="Ayuda rápida"><i class="bi bi-question-circle"></i></button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarEquipo"><i class="bi bi-plus-circle"></i> Agregar equipo</button>
      </div>
    </div>

    <!-- RESUMEN -->
    <div class="row g-3 mb-3 summary-cards">
      <?php 
      $total = intval($conn->query("SELECT COUNT(*) as c FROM equipos")->fetch_assoc()['c'] ?? 0);
      $activos = intval($conn->query("SELECT COUNT(*) as c FROM equipos WHERE estado='En uso'")->fetch_assoc()['c'] ?? 0);
      $reparacion = intval($conn->query("SELECT COUNT(*) as c FROM equipos WHERE estado='En reparación'")->fetch_assoc()['c'] ?? 0);
      $danados = intval($conn->query("SELECT COUNT(*) as c FROM equipos WHERE estado='Dañado'")->fetch_assoc()['c'] ?? 0);
      ?>
      <div class="col-md-3">
        <div class="card card-soft p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="small-muted">Total equipos</small>
              <div class="fs-4 fw-bold"><?= $total ?></div>
            </div>
            <div><i class="bi bi-hdd-stack fs-2 text-primary"></i></div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-soft p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="small-muted">En uso</small>
              <div class="fs-4 fw-bold text-success"><?= $activos ?></div>
            </div>
            <div><i class="bi bi-laptop fs-2 text-success"></i></div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-soft p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="small-muted">En reparación</small>
              <div class="fs-4 fw-bold text-warning"><?= $reparacion ?></div>
            </div>
            <div><i class="bi bi-tools fs-2 text-warning"></i></div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-soft p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="small-muted">Dañados</small>
              <div class="fs-4 fw-bold text-danger"><?= $danados ?></div>
            </div>
            <div><i class="bi bi-exclamation-octagon fs-2 text-danger"></i></div>
          </div>
        </div>
      </div>
    </div>

    <!-- FILTROS Y EXPORT -->
    <div class="card card-soft mb-3 p-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="filter">
          <select id="filtro_estado" class="form-select form-select-sm">
            <option value="">Estado (Todos)</option>
            <option>En uso</option>
            <option>En reparación</option>
            <option>Dañado</option>
            <option>Dado de baja</option>
          </select>
        </div>

        <div class="filter">
          <select id="filtro_categoria" class="form-select form-select-sm">
            <option value="">Categoría (Todas)</option>
            <?php foreach($categorias as $c): ?><option><?= htmlspecialchars($c['nombre_categoria']) ?></option><?php endforeach; ?>
          </select>
        </div>

        <div class="filter">
          <select id="filtro_ubicacion" class="form-select form-select-sm">
            <option value="">Ubicación (Todas)</option>
            <?php foreach($ubicaciones as $u): ?><option><?= htmlspecialchars($u['nombre']) ?></option><?php endforeach; ?>
          </select>
        </div>

        <div class="filter">
          <select id="filtro_departamento" class="form-select form-select-sm">
            <option value="">Departamento (Todos)</option>
            <?php foreach($anexos as $a): ?><option><?= htmlspecialchars($a['nombre_dep']) ?></option><?php endforeach; ?>
          </select>
        </div>

        <div class="filter">
          <input id="search-global" class="form-control form-control-sm" placeholder="Buscar por código, marca o modelo..." />
        </div>
      </div>

      <div class="d-flex gap-2">
        <a href="#" id="btnExcel" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Excel</a>
        <a href="#" id="btnPDF" class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
      </div>
    </div>

    <!-- TABLA EQUIPOS -->
    <div class="card card-soft p-3 shadow-sm">
      <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered align-middle" id="tabla_equipos">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>Imagen</th>
              <th>Código</th>
              <th>Marca</th>
              <th>Modelo</th>
              <th>Serie</th>
              <th>Estado</th>
              <th>Departamento</th>
              <th>Categoría</th>
              <th>Ubicación</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $equipos = $conn->query("SELECT e.*, d.nombre_dep, c.nombre_categoria, u.nombre as ubicacion
                                         FROM equipos e
                                         LEFT JOIN departamentos d ON e.id_dep=d.id_dep
                                         LEFT JOIN categorias c ON e.id_categoria=c.id_categoria
                                         LEFT JOIN ubicaciones u ON e.id_ubicacion=u.id_ubicacion
                                         ORDER BY e.id_equipo DESC");
            while ($row = $equipos->fetch_assoc()):
                $badgeClass = 'secondary';
                if($row['estado']=='En uso') $badgeClass='success';
                elseif($row['estado']=='En reparación') $badgeClass='warning';
                elseif($row['estado']=='Dañado') $badgeClass='danger';
            ?>
            <tr>
              <td><?= $row['id_equipo'] ?></td>
              <td>
                <?php if($row['imagen']): ?>
                  <img src="<?= $row['imagen'] ?>" width="60" class="img-preview">
                <?php else: ?>
                  <div class="text-muted small">Sin imagen</div>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['codigo_equipo']) ?></td>
              <td><?= htmlspecialchars($row['marca']) ?></td>
              <td><?= htmlspecialchars($row['modelo']) ?></td>
              <td><?= htmlspecialchars($row['serie']) ?></td>
              <td><span class="badge bg-<?= $badgeClass ?>"><?= htmlspecialchars($row['estado']) ?></span></td>
              <td><?= htmlspecialchars($row['nombre_dep']) ?></td>
              <td><?= htmlspecialchars($row['nombre_categoria']) ?></td>
              <td><?= htmlspecialchars($row['ubicacion']) ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Modal Agregar Equipo (mejorado visualmente) -->
<div class="modal fade" id="modalAgregarEquipo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" method="POST" action="" enctype="multipart/form-data" id="formAgregarEquipo">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i> Agregar Nuevo Equipo</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">Anexo</label>
            <select name="id_dep" class="form-select" required>
              <option value="">Seleccione un Anexo</option>
              <?php foreach($anexos as $a): ?><option value="<?= $a['id_dep'] ?>"><?= htmlspecialchars($a['nombre_dep']) ?></option><?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Categoría</label>
            <select name="id_categoria" class="form-select" required>
              <option value="">Seleccione una Categoría</option>
              <?php foreach($categorias as $c): ?><option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option><?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Ubicación</label>
            <select name="id_ubicacion" class="form-select" required>
              <option value="">Seleccione una Ubicación</option>
              <?php foreach($ubicaciones as $u): ?><option value="<?= $u['id_ubicacion'] ?>"><?= htmlspecialchars($u['nombre']) ?></option><?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Nivel</label>
            <input type="text" name="nivel" class="form-control" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Código (Generado)</label>
            <input type="text" id="codigo_equipo" class="form-control" readonly style="background-color:#e9ecef;">
          </div>

          <div class="col-md-4">
            <label class="form-label">Marca</label>
            <input type="text" name="marca" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">Modelo</label>
            <input type="text" name="modelo" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">Serie</label>
            <input type="text" name="serie" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">Procesador</label>
            <input type="text" name="procesador" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">RAM</label>
            <input type="text" name="ram" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">Disco</label>
            <input type="text" name="disco" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">S.O.</label>
            <input type="text" name="so" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select">
              <option>En uso</option>
              <option>En reparación</option>
              <option>Dañado</option>
              <option>Dado de baja</option>
            </select>
          </div>

          <div class="col-md-12">
            <label class="form-label">Observaciones</label>
            <textarea name="observaciones" class="form-control" rows="2"></textarea>
          </div>

          <div class="col-md-6">
            <label class="form-label">Imagen</label>
            <input type="file" name="imagen" class="form-control" id="input-imagen">
            <div class="mt-2">
              <img id="preview-img" src="" alt="" class="img-preview" style="display:none;">
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Última Revisión</label>
            <input type="datetime-local" name="ultima_revision" class="form-control">
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="submit" name="agregar_equipo" class="btn btn-success">Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Toasts -->
<div id="app-toast" aria-live="polite" aria-atomic="true">
  <div class="toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true" id="mainToast">
    <div class="d-flex">
      <div class="toast-body" id="mainToastBody">Mensaje</div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<!-- SCRIPTS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ---------- GENERAR CÓDIGO DINÁMICO ----------
function generarCodigo() {
    const nivel = document.querySelector('input[name="nivel"]').value.trim();
    const id_dep = document.querySelector('select[name="id_dep"]').value;
    const id_categoria = document.querySelector('select[name="id_categoria"]').value;

    if(nivel && id_dep && id_categoria){
        fetch(`equipos.php?accion=generar_codigo&nivel=${encodeURIComponent(nivel)}&id_dep=${id_dep}&id_categoria=${id_categoria}`)
            .then(res => res.text())
            .then(data => document.getElementById('codigo_equipo').value = data)
            .catch(err => console.error(err));
    } else {
        document.getElementById('codigo_equipo').value = '';
    }
}
document.querySelector('input[name="nivel"]').addEventListener('input', generarCodigo);
document.querySelector('select[name="id_dep"]').addEventListener('change', generarCodigo);
document.querySelector('select[name="id_categoria"]').addEventListener('change', generarCodigo);

// ---------- PREVIEW IMAGEN ----------
const inputImagen = document.getElementById('input-imagen');
const previewImg = document.getElementById('preview-img');
if (inputImagen) {
  inputImagen.addEventListener('change', (e) => {
    const f = e.target.files[0];
    if (!f) { previewImg.style.display='none'; previewImg.src=''; return; }
    const url = URL.createObjectURL(f);
    previewImg.src = url;
    previewImg.style.display = 'inline-block';
  });
}

// ---------- FILTROS EN TIEMPO REAL (cliente) ----------
document.querySelectorAll('#filtro_estado,#filtro_categoria,#filtro_ubicacion,#filtro_departamento').forEach(select=>{
    select.addEventListener('change',function(){
        applyFilters();
    });
});

document.getElementById('search-global').addEventListener('input', function(){
    applyFilters();
});

function applyFilters() {
    const estado = document.getElementById('filtro_estado').value.toLowerCase();
    const categoria = document.getElementById('filtro_categoria').value.toLowerCase();
    const ubicacion = document.getElementById('filtro_ubicacion').value.toLowerCase();
    const departamento = document.getElementById('filtro_departamento').value.toLowerCase();
    const q = document.getElementById('search-global').value.toLowerCase().trim();

    document.querySelectorAll('#tabla_equipos tbody tr').forEach(row=>{
        const rowEstado = row.cells[6].innerText.toLowerCase();
        const rowCategoria = row.cells[8].innerText.toLowerCase();
        const rowUbicacion = row.cells[9].innerText.toLowerCase();
        const rowDep = row.cells[7].innerText.toLowerCase();
        const rowText = row.innerText.toLowerCase();

        const ok = (estado==''||rowEstado==estado) &&
                   (categoria==''||rowCategoria==categoria) &&
                   (ubicacion==''||rowUbicacion==ubicacion) &&
                   (departamento==''||rowDep==departamento) &&
                   (q==''||rowText.indexOf(q)!==-1);

        row.style.display = ok ? '' : 'none';
    });
}

// ---------- EXPORT buttons (respetan filtros) ----------
document.getElementById("btnExcel").addEventListener("click",function(e){e.preventDefault();
    const estado = encodeURIComponent(document.getElementById("filtro_estado").value);
    const categoria = encodeURIComponent(document.getElementById("filtro_categoria").value);
    const ubicacion = encodeURIComponent(document.getElementById("filtro_ubicacion").value);
    const departamento = encodeURIComponent(document.getElementById("filtro_departamento").value);
    // Lógica original de export: mantiene tus endpoints
    window.location.href=`export_equipos_excel.php?estado=${estado}&categoria=${categoria}&ubicacion=${ubicacion}&departamento=${departamento}`;
});
document.getElementById("btnPDF").addEventListener("click",function(e){e.preventDefault();
    const estado = encodeURIComponent(document.getElementById("filtro_estado").value);
    const categoria = encodeURIComponent(document.getElementById("filtro_categoria").value);
    const ubicacion = encodeURIComponent(document.getElementById("filtro_ubicacion").value);
    const departamento = encodeURIComponent(document.getElementById("filtro_departamento").value);
    window.location.href=`export_equipos_pdf.php?estado=${estado}&categoria=${categoria}&ubicacion=${ubicacion}&departamento=${departamento}`;
});

// ---------- THEME toggle (dark / light) ----------
const themeBtn = document.getElementById('theme-toggle');
function applyTheme(v) {
  if (v === 'dark') document.body.classList.add('dark-mode');
  else document.body.classList.remove('dark-mode');
  localStorage.setItem('equipos_theme', v);
}
themeBtn.addEventListener('click', ()=>{
  const cur = localStorage.getItem('equipos_theme') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark':'light');
  const next = cur === 'dark' ? 'light' : 'dark';
  applyTheme(next);
});
(function initTheme(){
  const saved = localStorage.getItem('equipos_theme');
  if (saved) applyTheme(saved);
  else {
    const prefer = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    applyTheme(prefer);
  }
})();

// ---------- TOAST helper ----------
const mainToastEl = document.getElementById('mainToast');
const mainToast = new bootstrap.Toast(mainToastEl, { delay: 4000 });
function showToast(msg, type='info', timeout=4000) {
  const body = document.getElementById('mainToastBody');
  body.textContent = msg;
  mainToastEl.classList.remove('border-success','border-danger','border-info');
  if (type==='success') mainToastEl.classList.add('border-success');
  else if (type==='error') mainToastEl.classList.add('border-danger');
  else mainToastEl.classList.add('border-info');
  mainToast.show();
  // optional small ping
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const o = ctx.createOscillator(); const g = ctx.createGain();
    o.type='sine'; o.frequency.value = (type==='success'?880:520);
    g.gain.value = 0.02; o.connect(g); g.connect(ctx.destination);
    o.start(); setTimeout(()=>o.stop(),120);
  } catch(e){}
}

// Si hay toast en sesión (del servidor), mostrarlo
<?php if (isset($_SESSION['toast'])): 
    $t = $_SESSION['toast']; 
    $t_msg = addslashes($t['msg'] ?? '');
    $t_type = addslashes($t['type'] ?? 'info');
    $t_timeout = intval($t['timeout'] ?? 4000);
    unset($_SESSION['toast']);
?>
  document.addEventListener('DOMContentLoaded', ()=>{ showToast("<?= $t_msg ?>","<?= $t_type ?>", <?= $t_timeout ?>); });
<?php endif; ?>

// ---------- Pequeña ayuda: abrir modal y centrar en móvil ----------
document.getElementById('btn-help').addEventListener('click', ()=>{
  showToast('Para agregar un equipo completa el formulario y pulsa Guardar. Usa exportadores para descargar según filtros.', 'info', 6000);
});

</script>

<?php include 'asistente/bot.php'; ?>
