<?php
// historial.php — Timeline + comentarios + notificaciones + modo oscuro (todo en 1)
session_start();
require_once "db.php";
require_once "includes/header.php";

if (!isset($conn) || !$conn) die('❌ Conexión a la base de datos no disponible. Revisa db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'];

    // --- Añadir comentario ---
    if ($action === 'add_comment') {
        $id_hist = intval($_POST['id_historial'] ?? 0);
        $id_prod = intval($_POST['id_producto'] ?? 0);
        if ($id_prod <= 0 && $id_hist > 0) {
            $resTmp = $conn->query("SELECT id_equipo FROM historial_estados WHERE id_historial = $id_hist LIMIT 1");
            $id_prod = ($resTmp && $resTmp->num_rows) ? intval($resTmp->fetch_assoc()['id_equipo']) : 0;
        }
        $usuario = $conn->real_escape_string(trim($_POST['usuario'] ?? 'Anonimo'));
        $comentario = $conn->real_escape_string(trim($_POST['comentario'] ?? ''));
        if ($id_prod <= 0 || $comentario === '') echo json_encode(['ok'=>false,'msg'=>'Parámetros incompletos.']), exit;
        $fecha = date('Y-m-d H:i:s');
        $ok = $conn->query("INSERT INTO comentarios (id_producto, usuario, comentario, fecha) VALUES ($id_prod,'$usuario','$comentario','$fecha')");
        if ($ok) echo json_encode(['ok'=>true,'msg'=>'Comentario agregado.','comentario'=>['id_com'=>$conn->insert_id,'id_producto'=>$id_prod,'usuario'=>htmlspecialchars($usuario),'comentario'=>htmlspecialchars($comentario),'fecha'=>date('d/m/Y H:i',strtotime($fecha))]]); 
        else echo json_encode(['ok'=>false,'msg'=>'Error al guardar comentario: '.$conn->error]);
        exit;
    }

    // --- Obtener comentarios ---
    if ($action === 'get_comments') {
        $id_hist = intval($_POST['id_historial'] ?? 0);
        $id_prod = intval($_POST['id_producto'] ?? 0);
        if ($id_prod <= 0 && $id_hist > 0) {
            $resTmp = $conn->query("SELECT id_equipo FROM historial_estados WHERE id_historial = $id_hist LIMIT 1");
            $id_prod = ($resTmp && $resTmp->num_rows) ? intval($resTmp->fetch_assoc()['id_equipo']) : 0;
        }
        if ($id_prod <= 0) echo json_encode(['ok'=>false,'msg'=>'ID inválido.']), exit;
        $q = $conn->query("SELECT id_com, id_producto, usuario, comentario, fecha FROM comentarios WHERE id_producto=$id_prod ORDER BY fecha DESC");
        $rows = [];
        while ($row = $q->fetch_assoc()) $rows[]=['id_com'=>$row['id_com'],'usuario'=>htmlspecialchars($row['usuario']),'comentario'=>htmlspecialchars($row['comentario']),'fecha'=>date('d/m/Y H:i',strtotime($row['fecha']))];
        echo json_encode(['ok'=>true,'comentarios'=>$rows]);
        exit;
    }

    // --- Obtener detalle de historial ---
    if ($action === 'get_detail') {
        $id_hist = intval($_POST['id_historial'] ?? 0);
        if ($id_hist <= 0) echo json_encode(['ok'=>false,'msg'=>'ID inválido para detalle.']), exit;
        $r = $conn->query("SELECT h.id_historial,h.id_equipo,e.codigo_equipo,e.marca,e.modelo,h.estado_anterior,h.estado_nuevo,h.usuario,h.fecha,h.detalle FROM historial_estados h LEFT JOIN equipos e ON e.id_equipo=h.id_equipo WHERE h.id_historial=$id_hist LIMIT 1");
        if (!$r || $r->num_rows===0) echo json_encode(['ok'=>false,'msg'=>'Registro no encontrado.']), exit;
        $row = $r->fetch_assoc();
        $equipoText = ($row['codigo_equipo']? $row['codigo_equipo'].' - ':'').trim($row['marca'].' '.$row['modelo']);
        echo json_encode(['ok'=>true,'record'=>['id_historial'=>intval($row['id_historial']),'id_equipo'=>intval($row['id_equipo']),'equipo'=>$equipoText,'estado_anterior'=>$row['estado_anterior'],'estado_nuevo'=>$row['estado_nuevo'],'usuario'=>$row['usuario'],'fecha'=>date('d/m/Y H:i',strtotime($row['fecha'])),'detalle'=>$row['detalle']]]);
        exit;
    }

    // --- Check nuevos registros ---
    if ($action==='check_new') {
        $last = intval($_POST['last_max_id'] ?? 0);
        $q = $conn->query("SELECT MAX(id_historial) AS maxid FROM historial_estados");
        $maxid = $q->fetch_assoc()['maxid'] ?? 0;
        if ($maxid>$last) {
            $r = $conn->query("SELECT h.id_historial,h.id_equipo,e.codigo_equipo,e.marca,e.modelo,h.estado_nuevo,h.usuario,h.fecha FROM historial_estados h LEFT JOIN equipos e ON e.id_equipo=h.id_equipo WHERE h.id_historial=$maxid LIMIT 1")->fetch_assoc();
            echo json_encode(['ok'=>true,'new'=>true,'maxid'=>intval($maxid),'record'=>['id'=>intval($r['id_historial']),'equipo'=>($r['codigo_equipo']?$r['codigo_equipo'].' - ':'').($r['marca'].' '.$r['modelo']),'estado_nuevo'=>$r['estado_nuevo'],'usuario'=>$r['usuario'],'fecha'=>date('d/m/Y H:i',strtotime($r['fecha']))]]);
        } else echo json_encode(['ok'=>true,'new'=>false,'maxid'=>intval($maxid)]);
        exit;
    }

    echo json_encode(['ok'=>false,'msg'=>'Accion desconocida.']);
    exit;
}

// --- GET: timeline principal ---
$filtro_usuario = $conn->real_escape_string($_GET['usuario'] ?? '');
$filtro_estado = $conn->real_escape_string($_GET['estado'] ?? '');
$sql = "SELECT h.id_historial,h.id_equipo,e.codigo_equipo,e.marca,e.modelo,h.estado_anterior,h.estado_nuevo,h.usuario,h.fecha,h.detalle FROM historial_estados h LEFT JOIN equipos e ON e.id_equipo=h.id_equipo WHERE 1=1";
if ($filtro_usuario!=='') $sql.=" AND h.usuario LIKE '%$filtro_usuario%'";
if ($filtro_estado!=='') $sql.=" AND (h.estado_nuevo='$filtro_estado' OR h.estado_anterior='$filtro_estado')";
$sql.=" ORDER BY h.fecha DESC";
$res=$conn->query($sql);

$estadisticas=[
    'total'=>intval($conn->query("SELECT COUNT(*) AS n FROM historial_estados")->fetch_assoc()['n'] ?? 0),
    'reparado'=>intval($conn->query("SELECT COUNT(*) AS n FROM historial_estados WHERE estado_nuevo='Reparado'")->fetch_assoc()['n'] ?? 0),
    'pendiente'=>intval($conn->query("SELECT COUNT(*) AS n FROM historial_estados WHERE estado_nuevo='Pendiente'")->fetch_assoc()['n'] ?? 0),
    'en_reparacion'=>intval($conn->query("SELECT COUNT(*) AS n FROM historial_estados WHERE estado_nuevo='En reparación'")->fetch_assoc()['n'] ?? 0)
];

$max_initial=intval($conn->query("SELECT MAX(id_historial) AS maxid FROM historial_estados")->fetch_assoc()['maxid'] ?? 0);
?>
<!-- ===========================
     ESTILOS LOCALES (Timeline, dark mode, toasts)
     =========================== -->
<style>
/* (tu CSS exactamente igual) */
:root{
  --bg:#f7fafc;
  --card:#fff;
  --muted:#6c757d;
  --accent:#4b9cdb;
  --text:#212529;
}
body.dark-mode {
  --bg:#0f1720;
  --card:#0b1220;
  --muted:#9aa6b2;
  --accent:#5eb4ff;
  --text:#e6eef8;
}
.hist-container { background: linear-gradient(180deg, var(--bg), #eef6ff); padding: 28px 0; min-height:70vh; transition: background 300ms ease; }
.card-soft { background: var(--card); border-radius: 12px; box-shadow: 0 6px 20px rgba(15,23,34,0.06); color:var(--text); }
.small-muted { color:var(--muted); font-size:0.9rem; }

/* Panel de controles */
.controls { display:flex; gap:10px; align-items:center; justify-content:space-between; margin-bottom:14px; }
.controls .left { display:flex; gap:8px; align-items:center; }
.controls .right { display:flex; gap:8px; align-items:center; }

/* Timeline */
.timeline { position:relative; padding-left: 28px; margin-top:18px; }
.timeline:before { content:''; position:absolute; left:12px; top:0; bottom:0; width:4px; background: linear-gradient(180deg,var(--accent), #8fcfff); border-radius:4px; opacity:0.12; }
.t-item { position:relative; margin-bottom:18px; padding:12px 16px; border-radius:10px; transition: transform .18s ease, box-shadow .18s ease; background: linear-gradient(180deg, rgba(255,255,255,0.85), rgba(255,255,255,0.7)); }
body.dark-mode .t-item { background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01)); box-shadow:none; border:1px solid rgba(255,255,255,0.03); }
.t-item:hover { transform: translateY(-4px); box-shadow: 0 8px 30px rgba(15,23,34,0.08); }
.t-bullet { position:absolute; left:-6px; top:18px; width:18px; height:18px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:11px; box-shadow:0 3px 12px rgba(10,10,10,0.12); }
.badge-state { padding:6px 10px; border-radius:8px; font-weight:600; }

/* small helpers */
.meta { font-size:0.85rem; color:var(--muted); display:flex; gap:10px; align-items:center; }
.timeline .detail { margin-top:8px; font-size:0.95rem; color:var(--text); }

/* Card view & table */
.table-view { display:none; margin-top:16px; }
.view-toggle.active .timeline { display:block; }
.view-toggle.table .timeline { display:none; }
.view-toggle.table .table-view { display:block; }

/* Toast (notif) */
#notif-toast { position: fixed; right: 20px; top: 20px; z-index: 12000; }

/* Dark mode button */
.theme-btn { border-radius: 8px; padding:8px 10px; background:var(--card); border:1px solid rgba(0,0,0,0.04); cursor:pointer; box-shadow:0 4px 14px rgba(2,6,23,0.03); }

/* responsive */
@media (max-width:767px) {
  .controls { flex-direction:column; align-items:stretch; gap:8px; }
  .timeline { padding-left: 18px; }
}
</style>

<div class="hist-container">
  <div class="container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h2 class="mb-0">📜 Historial de cambios</h2>
        <p class="small-muted mb-0">Timeline activo • Comentarios y notificaciones en tiempo real</p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button id="btn-change-view" class="btn btn-outline-primary btn-sm">Ver tabla</button>
        <button id="btn-refresh" class="btn btn-outline-secondary btn-sm">🔄 Actualizar</button>
        <button id="theme-toggle" class="theme-btn" title="Modo oscuro / claro">🌙</button>
      </div>
    </div>

    <!-- Resumen + buscador -->
    <div class="row g-3 mb-3">
      <div class="col-md-8">
        <div class="card card-soft p-3 d-flex align-items-center">
          <div class="d-flex gap-3 w-100 align-items-center">
            <div class="text-center me-3" style="min-width:110px;">
              <div class="small-muted">Total</div>
              <div class="h4 mb-0"><?= $estadisticas['total'] ?></div>
            </div>
            <div style="flex:1">
              <div class="d-flex gap-2">
                <div class="small-muted">Reparados <span class="fw-bold text-success"><?= $estadisticas['reparado'] ?></span></div>
                <div class="small-muted">Pendientes <span class="fw-bold text-warning"><?= $estadisticas['pendiente'] ?></span></div>
                <div class="small-muted">En reparación <span class="fw-bold text-info"><?= $estadisticas['en_reparacion'] ?></span></div>
              </div>
              <div class="mt-2">
                <input id="quick-search" class="form-control form-control-sm" placeholder="Buscar por usuario, equipo o texto de detalle..." />
              </div>
            </div>
            <div class="text-end" style="min-width:130px;">
              <div class="small-muted">Vista</div>
              <div class="fw-bold">Timeline</div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card card-soft p-3">
          <div class="small-muted">Filtros rápidos</div>
          <form id="fast-filter" class="mt-2">
            <div class="mb-2">
              <input type="text" id="f-usuario" name="usuario" class="form-control form-control-sm" placeholder="Usuario..." value="<?= htmlspecialchars($filtro_usuario) ?>">
            </div>
            <div class="mb-2">
              <select id="f-estado" name="estado" class="form-select form-select-sm">
                <option value="">Todos los estados</option>
                <option value="Pendiente" <?= $filtro_estado=="Pendiente"?"selected":"" ?>>Pendiente</option>
                <option value="En reparación" <?= $filtro_estado=="En reparación"?"selected":"" ?>>En reparación</option>
                <option value="Reparado" <?= $filtro_estado=="Reparado"?"selected":"" ?>>Reparado</option>
              </select>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-primary btn-sm" type="submit">Aplicar</button>
              <button id="reset-filters" class="btn btn-outline-secondary btn-sm" type="button">Reset</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Contenedor principal con timeline + tabla -->
    <div id="main-area" class="view-toggle">
      <!-- Timeline -->
      <div id="timeline-view" class="timeline">
        <?php while ($row = $res->fetch_assoc()):
            $estadoNuevo = $row['estado_nuevo'] ?: '-';
            $colorClass = 'secondary';
            $bulletColor = '#6c757d';
            if ($estadoNuevo === 'Reparado') { $colorClass='success'; $bulletColor='#20c997'; }
            elseif ($estadoNuevo === 'Pendiente') { $colorClass='warning'; $bulletColor='#f6c84c'; }
            elseif ($estadoNuevo === 'En reparación') { $colorClass='info'; $bulletColor='#4db6e8'; }
        ?>
          <div class="t-item card-soft" data-id="<?= intval($row['id_historial']) ?>"
               data-id-equipo="<?= intval($row['id_equipo']) ?>"
               data-equipo="<?= htmlspecialchars(($row['codigo_equipo']? $row['codigo_equipo'].' - ':'') . $row['marca'].' '.$row['modelo']) ?>"
               data-usuario="<?= htmlspecialchars($row['usuario']) ?>"
               data-fecha="<?= date('d/m/Y H:i', strtotime($row['fecha'])) ?>"
               data-detalle="<?= htmlspecialchars($row['detalle'] ?: 'Sin detalles') ?>"
               >
            <div class="t-bullet" style="background: <?= $bulletColor ?>;">
                <i class="bi bi-gear-wide-connected"></i>
            </div>
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-bold"><?= htmlspecialchars(($row['codigo_equipo']? $row['codigo_equipo'].' - ':'') . $row['marca'].' '.$row['modelo']) ?></div>
                <div class="meta small-muted">
                  <span><i class="bi bi-person-fill"></i> <?= htmlspecialchars($row['usuario'] ?: 'Desconocido') ?></span>
                  <span><i class="bi bi-clock"></i> <?= date('d/m/Y H:i', strtotime($row['fecha'])) ?></span>
                  <span><span class="badge badge-state bg-<?= $colorClass ?>"><?= htmlspecialchars($estadoNuevo) ?></span></span>
                </div>
                <div class="detail"><?= nl2br(htmlspecialchars($row['detalle'] ?: 'Sin detalles')) ?></div>
              </div>
              <div class="text-end">
                <div class="btn-group-vertical">
                 <button class="btn-view btn btn-sm btn-outline-primary"
        data-id="<?= intval($row['id_historial']) ?>"
        data-id-equipo="<?= intval($row['id_equipo']) ?>"
        data-equipo="<?= htmlspecialchars(($row['codigo_equipo']? $row['codigo_equipo'].' - ':'') . $row['marca'].' '.$row['modelo']) ?>"
        data-usuario="<?= htmlspecialchars($row['usuario']) ?>"
        data-fecha="<?= date('d/m/Y H:i', strtotime($row['fecha'])) ?>"
        data-detalle="<?= htmlspecialchars($row['detalle'] ?: 'Sin detalles') ?>"
        data-estado-anterior="<?= htmlspecialchars($row['estado_anterior']) ?>"
        data-estado-nuevo="<?= htmlspecialchars($row['estado_nuevo']) ?>"
>
  Ver
</button>


                </div>
            
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>

      <!-- Tabla (oculta por defecto) -->
      <div id="table-view" class="table-view mt-3 card-soft p-3">
        <div class="table-responsive">
          <table class="table table-hover table-sm align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>ID</th><th>Equipo</th><th>Antiguo</th><th>Nuevo</th><th>Usuario</th><th>Fecha</th><th>Acción</th>
              </tr>
            </thead>
            <tbody id="table-body">
<?php
// Re-query simplified for table (we already consumed $res). Re-run query with same filters.
$sql2 = "
    SELECT h.id_historial, h.id_equipo, e.codigo_equipo, e.marca, e.modelo, h.estado_anterior, h.estado_nuevo, h.usuario, h.fecha
    FROM historial_estados h
    LEFT JOIN equipos e ON e.id_equipo = h.id_equipo
    WHERE 1=1
";
if ($filtro_usuario !== '') $sql2 .= " AND h.usuario LIKE '%" . $filtro_usuario . "%'";
if ($filtro_estado !== '') $sql2 .= " AND (h.estado_nuevo = '" . $filtro_estado . "' OR h.estado_anterior = '" . $filtro_estado . "')";
$sql2 .= " ORDER BY h.fecha DESC LIMIT 100";
$q2 = $conn->query($sql2);
while ($r = $q2->fetch_assoc()):
    $estadoNuevo = $r['estado_nuevo'] ?: '-';
    $colorClass = 'secondary';
    if ($estadoNuevo === 'Reparado') $colorClass='success';
    elseif ($estadoNuevo === 'Pendiente') $colorClass='warning';
    elseif ($estadoNuevo === 'En reparación') $colorClass='info';
?>
              <tr class="text-center" data-id="<?= intval($r['id_historial']) ?>" data-id-equipo="<?= intval($r['id_equipo']) ?>">
                <td><?= intval($r['id_historial']) ?></td>
                <td><?= htmlspecialchars(($r['codigo_equipo']? $r['codigo_equipo'].' - ':'') . $r['marca'].' '.$r['modelo']) ?></td>
                <td><?= htmlspecialchars($r['estado_anterior'] ?: '-') ?></td>
                <td><span class="badge bg-<?= $colorClass ?>"><?= htmlspecialchars($estadoNuevo) ?></span></td>
                <td><?= htmlspecialchars($r['usuario'] ?: 'Desconocido') ?></td>
                <td><?= date('d/m/Y H:i', strtotime($r['fecha'])) ?></td>
                <td><button class="btn btn-sm btn-outline-primary btn-view" data-id="<?= intval($r['id_historial']) ?>" data-id-equipo="<?= intval($r['id_equipo']) ?>">Ver</button></td>
              </tr>
<?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div> <!-- main-area -->
  </div>
</div>

<!-- Modal detalle + comentarios -->
<div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content card-soft">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Detalle del cambio</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="md-content">
          <p><strong>Equipo:</strong> <span id="md-equipo"></span></p>
          <p><strong>Usuario:</strong> <span id="md-usuario"></span> • <strong>Fecha:</strong> <span id="md-fecha"></span></p>
          <p><strong>Estado anterior:</strong> <span id="md-anterior" class="badge bg-secondary"></span> 
             &nbsp; <strong>Estado nuevo:</strong> <span id="md-nuevo" class="badge"></span></p>
          <hr>
          <p><strong>Detalle:</strong></p>
          <div id="md-detalle" class="p-2 mb-3" style="background:rgba(0,0,0,0.03); border-radius:8px;"></div>

          <hr>
          <h6>💬 Comentarios</h6>
          <div id="comentarios-list" style="max-height:240px; overflow:auto; margin-bottom:10px;"></div>

          <form id="form-comentario" class="row g-2 align-items-center">
            <input type="hidden" id="coment-id_historial" name="id_historial" value="">
            <input type="hidden" id="coment-id_equipo" name="id_equipo" value="">
            <div class="col-4">
              <input required id="coment-usuario" name="usuario" placeholder="Tu nombre" class="form-control form-control-sm" />
            </div>
            <div class="col-6">
              <input required id="coment-text" name="comentario" placeholder="Escribe un comentario..." class="form-control form-control-sm" />
            </div>
            <div class="col-2 text-end">
              <button class="btn btn-sm btn-primary" type="submit">Enviar</button>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- Toast notificación -->
<div id="notif-toast" aria-live="polite" aria-atomic="true">
  <div class="toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toast-body">Nuevo registro</div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<!-- Scripts: bootstrap (por seguridad incluimos bundle), y lógica JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function(){

  // ---------------------------
  // Variables iniciales
  // ---------------------------
  let lastMaxId = <?= $max_initial ?>;
  const pollInterval = 15000; // 15s
  let pollTimer = null;
  const toastContainer = document.getElementById('notif-toast');
  const toastEl = toastContainer ? toastContainer.querySelector('.toast') : null;
  const notifToast = toastEl ? new bootstrap.Toast(toastEl, { delay: 8000 }) : null;

  // sonido suave (ping)
  const audioPing = (function(){
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      return function(){
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.type = 'sine';
        o.frequency.value = 880;
        g.gain.value = 0.02;
        o.connect(g);
        g.connect(ctx.destination);
        o.start();
        setTimeout(()=> { o.stop(); }, 120);
      };
    } catch(e){ return ()=>{}; }
  })();

  // ---------------------------
  // Cambio de vista (Timeline <> Tabla)
  // ---------------------------
  const btnChange = document.getElementById('btn-change-view');
  const mainArea = document.getElementById('main-area');
  let isTable = false;
  btnChange.addEventListener('click', () => {
    isTable = !isTable;
    if (isTable) {
      mainArea.classList.add('table');
      btnChange.textContent = 'Ver timeline';
      document.getElementById('table-view').style.display = 'block';
      document.getElementById('timeline-view').style.display = 'none';
    } else {
      mainArea.classList.remove('table');
      btnChange.textContent = 'Ver tabla';
      document.getElementById('table-view').style.display = 'none';
      document.getElementById('timeline-view').style.display = 'block';
    }
  });

  // ---------------------------
  // Quick search (filtrado en cliente)
  // ---------------------------
  const qSearch = document.getElementById('quick-search');
  qSearch.addEventListener('input', (e) => {
    const q = e.target.value.toLowerCase().trim();
    document.querySelectorAll('#timeline-view .t-item').forEach(it => {
      const text = (it.textContent || '').toLowerCase();
      it.style.display = text.indexOf(q) === -1 ? 'none' : 'block';
    });
    // tabla filter
    document.querySelectorAll('#table-body tr').forEach(tr => {
      const text = (tr.textContent || '').toLowerCase();
      tr.style.display = text.indexOf(q) === -1 ? 'none' : '';
    });
  });

  // ---------------------------
  // Filtros rápidos (submit)
  // ---------------------------
  document.getElementById('fast-filter').addEventListener('submit', (e) => {
    e.preventDefault();
    const u = document.getElementById('f-usuario').value.trim();
    const es = document.getElementById('f-estado').value;
    const params = new URLSearchParams();
    if (u) params.set('usuario', u);
    if (es) params.set('estado', es);
    window.location.href = window.location.pathname + '?' + params.toString();
  });
  document.getElementById('reset-filters').addEventListener('click', () => {
    window.location.href = window.location.pathname;
  });
  document.getElementById('btn-refresh').addEventListener('click', () => { manualRefresh(); });

  // ---------------------------
  // Modal detalle y carga de comentarios
  // ---------------------------
  const modalEl = document.getElementById('modalDetalle');
  const modal = new bootstrap.Modal(modalEl);

  function escapeHtml(unsafe) {
    return (unsafe || '')
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
  }

  function openModalWithData(record) {
    // record: { id_historial, id_equipo, equipo, usuario, fecha, detalle, estado_anterior, estado_nuevo }
    document.getElementById('md-equipo').textContent = record.equipo || '';
    document.getElementById('md-usuario').textContent = record.usuario || 'Desconocido';
    document.getElementById('md-fecha').textContent = record.fecha || '';
    document.getElementById('md-detalle').innerHTML = record.detalle ? escapeHtml(record.detalle).replace(/\n/g,'<br>') : '';
    document.getElementById('md-anterior').textContent = record.estado_anterior || '';
    const nuevoBadge = document.getElementById('md-nuevo');
    nuevoBadge.textContent = record.estado_nuevo || '-';
    nuevoBadge.className = 'badge';
    if (record.estado_nuevo === 'Reparado') nuevoBadge.classList.add('bg-success');
    else if (record.estado_nuevo === 'Pendiente') nuevoBadge.classList.add('bg-warning');
    else if (record.estado_nuevo === 'En reparación') nuevoBadge.classList.add('bg-info');
    else nuevoBadge.classList.add('bg-secondary');

    // fill hidden inputs for comments
    document.getElementById('coment-id_historial').value = record.id_historial || '';
    document.getElementById('coment-id_equipo').value = record.id_equipo || '';

    loadComments(record.id_equipo);
    modal.show();
  }

  // Abrir modal al hacer click en botones .btn-view
  function attachViewButtons() {
    document.querySelectorAll('.btn-view').forEach(btn => {
      // remove previous handlers safely by cloning
      const newBtn = btn.cloneNode(true);
      btn.parentNode.replaceChild(newBtn, btn);
      newBtn.addEventListener('click', viewClickHandler);
    });
  }

function viewClickHandler(e) {
    const btn = e.currentTarget;
    const id_hist = btn.getAttribute('data-id') || btn.dataset.id;
    const id_equipo = btn.getAttribute('data-id-equipo') || btn.dataset.idEquipo;
    const equipoText = btn.getAttribute('data-equipo') || btn.dataset.equipo || '';

    if (!id_hist) {
        alert('❌ No se pudo determinar el ID del historial.');
        return;
    }

    fetch(window.location.pathname, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ action:'get_detail', id_historial:id_hist })
    }).then(r=>r.json()).then(data=>{
        if (!data.ok) {
            openModalWithData({
                id_historial: id_hist,
                id_equipo: id_equipo || '',
                equipo: equipoText,
                usuario: '',
                fecha: '',
                detalle: '',
                estado_anterior: '',
                estado_nuevo: ''
            });
            return;
        }
        openModalWithData(data.record);
    }).catch(()=>{
        openModalWithData({
            id_historial: id_hist,
            id_equipo: id_equipo || '',
            equipo: equipoText,
            usuario: '',
            fecha: '',
            detalle: '',
            estado_anterior: '',
            estado_nuevo: ''
        });
    });
}


  // Attach once on load
  attachViewButtons();

  // ---------------------------
  // Load comments (AJAX)
  // ---------------------------
  function loadComments(id_equipo) {
    const list = document.getElementById('comentarios-list');
    list.innerHTML = '<div class="small-muted">Cargando comentarios…</div>';
    if (!id_equipo) {
      list.innerHTML = '<div class="small-muted">Sin comentarios (ID equipo no definido).</div>';
      return;
    }
    fetch(window.location.pathname, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({ action: 'get_comments', id_producto: id_equipo })
    }).then(r=>r.json()).then(data=>{
      if (!data.ok) { list.innerHTML = '<div class="text-danger">No se pudieron cargar comentarios.</div>'; return; }
      if (!data.comentarios || data.comentarios.length === 0) { list.innerHTML = '<div class="small-muted">Sin comentarios aún.</div>'; return; }
      list.innerHTML = '';
      data.comentarios.forEach(c => {
        const p = document.createElement('div');
        p.className = 'mb-2';
        p.innerHTML = `<div class="fw-bold small">${c.usuario} <small class="small-muted">· ${c.fecha}</small></div><div>${c.comentario}</div>`;
        list.appendChild(p);
      });
    }).catch(()=>{ list.innerHTML = '<div class="text-danger">Error al cargar comentarios.</div>'; });
  }

  // ---------------------------
  // Submit comentario (AJAX)
  // ---------------------------
  document.getElementById('form-comentario').addEventListener('submit', (e) => {
    e.preventDefault();
    const id_hist = document.getElementById('coment-id_historial').value;
    const id_equipo = document.getElementById('coment-id_equipo').value;
    const usuario = document.getElementById('coment-usuario').value.trim();
    const texto = document.getElementById('coment-text').value.trim();
    if (!usuario || !texto) return alert('Completa usuario y comentario.');

    // preferimos enviar id_producto (id_equipo) porque tus comentarios están ligados al equipo
    const bodyParams = new URLSearchParams({ action: 'add_comment', usuario: usuario, comentario: texto });
    if (id_equipo) bodyParams.set('id_producto', id_equipo);
    else if (id_hist) bodyParams.set('id_historial', id_hist);

    fetch(window.location.pathname, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: bodyParams
    }).then(r=>r.json()).then(data=>{
      if (data.ok) {
        // Añadir al inicio de la lista
        const list = document.getElementById('comentarios-list');
        const p = document.createElement('div');
        p.className = 'mb-2';
        p.innerHTML = `<div class="fw-bold small">${data.comentario.usuario} <small class="small-muted">· ${data.comentario.fecha}</small></div><div>${data.comentario.comentario}</div>`;
        list.prepend(p);
        document.getElementById('coment-text').value = '';

        // Mostrar toast de éxito
        if (notifToast) {
          document.getElementById('toast-body').textContent = `✅ Comentario agregado por ${data.comentario.usuario}`;
          notifToast.show();
        } else {
          // fallback alert
          console.info('Comentario agregado');
        }

      } else {
        alert('Error: ' + data.msg);
      }
    }).catch(()=>alert('Error de conexión al enviar comentario.'));
  });

  // ---------------------------
  // Polling: comprobar nuevos registros
  // ---------------------------
  function checkNew() {
    fetch(window.location.pathname, {
      method:'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({ action:'check_new', last_max_id: lastMaxId })
    }).then(r=>r.json()).then(data=>{
      if (!data.ok) return;
      if (data.new) {
        lastMaxId = data.maxid;
        // Mostrar toast y sonido suave
        if (notifToast) {
          document.getElementById('toast-body').textContent = `🔔 Nuevo cambio por ${data.record.usuario} en ${data.record.equipo}`;
          notifToast.show();
        }
        audioPing();
      }
    }).catch(()=>{/* ignore errors silently */});
  }

  function startPolling(){
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(checkNew, pollInterval);
  }
  startPolling();

  // manual refresh recarga página (simple)
  function manualRefresh(){
    window.location.reload();
  }

  // ---------------------------
  // Theme (dark / light) toggle
  // ---------------------------
  const themeBtn = document.getElementById('theme-toggle');
  function applyTheme(v) {
    if (v === 'dark') document.body.classList.add('dark-mode');
    else document.body.classList.remove('dark-mode');
    localStorage.setItem('hist_theme', v);
  }
  themeBtn.addEventListener('click', ()=>{
    const cur = localStorage.getItem('hist_theme') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark':'light');
    const next = cur === 'dark' ? 'light' : 'dark';
    applyTheme(next);
  });
  (function initTheme(){
    const saved = localStorage.getItem('hist_theme');
    if (saved) applyTheme(saved);
    else {
      const prefer = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
      applyTheme(prefer);
    }
  })();

  // Si la tabla o timeline se recargan dinámicamente (p. ej. por JS) -> re-attach buttons
  // (no necesario ahora, pero útil si luego actualizas sin reload)
  // observe mutations in case new .btn-view appear
  const observer = new MutationObserver(() => { attachViewButtons(); });
  observer.observe(document.getElementById('timeline-view'), { childList: true, subtree: true });
  observer.observe(document.getElementById('table-body'), { childList: true, subtree: true });

});
</script>

<?php
// footer y asistente
include "includes/footer.php";
include 'asistente/bot.php';
?>
