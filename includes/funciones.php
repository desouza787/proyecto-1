<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Escapar HTML
 */
function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Registrar historial de cambios
 */
function registrar_historial($conn, $id_usuario, $id_equipo, $accion, $estado_anterior = '', $estado_nuevo = '') {
    $detalle = $accion;
    $stmt = $conn->prepare("
        INSERT INTO historial_estados (id_equipo, usuario, fecha, detalle, estado_anterior, estado_nuevo)
        VALUES (?, ?, NOW(), ?, ?, ?)
    ");
    if (!$stmt) return false;
    $stmt->bind_param("iisss", $id_equipo, $id_usuario, $detalle, $estado_anterior, $estado_nuevo);
    return $stmt->execute();
}

/**
 * Generar código interno incremental
 * Formato: XX-XX-XX-0001
 */




function generarCodigoInterno($conn, $nivel, $id_dep, $id_categoria) {
    if (empty($nivel) || empty($id_dep) || empty($id_categoria)) return '';

    // Obtener siglas del nivel
    $nivel = strtoupper(substr(trim($nivel), 0, 2));

    // Obtener siglas del departamento
    $depRow = $conn->query("SELECT nombre_dep FROM departamentos WHERE id_dep=".intval($id_dep)." LIMIT 1")->fetch_assoc();
    $dep = $depRow ? strtoupper(substr(trim($depRow['nombre_dep']), 0, 2)) : 'XX';

    // Obtener siglas de la categoría
    $catRow = $conn->query("SELECT nombre_categoria FROM categorias WHERE id_categoria=".intval($id_categoria)." LIMIT 1")->fetch_assoc();
    $cat = $catRow ? strtoupper(substr(trim($catRow['nombre_categoria']), 0, 2)) : 'XX';

    // Prefijo base
    $prefijo = "$nivel-$dep-$cat-";

    // Buscar el último código existente con ese prefijo
    $query = $conn->prepare("SELECT codigo_equipo FROM equipos WHERE codigo_equipo LIKE CONCAT(?, '%') ORDER BY id_equipo DESC LIMIT 1");
    $query->bind_param("s", $prefijo);
    $query->execute();
    $result = $query->get_result();
    $ultimoCodigo = $result->fetch_assoc()['codigo_equipo'] ?? null;
    $query->close();

    // Obtener el número siguiente
    if ($ultimoCodigo) {
        $partes = explode('-', $ultimoCodigo);
        $numero = intval(end($partes)) + 1;
    } else {
        $numero = 1;
    }

    // Formatear con ceros (4 dígitos)
    $numeroFormateado = str_pad($numero, 4, '0', STR_PAD_LEFT);

    return $prefijo . $numeroFormateado;
}


?>
