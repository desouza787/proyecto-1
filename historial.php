<?php
session_start();
require_once "db.php";
require_once "includes/header.php";

$res = $conn->query("
    SELECT 
        h.id_historial,
        e.codigo_equipo,
        e.marca,
        e.modelo,
        h.estado_anterior,
        h.estado_nuevo,
        h.usuario,
        h.fecha,
        h.detalle
    FROM historial_estados h
    LEFT JOIN equipos e ON e.id_equipo = h.id_equipo
    ORDER BY h.fecha DESC
");

if (!$res) {
    die('❌ Error en la consulta SQL: ' . $conn->error);
}
?>

<div class="container mt-4">
    <h3 class="mb-4">📜 Historial de cambios</h3>
    <table class="table table-striped table-sm align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Equipo</th>
                <th>Estado anterior</th>
                <th>Estado nuevo</th>
                <th>Usuario</th>
                <th>Fecha</th>
                <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
        <?php while($r = $res->fetch_assoc()): ?>
            <tr>
                <td><?= $r['id_historial'] ?></td>
                <td><?= $r['codigo_equipo'].' - '.$r['marca'].' '.$r['modelo'] ?></td>
                <td><?= $r['estado_anterior'] ?? '-' ?></td>
                <td><?= $r['estado_nuevo'] ?? '-' ?></td>
                <td><?= $r['usuario'] ?? 'Desconocido' ?></td>
                <td><?= $r['fecha'] ?></td>
                <td><?= $r['detalle'] ?? '-' ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include "includes/footer.php"; ?>
