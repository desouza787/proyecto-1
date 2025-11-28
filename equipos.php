<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "db.php";
require_once "includes/funciones.php";
require_once "includes/header.php";

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}

// ------------------ AUTO-CREAR COLUMNAS (si faltan) ------------------
// Intentamos agregar columnas necesarias sin romper nada (se ejecuta solo si no existen)
function agregar_columna_si_no_existe($conn, $tabla, $columna, $definicion) {
    $res = $conn->query("SHOW COLUMNS FROM `$tabla` LIKE '$columna'");
    if ($res && $res->num_rows === 0) {
        $conn->query("ALTER TABLE `$tabla` ADD COLUMN $columna $definicion");
    }
}
// Columnas que agregamos si no existen (id_usuario, codigo_qr, tipo_disco, mac)
agregar_columna_si_no_existe($conn, 'equipos', 'id_usuario', 'INT NULL');
agregar_columna_si_no_existe($conn, 'equipos', 'codigo_qr', "VARCHAR(255) NULL");
agregar_columna_si_no_existe($conn, 'equipos', 'tipo_disco', "VARCHAR(10) NULL");
agregar_columna_si_no_existe($conn, 'equipos', 'mac', "VARCHAR(32) NULL");

// ------------------ UTIL: generarCodigoInterno (fallback si no existe) ------------------
if (!function_exists('generarCodigoInterno')) {
    /**
     * Genera un código del tipo DEP-UB-CAT-0001 basándose en nombres.
     * Usa primeras 2-4 letras de cada nombre (limpio de espacios y acentos), mayúsculas.
     */
    function generarCodigoInterno($conn, $nivel_unused = '', $id_dep = 0, $id_categoria = 0, $id_ubicacion = 0) {
        $id_dep = intval($id_dep);
        $id_categoria = intval($id_categoria);
        $id_ubicacion = intval($id_ubicacion);

        // obtener nombres
        $dep = $conn->query("SELECT nombre_dep FROM departamentos WHERE id_dep=$id_dep")->fetch_assoc()['nombre_dep'] ?? 'DEP';
        $cat = $conn->query("SELECT nombre_categoria FROM categorias WHERE id_categoria=$id_categoria")->fetch_assoc()['nombre_categoria'] ?? 'CAT';
        $ubi = $conn->query("SELECT nombre FROM ubicaciones WHERE id_ubicacion=$id_ubicacion")->fetch_assoc()['nombre'] ?? 'UBI';

        // normalizar (quitar acentos y no alfanuméricos)
        $norm = function($s, $len=3) {
            $s = preg_replace('/[^\p{L}\p{N}]+/u', '', $s); // quitar espacios y símbolos
            $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            $s = strtoupper(substr($s, 0, max(2, $len)));
            return $s;
        };
        $p1 = $norm($dep, 2);
        $p2 = $norm($ubi, 2);
        $p3 = $norm($cat, 2);

        $prefix = "{$p1}-{$p2}-{$p3}";

        // buscar último correlativo con ese prefix
        $like = $conn->real_escape_string($prefix . '%');
        $r = $conn->query("SELECT codigo_equipo FROM equipos WHERE codigo_equipo LIKE '$like' ORDER BY id_equipo DESC LIMIT 1");
        $last = $r && $r->num_rows ? $r->fetch_assoc()['codigo_equipo'] : null;

        $nextNum = 1;
        if ($last) {
            // extraer número final (busca última secuencia de dígitos)
            if (preg_match('/(\d+)$/', $last, $m)) {
                $nextNum = intval($m[1]) + 1;
            }
        }
        $num = str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$num}";
    }
}

// ------------------ Endpoints AJAX ------------------
if (isset($_GET['accion'])) {
    $accion = $_GET['accion'];

    // generar código (no requiere nivel)
    if ($accion === 'generar_codigo') {
        ob_clean();
        $id_dep = intval($_GET['id_dep'] ?? 0);
        $id_categoria = intval($_GET['id_categoria'] ?? 0);
        $id_ubicacion = intval($_GET['id_ubicacion'] ?? 0);
        if ($id_dep && $id_categoria && $id_ubicacion) {
            echo generarCodigoInterno($conn, '', $id_dep, $id_categoria, $id_ubicacion);
        } else {
            echo '';
        }
        exit;
    }

    // obtener modelos por marca (para select dinámico)
    if ($accion === 'obtener_modelos') {
        ob_clean();
        $id_marca = intval($_GET['id_marca'] ?? 0);
        $out = [];
        if ($id_marca) {
            $res = $conn->query("SELECT id_modelo, nombre FROM modelos WHERE id_marca = $id_marca ORDER BY nombre ASC");
            while ($r = $res->fetch_assoc()) $out[] = $r;
        }
        header('Content-Type: application/json');
        echo json_encode($out);
        exit;
    }
}

// ---------- Consultas para selects ----------
$anexos = $conn->query("SELECT id_dep, nombre_dep FROM departamentos ORDER BY nombre_dep ASC")->fetch_all(MYSQLI_ASSOC);
$categorias = $conn->query("SELECT id_categoria, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC")->fetch_all(MYSQLI_ASSOC);
$ubicaciones = $conn->query("SELECT id_ubicacion, nombre FROM ubicaciones ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);
$marcas = $conn->query("SELECT id_marca, nombre FROM marcas ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);
$usuarios = $conn->query("SELECT id, nombre FROM usuarios ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);

// ------------------ Procesar formulario Agregar Equipo ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // === Import masivo via Excel/CSV ===
    if (isset($_POST['import_excel'])) {
        // archivo
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Error subiendo el archivo.'];
            header("Location: equipos.php");
            exit;
        }

        $tmp = $_FILES['excel_file']['tmp_name'];
        $name = $_FILES['excel_file']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $insertados = 0;
        $errores = [];

        // Prefer PhpSpreadsheet si está instalado
        $rows = [];
        if (in_array($ext, ['xlsx','xls']) && class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
                $sheet = $spreadsheet->getActiveSheet();
                foreach ($sheet->getRowIterator(2) as $row) { // asumimos encabezado en fila 1
                    $cells = [];
                    $ci = $row->getCellIterator();
                    $ci->setIterateOnlyExistingCells(false);
                    foreach ($ci as $cell) $cells[] = trim((string)$cell->getValue());
                    $rows[] = $cells;
                }
            } catch (Exception $e) {
                $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Error leyendo Excel: '.$e->getMessage()];
                header("Location: equipos.php");
                exit;
            }
        } elseif ($ext === 'csv') {
            if (($h = fopen($tmp, 'r')) !== false) {
                // leer encabezado
                $header = fgetcsv($h);
                while (($data = fgetcsv($h)) !== false) $rows[] = $data;
                fclose($h);
            }
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'msg' => 'Formato no soportado. Use .xlsx o .csv.'];
            header("Location: equipos.php");
            exit;
        }

        // FORMATO REQUERIDO (Ejemplo de columnas esperadas)
        // columnas (orden): departamento, ubicacion, categoria, marca, modelo, serie, procesador, ram, tipo_disco(HDD/SSD), disco(capacidad), so, estado, usuario_nombre (opcional), observaciones
        foreach ($rows as $idx => $r) {
            // defensa ante filas vacías
            if (!isset($r[0]) || trim($r[0]) === '') continue;

            $dep_name = trim($r[0] ?? '');
            $ubi_name = trim($r[1] ?? '');
            $cat_name = trim($r[2] ?? '');
            $marca_name = trim($r[3] ?? '');
            $modelo_name = trim($r[4] ?? '');
            $serie = $conn->real_escape_string(trim($r[5] ?? ''));
            $procesador = $conn->real_escape_string(trim($r[6] ?? ''));
            $ram = $conn->real_escape_string(trim($r[7] ?? ''));
            $tipo_disco = strtoupper(trim($r[8] ?? ''));
            $disco_cap = $conn->real_escape_string(trim($r[9] ?? ''));
            $so = $conn->real_escape_string(trim($r[10] ?? ''));
            $estado = trim($r[11] ?? 'En uso');
            $usuario_nombre = trim($r[12] ?? '');
            $observaciones = $conn->real_escape_string(trim($r[13] ?? ''));

            // localizar ids por nombre (si no existe, crear)
            $id_dep = 0; $res = $conn->query("SELECT id_dep FROM departamentos WHERE nombre_dep = '".$conn->real_escape_string($dep_name)."' LIMIT 1");
            if ($res && $res->num_rows) $id_dep = intval($res->fetch_assoc()['id_dep']);
            else {
                $conn->query("INSERT INTO departamentos (nombre_dep, descripcion, tipo) VALUES ('".$conn->real_escape_string($dep_name)."','Importado','','')");
                $id_dep = $conn->insert_id;
            }

            $id_ubicacion = 0; $res = $conn->query("SELECT id_ubicacion FROM ubicaciones WHERE nombre = '".$conn->real_escape_string($ubi_name)."' LIMIT 1");
            if ($res && $res->num_rows) $id_ubicacion = intval($res->fetch_assoc()['id_ubicacion']);
            else {
                $conn->query("INSERT INTO ubicaciones (nombre, descripcion, nivel) VALUES ('".$conn->real_escape_string($ubi_name)."','Importado','')"); // nivel puede quedar vacio
                $id_ubicacion = $conn->insert_id;
            }

            $id_categoria = 0; $res = $conn->query("SELECT id_categoria FROM categorias WHERE nombre_categoria = '".$conn->real_escape_string($cat_name)."' LIMIT 1");
            if ($res && $res->num_rows) $id_categoria = intval($res->fetch_assoc()['id_categoria']);
            else {
                $conn->query("INSERT INTO categorias (nombre_categoria, descripcion) VALUES ('".$conn->real_escape_string($cat_name)."','Importado')");
                $id_categoria = $conn->insert_id;
            }

            // marca/modelo
            $id_marca = 0; $res = $conn->query("SELECT id_marca FROM marcas WHERE nombre = '".$conn->real_escape_string($marca_name)."' LIMIT 1");
            if ($res && $res->num_rows) $id_marca = intval($res->fetch_assoc()['id_marca']);
            else {
                $conn->query("INSERT INTO marcas (nombre) VALUES ('".$conn->real_escape_string($marca_name)."')");
                $id_marca = $conn->insert_id;
            }

            $id_modelo = 0; $res = $conn->query("SELECT id_modelo FROM modelos WHERE nombre = '".$conn->real_escape_string($modelo_name)."' AND id_marca = $id_marca LIMIT 1");
            if ($res && $res->num_rows) $id_modelo = intval($res->fetch_assoc()['id_modelo']);
            else {
                $conn->query("INSERT INTO modelos (id_marca, nombre) VALUES ($id_marca, '".$conn->real_escape_string($modelo_name)."')");
                $id_modelo = $conn->insert_id;
            }

            // usuario asignado (opcional)
            $id_usuario = null;
            if ($usuario_nombre !== '') {
                $res = $conn->query("SELECT id FROM usuario WHERE nombre = '".$conn->real_escape_string($usuario_nombre)."' LIMIT 1");
                if ($res && $res->num_rows) $id_usuario = intval($res->fetch_assoc()['id']);
                else {
                    // no creamos usuarios nuevos por seguridad; se deja null
                    $id_usuario = null;
                }
            }

            // generar código
            $codigo = generarCodigoInterno($conn, '', $id_dep, $id_categoria, $id_ubicacion);

            // evitar duplicados por serie o mac vacío (si ya existe serie idéntica, omitir)
            $dup = false;
            if ($serie !== '') {
                $rdup = $conn->query("SELECT id_equipo FROM equipos WHERE serie = '".$conn->real_escape_string($serie)."' LIMIT 1");
                if ($rdup && $rdup->num_rows) { $errores[] = "Fila ".($idx+2).": serie duplicada ($serie)"; $dup = true; }
            }

            if ($dup) continue;

            $stmt = $conn->prepare("INSERT INTO equipos (codigo_equipo, marca, modelo, serie, procesador, ram, disco, so, estado, id_dep, id_categoria, id_ubicacion, tipo_disco, observaciones, id_usuario, fecha_ingreso) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
            $marca_ins = $marca_name;
            $modelo_ins = $modelo_name;
            $stmt->bind_param("ssssssssiiisss", $codigo, $marca_ins, $modelo_ins, $serie, $procesador, $ram, $disco_cap, $so, $estado, $id_dep, $id_categoria, $id_ubicacion, $tipo_disco, $observaciones);
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                // generar QR y guardar path
                $qrpath = generarGuardarQR($new_id, $codigo);
                if ($qrpath) $conn->query("UPDATE equipos SET codigo_qr = '".$conn->real_escape_string($qrpath)."' WHERE id_equipo = $new_id");
                $insertados++;
            } else {
                $errores[] = "Fila ".($idx+2).": Error DB (".$stmt->error.")";
            }
            $stmt->close();
        }

        $_SESSION['toast'] = ['type'=>'success','msg'=>"$insertados equipos importados. Errores: ".count($errores)];
        // podrias almacenar $errores en sesión para descargarlos si quieres
        header("Location: equipos.php");
        exit;
    }

    // === Agregar equipo individual ===
    if (isset($_POST['agregar_equipo'])) {
        // tomamos valores (nivel ya no es obligatorio en formulario; se ignora)
        $id_dep = intval($_POST['id_dep'] ?? 0);
        $id_categoria = intval($_POST['id_categoria'] ?? 0);
        $id_ubicacion = intval($_POST['id_ubicacion'] ?? 0);
        $estado = $conn->real_escape_string($_POST['estado'] ?? 'En uso');
        $marca = $conn->real_escape_string($_POST['marca'] ?? '');
        $modelo = $conn->real_escape_string($_POST['modelo'] ?? '');
        $serie = $conn->real_escape_string($_POST['serie'] ?? '');
        $procesador = $conn->real_escape_string($_POST['procesador'] ?? '');
        $ram = $conn->real_escape_string($_POST['ram'] ?? '');
        $disco = $conn->real_escape_string($_POST['disco'] ?? '');
        $tipo_disco = $conn->real_escape_string($_POST['tipo_disco'] ?? '');
        $so = $conn->real_escape_string($_POST['so'] ?? '');
        $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
        $ultima_revision = !empty($_POST['ultima_revision']) ? $conn->real_escape_string($_POST['ultima_revision']) : NULL;
        $id_usuario = !empty($_POST['id_usuario']) ? intval($_POST['id_usuario']) : null;
        $mac = $conn->real_escape_string($_POST['mac'] ?? '');

        // imagen
        $imagen_path = NULL;
        if (!empty($_FILES['imagen']['name'])) {
            $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $imagen_path = "uploads/" . uniqid() . ".$ext";
            @mkdir(dirname($imagen_path), 0755, true);
            move_uploaded_file($_FILES['imagen']['tmp_name'], $imagen_path);
        }

        // validación MAC (si viene)
        if (!empty($mac)) {
            $mac_clean = strtoupper(preg_replace('/[^A-F0-9]/i', '', $mac));
            if (!preg_match('/^[A-F0-9]{12}$/', $mac_clean)) {
                $_SESSION['toast'] = ['type'=>'error','msg'=>'MAC inválida. Formato aceptado: AA:BB:CC:11:22:33 o AABBCCDDEEFF.'];
                header("Location: equipos.php");
                exit;
            }
            // formatear con : para almacenar opcionalmente
            $mac = strtoupper(implode(':', str_split($mac_clean,2)));
        }

        // Generar código basado en departamento+ubicacion+categoria
        $codigo_equipo = generarCodigoInterno($conn, '', $id_dep, $id_categoria, $id_ubicacion);

        // comprobar duplicado por codigo, serie o mac
        $stmt_check = $conn->prepare("SELECT id_equipo FROM equipos WHERE (codigo_equipo=? OR serie=? OR (mac IS NOT NULL AND mac <> '' AND mac=?)) LIMIT 1");
        $stmt_check->bind_param("sss", $codigo_equipo, $serie, $mac);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        $stmt_check->close();
        if ($res_check && $res_check->num_rows > 0) {
            $_SESSION['toast'] = ['type'=>'error','msg'=>"Error: código/serie/MAC ya existe en la base de datos."];
            header("Location: equipos.php");
            exit;
        }

        // Insertar equipo (mirando las columnas disponibles)
        $stmt = $conn->prepare("INSERT INTO equipos 
            (codigo_equipo, marca, modelo, serie, procesador, ram, disco, tipo_disco, so, estado, id_dep, id_categoria, id_ubicacion, observaciones, imagen, ultima_revision, id_usuario, mac)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param(
            "sssssssssiissssiss",
            $codigo_equipo,
            $marca,
            $modelo,
            $serie,
            $procesador,
            $ram,
            $disco,
            $tipo_disco,
            $so,
            $estado,
            $id_dep,
            $id_categoria,
            $id_ubicacion,
            $observaciones,
            $imagen_path,
            $ultima_revision,
            $id_usuario,
            $mac
        );

        if (!$stmt->execute()) {
            $_SESSION['toast'] = ['type'=>'error','msg'=>'Error al agregar equipo: '.$stmt->error];
            header("Location: equipos.php");
            exit;
        }

        $id_equipo = $conn->insert_id;
        $usuario = $_SESSION['usuario'] ?? 'Sistema';
        $detalle = "Equipo agregado al sistema";
        $conn->query("INSERT INTO historial_estados (id_equipo, estado_anterior, estado_nuevo, usuario, fecha, detalle)
                      VALUES ($id_equipo,'N/A','$estado','$usuario',NOW(),'$detalle')");

        // Generar QR y guardar ruta en campo codigo_qr
        $qrpath = generarGuardarQR($id_equipo, $codigo_equipo);
        if ($qrpath) {
            $stmt2 = $conn->prepare("UPDATE equipos SET codigo_qr = ? WHERE id_equipo = ?");
            $stmt2->bind_param("si", $qrpath, $id_equipo);
            $stmt2->execute();
            $stmt2->close();
        }

        $stmt->close();

        $_SESSION['toast'] = ['type' => 'success', 'msg' => '✅ Equipo agregado correctamente', 'timeout' => 4000];
        header("Location: equipos.php");
        exit;
    }
}

// ------------------ FUNCION: generar y guardar QR ------------------
function generarGuardarQR($id_equipo, $codigo_equipo) {
    global $conn;
    // ruta donde guardamos
    @mkdir('qrs', 0755, true);
    $filename = "qrs/QR_{$id_equipo}.png";
    // URL que el QR debe apuntar: ficha pública/privada
    // Puedes cambiar ver_equipo.php por la ruta que uses para mostrar la ficha del equipo
    $target = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='off' ? 'https' : 'http') . "://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/ver_equipo.php?id=".$id_equipo;
    // usamos servicio público api.qrserver.com
    $qr_api = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=".urlencode($target);
    // intentar descarguar
    try {
        $img = @file_get_contents($qr_api);
        if ($img === false) {
            return null;
        }
        file_put_contents($filename, $img);
        return $filename;
    } catch (Exception $e) {
        return null;
    }
}

// ------------------ FIN BACKEND ------------------
?>
<!-- ===========================
     ESTILOS LOCALES (sin cambios visuales)
     =========================== -->
<style>
/* (mantener estilos existentes, no modificar visual) */
:root{ --bg:#f6fbff; --card:#ffffff; --muted:#6c757d; --accent:#4b9cdb; --text:#15202b; --glass: rgba(255,255,255,0.7); }
body.dark-mode { --bg:#071122; --card:#071829; --muted:#9aa6b2; --accent:#5eb4ff; --text:#e6eef8; --glass: rgba(255,255,255,0.03); }
.page-wrap { background: linear-gradient(180deg,var(--bg), #eef6ff); padding: 28px 0; min-height:80vh; transition: background 300ms ease; }
.card-soft { background: var(--card); border-radius: 12px; box-shadow: 0 6px 30px rgba(2,6,23,0.06); color:var(--text); }
.header-compact { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; }
.controls { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.controls .filter { display:flex; gap:8px; align-items:center; }
/* table */
.table thead th { background: linear-gradient(90deg, rgba(0,0,0,0.03), rgba(0,0,0,0.02)); }
.table tbody tr { transition: background 160ms ease, transform 120ms ease; }
.table tbody tr:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(2,6,23,0.04); background: var(--glass); }
.img-preview { width:80px; height:80px; object-fit:cover; border-radius:8px; border:1px solid rgba(0,0,0,0.06); }
.qr-thumb { width:60px; height:60px; object-fit:cover; border-radius:6px; border:1px solid rgba(0,0,0,0.06); }
.theme-btn { border-radius: 8px; padding:8px 10px; background:var(--card); border:1px solid rgba(0,0,0,0.04); cursor:pointer; box-shadow:0 4px 14px rgba(2,6,23,0.03); }
#app-toast { position: fixed; right: 20px; bottom: 20px; z-index: 12000; }
@media (max-width: 767px) { .header-compact { flex-direction:column; align-items:flex-start; gap:8px; } }
</style>

<div class="page-wrap">
  <div class="container">
    <!-- HEADER -->
    <div class="header-compact">
      <div>
        <h3 class="mb-0">💻 Gestión de Equipos</h3>
        <div class="small-muted">Administra, filtra y exporta tu inventario de equipos</div>
      </div>

      <div class="d-flex gap-2 align-items-center">
        <button id="theme-toggle" class="theme-btn" title="Modo oscuro / claro">🌙</button>
        <button class="btn btn-outline-secondary" id="btn-help" title="Ayuda rápida"><i class="bi bi-question-circle"></i></button>
        <div class="btn-group">
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarEquipo"><i class="bi bi-plus-circle"></i> Agregar equipo</button>
          <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modalImportExcel"><i class="bi bi-file-earmark-spreadsheet"></i> Agregar por Excel</button>
        </div>
      </div>
    </div>

    <!-- RESUMEN -->
    <div class="row g-3 mb-3 summary-cards">
      <?php 
      $total = intval($conn->query("SELECT COUNT(*) as c FROM equipos")->fetch_assoc()['c'] ?? 0);
      $activos = intval($conn->query("SELECT COUNT(*) as c FROM equipos WHERE estado='En uso'")->fetch_assoc()['c'] ?? 0);
      $reparacion = intval($conn->query("SELECT COUNT(*) as c FROM equipos WHERE estado='En reparación'")->fetch_assoc()['c'] ?? 0);
      $danados = intval($conn->query("SELECT COUNT(*) as c FROM equipos WHERE estado='Dañado'")->fetch_assoc()['c'] ?? 0);
      ?>
      <div class="col-md-3">
        <div class="card card-soft p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="small-muted">Total equipos</small>
              <div class="fs-4 fw-bold"><?= $total ?></div>
            </div>
            <div><i class="bi bi-hdd-stack fs-2 text-primary"></i></div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-soft p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="small-muted">En uso</small>
              <div class="fs-4 fw-bold text-success"><?= $activos ?></div>
            </div>
            <div><i class="bi bi-laptop fs-2 text-success"></i></div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-soft p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="small-muted">En reparación</small>
              <div class="fs-4 fw-bold text-warning"><?= $reparacion ?></div>
            </div>
            <div><i class="bi bi-tools fs-2 text-warning"></i></div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-soft p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <small class="small-muted">Dañados</small>
              <div class="fs-4 fw-bold text-danger"><?= $danados ?></div>
            </div>
            <div><i class="bi bi-exclamation-octagon fs-2 text-danger"></i></div>
          </div>
        </div>
      </div>
    </div>

    <!-- FILTROS Y EXPORT -->
    <div class="card card-soft mb-3 p-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="filter">
          <select id="filtro_estado" class="form-select form-select-sm">
            <option value="">Estado (Todos)</option>
            <option>En uso</option>
            <option>En reparación</option>
            <option>Dañado</option>
            <option>Dado de baja</option>
          </select>
        </div>

        <div class="filter">
          <select id="filtro_categoria" class="form-select form-select-sm">
            <option value="">Categoría (Todas)</option>
            <?php foreach($categorias as $c): ?><option><?= htmlspecialchars($c['nombre_categoria']) ?></option><?php endforeach; ?>
          </select>
        </div>

        <div class="filter">
          <select id="filtro_ubicacion" class="form-select form-select-sm">
            <option value="">Ubicación (Todas)</option>
            <?php foreach($ubicaciones as $u): ?><option><?= htmlspecialchars($u['nombre']) ?></option><?php endforeach; ?>
          </select>
        </div>

        <div class="filter">
          <select id="filtro_departamento" class="form-select form-select-sm">
            <option value="">Departamento (Todos)</option>
            <?php foreach($anexos as $a): ?><option><?= htmlspecialchars($a['nombre_dep']) ?></option><?php endforeach; ?>
          </select>
        </div>

        <div class="filter">
          <input id="search-global" class="form-control form-control-sm" placeholder="Buscar por código, marca o modelo..." />
        </div>
      </div>

      <div class="d-flex gap-2">
        <a href="#" id="btnExcel" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Excel</a>
        <a href="#" id="btnPDF" class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
      </div>
    </div>

    <!-- TABLA EQUIPOS -->
    <div class="card card-soft p-3 shadow-sm">
      <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered align-middle" id="tabla_equipos">
          <thead class="table-dark">
            <tr>
              <th>ID</th>
              <th>Imagen</th>
              <th>Código</th>
              <th>Marca</th>
              <th>Modelo</th>
              <th>Serie</th>
              <th>MAC</th>
              <th>Tipo Disco</th>
              <th>Estado</th>
              <th>Departamento</th>
              <th>Categoría</th>
              <th>Ubicación</th>
              <th>QR</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $equipos = $conn->query("SELECT e.*, d.nombre_dep, c.nombre_categoria, u.nombre as ubicacion
                                         FROM equipos e
                                         LEFT JOIN departamentos d ON e.id_dep=d.id_dep
                                         LEFT JOIN categorias c ON e.id_categoria=c.id_categoria
                                         LEFT JOIN ubicaciones u ON e.id_ubicacion=u.id_ubicacion
                                         ORDER BY e.id_equipo DESC");
            while ($row = $equipos->fetch_assoc()):
                $badgeClass = 'secondary';
                if($row['estado']=='En uso') $badgeClass='success';
                elseif($row['estado']=='En reparación') $badgeClass='warning';
                elseif($row['estado']=='Dañado') $badgeClass='danger';
            ?>
            <tr>
              <td><?= $row['id_equipo'] ?></td>
              <td>
                <?php if($row['imagen']): ?>
                  <img src="<?= $row['imagen'] ?>" width="60" class="img-preview">
                <?php else: ?>
                  <div class="text-muted small">Sin imagen</div>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($row['codigo_equipo']) ?></td>
              <td><?= htmlspecialchars($row['marca']) ?></td>
              <td><?= htmlspecialchars($row['modelo']) ?></td>
              <td><?= htmlspecialchars($row['serie']) ?></td>
              <td><?= htmlspecialchars($row['mac'] ?? '') ?></td>
              <td><?= htmlspecialchars($row['tipo_disco'] ?? '') ?></td>
              <td><span class="badge bg-<?= $badgeClass ?>"><?= htmlspecialchars($row['estado']) ?></span></td>
              <td><?= htmlspecialchars($row['nombre_dep']) ?></td>
              <td><?= htmlspecialchars($row['nombre_categoria']) ?></td>
              <td><?= htmlspecialchars($row['ubicacion']) ?></td>
              <td>
                <?php if (!empty($row['codigo_qr']) && file_exists($row['codigo_qr'])): ?>
                  <a href="<?= $row['codigo_qr'] ?>" target="_blank"><img src="<?= $row['codigo_qr'] ?>" class="qr-thumb" alt="QR"></a>
                <?php else: ?>
                  <div class="small text-muted">Sin QR</div>
                <?php endif; ?>
              </td>
              <td>
                <div class="d-flex gap-1">
                  <a href="editar_equipo.php?id=<?= $row['id_equipo'] ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                  <form method="POST" action="toggle_estado.php" style="display:inline;">
                    <input type="hidden" name="id_equipo" value="<?= $row['id_equipo'] ?>">
                    <button type="submit" name="toggle_disable" class="btn btn-sm btn-outline-danger" title="Deshabilitar / Activar"><i class="bi bi-power"></i></button>
                  </form>
                  <a href="ver_equipo.php?id=<?= $row['id_equipo'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver ficha"><i class="bi bi-card-text"></i></a>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Modal Agregar Equipo (mejorado visualmente) -->
<div class="modal fade" id="modalAgregarEquipo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" method="POST" action="" enctype="multipart/form-data" id="formAgregarEquipo">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i> Agregar Nuevo Equipo</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">Anexo</label>
            <select name="id_dep" class="form-select" required id="sel_dep">
              <option value="">Seleccione un Anexo</option>
              <?php foreach($anexos as $a): ?><option value="<?= $a['id_dep'] ?>"><?= htmlspecialchars($a['nombre_dep']) ?></option><?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Categoría</label>
            <select name="id_categoria" class="form-select" required id="sel_categoria">
              <option value="">Seleccione una Categoría</option>
              <?php foreach($categorias as $c): ?><option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option><?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Ubicación</label>
            <select name="id_ubicacion" class="form-select" required id="sel_ubicacion">
              <option value="">Seleccione una Ubicación</option>
              <?php foreach($ubicaciones as $u): ?><option value="<?= $u['id_ubicacion'] ?>"><?= htmlspecialchars($u['nombre']) ?></option><?php endforeach; ?>
            </select>
          </div>

          <!-- Nivel: lo ocultamos del formulario según tu pedido, pero lo dejamos presente en BD si lo usas -->
          <div class="col-md-4" style="display:none;">
            <label class="form-label">Nivel</label>
            <input type="text" name="nivel" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">Código (Generado)</label>
            <input type="text" id="codigo_equipo" class="form-control" readonly style="background-color:#e9ecef;">
          </div>

          <div class="col-md-4">
            <label class="form-label">Marca</label>
            <select name="marca" id="sel_marca" class="form-select">
              <option value="">-- Nueva o seleccione marca --</option>
              <?php foreach($marcas as $m): ?><option value="<?= htmlspecialchars($m['nombre']) ?>" data-id="<?= $m['id_marca'] ?>"><?= htmlspecialchars($m['nombre']) ?></option><?php endforeach; ?>
              <option value="__otra__">-- Otra --</option>
            </select>
            <input type="text" id="marca_otro" class="form-control mt-1" placeholder="Ingrese nueva marca" style="display:none;">
          </div>

          <div class="col-md-4">
            <label class="form-label">Modelo</label>
            <select name="modelo" id="sel_modelo" class="form-select">
              <option value="">-- Seleccione modelo --</option>
            </select>
            <input type="text" id="modelo_otro" name="modelo_otro" class="form-control mt-1" placeholder="Ingrese nuevo modelo" style="display:none;">
          </div>

          <div class="col-md-4">
            <label class="form-label">Serie</label>
            <input type="text" name="serie" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">MAC (opcional)</label>
            <input type="text" name="mac" class="form-control" placeholder="AA:BB:CC:11:22:33">
          </div>

          <div class="col-md-4">
            <label class="form-label">Tipo Disco</label>
            <select name="tipo_disco" class="form-select">
              <option value="">Seleccione</option>
              <option>HDD</option>
              <option>SSD</option>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Disco (capacidad)</label>
            <input type="text" name="disco" class="form-control" placeholder="500GB, 1TB">
          </div>

          <div class="col-md-4">
            <label class="form-label">Procesador</label>
            <input type="text" name="procesador" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">RAM</label>
            <input type="text" name="ram" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">S.O.</label>
            <input type="text" name="so" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">Usuario asignado</label>
            <select name="id_usuario" class="form-select">
              <option value="">Sin asignar</option>
              <?php foreach($usuarios as $u): ?><option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option><?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select">
              <option>En uso</option>
              <option>En reparación</option>
              <option>Dañado</option>
              <option>Dado de baja</option>
            </select>
          </div>

          <div class="col-md-12">
            <label class="form-label">Observaciones</label>
            <textarea name="observaciones" class="form-control" rows="2"></textarea>
          </div>

          <div class="col-md-6">
            <label class="form-label">Imagen</label>
            <input type="file" name="imagen" class="form-control" id="input-imagen">
            <div class="mt-2">
              <img id="preview-img" src="" alt="" class="img-preview" style="display:none;">
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Última Revisión</label>
            <input type="datetime-local" name="ultima_revision" class="form-control">
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="submit" name="agregar_equipo" class="btn btn-success">Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Importar Excel -->
<div class="modal fade" id="modalImportExcel" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" method="POST" action="" enctype="multipart/form-data">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title"><i class="bi bi-file-earmark-spreadsheet me-2"></i> Importar Equipos desde Excel/CSV</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Formato esperado (encabezado en la primera fila):</p>
        <pre>departamento,ubicacion,categoria,marca,modelo,serie,procesador,ram,tipo_disco(HDD/SSD),disco,so,estado,usuario_nombre,observaciones</pre>
        <p>Puedes descargar una plantilla de ejemplo si lo deseas.</p>

        <div class="mb-3">
          <label class="form-label">Archivo (.xlsx o .csv)</label>
          <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
        </div>

        <div class="alert alert-info small">
          Recomendación: revisa que las columnas estén en el orden exacto. El import creará registros nuevos para <strong>departamentos, ubicaciones y categorías</strong> que no existan (con etiqueta "Importado").
        </div>
      </div>

      <div class="modal-footer">
        <button type="submit" name="import_excel" class="btn btn-success">Importar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Toasts -->
<div id="app-toast" aria-live="polite" aria-atomic="true">
  <div class="toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true" id="mainToast">
    <div class="d-flex">
      <div class="toast-body" id="mainToastBody">Mensaje</div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<!-- SCRIPTS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ---------- GENERAR CÓDIGO DINÁMICO (ahora basado en dept+ubic+cat) ----------
function generarCodigo() {
    const id_dep = document.querySelector('select[name="id_dep"]').value;
    const id_categoria = document.querySelector('select[name="id_categoria"]').value;
    const id_ubicacion = document.querySelector('select[name="id_ubicacion"]').value;

    if(id_dep && id_categoria && id_ubicacion){
        fetch(`equipos.php?accion=generar_codigo&id_dep=${id_dep}&id_categoria=${id_categoria}&id_ubicacion=${id_ubicacion}`)
            .then(res => res.text())
            .then(data => document.getElementById('codigo_equipo').value = data)
            .catch(err => console.error(err));
    } else {
        document.getElementById('codigo_equipo').value = '';
    }
}
document.querySelector('select[name="id_dep"]').addEventListener('change', generarCodigo);
document.querySelector('select[name="id_categoria"]').addEventListener('change', generarCodigo);
document.querySelector('select[name="id_ubicacion"]').addEventListener('change', generarCodigo);

// ---------- PREVIEW IMAGEN ----------
const inputImagen = document.getElementById('input-imagen');
const previewImg = document.getElementById('preview-img');
if (inputImagen) {
  inputImagen.addEventListener('change', (e) => {
    const f = e.target.files[0];
    if (!f) { previewImg.style.display='none'; previewImg.src=''; return; }
    const url = URL.createObjectURL(f);
    previewImg.src = url;
    previewImg.style.display = 'inline-block';
  });
}

// ---------- Marcas y Modelos dinámicos ----------
const selMarca = document.getElementById('sel_marca');
const selModelo = document.getElementById('sel_modelo');
selMarca.addEventListener('change', function(){
    const val = this.value;
    if (val === '__otra__') {
        document.getElementById('marca_otro').style.display='block';
        selModelo.innerHTML = '<option value="">-- Ingrese modelo --</option>';
        document.getElementById('modelo_otro').style.display='block';
        return;
    } else {
        document.getElementById('marca_otro').style.display='none';
        document.getElementById('modelo_otro').style.display='none';
    }
    // intentar obtener id_marca del option dataset (si lo tienes en el option)
    const opt = this.selectedOptions[0];
    const id_marca = opt ? opt.getAttribute('data-id') : '';
    if (!id_marca) {
        selModelo.innerHTML = '<option value="">-- Ingrese modelo --</option>';
        return;
    }
    fetch(`equipos.php?accion=obtener_modelos&id_marca=${id_marca}`)
      .then(r=>r.json())
      .then(data=>{
         selModelo.innerHTML = '<option value="">-- Seleccione modelo --</option>';
         data.forEach(m=>{
            const o = document.createElement('option'); o.value = m.nombre || m.id_modelo; o.textContent = m.nombre; o.dataset.id = m.id_modelo;
            selModelo.appendChild(o);
         });
      }).catch(e=>{ console.error(e); selModelo.innerHTML = '<option value="">-- Error cargando --</option>'; });
});

// ---------- FILTROS EN TIEMPO REAL (cliente) ----------
document.querySelectorAll('#filtro_estado,#filtro_categoria,#filtro_ubicacion,#filtro_departamento').forEach(select=>{ select.addEventListener('change',applyFilters); });
document.getElementById('search-global').addEventListener('input', applyFilters);
function applyFilters() {
    const estado = document.getElementById('filtro_estado').value.toLowerCase();
    const categoria = document.getElementById('filtro_categoria').value.toLowerCase();
    const ubicacion = document.getElementById('filtro_ubicacion').value.toLowerCase();
    const departamento = document.getElementById('filtro_departamento').value.toLowerCase();
    const q = document.getElementById('search-global').value.toLowerCase().trim();

    document.querySelectorAll('#tabla_equipos tbody tr').forEach(row=>{
        const rowEstado = row.cells[8].innerText.toLowerCase();
        const rowCategoria = row.cells[10].innerText.toLowerCase();
        const rowUbicacion = row.cells[11].innerText.toLowerCase();
        const rowDep = row.cells[9].innerText.toLowerCase();
        const rowText = row.innerText.toLowerCase();

        const ok = (estado==''||rowEstado==estado) &&
                   (categoria==''||rowCategoria==categoria) &&
                   (ubicacion==''||rowUbicacion==ubicacion) &&
                   (departamento==''||rowDep==departamento) &&
                   (q==''||rowText.indexOf(q)!==-1);

        row.style.display = ok ? '' : 'none';
    });
}

// ---------- EXPORT buttons ----------
document.getElementById("btnExcel").addEventListener("click",function(e){e.preventDefault();
    const estado = encodeURIComponent(document.getElementById("filtro_estado").value);
    const categoria = encodeURIComponent(document.getElementById("filtro_categoria").value);
    const ubicacion = encodeURIComponent(document.getElementById("filtro_ubicacion").value);
    const departamento = encodeURIComponent(document.getElementById("filtro_departamento").value);
    window.location.href=`export_equipos_excel.php?estado=${estado}&categoria=${categoria}&ubicacion=${ubicacion}&departamento=${departamento}`;
});
document.getElementById("btnPDF").addEventListener("click",function(e){e.preventDefault();
    const estado = encodeURIComponent(document.getElementById("filtro_estado").value);
    const categoria = encodeURIComponent(document.getElementById("filtro_categoria").value);
    const ubicacion = encodeURIComponent(document.getElementById("filtro_ubicacion").value);
    const departamento = encodeURIComponent(document.getElementById("filtro_departamento").value);
    window.location.href=`export_equipos_pdf.php?estado=${estado}&categoria=${categoria}&ubicacion=${ubicacion}&departamento=${departamento}`;
});

// ---------- THEME toggle ----------
const themeBtn = document.getElementById('theme-toggle');
function applyTheme(v) {
  if (v === 'dark') document.body.classList.add('dark-mode');
  else document.body.classList.remove('dark-mode');
  localStorage.setItem('equipos_theme', v);
}
themeBtn.addEventListener('click', ()=>{
  const cur = localStorage.getItem('equipos_theme') || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark':'light');
  const next = cur === 'dark' ? 'light' : 'dark';
  applyTheme(next);
});
(function initTheme(){
  const saved = localStorage.getItem('equipos_theme');
  if (saved) applyTheme(saved);
  else {
    const prefer = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
    applyTheme(prefer);
  }
})();

// ---------- TOAST helper ----------
const mainToastEl = document.getElementById('mainToast');
const mainToast = new bootstrap.Toast(mainToastEl, { delay: 4000 });
function showToast(msg, type='info', timeout=4000) {
  const body = document.getElementById('mainToastBody');
  body.textContent = msg;
  mainToastEl.classList.remove('border-success','border-danger','border-info');
  if (type==='success') mainToastEl.classList.add('border-success');
  else if (type==='error') mainToastEl.classList.add('border-danger');
  else mainToastEl.classList.add('border-info');
  mainToast.show();
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const o = ctx.createOscillator(); const g = ctx.createGain();
    o.type='sine'; o.frequency.value = (type==='success'?880:520);
    g.gain.value = 0.02; o.connect(g); g.connect(ctx.destination);
    o.start(); setTimeout(()=>o.stop(),120);
  } catch(e){}
}
<?php if (isset($_SESSION['toast'])):
    $t = $_SESSION['toast'];
    $t_msg = addslashes($t['msg'] ?? '');
    $t_type = addslashes($t['type'] ?? 'info');
    $t_timeout = intval($t['timeout'] ?? 4000);
    unset($_SESSION['toast']);
?>
  document.addEventListener('DOMContentLoaded', ()=>{ showToast("<?= $t_msg ?>","<?= $t_type ?>", <?= $t_timeout ?>); });
<?php endif; ?>

document.getElementById('btn-help').addEventListener('click', ()=>{
  showToast('Para agregar un equipo completa el formulario y pulsa Guardar. Usa el import para agregar varios equipos desde Excel (ver formato).', 'info', 7000);
});
</script>

<?php include 'asistente/bot.php'; ?>
