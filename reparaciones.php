<?php
session_start();
require_once "db.php";
require_once "includes/header.php";

// ✅ Registrar nueva reparación
if (isset($_POST['registrar'])) {
    $id_equipo = $conn->real_escape_string($_POST['id_equipo']);
    $problema = $conn->real_escape_string($_POST['problema']);
    $tecnico = $conn->real_escape_string($_POST['tecnico']);
    $fecha_inicio = $conn->real_escape_string($_POST['fecha_inicio']);
    $fecha_fin = $conn->real_escape_string($_POST['fecha_fin']);
    $solucion = $conn->real_escape_string($_POST['solucion']);
    $estado = $conn->real_escape_string($_POST['estado']);
    $usuario = $_SESSION['usuario'] ?? 'admin';

    $insert = $conn->query("
        INSERT INTO reparaciones 
        (id_equipo, problema, tecnico, fecha_inicio, fecha_fin, solucion, estado, usuario_registra)
        VALUES ('$id_equipo', '$problema', '$tecnico', '$fecha_inicio', '$fecha_fin', '$solucion', '$estado', '$usuario')
    ");

    if ($insert) {
        echo "<div class='alert alert-success'>✅ Reparación registrada correctamente</div>";
 // ✅ Registrar en historial
    $accion = "Se registró una reparación para el equipo ID $id_equipo (problema: $problema)";
    $conn->query("INSERT INTO historial (id_equipo, accion, usuario) VALUES ('$id_equipo', '$accion', '$usuario')");

    

    } else {
        echo "<div class='alert alert-danger'>❌ Error al registrar reparación: {$conn->error}</div>";
    }
}

// ✅ Listar reparaciones
$res = $conn->query("
    SELECT 
        r.id_reparacion, 
        r.problema,
        r.tecnico,
        r.fecha_inicio,
        r.fecha_fin,
        r.solucion,
        r.estado,
        r.usuario_registra,
        e.codigo_equipo,
        e.marca,
        e.modelo
    FROM reparaciones r
    LEFT JOIN equipos e ON e.id_equipo = r.id_equipo
    ORDER BY r.fecha_inicio DESC
");

if (!$res) {
    die('❌ Error en la consulta SQL: ' . $conn->error);
}
?>

<div class="container mt-4">
  <h3 class="mb-4">🔧 Reparaciones</h3>

  <!-- Formulario para nueva reparación -->
  <div class="card mb-4">
    <div class="card-header bg-primary text-white">Registrar nueva reparación</div>
    <div class="card-body">
      <form method="POST" action="">
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Equipo</label>
            <select name="id_equipo" class="form-select" required>
              <option value="">-- Seleccionar equipo --</option>
              <?php
              $equipos = $conn->query("SELECT id_equipo, codigo_equipo, marca, modelo FROM equipos ORDER BY codigo_equipo ASC");
              while ($eq = $equipos->fetch_assoc()) {
                  echo "<option value='{$eq['id_equipo']}'>{$eq['codigo_equipo']} - {$eq['marca']} {$eq['modelo']}</option>";
              }
              ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Técnico</label>
            <input type="text" name="tecnico" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select" required>
              <option value="En reparación">En reparación</option>
              <option value="Reparado">Reparado</option>
              <option value="Pendiente">Pendiente</option>
            </select>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label">Problema</label>
            <input type="text" name="problema" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Fecha inicio</label>
            <input type="date" name="fecha_inicio" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Fecha fin</label>
            <input type="date" name="fecha_fin" class="form-control">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Solución</label>
          <textarea name="solucion" class="form-control" rows="2"></textarea>
        </div>

        <div class="text-end">
          <button type="submit" name="registrar" class="btn btn-success">💾 Guardar reparación</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabla de reparaciones -->
  <table class="table table-striped table-sm align-middle">
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
      <?php while($r = $res->fetch_assoc()): 
        // Colores según estado
        if ($r['estado'] == 'Reparado') $badge = 'success';
        elseif ($r['estado'] == 'En reparación') $badge = 'warning';
        elseif ($r['estado'] == 'Pendiente') $badge = 'danger';
        else $badge = 'secondary';
      ?>
      <tr>
        <td><?= $r['id_reparacion'] ?></td>
        <td><?= $r['codigo_equipo'].' - '.$r['marca'].' '.$r['modelo'] ?></td>
        <td><?= $r['problema'] ?></td>
        <td><?= $r['tecnico'] ?></td>
        <td><?= $r['fecha_inicio'] ?></td>
        <td><?= $r['fecha_fin'] ?></td>
        <td><?= $r['solucion'] ?></td>
        <td><span class="badge bg-<?= $badge ?>"><?= $r['estado'] ?></span></td>
        <td><?= $r['usuario_registra'] ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include "includes/footer.php"; ?>
