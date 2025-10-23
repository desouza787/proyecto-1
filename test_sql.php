<?php
require 'db.php';  // tu conexión

$tablas = [
    'usuarios',
    'productos',
    'departamentos',
    'categorias',
    'stock',
    'alertas_stock',
    'activos'
];

foreach ($tablas as $tabla) {
    $result = $conn->query("SELECT COUNT(*) as n FROM $tabla");
    if (!$result) {
        echo "❌ Error en la tabla $tabla: " . $conn->error . "<br>";
    } else {
        $row = $result->fetch_assoc();
        echo "✅ Tabla $tabla OK, filas: " . ($row['n'] ?? 0) . "<br>";
    }
}
?>
