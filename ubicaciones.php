<?php
require_once "includes/header.php";

$res = $conn->query("SELECT u.*, COUNT(e.id_equipo) as total_equipos 
                     FROM ubicaciones u 
                     LEFT JOIN equipos e ON e.id_ubicacion = u.id_ubicacion 
                     GROUP BY u.id_ubicacion");

if (!$res) {
    die('❌ Error en la consulta: ' . $conn->error);
}


?>
<h3>Ubicaciones y Salas</h3>

<input id="f_nivel" class="form-control mb-2 w-25" placeholder="Filtrar por nivel...">

<table class="table table-striped" id="tabla_ubicaciones">
<thead>
<tr><th>ID</th><th>Nivel</th><th>Sala</th><th>Descripción</th><th>Total equipos</th></tr>
</thead>
<tbody>
<?php while($r = $res->fetch_assoc()): ?>
<tr>
<td><?= $r['id_ubicacion'] ?></td>
<td><?= $r['nivel'] ?></td>
<td><?= $r['nombre'] ?></td>
<td><?= $r['descripcion'] ?></td>
<td><?= $r['total_equipos'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<script>
document.getElementById('f_nivel').addEventListener('input', function() {
    const val = this.value.toLowerCase();
    document.querySelectorAll('#tabla_ubicaciones tbody tr').forEach(tr => {
        tr.style.display = tr.children[1].textContent.toLowerCase().includes(val) ? '' : 'none';
    });
});
</script>

<?php include "includes/footer.php"; ?>

