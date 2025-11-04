<?php
require_once "includes/header.php";

// Opciones de filtros
$niveles = ['Básica','Media','EPA','Otros'];
$estados = ['En uso','En reparación','Dado de baja'];

?>

<h3>Reportes de Equipos</h3>

<div class="row mb-3">
  <div class="col-md-3">
    <select id="f_nivel" class="form-select">
      <option value="">Nivel (Todos)</option>
      <?php foreach($niveles as $n): ?>
        <option value="<?= $n ?>"><?= $n ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <select id="f_estado" class="form-select">
      <option value="">Estado (Todos)</option>
      <?php foreach($estados as $e): ?>
        <option value="<?= $e ?>"><?= $e ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3">
    <input type="date" id="f_fecha" class="form-control">
  </div>
  <div class="col-md-3">
    <button class="btn btn-primary" onclick="cargarReporte()">Aplicar filtros</button>
  </div>
</div>

<div id="tabla_reporte">Seleccione filtros para ver resultados</div>

<div class="row mt-4">
  <div class="col-md-6">
    <canvas id="chartEstados"></canvas>
  </div>
  <div class="col-md-6">
    <canvas id="chartNiveles"></canvas>
  </div>
</div>

<script>
async function cargarReporte() {
  const nivel = document.getElementById('f_nivel').value;
  const estado = document.getElementById('f_estado').value;
  const fecha = document.getElementById('f_fecha').value;

  const res = await fetch(`ajax/reportes.php?nivel=${encodeURIComponent(nivel)}&estado=${encodeURIComponent(estado)}&fecha=${encodeURIComponent(fecha)}`);
  const data = await res.json();

  // Tabla
  let html = '<table class="table table-sm"><thead><tr><th>ID</th><th>Código</th><th>Nombre</th><th>Marca</th><th>Nivel</th><th>Estado</th><th>Fecha Ingreso</th></tr></thead><tbody>';
  data.equipos.forEach(d => {
    html += `<tr>
      <td>${d.id_equipo}</td>
      <td>${d.codigo_equipo}</td>
      <td>${d.nombre}</td>
      <td>${d.marca||''}</td>
      <td>${d.nivel||''}</td>
      <td>${d.estado||''}</td>
      <td>${d.fecha_ingreso}</td>
    </tr>`;
  });
  html += '</tbody></table>';
  document.getElementById('tabla_reporte').innerHTML = html;

  // Gráficos
  const estadosLabels = Object.keys(data.chartEstados);
  const estadosData = Object.values(data.chartEstados);

  const nivelesLabels = Object.keys(data.chartNiveles);
  const nivelesData = Object.values(data.chartNiveles);

  new Chart(document.getElementById('chartEstados'), {
    type: 'pie',
    data: { labels: estadosLabels, datasets: [{ label: 'Equipos por estado', data: estadosData, backgroundColor:['#4e73df','#1cc88a','#e74a3b'] }] }
  });

  new Chart(document.getElementById('chartNiveles'), {
    type: 'bar',
    data: { labels: nivelesLabels, datasets: [{ label: 'Equipos por nivel', data: nivelesData, backgroundColor:'#36b9cc' }] }
  });
}
</script>

<?php include "includes/footer.php"; ?>
