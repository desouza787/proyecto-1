// assets/js/filtros.js
async function applyFiltersGlobal(qEl = 'f_q', nivelEl='f_nivel', targetId='results_table'){
  const q = document.getElementById(qEl).value;
  const nivel = document.getElementById(nivelEl).value;
  const res = await fetch(`/registro_inventario/ajax/filtros.php?q=${encodeURIComponent(q)}&nivel=${encodeURIComponent(nivel)}`);
  const data = await res.json();
  let html = '<table class="table table-sm"><thead><tr><th>ID</th><th>Código</th><th>Nombre</th><th>Marca</th><th>Nivel</th><th>Estado</th><th>Acción</th></tr></thead><tbody>';
  data.forEach(d => {
    html += `<tr>
      <td>${d.id_equipo}</td>
      <td>${d.codigo_equipo}</td>
      <td>${d.nombre}</td>
      <td>${d.marca||''}</td>
      <td>${d.nivel||''}</td>
      <td>${d.estado||''}</td>
      <td><button class="btn btn-sm btn-outline-primary" onclick="showDetalle(${d.id_equipo})">Ver</button></td>
    </tr>`;
  });
  html += '</tbody></table>';
  document.getElementById(targetId).innerHTML = html;
}
