<?php
session_start();
require_once "db.php";
require_once "includes/header.php";
?>

<div class="container mt-4">
  <h3><i class="fa fa-exchange-alt"></i> Registro de Entradas y Salidas</h3>
  <hr>

  <!-- Formulario de movimiento -->
  <form method="POST" action="">
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <label class="form-label">Tipo de movimiento</label>
        <select name="tipo" class="form-select" required>
          <option value="">Seleccione...</option>
          <option value="entrada">Entrada (Recibido)</option>
          <option value="salida">Salida (Prestado)</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">Equipo / Producto</label>
        <select name="id_producto" class="form-select" required>
          <option value="">Seleccione equipo...</option>
          <?php
          $equipos = $conn->query("SELECT id_equipo, codigo_equipo, marca, modelo FROM equipos ORDER BY codigo_equipo ASC");
          while($eq = $equipos->fetch_assoc()){
            echo "<option value='{$eq['id_equipo']}'>{$eq['codigo_equipo']} - {$eq['marca']} {$eq['modelo']}</option>";
          }
          ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">Cantidad</label>
        <input type="number" name="cantidad" class="form-control" min="1" value="1" required>
      </div>

      <div class="col-md-6">
        <label class="form-label">Usuario responsable</label>
        <input type="text" name="usuario" class="form-control" placeholder="Nombre del usuario" required>
      </div>

      <div class="col-md-6">
        <label class="form-label">Detalle / Observación</label>
        <input type="text" name="detalle" class="form-control" placeholder="Ej: préstamo temporal, devolución, etc.">
      </div>

      <div class="col-12 text-end">
        <button type="submit" name="registrar" class="btn btn-primary">
          <i class="fa fa-save"></i> Registrar movimiento
        </button>
      </div>
    </div>
  </form>

  <?php
  // --- Registrar movimiento ---
  if(isset($_POST['registrar'])){
    $tipo = $_POST['tipo'];
    $id_producto = $_POST['id_producto'];
    $cantidad = $_POST['cantidad'];
    $usuario = $_POST['usuario'];
    $detalle = $_POST['detalle'];

    $stmt = $conn->prepare("INSERT INTO movimientos (id_producto, tipo, cantidad, detalle, fecha, usuario) VALUES (?,?,?,?,NOW(),?)");
    $stmt->bind_param("isiss", $id_producto, $tipo, $cantidad, $detalle, $usuario);

    if($stmt->execute()){
      echo "<div class='alert alert-success mt-3'>✅ Movimiento registrado correctamente.</div>";
    } else {
      echo "<div class='alert alert-danger mt-3'>❌ Error al registrar: {$conn->error}</div>";
    }
  }
  ?>

  <hr>
  <h5><i class="fa fa-history"></i> Historial de movimientos</h5>

  <table class="table table-striped table-sm mt-2">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Equipo</th>
        <th>Tipo</th>
        <th>Cantidad</th>
        <th>Detalle</th>
        <th>Usuario</th>
        <th>Fecha</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $res = $conn->query("
      SELECT m.*, e.codigo_equipo, e.marca, e.modelo 
      FROM movimientos m 
      LEFT JOIN equipos e ON e.id_equipo = m.id_producto 
      ORDER BY m.fecha DESC
    ");
    if($res->num_rows > 0){
        if(!$res){
  die("<div class='alert alert-danger'>Error en la consulta: " . $conn->error . "</div>");
}

      while($m = $res->fetch_assoc()){
        echo "<tr>
          <td>{$m['id_mov']}</td>
          <td>{$m['codigo_equipo']} - {$m['marca']} {$m['modelo']}</td>
          <td><span class='badge bg-".($m['tipo']=='entrada'?'success':'danger')."'>{$m['tipo']}</span></td>
          <td>{$m['cantidad']}</td>
          <td>{$m['detalle']}</td>
          <td>{$m['usuario']}</td>
          <td>{$m['fecha']}</td>
        </tr>";
      }
    } else {
      echo "<tr><td colspan='7'>No hay movimientos registrados</td></tr>";
    }
    ?>
    </tbody>
  </table>
</div>

<?php require_once "includes/footer.php"; ?>
