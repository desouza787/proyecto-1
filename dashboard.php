<?php
session_start();
require_once "db.php";
require_once "includes/funciones.php";
require_once "includes/header.php";


// ==========================
// BÚSQUEDA GENERAL AGREGADA
// ==========================
$equipo_encontrado = null;
if (isset($_GET['q']) && $_GET['q'] !== "") {
$q = trim($_GET['q']);
$sql = "SELECT e.*, d.nombre_dep, c.nombre_categoria, u.nombre AS nombre_ubicacion
FROM equipos e
LEFT JOIN departamentos d ON e.id_dep = d.id_dep
LEFT JOIN categorias c ON e.id_categoria = c.id_categoria
LEFT JOIN ubicaciones u ON e.id_ubicacion = u.id_ubicacion
WHERE e.id_equipo = ? OR e.codigo_equipo LIKE ?";
$stmt = $conn->prepare($sql);
$codigo_like = "%$q%";
$stmt->bind_param("is", $q, $codigo_like);
$stmt->execute();
$resultado = $stmt->get_result();
if ($resultado->num_rows > 0) {
$equipo_encontrado = $resultado->fetch_assoc();
}
}


// ---------- Estadísticas ----------
$total_equipos = $conn->query("SELECT COUNT(*) AS n FROM equipos")->fetch_assoc()['n'] ?? 0;
$en_uso = $conn->query("SELECT COUNT(*) AS n FROM equipos WHERE estado='En uso'")->fetch_assoc()['n'] ?? 0;
$en_reparacion = $conn->query("SELECT COUNT(*) AS n FROM reparaciones WHERE estado='En reparación'")->fetch_assoc()['n'] ?? 0;
$pendientes = $conn->query("SELECT COUNT(*) AS n FROM reparaciones WHERE estado='Pendiente'")->fetch_assoc()['n'] ?? 0;
$reparados = $conn->query("SELECT COUNT(*) AS n FROM reparaciones WHERE estado='Reparado'")->fetch_assoc()['n'] ?? 0;
$dado_baja = $conn->query("SELECT COUNT(*) AS n FROM equipos WHERE estado='Dado de baja'")->fetch_assoc()['n'] ?? 0;


// ---------- Última entrada/salida ----------
$ultima_entrada = $conn->query("SELECT cantidad FROM movimientos WHERE tipo='entrada' ORDER BY fecha DESC LIMIT 1")->fetch_assoc()['cantidad'] ?? 0;
$ultima_salida = $conn->query("SELECT cantidad FROM movimientos WHERE tipo='salida' ORDER BY fecha DESC LIMIT 1")->fetch_assoc()['cantidad'] ?? 0;


// ---------- Movimientos mensuales ----------
$mv = $conn->query("
SELECT DATE_FORMAT(fecha,'%Y-%m') AS ym,
SUM(CASE WHEN tipo='entrada' THEN cantidad ELSE 0 END) AS entradas,
SUM(CASE WHEN tipo='salida' THEN cantidad ELSE 0 END) AS salidas
FROM movimientos
GROUP BY ym ORDER BY ym DESC LIMIT 12
");
$labels = $entradas = $salidas = [];
if($mv){ while($r = $mv->fetch_assoc()){ array_unshift($labels,$r['ym']); array_unshift($entradas,(int)$r['entradas']); array_unshift($salidas,(int)$r['salidas']); }}


// ---------- Estados por nivel ----------
$res = $conn->query("SELECT nivel, estado, COUNT(*) AS c FROM equipos GROUP BY nivel, estado");
$estData = [];
if($res){ while($r = $res->fetch_assoc()){ $estData[$r['nivel']][$r['estado']] = (int)$r['c']; }}


// ---------- Top fallas ----------
$top_sql = "SELECT e.id_equipo, COALESCE(e.codigo_equipo,'-') AS codigo_equipo, COALESCE(e.marca,'-') AS marca, COALESCE(e.modelo,'-') AS modelo, COUNT(r.id_reparacion) AS total_fallas FROM equipos e LEFT JOIN reparaciones r ON e.id_equipo=r.id_equipo GROUP BY e.id_equipo ORDER BY total_fallas DESC LIMIT 5";
$top = $conn->query($top_sql);


// ---------- Últimas reparaciones ----------
$ult_reparaciones = $conn->query("SELECT r.id_reparacion, e.codigo_equipo, e.marca, e.modelo, r.estado, COALESCE(r.fecha_fin, r.fecha_inicio) AS fecha FROM reparaciones r JOIN equipos e ON e.id_equipo=r.id_equipo ORDER BY r.fecha_inicio DESC LIMIT 5");
?>


<div class="container-fluid mt-3">


<!-- BARRA DE BÚSQUEDA GENERAL -->
<form method="GET" action="" class="mb-4">
<div class="input-group">
<input type="text" name="q" class="form-control" placeholder="Buscar equipo por ID o Código" value="<?= isset($_GET['q']) ? $_GET['q'] : '' ?>">
<button class="btn btn-primary">Buscar</button>
</div>
</form>


<?php if ($equipo_encontrado): ?>
<div class="card p-3 shadow-sm mb-4">
<h5><?= $equipo_encontrado['codigo_equipo'] ?> — <?= $equipo_encontrado['marca'] . " " . $equipo_encontrado['modelo'] ?></h5>
<p><strong>Serie:</strong> <?= $equipo_encontrado['serie'] ?></p>
<p><strong>Procesador:</strong> <?= $equipo_encontrado['procesador'] ?></p>
<p><strong>RAM:</strong> <?= $equipo_encontrado['ram'] ?></p>
<p><strong>Disco:</strong> <?= $equipo_encontrado['disco'] ?></p>
<p><strong>Estado:</strong> <?= $equipo_encontrado['estado'] ?></p>
<p><strong>Departamento:</strong> <?= $equipo_encontrado['nombre_dep'] ?></p>
<p><strong>Categoría:</strong> <?= $equipo_encontrado['nombre_categoria'] ?></p>
<p><strong>Ubicación:</strong> <?= $equipo_encontrado['nombre_ubicacion'] ?></p>
<a href="equipos.php?id=<?= $equipo_encontrado['id_equipo'] ?>" class="btn btn-success">Ver este equipo</a>
</div>
<?php endif; ?>


<!-- TODO EL RESTO DEL DASHBOARD SE MANTIENE IGUAL -->


<div class="row g-3 mb-3">
<div class="col-md-2"><div class="card text-white bg-primary"><div class="card-body"><small>Total equipos</small><h3><?= $total_equipos ?></h3></div></div></div>
<div class="col-md-2"><div class="card text-white bg-success"><div class="card-body"><small>En uso</small><h3><?= $en_uso ?></h3></div></div></div>
<div class="col-md-2"><div class="card text-dark bg-warning"><div class="card-body"><small>En reparación</small><h3><?= $en_reparacion ?></h3></div></div></div>
<div class="col-md-2"><div class="card text-white bg-danger"><div class="card-body"><small>Dado de baja</small><h3><?= $dado_baja ?></h3></div></div></div>
<div class="col-md-2"><div class="card text-white bg-secondary"><div class="card-body"><small>Pendientes</small><h3><?= $pendientes ?></h3></div></div></div>
<div class="col-md-2"><div class="card text-white bg-info"><div class="card-body"><small>Reparados</small><h3><?= $reparados ?></h3></div></div></div>
</div>


<!-- RESTO DEL ARCHIVO SIN CAMBIOS ... -->

  <!-- Accesos rápidos -->
  <div class="mb-3 d-flex justify-content-between flex-wrap">
    <div class="mb-2">
      <a class="btn btn-primary" href="equipos.php?action=create"><i class="fa fa-plus"></i> Agregar equipo</a>
      <a class="btn btn-warning" href="reparaciones.php"><i class="fa fa-wrench"></i> Ver reparaciones</a>
      <a class="btn btn-secondary" href="movimientos.php"><i class="fa fa-exchange-alt"></i> Movimientos</a>
      <a class="btn btn-info" href="reportes.php"><i class="fa fa-file-alt"></i> Reportes</a>
      <a class="btn btn-success" href="export_todo_excel.php" target="_blank"><i class="fa fa-file-excel"></i> Excel</a>
      <a class="btn btn-danger" href="export_todo_pdf.php" target="_blank"><i class="fa fa-file-pdf"></i> PDF</a>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
      <input id="f_q" class="form-control form-control-sm" placeholder="Buscar código/marca/modelo..." style="min-width:180px;">
      <select id="f_nivel" class="form-select form-select-sm">
        <option value="">Nivel (Todos)</option>
        <option value="Basica">Básica</option>
        <option value="Media">Media</option>
        <option value="EPA">EPA</option>
        <option value="Otros">Otros</option>
      </select>
      <button class="btn btn-sm btn-outline-secondary" onclick="applyFilters()">Filtrar</button>
    </div>
  </div>

  <!-- Gráficos (exactamente como el original) -->
  <div class="row mb-3">
    <div class="col-md-6"><canvas id="chartMov"></canvas></div>
    <div class="col-md-6"><canvas id="chartEstados"></canvas></div>
  </div>

  <!-- Top 5 fallas -->
  <div class="row mb-4">
    <div class="col-md-12">
      <h5>Top 5 equipos con más fallas</h5>
      <ul>
        <?php
        if($top && $top->num_rows > 0){
            while($t = $top->fetch_assoc()){
                echo "<li>{$t['codigo_equipo']} — {$t['marca']} {$t['modelo']} ({$t['total_fallas']} fallas)</li>";
            }
        } else {
            echo "<li>No hay datos de fallas.</li>";
        }
        ?>
      </ul>
    </div>
  </div>

  <!-- Secciones mejoradas sin tocar el flujo -->
  <div class="row">
    <div class="col-md-12 mb-4" id="results_table">
      <div class="p-3 border rounded shadow-sm bg-white">
        <h5 class="mb-3"><i class="fa fa-desktop text-primary"></i> Equipos recientes</h5>
        <table class="table table-sm table-striped">
          <thead><tr><th>ID</th><th>Código</th><th>Marca/Modelo</th><th>Estado</th></tr></thead>
          <tbody>
          <?php
          $equipos_recientes = $conn->query("SELECT id_equipo, codigo_equipo, marca, modelo, estado FROM equipos ORDER BY id_equipo DESC LIMIT 5");
          if($equipos_recientes && $equipos_recientes->num_rows > 0){
              while($e = $equipos_recientes->fetch_assoc()){
                  echo "<tr><td>{$e['id_equipo']}</td><td>{$e['codigo_equipo']}</td><td>{$e['marca']} {$e['modelo']}</td><td>{$e['estado']}</td></tr>";
              }
          } else {
              echo "<tr><td colspan='4'>No hay equipos registrados</td></tr>";
          }
          ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="col-md-12 mb-5">
      <div class="p-3 border rounded shadow-sm bg-white">
        <h5 class="mb-3"><i class="fa fa-tools text-warning"></i> Últimas reparaciones</h5>
        <table class="table table-sm table-striped">
          <thead><tr><th>ID</th><th>Código</th><th>Marca/Modelo</th><th>Estado</th><th>Fecha</th></tr></thead>
          <tbody>
          <?php
          if($ult_reparaciones && $ult_reparaciones->num_rows > 0){
              while($r = $ult_reparaciones->fetch_assoc()){
                  echo "<tr><td>{$r['id_reparacion']}</td><td>{$r['codigo_equipo']}</td><td>{$r['marca']} {$r['modelo']}</td><td>{$r['estado']}</td><td>{$r['fecha']}</td></tr>";
              }
          } else {
              echo "<tr><td colspan='5'>No hay reparaciones registradas</td></tr>";
          }
          ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

<script>
const labels = <?= json_encode($labels) ?>;
const entradas = <?= json_encode($entradas) ?>;
const salidas = <?= json_encode($salidas) ?>;

// Gráfico de movimientos (idéntico al tuyo original)
new Chart(document.getElementById('chartMov'), {
  type: 'line',
  data: {
    labels,
    datasets: [
      { label: 'Entradas', data: entradas, borderColor: 'green', fill: false },
      { label: 'Salidas', data: salidas, borderColor: 'red', fill: false }
    ]
  },
  options: { responsive:true, maintainAspectRatio:true }
});

// Gráfico de estados por nivel
const estData = <?= json_encode($estData) ?>;
const levels = Object.keys(estData);
const states = Array.from(new Set(levels.flatMap(l => Object.keys(estData[l]))));
const datasets = states.map((s,i) => ({
  label: s,
  data: levels.map(l => estData[l][s]||0),
  backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545'][i%4]
}));
new Chart(document.getElementById('chartEstados'), {
  type: 'bar',
  data: { labels: levels, datasets },
  options: { responsive:true, maintainAspectRatio:true, scales:{x:{stacked:true}, y:{stacked:true, beginAtZero:true}} }
});

// Filtros Ajax
async function applyFilters(){
  const q = document.getElementById('f_q').value;
  const nivel = document.getElementById('f_nivel').value;
  const res = await fetch(`ajax/filtros.php?q=${encodeURIComponent(q)}&nivel=${encodeURIComponent(nivel)}`);
  const data = await res.json();
  let html = '<table id="dt_results" class="table table-sm table-striped"><thead><tr><th>ID</th><th>Código</th><th>Marca/Modelo</th><th>Nivel</th><th>Estado</th><th>Acción</th></tr></thead><tbody>';
  data.forEach(d=>{
    html += `<tr>
      <td>${d.id_equipo}</td>
      <td>${d.codigo_equipo}</td>
      <td>${d.marca||''} ${d.modelo||''}</td>
      <td>${d.nivel||''}</td>
      <td>${d.estado||''}</td>
      <td><button class="btn btn-sm btn-outline-primary" onclick="showDetalle(${d.id_equipo})">Ver</button></td>
    </tr>`;
  });
  html += '</tbody></table>';
  document.getElementById('results_table').innerHTML = html;
  $('#dt_results').DataTable({ pageLength:5, lengthChange:false, order:[[0,'desc']] });
}
</script>

<?php include "includes/footer.php"; ?>
<?php include 'asistente/bot.php'; ?>
