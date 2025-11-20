<?php
// reparaciones.php — versión mejorada visualmente (mantiene lógica original)
session_start();
require_once "db.php";
require_once "includes/header.php";

// =======================
// REGISTRAR NUEVA REPARACIÓN (LOGICA ORIGINAL, sin cambios funcionales)
// =======================
if (isset($_POST['registrar'])) {
    $id_equipo   = $conn->real_escape_string($_POST['id_equipo']);
    $problema    = $conn->real_escape_string($_POST['problema']);
    $tecnico     = $conn->real_escape_string($_POST['tecnico']);
    $fecha_inicio= $conn->real_escape_string($_POST['fecha_inicio']);
    $fecha_fin   = $conn->real_escape_string($_POST['fecha_fin']);
    $solucion    = $conn->real_escape_string($_POST['solucion']);
    $estado      = isset($_POST['estado']) ? $conn->real_escape_string($_POST['estado']) : 'En reparación';
    $usuario     = $_SESSION['usuario'] ?? 'admin';

    $insert = $conn->query("
        INSERT INTO reparaciones 
        (id_equipo, problema, tecnico, fecha_inicio, fecha_fin, solucion, estado, usuario_registra)
        VALUES ('$id_equipo','$problema','$tecnico','$fecha_inicio','$fecha_fin','$solucion','$estado','$usuario')
    ");

    if ($insert) {
        // Guardar aviso en sesión para mostrar Toast al recargar/volver a la página
        $_SESSION['toast'] = ['type' => 'success', 'msg' => '✅ Reparación registrada correctamente', 'timeout' => 4500];

        $accion = "Se registró reparación para el equipo ID $id_equipo (problema: $problema)";
        // Mantener tu inserción en historial (tabla 'historial' según original)
        $conn->query("INSERT INTO historial (id_equipo, accion, usuario) VALUES ('$id_equipo','$accion','$usuario')");
        header("Location: reparaciones.php");
        exit;
    } else {
        // Si falla, mostrar inmediatamente (seguimos tu comportamiento original, pero también guardamos toast de error)
        $_SESSION['toast'] = ['type' => 'error', 'msg' => '❌ Error al registrar: ' . $conn->error, 'timeout' => 7000];
        header("Location: reparaciones.php");
        exit;
    }
}

// =======================
// CONTADORES DE ESTADO
// =======================
$pendientes   = $conn->query("SELECT COUNT(*) AS total FROM reparaciones WHERE estado='Pendiente'")->fetch_assoc()['total'] ?? 0;
$enReparacion = $conn->query("SELECT COUNT(*) AS total FROM reparaciones WHERE estado='En reparación'")->fetch_assoc()['total'] ?? 0;
$reparados    = $conn->query("SELECT COUNT(*) AS total FROM reparaciones WHERE estado='Reparado'")->fetch_assoc()['total'] ?? 0;

// =======================
// FILTROS (mantengo tu lógica)
// =======================
$where = [];
$f_equipo  = $_GET['f_equipo'] ?? '';
$f_tecnico = $_GET['f_tecnico'] ?? '';
$f_estado  = $_GET['f_estado'] ?? '';

if ($f_equipo)  $where[] = "(e.codigo_equipo LIKE '%".$conn->real_escape_string($f_equipo)."%' OR e.marca LIKE '%".$conn->real_escape_string($f_equipo)."%' OR e.modelo LIKE '%".$conn->real_escape_string($f_equipo)."%')";
if ($f_tecnico) $where[] = "r.tecnico LIKE '%".$conn->real_escape_string($f_tecnico)."%'";
if ($f_estado)  $where[] = "r.estado='".$conn->real_escape_string($f_estado)."'";

$whereSQL = count($where) ? "WHERE ".implode(" AND ", $where) : "";

// =======================
// CONSULTA PRINCIPAL (idéntica a la tuya)
// =======================
$res = $conn->query("
    SELECT r.id_reparacion, r.problema, r.tecnico, r.fecha_inicio, r.fecha_fin, r.solucion, r.estado, r.usuario_registra,
           e.codigo_equipo, e.marca, e.modelo
    FROM reparaciones r
    LEFT JOIN equipos e ON e.id_equipo = r.id_equipo
    $whereSQL
    ORDER BY r.fecha_inicio DESC
");

if (!$res) die('❌ Error SQL: '.$conn->error);

// =======================
// QUERY STRING PARA EXPORTAR
// =======================
$filtros = http_build_query([
    'f_equipo' => $f_equipo,
    'f_tecnico'=> $f_tecnico,
    'f_estado' => $f_estado
]);
?>

<!-- ===========================
     STYLES (Dark mode + modern dashboard look)
     =========================== -->
<style>
:root{
  --bg: #eef6fb;
  --card: #ffffff;
  --muted: #6c757d;
  --accent: #2b7be9;
  --text: #0b1b2b;
}
body.dark-mode {
  --bg: #071124;
  --card: #07131a;
  --muted: #9aa6b2;
  --accent: #4da3ff;
  --text: #e6eef8;
}
.repair-page { background: linear-gradient(180deg,var(--bg), #f5fbff); min-height:80vh; padding:28px 0; transition:background .25s ease; }
.card-soft { background: var(--card); border-radius:12px; box-shadow: 0 8px 30px rgba(2,6,23,0.06); color:var(--text); }

/* Header */
.header-row { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; }
.header-left h2 { margin:0; font-weight:700; color:var(--text); }
.header-sub { color:var(--muted); font-size:0.95rem; }

/* Stat cards */
.stats .card { border-radius:12px; transition: transform .18s ease; }
.stats .card:hover { transform: translateY(-6px); box-shadow:0 18px 40px rgba(2,6,23,0.08); }

/* filter area */
.filters { display:flex; gap:10px; align-items:center; flex-wrap:wrap; padding:10px; }

/* table */
.table thead th { background: linear-gradient(90deg, rgba(255, 0, 0, 0.04), rgba(0,0,0,0.02)); }
.table tbody tr { transition: transform .12s ease, box-shadow .12s ease; }
.table tbody tr:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(2,6,23,0.04); background: rgba(255,255,255,0.6); }

/* modal */
.modal .modal-content { border-radius:12px; overflow:hidden; }
.form-section { padding:12px; border-radius:8px; background: linear-gradient(180deg, rgba(0,0,0,0.02), rgba(0,0,0,0.01)); }

/* buttons */
.btn-ghost { background:transparent; border:1px solid rgba(0,0,0,0.06); }

/* toast pos */
#mainToastWrap { position:fixed; right:20px; bottom:20px; z-index:12000; }

/* small responsive */
@media (max-width:767px) {
  .header-row { flex-direction:column; align-items:flex-start; gap:8px; }
}
</style>

<!-- ===========================
     HTML (Interfaz mejorada)
     =========================== -->
<div class="repair-page">
  <div class="container">
    <!-- Header -->
    <div class="header-row">
      <div class="header-left">
        <h2>🔧 Gestión de Reparaciones</h2>
        <div class="header-sub">Registra, filtra y exporta reparaciones — todo en un solo panel.</div>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <button id="theme-toggle" class="btn btn-sm btn-ghost">🌙</button>
        <button class="btn btn-outline-secondary btn-sm" id="help-btn" title="Ayuda rápida"><i class="bi bi-question-circle"></i></button>
        <!-- Botón abre modal para registrar (ahora usamos modal para mantener la lógica) -->
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalRegistrar">
          <i class="bi bi-plus-circle"></i> Nueva reparación
        </button>
      </div>
    </div>

    <!-- STAT CARDS -->
    <div class="row stats g-3 mb-4">
      <div class="col-md-4">
        <div class="card card-soft p-3 text-center">
          <div class="small-muted">Pendientes</div>
          <div class="fs-3 fw-bold text-danger"><?= intval($pendientes) ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card card-soft p-3 text-center">
          <div class="small-muted">En reparación</div>
          <div class="fs-3 fw-bold text-warning"><?= intval($enReparacion) ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card card-soft p-3 text-center">
          <div class="small-muted">Reparados</div>
          <div class="fs-3 fw-bold text-success"><?= intval($reparados) ?></div>
        </div>
      </div>
    </div>

    <!-- FILTERS & EXPORT -->
    <div class="card card-soft mb-4 p-3">
      <div class="d-flex justify-content-between align-items-center flex-wrap">
        <form method="GET" class="d-flex filters">
          <input type="text" name="f_equipo" class="form-control form-control-sm" placeholder="Buscar equipo (código/marca/modelo)" value="<?= htmlspecialchars($f_equipo) ?>" style="min-width:220px;">
          <input type="text" name="f_tecnico" class="form-control form-control-sm" placeholder="Técnico" value="<?= htmlspecialchars($f_tecnico) ?>" style="min-width:160px;">
          <select name="f_estado" class="form-select form-select-sm" style="min-width:160px;">
            <option value="">Todos los estados</option>
            <option value="En reparación" <?= $f_estado=='En reparación'?'selected':'' ?>>En reparación</option>
            <option value="Reparado" <?= $f_estado=='Reparado'?'selected':'' ?>>Reparado</option>
            <option value="Pendiente" <?= $f_estado=='Pendiente'?'selected':'' ?>>Pendiente</option>
          </select>
          <button type="submit" class="btn btn-primary btn-sm">Aplicar</button>
          <a href="reparaciones.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </form>

        <div class="d-flex gap-2 mt-2 mt-md-0">
          <a class="btn btn-success btn-sm" href="exportar_reparaciones.php?<?= $filtros ?>"><i class="bi bi-file-earmark-excel"></i> Excel</a>
          <a class="btn btn-danger btn-sm" href="exportar_reparaciones_pdf.php?<?= $filtros ?>"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
        </div>
      </div>
    </div>

    <!-- TABLE -->
    <div class="card card-soft p-3 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover table-sm align-middle" id="tabla-reparaciones">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>Equipo</th>
              <th>Problema</th>
              <th>Técnico</th>
              <th>Fecha inicio</th>
              <th>Fecha fin</th>
              <th>Solución</th>
              <th>Estado</th>
              <th>Usuario registra</th>
            </tr>
          </thead>
          <tbody>
            <?php while($r=$res->fetch_assoc()):
                $estado_normalizado = strtolower($r['estado']);
                // Mantengo los colores lógicos previos pero con mapeo seguro
                $badge = $estado_normalizado=='reparado'?'success':($estado_normalizado=='en reparación'?'warning':($estado_normalizado=='pendiente'?'danger':'secondary'));
            ?>
            <tr>
              <td><?= intval($r['id_reparacion']) ?></td>
              <td><?= htmlspecialchars($r['codigo_equipo'].' - '.$r['marca'].' '.$r['modelo']) ?></td>
              <td><?= htmlspecialchars($r['problema']) ?></td>
              <td><?= htmlspecialchars($r['tecnico']) ?></td>
              <td><?= htmlspecialchars(date('d-m-Y',strtotime($r['fecha_inicio']))) ?></td>
              <td><?= $r['fecha_fin'] ? htmlspecialchars(date('d-m-Y',strtotime($r['fecha_fin']))) : '-' ?></td>
              <td><?= htmlspecialchars($r['solucion'] ?: '-') ?></td>
              <td><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($r['estado']) ?></span></td>
              <td><?= htmlspecialchars($r['usuario_registra']) ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <!-- small footer -->
      <div class="text-muted small mt-2">Resultados actualizados — diseño moderno, sin cambiar lógica.</div>
    </div>

  </div>
</div>

<!-- ===========================
     MODAL: Registrar reparación (mantiene campos y funcionalidad)
     =========================== -->
<div class="modal fade" id="modalRegistrar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" method="POST" id="form-registrar">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-tools me-2"></i> Registrar nueva reparación</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Equipo</label>
            <select name="id_equipo" class="form-select" required>
              <option value="">-- Seleccionar equipo --</option>
              <?php
              $equipos = $conn->query("SELECT id_equipo,codigo_equipo,marca,modelo FROM equipos ORDER BY codigo_equipo ASC");
              while($eq=$equipos->fetch_assoc()){
                  echo "<option value='".$eq['id_equipo']."'>".htmlspecialchars($eq['codigo_equipo'].' - '.$eq['marca'].' '.$eq['modelo'])."</option>";
              }
              ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Técnico</label>
            <input type="text" name="tecnico" class="form-control" placeholder="Nombre del técnico" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select" required>
              <option value="En reparación">En reparación</option>
              <option value="Reparado">Reparado</option>
              <option value="Pendiente">Pendiente</option>
            </select>
          </div>

          <div class="col-md-8">
            <label class="form-label">Problema</label>
            <input type="text" name="problema" class="form-control" placeholder="Describe el problema" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Fecha inicio</label>
            <input type="date" name="fecha_inicio" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Fecha fin</label>
            <input type="date" name="fecha_fin" class="form-control">
          </div>

          <div class="col-md-12">
            <label class="form-label">Solución</label>
            <textarea name="solucion" class="form-control" rows="3" placeholder="Describe la solución aplicada"></textarea>
          </div>

        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="registrar" class="btn btn-success"><i class="bi bi-save me-1"></i> Guardar reparación</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- TOAST wrapper -->
<div id="mainToastWrap" aria-live="polite" aria-atomic="true">
  <div class="toast" id="mainToast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="mainToastBody"></div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<!-- ===========================
     SCRIPTS: Bootstrap + UI behaviour
     =========================== -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// THEME (dark/light) toggle (same behavior as other pages)
const themeBtn = document.getElementById('theme-toggle');
function applyTheme(v) {
  if (v === 'dark') document.body.classList.add('dark-mode');
  else document.body.classList.remove('dark-mode');
  localStorage.setItem('rep_theme', v);
}
themeBtn.addEventListener('click', ()=>{
  const cur = localStorage.getItem('rep_theme') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark':'light');
  const next = cur === 'dark' ? 'light' : 'dark';
  applyTheme(next);
});
(function initTheme(){
  const saved = localStorage.getItem('rep_theme');
  if (saved) applyTheme(saved);
  else {
    const prefer = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    applyTheme(prefer);
  }
})();

// HELP button (small toast)
document.getElementById('help-btn').addEventListener('click', ()=> {
  showToast('Abre "Nueva reparación" para registrar una reparación. Usa filtros y exportadores para obtener reportes.', 'info', 6000);
});

// TOAST util
const toastEl = document.getElementById('mainToast');
const toast = new bootstrap.Toast(toastEl, { delay: 4500 });
function showToast(msg, type='info', timeout=4500) {
  const body = document.getElementById('mainToastBody');
  body.textContent = msg;
  toastEl.classList.remove('border-success','border-danger','border-info');
  if (type==='success') toastEl.classList.add('border-success');
  else if (type==='error') toastEl.classList.add('border-danger');
  else toastEl.classList.add('border-info');
  toast.show();
  // small ping
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const o = ctx.createOscillator(); const g = ctx.createGain();
    o.type='sine'; o.frequency.value = (type==='success'?880:520);
    g.gain.value = 0.02; o.connect(g); g.connect(ctx.destination);
    o.start(); setTimeout(()=>o.stop(),120);
  } catch(e){}
}

// Mostrar toast si hay mensaje en sesión (servidor)
<?php if (isset($_SESSION['toast'])):
    $t = $_SESSION['toast'];
    $t_msg = addslashes($t['msg'] ?? '');
    $t_type = addslashes($t['type'] ?? 'info');
    $t_timeout = intval($t['timeout'] ?? 4500);
    unset($_SESSION['toast']);
?>
  document.addEventListener('DOMContentLoaded', ()=>{ showToast("<?= $t_msg ?>","<?= $t_type ?>", <?= $t_timeout ?>); });
<?php endif; ?>

// INIT: tooltips
document.addEventListener('DOMContentLoaded', ()=> {
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });
});

// small UX: focus first input when modal opens
var modalRegistrar = document.getElementById('modalRegistrar');
modalRegistrar.addEventListener('shown.bs.modal', function () {
  modalRegistrar.querySelector('select[name="id_equipo"]').focus();
});

</script>

<?php include "includes/footer.php"; ?>
<?php include 'asistente/bot.php'; ?>
