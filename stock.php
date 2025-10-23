<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

require 'db.php';
$usuario = $_SESSION['usuario'];

// Obtener todos los productos con su stock
$sql = "SELECT p.id_producto, p.codigo, p.nombre, s.cantidad
        FROM productos p
        LEFT JOIN stock s ON p.id_producto = s.id_producto
        ORDER BY p.id_producto DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Stock</title>
<link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<h2>Gestión de Stock</h2>
<p><a href="panel.php">⬅ Volver al Panel</a></p>

<table border="1" cellpadding="5">
<tr>
    <th>ID</th>
    <th>Código</th>
    <th>Nombre</th>
    <th>Cantidad</th>
    <th>Acciones</th>
</tr>
<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['id_producto'] ?></td>
    <td><?= htmlspecialchars($row['codigo']) ?></td>
    <td><?= htmlspecialchars($row['nombre']) ?></td>
    <td><?= $row['cantidad'] ?? 0 ?></td>
    <td>
        <form method="POST" action="actualizar_stock.php" style="display:inline;">
            <input type="hidden" name="id_producto" value="<?= $row['id_producto'] ?>">
            <input type="number" name="cantidad" value="<?= $row['cantidad'] ?? 0 ?>" min="0" style="width:60px">
            <button type="submit">Actualizar</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>
</body>
</html>
