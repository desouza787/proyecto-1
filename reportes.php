<?php
require_once "includes/header.php";

// Opciones de filtros
$niveles = ['Básica','Media','EPA','Otros'];
$estados = ['En uso','En reparación','Dado de baja'];
?>

<div class="container mt-4">
  <h3 class="text-center mb-4">📊 Reportes y Estadísticas de Equipos</h3>

  <!-- Tarjetas de estadísticas -->
  <div class="row text-center mb-4" id="resumen_estadisticas">
    <div class="col-md-3 mb-3">
      <div class="card shadow-sm border-0 bg-primary text-white">
        <div class="card-body">
          <h6>Total Equipos</h6>
          <h4 id="stat_total">0</h4>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card shadow-sm border-0 bg-success text-white">
        <div class="card-body">
          <h6>En Uso</h6>
          <h4 id="stat_uso">0</h4>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card shadow-sm border-0 bg-warning text-dark">
        <div class="card-body">
          <h6>En Reparación</h6>
          <h4 id="stat_reparacion">0</h4>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card shadow-sm border-0 bg-danger text-white">
        <div class="card-body">
          <h6>Dado de Baja</h6>
          <h4 id="stat_baja">0</h4>
        </div>
      </div>
    </div>
  </div>

  <!-- Filtros -->
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
      <button type="button" class="btn btn-primary w-100" onclick="cargarReporte()">📈 Aplicar filtros</button>
    </div>
  </div>

  <!-- Tabla -->
  <div id="tabla_reporte" class="table-responsive shadow-sm p-3 bg-white rounded">
    <p class="text-center text-muted mb-0">Seleccione filtros para ver resultados</p>
  </div>

  <!-- Gráficos -->
  <div class="row mt-5">
    <div class="col-md-4 mb-4">
      <canvas id="chartEstados"></canvas>
    </div>
    <div class="col-md-4 mb-4">
      <canvas id="chartNiveles"></canvas>
    </div>
    <div class="col-md-4 mb-4">
      <canvas id="chartMovimientos"></canvas>
    </div>
  </div>
</div>

<script>
let chartEstados, chartNiveles, chartMovimientos;

async function cargarReporte() {
  const nivel = document.getElementById('f_nivel').value;
  const estado = document.getElementById('f_estado').value;
  const fecha = document.getElementById('f_fecha').value;

  const res = await fetch(`ajax/reportes.php?nivel=${encodeURIComponent(nivel)}&estado=${encodeURIComponent(estado)}&fecha=${encodeURIComponent(fecha)}`);
  const data = await res.json();

  // 🧮 Actualizar estadísticas
  document.getElementById('stat_total').textContent = data.stats.total;
  document.getElementById('stat_uso').textContent = data.stats.uso;
  document.getElementById('stat_reparacion').textContent = data.stats.reparacion;
  document.getElementById('stat_baja').textContent = data.stats.baja;

  // 🧾 Generar tabla
  let html = `
    <table class="table table-striped table-hover align-middle">
      <thead class="table-dark">
        <tr>
          <th>ID</th><th>Código</th><th>Marca</th><th>Modelo</th><th>Nivel</th><th>Estado</th><th>Fecha Ingreso</th>
        </tr>
      </thead>
      <tbody>`;
  data.equipos.forEach(d => {
    html += `
      <tr>
        <td>${d.id_equipo}</td>
        <td>${d.codigo_equipo}</td>
        <td>${d.marca || '-'}</td>
        <td>${d.modelo || '-'}</td>
        <td>${d.nivel || '-'}</td>
        <td>${d.estado || '-'}</td>
        <td>${d.fecha_ingreso || '-'}</td>
      </tr>`;
  });
  html += '</tbody></table>';
  document.getElementById('tabla_reporte').innerHTML = html;

  // 🔄 Eliminar gráficos antiguos si existen
  [chartEstados, chartNiveles, chartMovimientos].forEach(c => { if(c) c.destroy(); });

  // 📊 Equipos por estado
  chartEstados = new Chart(document.getElementById('chartEstados'), {
    type: 'pie',
    data: { 
      labels: Object.keys(data.chartEstados),
      datasets: [{ 
        data: Object.values(data.chartEstados),
        backgroundColor: ['#4e73df','#1cc88a','#f6c23e','#e74a3b'] 
      }] 
    },
    options: { plugins: { title: { display: true, text: 'Equipos por Estado' } } }
  });

  // 📈 Equipos por nivel
  chartNiveles = new Chart(document.getElementById('chartNiveles'), {
    type: 'bar',
    data: { 
      labels: Object.keys(data.chartNiveles),
      datasets: [{ 
        label: 'Cantidad', 
        data: Object.values(data.chartNiveles),
        backgroundColor: '#36b9cc' 
      }]
    },
    options: { plugins: { title: { display: true, text: 'Equipos por Nivel' } }, scales: { y: { beginAtZero: true } } }
  });

  // 🕒 Movimientos (historial)
  chartMovimientos = new Chart(document.getElementById('chartMovimientos'), {
    type: 'line',
    data: {
      labels: Object.keys(data.chartHistorial),
      datasets: [{ 
        label: 'Movimientos por Día', 
        data: Object.values(data.chartHistorial),
        fill: false, 
        borderColor: '#6610f2', 
        tension: 0.3 
      }]
    },
    options: { plugins: { title: { display: true, text: 'Movimientos (Historial de Estados)' } } }
  });
}
</script>

<?php include "includes/footer.php"; ?>
