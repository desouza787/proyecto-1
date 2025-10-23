<?php
require 'db.php'; // tu archivo de conexión

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1️⃣ Recibir los datos del formulario
    $nombre = trim($_POST['nombre']);
    $id_categoria = $_POST['id_categoria'];
    $id_dep = $_POST['id_dep'];
    $precio = $_POST['precio'];
    $descripcion = $_POST['descripcion'];

    // 2️⃣ Generar código automáticamente
    $sql = "SELECT codigo FROM productos ORDER BY id_producto DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $ultimo_codigo = $row['codigo'];
        $num = (int) filter_var($ultimo_codigo, FILTER_SANITIZE_NUMBER_INT);
        $codigo = "PROD-" . str_pad($num + 1, 4, "0", STR_PAD_LEFT);
    } else {
        $codigo = "PROD-0001";
    }

    // 3️⃣ Insertar producto con el código generado
    $stmt = $conn->prepare("INSERT INTO productos (codigo, nombre, id_categoria, id_dep, precio, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiids", $codigo, $nombre, $id_categoria, $id_dep, $precio, $descripcion);

    if ($stmt->execute()) {
        echo "✅ Producto agregado correctamente con código: " . $codigo;
    } else {
        echo "❌ Error al agregar producto: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
