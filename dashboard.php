<?php
session_start();
require_once "db.php";
require_once "includes/funciones.php";
require_once "includes/header.php";

// ---------- Estadísticas ----------
$total_equipos = $conn->query("SELECT COUNT(*) AS n FROM equipos")->fetch_assoc()['n'] ?? 0;
$en_uso = $conn->query("SELECT COUNT(*) AS n FROM equipos WHERE estado='En uso'")->fetch_assoc()['n'] ?? 0;
$en_reparacion = $conn->query("SELECT COUNT(*) AS n FROM equipos WHERE estado='En preparacion'")->fetch_assoc()['n'] ?? 0;
$dado_baja = $conn->query("SELECT COUNT(*) AS n FROM equipos WHERE estado='Dado de baja'")->fetch_assoc()['n'] ?? 0;

// Última entrada y salida
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
if($mv){
    while($r = $mv->fetch_assoc()){
        array_unshift($labels, $r['ym']);
        array_unshift($entradas, (int)$r['entradas']);
        array_unshift($salidas, (int)$r['salidas']);
    }
}

// ---------- Estados por nivel ----------
$res = $conn->query("SELECT nivel, estado, COUNT(*) AS c FROM equipos GROUP BY nivel, estado");
$estData = [];
if($res){
    while($r = $res->fetch_assoc()){
        $estData[$r['nivel']][$r['estado']] = (int)$r['c'];
    }
}

// ---------- Top equipos con más fallas ----------
$top_sql = "
SELECT e.id_equipo, COALESCE(e.codigo_equipo,'-') AS codigo_equipo, 
       COALESCE(e.marca,'-') AS marca, COALESCE(e.modelo,'-') AS modelo, 
       COUNT(r.id_reparacion) AS total_fallas 
FROM equipos e 
LEFT JOIN reparaciones r ON e.id_equipo=r.id_equipo 
GROUP BY e.id_equipo, e.codigo_equipo, e.marca, e.modelo 
ORDER BY total_fallas DESC LIMIT 5";
$top = $conn->query($top_sql);

// ---------- Últimas reparaciones ----------
$ult_reparaciones = $conn->query("
    SELECT r.id_reparacion, e.codigo_equipo, e.marca, e.modelo, r.estado, r.fecha 
    FROM reparaciones r 
    JOIN equipos e ON e.id_equipo=r.id_equipo 
    ORDER BY r.fecha DESC LIMIT 5
");

// ---------- Distribución por tipo de equipo ----------
$tipo_data = [];
$tipos = $conn->query("SELECT tipo, COUNT(*) AS c FROM equipos GROUP BY tipo");
if($tipos){
    while($t = $tipos->fetch_assoc()){
        $tipo_data[$t['tipo']] = (int)$t['c'];
    }
}

?>

<div class="container-fluid mt-3">

  <!-- Tarjetas resumen -->
  <div class="row g-3 mb-3">
    <div class="col-md-2">
      <div class="card text-white bg-primary">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div><small>Total equipos</small><h3><?= $total_equipos ?></h3></div>
          <i class="fa fa-desktop fa-2x"></i>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-white bg-success">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div><small>En uso</small><h3><?= $en_uso ?></h3></div>
          <i class="fa fa-laptop fa-2x"></i>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-dark bg-warning">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div><small>En reparación</small><h3><?= $en_reparacion ?></h3></div>
          <i class="fa fa-wrench fa-2x"></i>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-white bg-danger">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div><small>Dado de baja</small><h3><?= $dado_baja ?></h3></div>
          <i class="fa fa-trash fa-2x"></i>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-white bg-info">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div><small>Última entrada</small><h3><?= $ultima_entrada ?></h3></div>
          <i class="fa fa-arrow-up fa-2x"></i>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-white bg-secondary">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div><small>Última salida</small><h3><?= $ultima_salida ?></h3></div>
          <i class="fa fa-arrow-down fa-2x"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Accesos rápidos -->
  <div class="mb-3 d-flex justify-content-between">
    <div>
      <a class="btn btn-primary" href="equipos.php?action=create"><i class="fa fa-plus"></i> Agregar equipo</a>
      <a class="btn btn-warning" href="reparaciones.php"><i class="fa fa-wrench"></i> Ver reparaciones</a>

<a class="btn btn-secondary" href="movimientos.php">
  <i class="fa fa-exchange-alt"></i> Movimientos
</a>


      <a class="btn btn-info" href="reportes.php"><i class="fa fa-file-alt"></i> Reportes</a>
            <a class="btn btn-success" href="export_excel.php"><i class="fa fa-file-excel"></i> Exportar Excel</a>
    </div>
    <div class="d-flex">
      <input id="f_q" class="form-control form-control-sm me-2" placeholder="Buscar código/marca/modelo...">
      <select id="f_nivel" class="form-select form-select-sm me-2">
        <option value="">Nivel (Todos)</option>
        <option value="Basica">Básica</option>
        <option value="Media">Media</option>
        <option value="EPA">EPA</option>
        <option value="Otros">Otros</option>
      </select>
      <button class="btn btn-sm btn-outline-secondary" onclick="applyFilters()">Filtrar</button>
    </div>
  </div>

  <!-- Gráficos -->
  <div class="row mb-3">
    <div class="col-md-6"><canvas id="chartMov"></canvas></div>
    <div class="col-md-6"><canvas id="chartEstados"></canvas></div>
  </div>

  <!-- Secciones adicionales -->
  <div class="row">
    <div class="col-md-6">
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
    <div class="col-md-6" id="results_table">
      <h5>Resultados</h5>
      Usa el filtro para ver los equipos aquí.
    </div>
  </div>

  <!-- Últimas reparaciones -->
  <div class="row mt-3">
    <div class="col-md-12">
      <h5>Últimas reparaciones</h5>
      <table class="table table-sm table-striped">
        <thead>
          <tr><th>ID</th><th>Código</th><th>Marca/Modelo</th><th>Estado</th><th>Fecha</th></tr>
        </thead>
        <tbody>
        <?php
        if($ult_reparaciones && $ult_reparaciones->num_rows > 0){
            while($r = $ult_reparaciones->fetch_assoc()){
                echo "<tr>
                        <td>{$r['id_reparacion']}</td>
                        <td>{$r['codigo_equipo']}</td>
                        <td>{$r['marca']} {$r['modelo']}</td>
                        <td>{$r['estado']}</td>
                        <td>{$r['fecha']}</td>
                      </tr>";
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

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

<script>
const labels = <?= json_encode($labels) ?>;
const entradas = <?= json_encode($entradas) ?>;
const salidas = <?= json_encode($salidas) ?>;

// Gráfico de movimientos
new Chart(document.getElementById('chartMov'), {
  type: 'line',
  data: {
    labels,
    datasets: [
      { label: 'Entradas', data: entradas, borderColor: 'green', fill: false },
      { label: 'Salidas', data: salidas, borderColor: 'red', fill: false }
    ]
  },
  options: { responsive:true, maintainAspectRatio:false }
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
  options: { responsive:true, maintainAspectRatio:false, scales:{x:{stacked:true}, y:{stacked:true, beginAtZero:true}} }
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
