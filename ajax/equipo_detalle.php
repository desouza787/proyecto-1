<?php
// ajax/equipo_detalle.php
require_once __DIR__ . '/../conexion.php';
require_once __DIR__ . '/../includes/funciones.php';
$id = intval($_GET['id'] ?? 0);
if($id <= 0){ echo "Equipo inválido"; exit; }

$st = $conn->prepare("SELECT e.*, c.nombre_categoria, d.nombre_dep FROM equipos e LEFT JOIN categorias c ON e.id_categoria=c.id_categoria LEFT JOIN departamentos d ON e.id_dep=d.id_dep WHERE e.id_equipo = ?");
$st->bind_param("i",$id); $st->execute(); $eq = $st->get_result()->fetch_assoc();

if(!$eq){ echo "No encontrado"; exit; }

// historial (últimos 20)
$h = $conn->prepare("SELECT usuario, fecha, detalle, estado_anterior, estado_nuevo FROM historial_estados WHERE id_equipo=? ORDER BY fecha DESC LIMIT 20");
$h->bind_param("i",$id); $h->execute(); $hist = $h->get_result();
?>
<div class="row">
  <div class="col-md-4">
    <?php if(!empty($eq['imagen']) && file_exists(__DIR__ . '/../' . $eq['imagen'])): ?>
      <img src="/registro_inventario/<?= e($eq['imagen']) ?>" class="img-fluid mb-2" alt="imagen">
    <?php else: ?>
      <div class="bg-light p-4 text-center mb-2">Sin imagen</div>
    <?php endif; ?>
    <p><strong>Código:</strong> <?= e($eq['codigo_equipo'] ?? '-') ?></p>
    <p><strong>Ubicación:</strong> <?= e($eq['nombre_dep'] ?? '-') ?> / <?= e($eq['nivel'] ?? '-') ?></p>
    <p><strong>Estado:</strong> <?= e($eq['estado'] ?? '-') ?></p>
  </div>
  <div class="col-md-8">
    <h5><?= e($eq['nombre'] ?? '-') ?></h5>
    <p><?= e($eq['observaciones'] ?? '') ?></p>
    <hr>
    <h6>Historial reciente</h6>
    <div style="max-height:220px; overflow:auto;">
      <?php while($r = $hist->fetch_assoc()): ?>
        <div class="small mb-2"><strong><?= e($r['usuario']) ?></strong> • <?= e($r['fecha']) ?><br><?= e($r['detalle']) ?> (<?= e($r['estado_anterior']) ?> → <?= e($r['estado_nuevo']) ?>)</div>
      <?php endwhile; ?>
    </div>

    <hr>
    <h6>Notas</h6>
    <div id="notas_container">
      <?php
      $nc = $conn->prepare("SELECT id_com, usuario, comentario, fecha FROM comentarios WHERE id_producto=? ORDER BY fecha DESC LIMIT 50");
      $nc->bind_param("i",$id); $nc->execute(); $notes = $nc->get_result();
      while($n = $notes->fetch_assoc()): ?>
        <div class="border rounded p-2 mb-2"><small class="text-muted"><?= e($n['usuario']) ?> • <?= e($n['fecha']) ?></small><div><?= e($n['comentario']) ?></div></div>
      <?php endwhile; ?>
    </div>

    <div class="mt-2">
      <textarea id="nota_text" class="form-control mb-2" rows="2" placeholder="Agregar nota..."></textarea>
      <button class="btn btn-sm btn-primary" onclick="addNote(<?= $id ?>)">Guardar nota</button>
    </div>
  </div>
</div>

<script>
async function addNote(id){
  const text = document.getElementById('nota_text').value;
  if(!text.trim()) return alert('Ingrese nota');
  const form = new FormData();
  form.append('id_equipo', id);
  form.append('comentario', text);
  const res = await fetch('/registro_inventario/ajax/notas_ajax.php', { method:'POST', body: form});
  const data = await res.json();
  if(data.ok){
    // insertar al inicio en container
    const c = document.getElementById('notas_container');
    const d = document.createElement('div'); d.className='border rounded p-2 mb-2';
    d.innerHTML = `<small class="text-muted"><?= e($_SESSION['usuario'] ?? 'Yo') ?> • ahora</small><div>${text}</div>`;
    c.prepend(d);
    document.getElementById('nota_text').value = '';
  } else {
    alert('Error al guardar');
  }
}
</script>
