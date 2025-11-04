<?php
if(session_status() === PHP_SESSION_NONE) session_start();

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function checkPermission($rol, $modulo, $accion, $conn){
    $stmt = $conn->prepare("SELECT puede_ver, puede_editar, puede_eliminar FROM roles_permisos WHERE rol=? AND modulo=? LIMIT 1");
    if(!$stmt) return false;
    $stmt->bind_param("ss", $rol, $modulo);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if(!$res) return false;
    if($accion === 'ver') return (bool)$res['puede_ver'];
    if($accion === 'editar') return (bool)$res['puede_editar'];
    if($accion === 'eliminar') return (bool)$res['puede_eliminar'];
    return false;
}

function registrar_historial($conn, $id_usuario, $id_equipo, $accion){
    $detalle = $accion;
    $stmt = $conn->prepare("INSERT INTO historial_estados (id_equipo, usuario, fecha, detalle, estado_anterior, estado_nuevo) VALUES (?, ?, NOW(), ?, '', '')");
    if(!$stmt) return false;
    $stmt->bind_param("iis", $id_equipo, $id_usuario, $detalle);
    return $stmt->execute();
}

function generarCodigoEquipo($conn){
    $r = $conn->query("SELECT codigo_equipo FROM equipos ORDER BY id_equipo DESC LIMIT 1");
    if($r && $r->num_rows){
        $last = $r->fetch_assoc()['codigo_equipo'];
        $n = (int) filter_var($last, FILTER_SANITIZE_NUMBER_INT) + 1;
    } else $n = 1;
    return "EQ-" . str_pad($n, 4, "0", STR_PAD_LEFT);
}
