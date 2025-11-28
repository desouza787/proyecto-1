<?php
session_start();
require_once "db.php";              
require_once "includes/header.php";  
require_once "includes/funciones.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

$usuario_actual = $_SESSION['usuario'];
$rol = $_SESSION['rol'] ?? 'Usuario';

// -------------------------
// Permisos por rol (lectura)
$permisos = [];
if ($conn->query("SHOW TABLES LIKE 'roles_permisos'")->num_rows) {
    $stmtp = $conn->prepare("SELECT modulo, puede_ver, puede_editar, puede_eliminar FROM roles_permisos WHERE rol=?");
    $stmtp->bind_param("s", $rol);
    $stmtp->execute();
    $res = $stmtp->get_result();
    while ($r = $res->fetch_assoc()) $permisos[$r['modulo']] = $r;
    $stmtp->close();
}
function tienePermiso($mod, $tipo) {
    global $permisos;
    return isset($permisos[$mod]) && intval($permisos[$mod][$tipo]) === 1;
}
// -------------------------


// -------------------------
// Manejo de acciones POST (CRUD + nuevas funcionalidades)
// -------------------------
$toast = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $entity = $_POST['entity'] ?? '';

    function p($k) { return $_POST[$k] ?? null; }

    try {
        // -------------------------
        // existing entities (marca, modelo, categoria, ubicacion, departamento, usuario)
        // (Se preserva exactamente la lógica previa — no modificado)
        // -------------------------
        // --- MARCA ---
        if ($entity === 'marca') {
            if ($action === 'add') {
                $nombre = trim(p('nombre'));
                if (!$nombre) throw new Exception("Nombre requerido.");
                $stmt = $conn->prepare("INSERT INTO marcas (nombre) VALUES (?)");
                $stmt->bind_param("s",$nombre); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Marca agregada."];
            } elseif ($action === 'edit') {
                $id = intval(p('id_marca')); $nombre = trim(p('nombre'));
                if (!$id) throw new Exception("ID inválido.");
                $stmt = $conn->prepare("UPDATE marcas SET nombre=? WHERE id_marca=?");
                $stmt->bind_param("si",$nombre,$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Marca actualizada."];
            } elseif ($action === 'delete') {
                $id = intval(p('id_marca'));
                $c = $conn->query("SELECT COUNT(*) as c FROM modelos WHERE id_marca=$id")->fetch_assoc()['c'] ?? 0;
                if ($c>0) throw new Exception("No se puede eliminar. Tiene modelos asociados ($c).");
                $stmt = $conn->prepare("DELETE FROM marcas WHERE id_marca=?"); $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Marca eliminada."];
            }
        }

        // --- MODELO ---
        if ($entity === 'modelo') {
            if ($action === 'add') {
                $id_marca = intval(p('id_marca')); $nombre = trim(p('nombre'));
                if (!$id_marca || !$nombre) throw new Exception("Marca y nombre son requeridos.");
                $stmt = $conn->prepare("INSERT INTO modelos (id_marca,nombre) VALUES (?,?)");
                $stmt->bind_param("is",$id_marca,$nombre); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Modelo agregado."];
            } elseif ($action === 'edit') {
                $id = intval(p('id_modelo')); $id_marca = intval(p('id_marca')); $nombre = trim(p('nombre'));
                if (!$id) throw new Exception("ID inválido.");
                $stmt = $conn->prepare("UPDATE modelos SET id_marca=?, nombre=? WHERE id_modelo=?");
                $stmt->bind_param("isi",$id_marca,$nombre,$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Modelo actualizado."];
            } elseif ($action === 'delete') {
                $id = intval(p('id_modelo'));
                $stmt = $conn->prepare("DELETE FROM modelos WHERE id_modelo=?"); $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Modelo eliminado."];
            }
        }

        // --- CATEGORIA ---
        if ($entity === 'categoria') {
            if ($action === 'add') {
                $nombre = trim(p('nombre')); $desc = trim(p('descripcion'));
                $stmt = $conn->prepare("INSERT INTO categorias (nombre_categoria, descripcion) VALUES (?,?)");
                $stmt->bind_param("ss",$nombre,$desc); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Categoría agregada."];
            } elseif ($action === 'edit') {
                $id = intval(p('id_categoria')); $nombre = trim(p('nombre')); $desc = trim(p('descripcion'));
                $stmt = $conn->prepare("UPDATE categorias SET nombre_categoria=?, descripcion=? WHERE id_categoria=?");
                $stmt->bind_param("ssi",$nombre,$desc,$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Categoría actualizada."];
            } elseif ($action === 'delete') {
                $id = intval(p('id_categoria'));
                $c = $conn->query("SELECT COUNT(*) as c FROM equipos WHERE id_categoria=$id")->fetch_assoc()['c'] ?? 0;
                if ($c>0) throw new Exception("No se puede eliminar. Tiene equipos asociados ($c).");
                $stmt = $conn->prepare("DELETE FROM categorias WHERE id_categoria=?"); $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Categoría eliminada."];
            }
        }

        // --- UBICACION ---
        if ($entity === 'ubicacion') {
            if ($action === 'add') {
                $nivel = trim(p('nivel')); $nombre = trim(p('nombre')); $desc = trim(p('descripcion'));
                $stmt = $conn->prepare("INSERT INTO ubicaciones (nivel,nombre,descripcion) VALUES (?,?,?)");
                $stmt->bind_param("sss",$nivel,$nombre,$desc); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Ubicación agregada."];
            } elseif ($action === 'edit') {
                $id = intval(p('id_ubicacion')); $nivel = trim(p('nivel')); $nombre = trim(p('nombre')); $desc = trim(p('descripcion'));
                $stmt = $conn->prepare("UPDATE ubicaciones SET nivel=?, nombre=?, descripcion=? WHERE id_ubicacion=?");
                $stmt->bind_param("sssi",$nivel,$nombre,$desc,$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Ubicación actualizada."];
            } elseif ($action === 'delete') {
                $id = intval(p('id_ubicacion'));
                $c = $conn->query("SELECT COUNT(*) as c FROM equipos WHERE id_ubicacion=$id")->fetch_assoc()['c'] ?? 0;
                if ($c>0) throw new Exception("No se puede eliminar. Tiene equipos asociados ($c).");
                $stmt = $conn->prepare("DELETE FROM ubicaciones WHERE id_ubicacion=?"); $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Ubicación eliminada."];
            }
        }

        // --- DEPARTAMENTO ---
        if ($entity === 'departamento') {
            if ($action === 'add') {
                $nombre = trim(p('nombre')); $tipo = trim(p('tipo')); $desc = trim(p('descripcion'));
                $stmt = $conn->prepare("INSERT INTO departamentos (nombre_dep,tipo,descripcion) VALUES (?,?,?)");
                $stmt->bind_param("sss",$nombre,$tipo,$desc); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Departamento agregado."];
            } elseif ($action === 'edit') {
                $id = intval(p('id_dep')); $nombre = trim(p('nombre')); $tipo = trim(p('tipo')); $desc = trim(p('descripcion'));
                $stmt = $conn->prepare("UPDATE departamentos SET nombre_dep=?, tipo=?, descripcion=? WHERE id_dep=?");
                $stmt->bind_param("sssi",$nombre,$tipo,$desc,$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Departamento actualizado."];
            } elseif ($action === 'delete') {
                $id = intval(p('id_dep'));
                $c = $conn->query("SELECT COUNT(*) as c FROM equipos WHERE id_dep=$id")->fetch_assoc()['c'] ?? 0;
                if ($c>0) throw new Exception("No se puede eliminar. Tiene equipos asociados ($c).");
                $stmt = $conn->prepare("DELETE FROM departamentos WHERE id_dep=?"); $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Departamento eliminado."];
            }
        }

        // --- USUARIOS ---
        if ($entity === 'usuario' || $entity === 'usuarios') {
            if ($action === 'add') {
                $nombre = trim(p('nombre')); $email = trim(p('email')); $password = trim(p('password')); $rolnuevo = trim(p('rol'));
                if (!$nombre || !$email || !$password) throw new Exception("Todos los campos son requeridos.");
                $chk = $conn->prepare("SELECT id FROM usuarios WHERE email=? LIMIT 1");
                $chk->bind_param("s",$email); $chk->execute(); $chk->store_result();
                if ($chk->num_rows) { $chk->close(); throw new Exception("Email ya registrado."); }
                $chk->close();
                $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO usuarios (nombre,email,password,rol) VALUES (?,?,?,?)");
                $stmt->bind_param("ssss",$nombre,$email,$pass_hash,$rolnuevo); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Usuario creado."];
            } elseif ($action === 'edit') {
                $id = intval(p('id')); $nombre = trim(p('nombre')); $email = trim(p('email')); $rolnuevo = trim(p('rol'));
                $sql = "UPDATE usuarios SET nombre=?, email=?, rol=? WHERE id=?";
                $stmt = $conn->prepare($sql); $stmt->bind_param("sssi",$nombre,$email,$rolnuevo,$id); $stmt->execute(); $stmt->close();
                if (!empty(p('password'))) {
                    $pass_hash = password_hash(p('password'), PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE usuarios SET password=? WHERE id=?"); $stmt->bind_param("si",$pass_hash,$id); $stmt->execute(); $stmt->close();
                }
                $toast = ['type'=>'success','msg'=>"Usuario actualizado."];
            } elseif ($action === 'delete') {
                $id = intval(p('id'));
                if ($id == ($_SESSION['id'] ?? 0)) throw new Exception("No puedes eliminar tu propia cuenta.");
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE id=?"); $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Usuario eliminado."];
            }
        }

        // -------------------------
        // --- NUEVO: CRUD ESTADOS ---
        if ($entity === 'estado') {
            if ($action === 'add') {
                $nombre = trim(p('nombre')); $desc = trim(p('descripcion'));
                $stmt = $conn->prepare("INSERT INTO estados (nombre, descripcion) VALUES (?,?)");
                $stmt->bind_param("ss",$nombre,$desc); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Estado agregado."];
            } elseif ($action === 'edit') {
                $id = intval(p('id_estado')); $nombre = trim(p('nombre')); $desc = trim(p('descripcion'));
                $stmt = $conn->prepare("UPDATE estados SET nombre=?, descripcion=? WHERE id_estado=?");
                $stmt->bind_param("ssi",$nombre,$desc,$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Estado actualizado."];
            } elseif ($action === 'delete') {
                $id = intval(p('id_estado'));
                $stmt = $conn->prepare("DELETE FROM estados WHERE id_estado=?"); $stmt->bind_param("i",$id); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Estado eliminado."];
            }
        }

        // -------------------------
        // --- NUEVO: REPARACIONES (add, asignar tecnico, solucion) ---
        if ($entity === 'reparacion') {
            if ($action === 'add') {
                $id_equipo = intval(p('id_equipo')); $problema = trim(p('problema')); $tecnico = trim(p('tecnico'));
                $usuario = $usuario_actual;

                // fecha de inicio actual
                $fecha_inicio = date('Y-m-d H:i:s');
                $estado = 'En reparación';

                $stmt = $conn->prepare("
                    INSERT INTO reparaciones (id_equipo, problema, tecnico, fecha_inicio, estado, usuario_registra) 
                    VALUES (?,?,?,?,?,?)
                ");
                if (!$stmt) throw new Exception("Error prepare reparaciones: " . $conn->error);
                // id_equipo (int), problema (string), tecnico (string), fecha_inicio (string), estado (string), usuario_registra (string)
                $stmt->bind_param("isssss", $id_equipo, $problema, $tecnico, $fecha_inicio, $estado, $usuario);
                $stmt->execute(); $id_reparacion = $stmt->insert_id; $stmt->close();

                // historial_estados
                if ($conn->query("SHOW TABLES LIKE 'historial_estados'")->num_rows) {
                    $h = $conn->prepare("INSERT INTO historial_estados (id_equipo, estado_anterior, estado_nuevo, usuario, fecha, detalle) VALUES (?,?,?,?,NOW(),?)");
                    if ($h) {
                        $detalle = "Ingreso a reparación (#$id_reparacion)";
                        $estado_anterior = 'Operativo';
                        // tipos: id_equipo (i), estado_anterior (s), estado_nuevo (s), usuario (s), detalle (s)
                        $h->bind_param("issss",$id_equipo,$estado_anterior,$estado,$usuario,$detalle);
                        try { $h->execute(); $h->close(); } catch(Exception $ex) { /* ignore */ }
                    }
                }

                // crear notificacion global
                if ($conn->query("SHOW TABLES LIKE 'notificaciones'")->num_rows) {
                    $n = $conn->prepare("INSERT INTO notificaciones (id_usuario,titulo,mensaje,link,leido,fecha) VALUES (?,?,?,?,0,NOW())");
                    if ($n) {
                        $titulo = "Ingreso a reparación";
                        $mensaje = "Equipo $id_equipo ingresado a reparación por $usuario";
                        $link = "mantenedores.php?tab=reparaciones&id=".$id_reparacion;
                        $nid = 0;
                        $n->bind_param("isss",$nid,$titulo,$mensaje,$link);
                        $n->execute(); $n->close();
                    }
                }

                $toast = ['type'=>'success','msg'=>"Reparación registrada."];
            }

            if ($action === 'solucion') {
                $id_rep = intval(p('id_reparacion')); $solucion = trim(p('solucion'));
                $stmt = $conn->prepare("UPDATE reparaciones SET solucion=?, fecha_fin=?, estado='Finalizado' WHERE id_reparacion=?");
                $fecha_fin = date('Y-m-d H:i:s');
                $stmt->bind_param("ssi",$solucion,$fecha_fin,$id_rep);
                $stmt->execute(); $stmt->close();

                // actualizar historial_estados
                $q = $conn->prepare("SELECT id_equipo FROM reparaciones WHERE id_reparacion=?");
                $q->bind_param("i",$id_rep); $q->execute(); $res = $q->get_result(); $row = $res->fetch_assoc(); $id_equipo = $row['id_equipo'] ?? 0; $q->close();
                if ($id_equipo && $conn->query("SHOW TABLES LIKE 'historial_estados'")->num_rows) {
                    $h = $conn->prepare("INSERT INTO historial_estados (id_equipo, estado_anterior, estado_nuevo, usuario, fecha, detalle) VALUES (?,?,?,?,NOW(),?)");
                    if ($h) {
                        $detalle = "Reparación #$id_rep finalizada. " . substr($solucion,0,200);
                        $estado_anterior = "En reparación";
                        $estado_nuevo    = "Operativo";
                        // id_equipo (i), estado_anterior (s), estado_nuevo (s), usuario (s), detalle (s)
                        $h->bind_param("issss", $id_equipo, $estado_anterior, $estado_nuevo, $usuario_actual, $detalle);
                        try{ $h->execute(); $h->close(); } catch(Exception $ex){ /* ignore */ }
                    }
                }

                $toast = ['type'=>'success','msg'=>"Solución registrada y reparación finalizada."];
            }

            if ($action === 'asignar') {
                $id_rep = intval(p('id_reparacion')); $tecnico = trim(p('tecnico'));
                $stmt = $conn->prepare("UPDATE reparaciones SET tecnico=? WHERE id_reparacion=?");
                $stmt->bind_param("si",$tecnico,$id_rep); $stmt->execute(); $stmt->close();
                $toast = ['type'=>'success','msg'=>"Técnico asignado."];
            }
        }

        // -------------------------
        // --- NUEVO: COMENTARIOS ---
        if ($entity === 'comentario') {
            $id_equipo = intval(p('id_equipo')); $coment = trim(p('comentario'));
            $usuario = $usuario_actual;
            $stmt = $conn->prepare("INSERT INTO comentarios (id_producto, usuario, comentario, fecha) VALUES (?,?,?,NOW())");
            $stmt->bind_param("iss",$id_equipo,$usuario,$coment); $stmt->execute(); $stmt->close();
            $toast = ['type'=>'success','msg'=>"Comentario guardado."];
        }

        // -------------------------
        // --- NUEVO: ALERTA STOCK (manual) ---
        if ($entity === 'stock_alerta') {
            $id_producto = intval(p('id_producto')); $mensaje = trim(p('mensaje'));
            $stmt = $conn->prepare("INSERT INTO alertas_stock (id_producto, mensaje, fecha_alerta) VALUES (?, ?, NOW())");
            $stmt->bind_param("is",$id_producto,$mensaje); $stmt->execute(); $stmt->close();
            $toast = ['type'=>'success','msg'=>"Alerta de stock registrada."];
        }

        // -------------------------
        // --- NUEVO: GESTIÓN PERMISOS (UI -> roles_permisos)
        if ($entity === 'permiso') {
            $rol_target = trim(p('rol'));
            $modulo = trim(p('modulo'));
            $ver = intval(p('ver')?1:0);
            $editar = intval(p('editar')?1:0);
            $eliminar = intval(p('eliminar')?1:0);
            // Upsert simple
            $chk = $conn->prepare("SELECT id_permiso FROM roles_permisos WHERE rol=? AND modulo=? LIMIT 1");
            $chk->bind_param("ss",$rol_target,$modulo); $chk->execute(); $chk->store_result();
            if ($chk->num_rows) {
                $chk->bind_result($idp); $chk->fetch(); $chk->close();
                $u = $conn->prepare("UPDATE roles_permisos SET puede_ver=?, puede_editar=?, puede_eliminar=? WHERE id_permiso=?");
                $u->bind_param("iiii",$ver,$editar,$eliminar,$idp); $u->execute(); $u->close();
            } else {
                $chk->close();
                $i = $conn->prepare("INSERT INTO roles_permisos (rol, modulo, puede_ver, puede_editar, puede_eliminar) VALUES (?,?,?,?,?)");
                $i->bind_param("ssiii",$rol_target,$modulo,$ver,$editar,$eliminar); $i->execute(); $i->close();
            }
            $toast = ['type'=>'success','msg'=>"Permiso guardado."];
        }

        // -------------------------
        // --- NUEVO: NOTIFICACIONES -> marcar como leido
        if ($entity === 'notificacion' && $action === 'leer') {
            $idn = intval(p('id_n'));
            $stmt = $conn->prepare("UPDATE notificaciones SET leido=1 WHERE id=?");
            $stmt->bind_param("i",$idn); $stmt->execute(); $stmt->close();
            $toast = ['type'=>'success','msg'=>"Notificación marcada."];
        }

        // -------------------------
        // FIN POST
    } catch (Exception $ex) {
        $toast = ['type'=>'error','msg'=>$ex->getMessage()];
    }

    $_SESSION['mto_toast'] = $toast;
    header("Location: mantenedores.php");
    exit;
}

// Recuperar toast guardado luego del redirect
if (isset($_SESSION['mto_toast'])) {
    $toast = $_SESSION['mto_toast'];
    unset($_SESSION['mto_toast']);
}

// -------------------------
// Lecturas necesarias para listados y selects (añadidas)
// -------------------------
$marcas = $conn->query("SELECT id_marca,nombre FROM marcas ORDER BY nombre ASC");
$modelos = $conn->query("SELECT id_modelo,id_marca,nombre FROM modelos ORDER BY nombre ASC");
$categorias = $conn->query("SELECT id_categoria,nombre_categoria,descripcion FROM categorias ORDER BY nombre_categoria ASC");
$ubicaciones = $conn->query("SELECT id_ubicacion,nivel,nombre FROM ubicaciones ORDER BY nombre ASC");
$departamentos = $conn->query("SELECT id_dep,nombre_dep FROM departamentos ORDER BY nombre_dep ASC");
$sqlUsuarios = "SELECT id, nombre, email, rol FROM usuarios";
$usuarios = $conn->query($sqlUsuarios);
if (!$usuarios) {
    die("ERROR SQL USUARIOS: " . $conn->error);
}

// NUEVO: contar notificaciones no leídas
$not_count = 0;
$not_rows = [];
if ($conn->query("SHOW TABLES LIKE 'notificaciones'")->num_rows) {
    $resn = $conn->query("SELECT id, id_usuario, titulo, mensaje, link, leido, fecha FROM notificaciones ORDER BY fecha DESC LIMIT 50");
    if ($resn) {
        while ($nr = $resn->fetch_assoc()) $not_rows[] = $nr;
        foreach ($not_rows as $n) if (!$n['leido']) $not_count++;
    }
}

// NUEVO: stock crítico (chequeo básico)
$stock_critico = [];

if ($conn->query("SHOW TABLES LIKE 'stock'")->num_rows) {

    $sql = "
        SELECT 
            s.id_stock,
            s.id_producto,
            s.cantidad,
            a.nombre AS producto,
            c.nombre_categoria AS categoria
        FROM stock s
        LEFT JOIN activos a ON s.id_producto = a.id
        LEFT JOIN categorias c ON a.id_categoria = c.id_categoria
        ORDER BY s.cantidad ASC
    ";

    $qst = $conn->query($sql);

    if (!$qst) {
        // evitar fallos fatales; dejamos el array vacío y registramos el error opcionalmente
        // die("Error SQL STOCK: " . $conn->error . " | QUERY: " . $sql);
        $qst = false;
    }

    if ($qst) {
        while ($sr = $qst->fetch_assoc()) {
            if (intval($sr['cantidad']) <= 2) {
                $stock_critico[] = $sr;
            }
        }
    }
}

// NUEVO: próximas revisiones vencimiento (de equipos)
$rev_vencidas = [];
$sql = "
    SELECT 
        id_equipo, 
        codigo_equipo, 
        marca, 
        modelo, 
        ultima_revision 
    FROM equipos 
    WHERE ultima_revision IS NOT NULL 
    ORDER BY ultima_revision ASC 
    LIMIT 50
";

$qv = $conn->query($sql);

if ($qv) {
    while ($rv = $qv->fetch_assoc()) {
        // si vencida/por vencer en 30 dias — agregamos (simple heurística)
        $fecha = strtotime($rv['ultima_revision']);
        if ($fecha && $fecha <= strtotime('+30 days')) $rev_vencidas[] = $rv;
    }
} else {
    // evitar die(); dejamos $rev_vencidas vacío
    // die('ERROR SQL REVISIONES: ' . $conn->error . ' | QUERY: ' . $sql);
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Mantenedores — Sistema</title>

  <!-- Bootstrap + DataTables + SweetAlert + FontAwesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>

  <!-- Custom styles & animations (se conserva original) -->
  <style>
    :root{
      --accent:#12b886;
      --accent-2:#0ea5a4;
      --card-bg: linear-gradient(180deg, rgba(255,255,255,0.85), rgba(250,250,250,0.95));
      --glass: rgba(255,255,255,0.6);
      --soft-shadow: 0 8px 20px rgba(18,184,134,0.08);
    }

    body { background: linear-gradient(180deg,#f4f7fb,#eef6f4); font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; color:#263238; }

    /* Header shimmer */
    .page-head {
      padding: 18px;
      border-radius: 12px;
      background: linear-gradient(90deg, rgba(18,184,134,0.12), rgba(14,165,164,0.04));
      box-shadow: var(--soft-shadow);
      display:flex; gap:12px; align-items:center;
      transition: transform .22s ease, box-shadow .22s ease;
    }
    .page-head:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(18,184,134,0.12); }

    .shimmer {
      background: linear-gradient(90deg, rgba(255,255,255,0.2), rgba(255,255,255,0.6), rgba(255,255,255,0.2));
      background-size: 200% 100%;
      animation: shimmer 2.5s linear infinite;
      border-radius:8px;
    }
    @keyframes shimmer { from { background-position: 200% 0 } to { background-position: -200% 0 } }

    .card-animated { transition: transform .18s ease, box-shadow .18s ease; border-radius: 12px; background: var(--card-bg); }
    .card-animated:hover { transform: translateY(-6px); box-shadow: 0 18px 48px rgba(16,24,40,0.08); }

    .fab {
      position: fixed; right: 28px; bottom: 28px; z-index: 1100;
      background: linear-gradient(135deg,var(--accent),var(--accent-2));
      color:white; width:56px; height:56px; border-radius: 999px;
      display:flex; align-items:center; justify-content:center; box-shadow: 0 10px 30px rgba(16,24,40,0.18);
      cursor:pointer; transition: transform .15s ease;
    }
    .fab:active { transform: scale(.96); }

    /* table row fade-in */
    table.dataTable tbody tr { opacity: 0; transform: translateY(6px); transition: all .35s ease; }
    table.dataTable.loaded tbody tr { opacity: 1; transform: translateY(0); }

    /* subtle badge */
    .badge-soft { background: rgba(18,184,134,0.12); color:var(--accent); border-radius:999px; padding:.36rem .6rem; font-weight:600; }

    /* modal animations */
    .modal .modal-dialog { transition: transform .28s cubic-bezier(.2,.9,.3,1), opacity .2s ease; transform: translateY(24px); opacity: 0; }
    .modal.show .modal-dialog { transform: translateY(0); opacity: 1; }

    /* input focus */
    .form-control:focus { box-shadow: 0 6px 18px rgba(14,165,164,0.08); border-color: var(--accent); outline: none; }

    /* small helpers */
    .micro { font-size: .82rem; color:#6b7280; }
    .section-title { font-weight:700; letter-spacing:.2px; }

    /* animated empty placeholder */
    .empty-card { min-height:80px; display:flex; align-items:center; justify-content:center; color:#9ca3af; font-style:italic; }

    /* responsive tweaks */
    @media (max-width:720px){
      .page-head { flex-direction:column; align-items:flex-start; gap:8px; }
    }

    /* --- NUEVO: Dark mode variables (no cambia estilos base) --- */
    body.dark {
      background: linear-gradient(180deg,#071018,#071722); color: #e6eef0;
    }
    body.dark .card-animated { background: rgba(10,14,18,0.6); box-shadow: none; }
    body.dark .page-head { background: linear-gradient(90deg, rgba(18,184,134,0.06), rgba(14,165,164,0.02)); }
    body.dark .form-control { background: rgba(255,255,255,0.03); color: #e6eef0; border-color: rgba(255,255,255,0.06); }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="page-head w-100 card-animated p-3 d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h4 mb-0"><i class="fa-solid fa-tools text-success"></i> <span class="section-title">Mantenedores</span></h1>
        <div class="micro">Gestiona usuarios, marcas, modelos, categorías, ubicaciones, departamentos y más</div>
      </div>
      <div class="text-end">
        <div class="badge-soft shimmer">Sistema — <strong><?= htmlspecialchars($usuario_actual) ?></strong></div>
        <div class="micro mt-1">Base: <code class="micro">cmrj2</code></div>
      </div>
    </div>
  </div>

  <?php if($toast): ?>
    <div id="mtoToast" data-type="<?= htmlspecialchars($toast['type']) ?>" data-msg="<?= htmlspecialchars($toast['msg']) ?>"></div>
  <?php endif; ?>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-3" id="tabsMto" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabUsuarios">👤 Usuarios</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabRoles">🔐 Roles</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabMarcas">🏷 Marcas</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabModelos">💻 Modelos</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabCategorias">🗂 Categorías</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabUbicaciones">🧭 Ubicaciones</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabDepartamentos">🏢 Departamentos</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabEstados">⚙️ Estados</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabReparaciones">🔧 Reparaciones <span id="badgeReparaciones" class="badge bg-danger ms-1"></span></a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabHistorial">📜 Historial</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabStock">📦 Stock</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabNotificaciones">🔔 Notificaciones <span id="badgeNot" class="badge bg-danger ms-1"><?= $not_count ?></span></a></li>
    <li class="nav-item ms-auto"><a class="nav-link" href="#" id="toggleDark"><i class="fa-solid fa-moon"></i> Modo oscuro</a></li>
  </ul>

  <div class="tab-content">
    <!-- Usuarios (mantener igual) -->
    <div class="tab-pane fade show active" id="tabUsuarios">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Usuarios</h5>
        <div>
          <button class="btn btn-outline-secondary me-2" onclick="document.location.reload();"><i class="fa-solid fa-sync"></i></button>
          <?php if (tienePermiso('usuarios','puede_editar')): ?>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalUsuario" data-mode="add"><i class="fa-solid fa-user-plus"></i> Nuevo usuario</button>
          <?php endif; ?>
        </div>
      </div>

      <div class="card card-animated p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div><strong>Listado</strong> <span class="micro">— administra cuentas</span></div>
          <div><input id="searchUsuarios" class="form-control form-control-sm" placeholder="Buscar usuario..." style="width:220px"></div>
        </div>

        <table id="dtUsuarios" class="table table-sm table-striped display" style="width:100%">
          <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php while($r = $usuarios->fetch_assoc()): ?>
            <tr>
              <td><?= $r['id'] ?></td>
              <td><?= htmlspecialchars($r['nombre']) ?></td>
              <td><?= htmlspecialchars($r['email']) ?></td>
              <td><?= htmlspecialchars($r['rol']) ?></td>
              <td>
                <?php if (tienePermiso('usuarios','puede_editar')): ?>
                  <button class="btn btn-sm btn-outline-primary btn-edit-user" data-id="<?= $r['id'] ?>" data-nombre="<?= htmlspecialchars($r['nombre']) ?>" data-email="<?= htmlspecialchars($r['email']) ?>" data-rol="<?= htmlspecialchars($r['rol']) ?>"><i class="fa-solid fa-pen-to-square"></i></button>
                <?php endif; ?>
                <?php if (tienePermiso('usuarios','puede_eliminar')): ?>
                  <button class="btn btn-sm btn-outline-danger btn-del" data-entity="usuario" data-id="<?= $r['id'] ?>"><i class="fa-solid fa-trash"></i></button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Roles -->
    <div class="tab-pane fade" id="tabRoles">
      <div class="card card-animated p-3 mb-3">
        <div class="d-flex justify-content-between">
          <div>
            <h6 class="mb-0">Roles y Permisos</h6>
            <div class="micro">Asigna permisos por rol (ver/editar/eliminar)</div>
          </div>
          <div>
            <button id="btn-refresh-roles" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-sync"></i> Refrescar</button>
          </div>
        </div>
        <hr/>
        <div class="row">
          <div class="col-md-6">
            <form id="formPermiso" class="mb-3">
              <input type="hidden" name="entity" value="permiso">
              <div class="mb-2"><label class="form-label">Rol</label><input name="rol" class="form-control" required></div>
              <div class="mb-2"><label class="form-label">Módulo</label><input name="modulo" class="form-control" required></div>
              <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="ver" id="p_ver"><label class="form-check-label" for="p_ver">Ver</label></div>
              <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="editar" id="p_editar"><label class="form-check-label" for="p_editar">Editar</label></div>
              <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="eliminar" id="p_eliminar"><label class="form-check-label" for="p_eliminar">Eliminar</label></div>
              <div class="mt-2"><button class="btn btn-sm btn-success" type="submit">Guardar permiso</button></div>
            </form>
          </div>
          <div class="col-md-6">
            <table id="dtPermisos" class="table table-sm">
              <thead><tr><th>Rol</th><th>Módulo</th><th>Ver</th><th>Editar</th><th>Eliminar</th></tr></thead>
              <tbody>
                <?php
                if ($conn->query("SHOW TABLES LIKE 'roles_permisos'")->num_rows) {
                    $rp = $conn->query("SELECT * FROM roles_permisos ORDER BY rol, modulo");
                    while($row = $rp->fetch_assoc()){
                        echo "<tr><td>".htmlspecialchars($row['rol'])."</td><td>".htmlspecialchars($row['modulo'])."</td><td>".($row['puede_ver']? '✓':'✕')."</td><td>".($row['puede_editar']? '✓':'✕')."</td><td>".($row['puede_eliminar']? '✓':'✕')."</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='empty-card'>Tabla roles_permisos no encontrada.</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- MARCAS -->
    <div class="tab-pane fade" id="tabMarcas">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Marcas</h5>
        <?php if (tienePermiso('marcas','puede_editar')): ?>
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalMarca" data-mode="add"><i class="fa-solid fa-plus"></i> Nueva marca</button>
        <?php endif; ?>
      </div>
      <div class="card card-animated p-3 mb-3">
        <table id="dtMarcas" class="table table-sm table-striped display">
          <thead><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php $q = $conn->query("SELECT id_marca,nombre FROM marcas ORDER BY nombre ASC"); while($rw = $q->fetch_assoc()): ?>
            <tr>
              <td><?= $rw['id_marca'] ?></td>
              <td><?= htmlspecialchars($rw['nombre']) ?></td>
              <td>
                <?php if (tienePermiso('marcas','puede_editar')): ?>
                  <button class="btn btn-sm btn-outline-primary btn-edit-marca" data-id="<?= $rw['id_marca'] ?>" data-nombre="<?= htmlspecialchars($rw['nombre']) ?>"><i class="fa-solid fa-pen"></i></button>
                <?php endif; ?>
                <?php if (tienePermiso('marcas','puede_eliminar')): ?>
                  <button class="btn btn-sm btn-outline-danger btn-del" data-entity="marca" data-id="<?= $rw['id_marca'] ?>"><i class="fa-solid fa-trash"></i></button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODELOS -->
    <div class="tab-pane fade" id="tabModelos">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Modelos</h5>
        <?php if (tienePermiso('modelos','puede_editar')): ?>
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalModelo" data-mode="add"><i class="fa-solid fa-plus"></i> Nuevo modelo</button>
        <?php endif; ?>
      </div>
      <div class="card card-animated p-3 mb-3">
        <table id="dtModelos" class="table table-sm table-striped display">
          <thead><tr><th>ID</th><th>Marca</th><th>Modelo</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php
          $q = $conn->query("SELECT m.id_modelo,m.nombre, ma.nombre AS marca, m.id_marca FROM modelos m LEFT JOIN marcas ma ON m.id_marca=ma.id_marca ORDER BY ma.nombre, m.nombre");
          while($rw = $q->fetch_assoc()):
          ?>
            <tr>
              <td><?= $rw['id_modelo'] ?></td>
              <td><?= htmlspecialchars($rw['marca']) ?></td>
              <td><?= htmlspecialchars($rw['nombre']) ?></td>
              <td>
                <?php if (tienePermiso('modelos','puede_editar')): ?>
                  <button class="btn btn-sm btn-outline-primary btn-edit-modelo" data-id="<?= $rw['id_modelo'] ?>" data-id_marca="<?= intval($rw['id_marca'] ?? 0) ?>" data-nombre="<?= htmlspecialchars($rw['nombre']) ?>"><i class="fa-solid fa-pen"></i></button>
                <?php endif; ?>
                <?php if (tienePermiso('modelos','puede_eliminar')): ?>
                  <button class="btn btn-sm btn-outline-danger btn-del" data-entity="modelo" data-id="<?= $rw['id_modelo'] ?>"><i class="fa-solid fa-trash"></i></button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- CATEGORIAS -->
    <div class="tab-pane fade" id="tabCategorias">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Categorías</h5>
        <?php if (tienePermiso('categorias','puede_editar')): ?>
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCategoria" data-mode="add"><i class="fa-solid fa-plus"></i> Nueva categoría</button>
        <?php endif; ?>
      </div>
      <div class="card card-animated p-3 mb-3">
        <table id="dtCategorias" class="table table-sm table-striped display">
          <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php $q = $conn->query("SELECT id_categoria,nombre_categoria,descripcion FROM categorias ORDER BY nombre_categoria"); while($rw = $q->fetch_assoc()): ?>
            <tr>
              <td><?= $rw['id_categoria'] ?></td>
              <td><?= htmlspecialchars($rw['nombre_categoria']) ?></td>
              <td><?= htmlspecialchars($rw['descripcion']) ?></td>
              <td>
                <?php if (tienePermiso('categorias','puede_editar')): ?>
                  <button class="btn btn-sm btn-outline-primary btn-edit-categoria" data-id="<?= $rw['id_categoria'] ?>" data-nombre="<?= htmlspecialchars($rw['nombre_categoria']) ?>" data-desc="<?= htmlspecialchars($rw['descripcion']) ?>"><i class="fa-solid fa-pen"></i></button>
                <?php endif; ?>
                <?php if (tienePermiso('categorias','puede_eliminar')): ?>
                  <button class="btn btn-sm btn-outline-danger btn-del" data-entity="categoria" data-id="<?= $rw['id_categoria'] ?>"><i class="fa-solid fa-trash"></i></button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- UBICACIONES -->
    <div class="tab-pane fade" id="tabUbicaciones">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Ubicaciones</h5>
        <?php if (tienePermiso('ubicaciones','puede_editar')): ?>
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalUbicacion" data-mode="add"><i class="fa-solid fa-plus"></i> Nueva ubicación</button>
        <?php endif; ?>
      </div>
      <div class="card card-animated p-3 mb-3">
        <table id="dtUbicaciones" class="table table-sm table-striped display">
          <thead><tr><th>ID</th><th>Nivel</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php $q = $conn->query("SELECT id_ubicacion,nivel,nombre,descripcion FROM ubicaciones ORDER BY nombre"); while($rw = $q->fetch_assoc()): ?>
            <tr>
              <td><?= $rw['id_ubicacion'] ?></td>
              <td><?= htmlspecialchars($rw['nivel']) ?></td>
              <td><?= htmlspecialchars($rw['nombre']) ?></td>
              <td><?= htmlspecialchars($rw['descripcion']) ?></td>
              <td>
                <?php if (tienePermiso('ubicaciones','puede_editar')): ?>
                  <button class="btn btn-sm btn-outline-primary btn-edit-ubicacion" data-id="<?= $rw['id_ubicacion'] ?>" data-nivel="<?= htmlspecialchars($rw['nivel']) ?>" data-nombre="<?= htmlspecialchars($rw['nombre']) ?>" data-desc="<?= htmlspecialchars($rw['descripcion']) ?>"><i class="fa-solid fa-pen"></i></button>
                <?php endif; ?>
                <?php if (tienePermiso('ubicaciones','puede_eliminar')): ?>
                  <button class="btn btn-sm btn-outline-danger btn-del" data-entity="ubicacion" data-id="<?= $rw['id_ubicacion'] ?>"><i class="fa-solid fa-trash"></i></button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DEPARTAMENTOS -->
    <div class="tab-pane fade" id="tabDepartamentos">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Departamentos</h5>
        <?php if (tienePermiso('departamentos','puede_editar')): ?>
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalDepartamento" data-mode="add"><i class="fa-solid fa-plus"></i> Nuevo departamento</button>
        <?php endif; ?>
      </div>
      <div class="card card-animated p-3 mb-3">
        <table id="dtDepartamentos" class="table table-sm table-striped display">
          <thead><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Descripción</th><th>Acciones</th></tr></thead>
          <tbody>
          <?php $q = $conn->query("SELECT id_dep,nombre_dep,tipo,descripcion FROM departamentos ORDER BY nombre_dep"); while($rw = $q->fetch_assoc()): ?>
            <tr>
              <td><?= $rw['id_dep'] ?></td>
              <td><?= htmlspecialchars($rw['nombre_dep']) ?></td>
              <td><?= htmlspecialchars($rw['tipo']) ?></td>
              <td><?= htmlspecialchars($rw['descripcion']) ?></td>
              <td>
                <?php if (tienePermiso('departamentos','puede_editar')): ?>
                  <button class="btn btn-sm btn-outline-primary btn-edit-dep" data-id="<?= $rw['id_dep'] ?>" data-nombre="<?= htmlspecialchars($rw['nombre_dep']) ?>" data-tipo="<?= htmlspecialchars($rw['tipo']) ?>" data-desc="<?= htmlspecialchars($rw['descripcion']) ?>"><i class="fa-solid fa-pen"></i></button>
                <?php endif; ?>
                <?php if (tienePermiso('departamentos','puede_eliminar')): ?>
                  <button class="btn btn-sm btn-outline-danger btn-del" data-entity="departamento" data-id="<?= $rw['id_dep'] ?>"><i class="fa-solid fa-trash"></i></button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- --- NUEVO: Estados --- -->
<div class="tab-pane fade" id="tabEstados">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h5>Estados</h5>
    <?php if (tienePermiso('estados','puede_editar')): ?>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalEstado" data-mode="add">
        <i class="fa-solid fa-plus"></i> Nuevo estado
      </button>
    <?php endif; ?>
  </div>
  <div class="card card-animated p-3 mb-3">
    <table id="dtEstados" class="table table-sm table-striped display">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Descripción</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if ($conn->query("SHOW TABLES LIKE 'estados'")->num_rows) {
            $qe = $conn->query("SELECT id_estado, nombre, descripcion FROM estados ORDER BY nombre");
            if ($qe && $qe->num_rows) {
                while($rw = $qe->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$rw['id_estado']}</td>";
                    echo "<td>".htmlspecialchars($rw['nombre'])."</td>";
                    echo "<td>".htmlspecialchars($rw['descripcion'])."</td>";

                    // Acciones
                    echo "<td>";
                    $acciones = '';
                    if (tienePermiso('estados','puede_editar')) {
                        $acciones .= "<button class='btn btn-sm btn-outline-primary btn-edit-estado' data-id='{$rw['id_estado']}' data-nombre='".htmlspecialchars($rw['nombre'],ENT_QUOTES)."' data-desc='".htmlspecialchars($rw['descripcion'],ENT_QUOTES)."'><i class='fa-solid fa-pen'></i></button> ";
                    }
                    if (tienePermiso('estados','puede_eliminar')) {
                        $acciones .= "<button class='btn btn-sm btn-outline-danger btn-del' data-entity='estado' data-id='{$rw['id_estado']}'><i class='fa-solid fa-trash'></i></button>";
                    }
                    echo $acciones;
                    echo "</td>";

                    echo "</tr>";
                }
            } else {
                // Para DataTables: fila vacía con td normales
                echo "<tr><td></td><td></td><td>No hay registros</td><td></td></tr>";
            }
        } else {
            echo "<tr><td></td><td></td><td>Tabla estados no encontrada</td><td></td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>
    <!-- --- NUEVO: Reparaciones --- -->
    <div class="tab-pane fade" id="tabReparaciones">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5>Reparaciones</h5>
        <?php if (tienePermiso('reparaciones','puede_editar')): ?>
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalReparacion" data-mode="add"><i class="fa-solid fa-plus"></i> Nueva reparación</button>
        <?php endif; ?>
      </div>
      <div class="card card-animated p-3 mb-3">
        <div class="row">
          <div class="col-md-8">
            <table id="dtReparaciones" class="table table-sm table-striped display">
              <thead><tr><th>ID</th><th>Equipo</th><th>Problema</th><th>Técnico</th><th>Estado</th><th>Inicio</th><th>Acciones</th></tr></thead>
              <tbody>
                <?php
                if ($conn->query("SHOW TABLES LIKE 'reparaciones'")->num_rows) {
                    $qr = $conn->query("SELECT r.*, e.codigo_equipo FROM reparaciones r LEFT JOIN equipos e ON r.id_equipo=e.id_equipo ORDER BY r.fecha_inicio DESC");
                    if ($qr) {
                      while($rw = $qr->fetch_assoc()) {
                          echo "<tr><td>{$rw['id_reparacion']}</td><td>".htmlspecialchars($rw['codigo_equipo'])."</td><td>".htmlspecialchars($rw['problema'])."</td><td>".htmlspecialchars($rw['tecnico'])."</td><td>".htmlspecialchars($rw['estado'])."</td><td>{$rw['fecha_inicio']}</td><td>";
                          if (tienePermiso('reparaciones','puede_editar')) echo "<button class='btn btn-sm btn-outline-primary btn-view-rep' data-id='{$rw['id_reparacion']}'><i class='fa-solid fa-eye'></i></button> ";
                          if (tienePermiso('reparaciones','puede_editar')) echo "<button class='btn btn-sm btn-outline-success btn-solucion' data-id='{$rw['id_reparacion']}'><i class='fa-solid fa-check'></i></button> ";
                          echo "</td></tr>";
                      }
                    } else {
                      echo "<tr><td colspan='7' class='empty-card'>No se pudo leer reparaciones.</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='empty-card'>Tabla reparaciones no encontrada.</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
          <div class="col-md-4">
            <h6>Últimas acciones</h6>
            <div class="micro">Notificaciones rápidas y stock crítico</div>
            <hr/>
            <div class="mb-2">
              <strong>Stock crítico</strong>
              <ul>
                <?php if (count($stock_critico)): foreach($stock_critico as $s): ?>
                  <li>#<?= $s['id_producto'] ?> — <?= htmlspecialchars($s['categoria']) ?>: <?= intval($s['cantidad']) ?> unidades</li>
                <?php endforeach; else: ?>
                  <li class="micro">No hay stocks críticos.</li>
                <?php endif; ?>
              </ul>
            </div>
            <div class="mb-2">
              <strong>Revisiones próximas</strong>
              <ul>
                <?php if (count($rev_vencidas)): foreach($rev_vencidas as $v): ?>
                  <li><?= htmlspecialchars($v['codigo_equipo']) ?> — <?= htmlspecialchars($v['ultima_revision']) ?></li>
                <?php endforeach; else: ?>
                  <li class="micro">No hay revisiones próximas en 30 días.</li>
                <?php endif; ?>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- --- NUEVO: Historial completo --- -->
    <div class="tab-pane fade" id="tabHistorial">
      <div class="card card-animated p-3 mb-3">
        <h5>Historial de estados y cambios</h5>
        <div class="micro mb-2">Consulta el historial por equipo</div>
        <div class="mb-2">
          <input id="hist_buscar" class="form-control" placeholder="Ingrese código o ID de equipo...">
        </div>
        <div id="hist_result" class="mt-3"></div>
      </div>
    </div>

    <!-- --- NUEVO: Stock --- -->
    <div class="tab-pane fade" id="tabStock">
      <div class="card card-animated p-3 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5>Stock & Alertas</h5>
          <?php if (tienePermiso('stock','puede_editar')): ?>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAlertaStock" data-mode="add"><i class="fa-solid fa-bell"></i> Nueva alerta</button>
          <?php endif; ?>
        </div>

        <div class="row">
          <div class="col-md-7">
            <table id="dtStock" class="table table-sm display">
              <thead><tr><th>ID Stock</th><th>Producto</th><th>Cantidad</th><th>Dep</th><th>Fecha</th></tr></thead>
              <tbody>
                <?php
                if ($conn->query("SHOW TABLES LIKE 'stock'")->num_rows) {
                  $qs = $conn->query("SELECT s.*, a.nombre as nombre_producto FROM stock s LEFT JOIN activos a ON s.id_producto=a.id ORDER BY s.fecha DESC LIMIT 200");
                  if ($qs) {
                    while($rw = $qs->fetch_assoc()) {
                      echo "<tr><td>{$rw['id_stock']}</td><td>".htmlspecialchars($rw['nombre_producto'])."</td><td>{$rw['cantidad']}</td><td>{$rw['id_dep']}</td><td>{$rw['fecha']}</td></tr>";
                    }
                  } else {
                    echo "<tr><td colspan='5' class='empty-card'>No se pudo leer tabla stock.</td></tr>";
                  }
                } else {
                  echo "<tr><td colspan='5' class='empty-card'>Tabla stock no encontrada.</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
          <div class="col-md-5">
            <h6>Alertas de stock</h6>
            <ul id="list_alertas">
              <?php
              if ($conn->query("SHOW TABLES LIKE 'alertas_stock'")->num_rows) {
                  $qa = $conn->query("SELECT a.*, act.nombre as producto FROM alertas_stock a LEFT JOIN activos act ON a.id_producto=act.id ORDER BY a.fecha_alerta DESC LIMIT 50");
                  if ($qa) {
                    while($ar = $qa->fetch_assoc()) {
                        echo "<li><strong>".htmlspecialchars($ar['producto'])."</strong>: ".htmlspecialchars($ar['mensaje'])." <small class='text-muted'>".substr($ar['fecha_alerta'],0,16)."</small></li>";
                    }
                  } else {
                    echo "<li class='micro'>No se pudo leer alertas_stock.</li>";
                  }
              } else {
                  echo "<li class='micro'>No hay alertas registradas.</li>";
              }
              ?>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- --- NUEVO: Notificaciones --- -->
    <div class="tab-pane fade" id="tabNotificaciones">
      <div class="card card-animated p-3 mb-3">
        <div class="d-flex justify-content-between">
          <h5>Notificaciones</h5>
          <div><button id="btnMarcarTodas" class="btn btn-sm btn-outline-primary">Marcar todas leídas</button></div>
        </div>
        <hr/>
        <ul id="listNotificaciones" class="list-group">
          <?php if (count($not_rows)): foreach($not_rows as $n): ?>
            <li class="list-group-item d-flex justify-content-between align-items-start <?= $n['leido'] ? 'text-muted' : '' ?>">
              <div>
                <div class="fw-bold"><?= htmlspecialchars($n['titulo']) ?></div>
                <div class="micro"><?= htmlspecialchars($n['mensaje']) ?></div>
              </div>
              <div class="text-end">
                <small class="micro"><?= $n['fecha'] ?></small><br/>
                <button class="btn btn-sm btn-outline-success btn-leer-not" data-id="<?= $n['id'] ?>">Marcar</button>
                <?php if (!empty($n['link'])): ?><a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($n['link']) ?>">Ir</a><?php endif; ?>
              </div>
            </li>
          <?php endforeach; else: ?>
            <li class="list-group-item empty-card">No hay notificaciones.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
<!-- Botones para abrir los modales -->
<div class="mb-3">
  <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalUsuario" data-mode="add">Nuevo Usuario</button>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMarca" data-mode="add">Nueva Marca</button>
  <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalModelo" data-mode="add">Nuevo Modelo</button>
  <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalCategoria" data-mode="add">Nueva Categoría</button>
  <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modalUbicacion" data-mode="add">Nueva Ubicación</button>
  <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalDepartamento" data-mode="add">Nuevo Departamento</button>
  <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#modalEstado" data-mode="add">Nuevo Estado</button>
</div>
  </div> <!-- tab-content -->
</div> <!-- container -->

<!-- =========================
     Modales (existentes + nuevos
     ========================= -->


<!-- Modal Usuario (igual) -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formUsuario">
      <input type="hidden" name="entity" value="usuario">
      <input type="hidden" name="action" id="usuario_action" value="add">
      <input type="hidden" name="id" id="usuario_id" value="">
      <div class="modal-header"><h5 class="modal-title">Usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nombre</label><input name="nombre" id="usuario_nombre" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Email</label><input name="email" id="usuario_email" type="email" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Rol</label><input name="rol" id="usuario_rol" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Contraseña (solo para nuevo o cambiar)</label><input name="password" id="usuario_password" type="password" class="form-control"></div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> Guardar</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button></div>
    </form>
  </div>
</div>

<!-- Modal Marca (igual) -->
<div class="modal fade" id="modalMarca" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formMarca">
      <input type="hidden" name="entity" value="marca">
      <input type="hidden" name="action" id="marca_action" value="add">
      <input type="hidden" name="id_marca" id="marca_id" value="">
      <div class="modal-header"><h5 class="modal-title">Marca</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nombre</label><input name="nombre" id="marca_nombre" class="form-control" required></div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-success">Guardar</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button></div>
    </form>
  </div>
</div>

<!-- Modal Modelo (igual) -->
<div class="modal fade" id="modalModelo" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formModelo">
      <input type="hidden" name="entity" value="modelo">
      <input type="hidden" name="action" id="modelo_action" value="add">
      <input type="hidden" name="id_modelo" id="modelo_id" value="">
      <div class="modal-header"><h5 class="modal-title">Modelo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Marca</label>
          <select name="id_marca" id="modelo_id_marca" class="form-control" required>
            <option value="">-- Seleccione --</option>
            <?php $m = $conn->query("SELECT id_marca,nombre FROM marcas ORDER BY nombre"); while($op = $m->fetch_assoc()): ?>
              <option value="<?= $op['id_marca'] ?>"><?= htmlspecialchars($op['nombre']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Nombre</label><input name="nombre" id="modelo_nombre" class="form-control" required></div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-success">Guardar</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button></div>
    </form>
  </div>
</div>

<!-- Modal Categoria (igual) -->
<div class="modal fade" id="modalCategoria" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formCategoria">
      <input type="hidden" name="entity" value="categoria">
      <input type="hidden" name="action" id="categoria_action" value="add">
      <input type="hidden" name="id_categoria" id="categoria_id" value="">
      <div class="modal-header"><h5 class="modal-title">Categoría</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nombre</label><input name="nombre" id="categoria_nombre" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Descripción</label><textarea name="descripcion" id="categoria_desc" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-success">Guardar</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button></div>
    </form>
  </div>
</div>

<!-- Modal Ubicacion (igual) -->
<div class="modal fade" id="modalUbicacion" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formUbicacion">
      <input type="hidden" name="entity" value="ubicacion">
      <input type="hidden" name="action" id="ubicacion_action" value="add">
      <input type="hidden" name="id_ubicacion" id="ubicacion_id" value="">
      <div class="modal-header"><h5 class="modal-title">Ubicación</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nivel</label><input name="nivel" id="ubicacion_nivel" class="form-control"></div>
        <div class="mb-2"><label class="form-label">Nombre</label><input name="nombre" id="ubicacion_nombre" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Descripción</label><textarea name="descripcion" id="ubicacion_desc" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer"><button type="submit" class="btn btn-success">Guardar</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button></div>
    </form>
  </div>
</div>
<!-- Modal Departamento -->
<div class="modal fade" id="modalDepartamento" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formDepartamento">
      <input type="hidden" name="entity" value="departamento">
      <input type="hidden" name="action" id="dep_action" value="add">
      <input type="hidden" name="id_dep" id="dep_id" value="">
      <div class="modal-header">
        <h5 class="modal-title">Departamento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nombre</label><input name="nombre" id="dep_nombre" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Tipo</label><input name="tipo" id="dep_tipo" class="form-control"></div>
        <div class="mb-2"><label class="form-label">Descripción</label><textarea name="descripcion" id="dep_desc" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Estado -->
<div class="modal fade" id="modalEstado" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formEstado">
      <input type="hidden" name="entity" value="estado">
      <input type="hidden" name="action" id="estado_action" value="add">
      <input type="hidden" name="id_estado" id="estado_id" value="">
      <div class="modal-header">
        <h5 class="modal-title">Estado</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Nombre</label><input name="nombre" id="estado_nombre" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Descripción</label><textarea name="descripcion" id="estado_desc" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Reparacion -->
<div class="modal fade" id="modalReparacion" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formReparacion">
      <input type="hidden" name="entity" value="reparacion">
      <input type="hidden" name="action" id="reparacion_action" value="add">
      <input type="hidden" name="id_reparacion" id="reparacion_id" value="">
      <div class="modal-header">
        <h5 class="modal-title">Registrar reparación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Equipo (ID)</label><input name="id_equipo" id="rep_id_equipo" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Problema</label><textarea name="problema" id="rep_problema" class="form-control" required></textarea></div>
        <div class="mb-2"><label class="form-label">Técnico</label><input name="tecnico" id="rep_tecnico" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Solución -->
<div class="modal fade" id="modalSolucion" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formSolucion">
      <input type="hidden" name="entity" value="reparacion">
      <input type="hidden" name="action" id="sol_action" value="solucion">
      <input type="hidden" name="id_reparacion" id="sol_id_reparacion" value="">
      <div class="modal-header">
        <h5 class="modal-title">Registrar solución</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Solución</label><textarea name="solucion" id="sol_text" class="form-control" required></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Registrar solución</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Comentario -->
<div class="modal fade" id="modalComentario" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formComentario">
      <input type="hidden" name="entity" value="comentario">
      <input type="hidden" name="id_equipo" id="com_id_equipo" value="">
      <div class="modal-header">
        <h5 class="modal-title">Agregar comentario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Comentario</label><textarea name="comentario" id="comentario_text" class="form-control" required></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar comentario</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Alerta Stock -->
<div class="modal fade" id="modalAlertaStock" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formAlertaStock">
      <input type="hidden" name="entity" value="stock_alerta">
      <div class="modal-header">
        <h5 class="modal-title">Nueva alerta de stock</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Producto (ID)</label><input name="id_producto" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Mensaje</label><textarea name="mensaje" class="form-control" required></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Guardar alerta</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Floating action button -->
<div class="fab" id="fabQuick" title="Atajos">
  <i class="fa-solid fa-magic"></i>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function(){

  // Modo oscuro
  if(localStorage.getItem('mto_dark')==='1') $('body').addClass('dark');

  // Marcar tablas cargadas
  setTimeout(()=> $('table.display').addClass('loaded'), 300);

  // Toast inicial
  const tdiv = document.getElementById('mtoToast');
  if(tdiv){
    const type=tdiv.dataset.type, msg=tdiv.dataset.msg;
    Swal.fire({icon:type==='success'?'success':'error', text:msg, toast:true, position:'top-end', timer:type==='success'?2200:3500, showConfirmButton:false});
  }

  // Inicializar DataTables
  $('table.display').each(function(){
    if(!$.fn.DataTable.isDataTable(this)) $(this).DataTable({responsive:true,pageLength:10});
  });

  // Búsqueda rápida
  $('#searchUsuarios').on('input', function(){ $('#dtUsuarios').DataTable().search(this.value).draw(); });

  // Confirmación de eliminación
  $(document).on('click','.btn-del', function(){
    const ent=$(this).data('entity'), id=$(this).data('id');
    Swal.fire({
      title:'Confirmar eliminación',
      html:`<div>¿Eliminar <strong>${ent}</strong> #${id}?<br><small>Esta acción no se puede revertir.</small></div>`,
      icon:'warning', showCancelButton:true, confirmButtonText:'Sí, eliminar', cancelButtonText:'Cancelar'
    }).then(res=>{
      if(res.isConfirmed){
        const f=$('<form method="POST"></form>');
        f.append(`<input type="hidden" name="entity" value="${ent}">`);
        f.append(`<input type="hidden" name="action" value="delete">`);
        let idname='id';
        if(ent==='marca') idname='id_marca'; else if(ent==='modelo') idname='id_modelo';
        else if(ent==='categoria') idname='id_categoria'; else if(ent==='ubicacion') idname='id_ubicacion';
        else if(ent==='departamento') idname='id_dep'; else if(ent==='usuario') idname='id'; else if(ent==='estado') idname='id_estado';
        f.append(`<input type="hidden" name="${idname}" value="${id}">`);
        $('body').append(f); f.submit();
      }
    });
  });

  // Manejo de modales (Usuario, Marca, Modelo, Categoria, Ubicacion, Departamento, Estado)
  function modalSetup(modalId, actionId, fields, editBtnsClass){
    $(modalId).on('show.bs.modal', function(e){
      const mode=$(e.relatedTarget).data('mode')||'add';
      $(actionId).val(mode==='add'?'add':'edit');
      if(mode==='add') fields.forEach(f=> $(f).val(''));
    });
    $(document).on('click', editBtnsClass, function(){
      fields.forEach(f=> $(f).val($(this).data(f.replace('#','').replace(/_/g,''))||''));
      $(actionId).val('edit'); $(modalId).modal('show');
    });
  }

  modalSetup('#modalUsuario','#usuario_action',['#usuario_id','#usuario_nombre','#usuario_email','#usuario_rol','#usuario_password'],'.btn-edit-user');
  modalSetup('#modalMarca','#marca_action',['#marca_id','#marca_nombre'],'.btn-edit-marca');
  modalSetup('#modalModelo','#modelo_action',['#modelo_id','#modelo_id_marca','#modelo_nombre'],'.btn-edit-modelo');
  modalSetup('#modalCategoria','#categoria_action',['#categoria_id','#categoria_nombre','#categoria_desc'],'.btn-edit-categoria');
  modalSetup('#modalUbicacion','#ubicacion_action',['#ubicacion_id','#ubicacion_nivel','#ubicacion_nombre','#ubicacion_desc'],'.btn-edit-ubicacion');
  modalSetup('#modalDepartamento','#dep_action',['#dep_id','#dep_nombre','#dep_tipo','#dep_desc'],'.btn-edit-dep');
  modalSetup('#modalEstado','#estado_action',['#estado_id','#estado_nombre','#estado_desc'],'.btn-edit-estado');

  // Reparación modal
  $('#modalReparacion').on('show.bs.modal', function(e){
    const mode=$(e.relatedTarget).data('mode')||'add';
    $('#reparacion_action').val(mode==='add'?'add':'edit');
    if(mode==='add'){ $('#reparacion_id,#rep_id_equipo,#rep_problema,#rep_tecnico').val(''); }
  });

  // Abrir modal solución
  $(document).on('click','.btn-solucion', function(){ $('#sol_id_reparacion').val($(this).data('id')); $('#modalSolucion').modal('show'); });

  // Ver reparación timeline
  $(document).on('click','.btn-view-rep', function(){
    const id=$(this).data('id');
    $.get('ajax_reparacion_timeline.php',{id:id}, html=>{
      Swal.fire({title:'Línea de tiempo', html:html, width:800});
    }).fail(()=>Swal.fire('Error','No se pudo obtener el detalle.','error'));
  });

  // Comentarios
  $(document).on('click','.btn-comment-equipo', function(){ $('#com_id_equipo').val($(this).data('id')); $('#modalComentario').modal('show'); });

  // Floating actions
  $('#fabQuick').on('click', function(){
    Swal.fire({
      title:'Atajos rápidos',
      html:'<button class="btn btn-success m-1" id="goUser">Nuevo Usuario</button><button class="btn btn-primary m-1" id="goMarca">Nueva Marca</button>',
      showConfirmButton:false, showCloseButton:true
    });
  });
  $(document).on('click','#goUser', ()=>{ $('#modalUsuario').modal('show'); Swal.close(); });
  $(document).on('click','#goMarca', ()=>{ $('#modalMarca').modal('show'); Swal.close(); });

  // Evitar doble submit
  $('form').on('submit', function(){ $(this).find('button[type=submit]').prop('disabled',true); });

  // Tabs highlight
  $('a[data-bs-toggle="tab"]').on('shown.bs.tab', e=>{
    $(e.target).closest('.page-head').addClass('animated-highlight');
    setTimeout(()=> $(e.target).closest('.page-head').removeClass('animated-highlight'), 900);
  });

  // Guardar solución
  $('#formSolucion').on('submit', function(e){
    e.preventDefault();
    $.post('mantenedores.php', $(this).serialize(), ()=>{ Swal.fire({icon:'success',text:'Solución registrada',toast:true,position:'top-end',timer:1800,showConfirmButton:false}); setTimeout(()=>location.reload(),900); })
    .fail(()=>Swal.fire('Error','No se pudo registrar la solución','error'));
  });

  // Guardar comentario
  $('#formComentario').on('submit', function(e){
    e.preventDefault();
    $.post('mantenedores.php', $(this).serialize(), ()=>{ Swal.fire({icon:'success',text:'Comentario guardado',toast:true,position:'top-end',timer:1600,showConfirmButton:false}); $('#modalComentario').modal('hide'); })
    .fail(()=>Swal.fire('Error','No se pudo guardar comentario','error'));
  });

  // Guardar alerta stock
  $('#formAlertaStock').on('submit', function(e){
    e.preventDefault();
    $.post('mantenedores.php', $(this).serialize(), ()=>{ Swal.fire({icon:'success',text:'Alerta registrada',toast:true,position:'top-end',timer:1600,showConfirmButton:false}); $('#modalAlertaStock').modal('hide'); })
    .fail(()=>Swal.fire('Error','No se pudo registrar alerta','error'));
  });

  // Notificaciones
  $(document).on('click','.btn-leer-not', function(){
    const idn=$(this).data('id');
    $.post('mantenedores.php',{entity:'notificacion',action:'leer',id_n:idn}, ()=>{ Swal.fire({icon:'success',text:'Marcada',toast:true,position:'top-end',timer:1200,showConfirmButton:false}); setTimeout(()=>location.reload(),800); });
  });

  $('#btnMarcarTodas').on('click', function(){
    Swal.fire({title:'Confirmar',text:'Marcar todas las notificaciones visibles como leídas?',showCancelButton:true})
    .then(res=>{ if(res.isConfirmed){ $('#listNotificaciones .btn-leer-not').each(function(){ $(this).click(); }); } });
  });

  // Historial equipo
  $('#hist_buscar').on('keypress', function(e){
    if(e.which==13){
      const q=$(this).val();
      if(!q) return;
      $('#hist_result').html('<div class="micro">Cargando...</div>');
      $.get('ajax_historial_equipo.php',{q:q}, html=>{ $('#hist_result').html(html); })
      .fail(()=>$('#hist_result').html('<div class="empty-card">No se pudo obtener historial.</div>'));
    }
  });

  // Toggle modo oscuro
  $('#toggleDark').on('click', function(e){ e.preventDefault(); $('body').toggleClass('dark'); localStorage.setItem('mto_dark',$('body').hasClass('dark')?'1':'0'); });

  // Polling notificaciones y stock crítico cada 60s
  setInterval(()=>{
    $.getJSON('ajax_poll.php', function(data){
      if(data.not_count) $('#badgeNot').text(data.not_count);
      if(data.reparaciones_count) $('#badgeReparaciones').text(data.reparaciones_count);
      if(data.stock_critico?.length) Swal.fire({icon:'warning', title:'Stock crítico', text: data.stock_critico.length+' producto(s) con stock crítico', toast:true, position:'top-end', timer:3500, showConfirmButton:false});
    });
  },60000);

});
</script>

<?php require_once "includes/footer.php"; ?>
