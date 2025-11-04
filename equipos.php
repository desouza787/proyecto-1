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

// ---------- AJAX para generar código dinámico ----------
if (isset($_GET['accion']) && $_GET['accion'] === 'generar_codigo') {
    ob_clean();

    $nivel = $_GET['nivel'] ?? '';
    $id_dep = intval($_GET['id_dep'] ?? 0);
    $id_categoria = intval($_GET['id_categoria'] ?? 0);

    if ($nivel && $id_dep && $id_categoria) {
        $nivelPref = strtoupper(substr($nivel, 0, 2));
        $depRes = $conn->query("SELECT nombre_dep FROM departamentos WHERE id_dep=$id_dep");
        $depPref = strtoupper(substr($depRes->fetch_assoc()['nombre_dep'] ?? '', 0, 2));
        $catRes = $conn->query("SELECT nombre_categoria FROM categorias WHERE id_categoria=$id_categoria");
        $catPref = strtoupper(substr($catRes->fetch_assoc()['nombre_categoria'] ?? '', 0, 2));
        $prefijo = "$nivelPref-$depPref-$catPref";

        $lastRes = $conn->query("SELECT codigo_equipo FROM equipos WHERE codigo_equipo LIKE '$prefijo-%' ORDER BY id_equipo DESC LIMIT 1");
        if ($lastRes && $row = $lastRes->fetch_assoc()) {
            preg_match('/-(\d+)$/', $row['codigo_equipo'], $matches);
            $lastNum = isset($matches[1]) ? intval($matches[1]) : 0;
            $num = $lastNum + 1;
        } else {
            $num = 1;
        }

        echo $prefijo . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    } else {
        echo '';
    }
    exit;
}

// ---------- Consultas para selects ----------
$anexos = $conn->query("SELECT id_dep, nombre_dep FROM departamentos ORDER BY nombre_dep ASC")->fetch_all(MYSQLI_ASSOC);
$categorias = $conn->query("SELECT id_categoria, nombre_categoria FROM categorias ORDER BY nombre_categoria ASC")->fetch_all(MYSQLI_ASSOC);
$ubicaciones = $conn->query("SELECT id_ubicacion, nombre FROM ubicaciones ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);

// ---------- Procesar formulario Agregar Equipo ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_equipo'])) {
    $nivel = $_POST['nivel'] ?? '';
    $id_dep = intval($_POST['id_dep'] ?? 0);
    $id_categoria = intval($_POST['id_categoria'] ?? 0);
    $id_ubicacion = intval($_POST['id_ubicacion'] ?? 0);
    $estado = $_POST['estado'] ?? 'En uso';

    $nivelPref = strtoupper(substr($nivel, 0, 2));
    $depPref = strtoupper(substr($conn->query("SELECT nombre_dep FROM departamentos WHERE id_dep=$id_dep")->fetch_assoc()['nombre_dep'] ?? '', 0, 2));
    $catPref = strtoupper(substr($conn->query("SELECT nombre_categoria FROM categorias WHERE id_categoria=$id_categoria")->fetch_assoc()['nombre_categoria'] ?? '', 0, 2));
    $prefijo = "$nivelPref-$depPref-$catPref";

    // ---------- Generar código correcto ----------
    $lastRes = $conn->query("SELECT codigo_equipo FROM equipos WHERE codigo_equipo LIKE '$prefijo-%' ORDER BY id_equipo DESC LIMIT 1");
    if ($lastRes && $row = $lastRes->fetch_assoc()) {
        preg_match('/-(\d+)$/', $row['codigo_equipo'], $matches);
        $lastNum = isset($matches[1]) ? intval($matches[1]) : 0;
        $num = $lastNum + 1;
    } else {
        $num = 1;
    }
    $codigo_equipo = $prefijo . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);

    // Campos restantes
    $marca = $conn->real_escape_string($_POST['marca'] ?? '');
    $modelo = $conn->real_escape_string($_POST['modelo'] ?? '');
    $serie = $conn->real_escape_string($_POST['serie'] ?? '');
    $procesador = $conn->real_escape_string($_POST['procesador'] ?? '');
    $ram = $conn->real_escape_string($_POST['ram'] ?? '');
    $disco = $conn->real_escape_string($_POST['disco'] ?? '');
    $so = $conn->real_escape_string($_POST['so'] ?? '');
    $observaciones = $conn->real_escape_string($_POST['observaciones'] ?? '');
    $ultima_revision = !empty($_POST['ultima_revision']) ? $conn->real_escape_string($_POST['ultima_revision']) : NULL;

    // Imagen
    $imagen_path = NULL;
    if (!empty($_FILES['imagen']['name'])) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $imagen_path = "uploads/" . uniqid() . ".$ext";
        move_uploaded_file($_FILES['imagen']['tmp_name'], $imagen_path);
    }

    // Insertar en DB
    $stmt = $conn->prepare("INSERT INTO equipos 
        (codigo_equipo, marca, modelo, serie, procesador, ram, disco, so, estado, id_dep, id_categoria, id_ubicacion, nivel, observaciones, imagen, ultima_revision)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param(
        "ssssssssiiisssss",
        $codigo_equipo,
        $marca,
        $modelo,
        $serie,
        $procesador,
        $ram,
        $disco,
        $so,
        $estado,
        $id_dep,
        $id_categoria,
        $id_ubicacion,
        $nivel,
        $observaciones,
        $imagen_path,
        $ultima_revision
    );
    if (!$stmt->execute()) die("Error al agregar equipo: " . $stmt->error);

    // Registrar historial
    $id_equipo = $conn->insert_id;
    registrar_historial($conn, $_SESSION['id_usuario'], $id_equipo, "Equipo agregado: $codigo_equipo");

    $usuario = $_SESSION['usuario'] ?? 'Sistema';
    $detalle = "Equipo agregado al sistema";
    $conn->query("INSERT INTO historial_estados (id_equipo, estado_anterior, estado_nuevo, usuario, fecha, detalle)
                  VALUES ($id_equipo,'N/A','$estado','$usuario',NOW(),'$detalle')");

    $stmt->close();
    header("Location: equipos.php");
    exit;
}
?>

<!-- Botón Modal -->
<div class="container mt-4">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarEquipo">Agregar Equipo</button>
</div>

<!-- Modal Agregar Equipo -->
<div class="modal fade" id="modalAgregarEquipo" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" method="POST" action="" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Nuevo Equipo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- ANEXO -->
                    <div class="mb-3 col-md-4">
                        <label>Anexo</label>
                        <select name="id_dep" class="form-control" required>
                            <option value="">Seleccione un Anexo</option>
                            <?php foreach($anexos as $a): ?>
                                <option value="<?= $a['id_dep'] ?>"><?= htmlspecialchars($a['nombre_dep']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- CATEGORÍA -->
                    <div class="mb-3 col-md-4">
                        <label>Categoría</label>
                        <select name="id_categoria" class="form-control" required>
                            <option value="">Seleccione una Categoría</option>
                            <?php foreach($categorias as $c): ?>
                                <option value="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- UBICACIÓN -->
                    <div class="mb-3 col-md-4">
                        <label>Ubicación</label>
                        <select name="id_ubicacion" class="form-control" required>
                            <option value="">Seleccione una Ubicación</option>
                            <?php foreach($ubicaciones as $u): ?>
                                <option value="<?= $u['id_ubicacion'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Código Equipo -->
                    <div class="mb-3 col-md-4">
                        <label>Código Equipo</label>
                        <input type="text" name="codigo_equipo" id="codigo_equipo" class="form-control" readonly>
                    </div>
                    <!-- Otros campos -->
                    <div class="mb-3 col-md-4"><label>Marca</label><input type="text" name="marca" class="form-control"></div>
                    <div class="mb-3 col-md-4"><label>Modelo</label><input type="text" name="modelo" class="form-control"></div>
                    <div class="mb-3 col-md-4"><label>Serie</label><input type="text" name="serie" class="form-control"></div>
                    <div class="mb-3 col-md-4"><label>Procesador</label><input type="text" name="procesador" class="form-control"></div>
                    <div class="mb-3 col-md-4"><label>RAM</label><input type="text" name="ram" class="form-control"></div>
                    <div class="mb-3 col-md-4"><label>Disco</label><input type="text" name="disco" class="form-control"></div>
                    <div class="mb-3 col-md-4"><label>Sistema Operativo</label><input type="text" name="so" class="form-control"></div>
                    <div class="mb-3 col-md-4">
                        <label>Estado</label>
                        <select name="estado" class="form-control">
                            <option value="En uso">En uso</option>
                            <option value="En reparación">En reparación</option>
                            <option value="Dañado">Dañado</option>
                            <option value="Dado de baja">Dado de baja</option>
                        </select>
                    </div>
                    <div class="mb-3 col-md-4"><label>Nivel</label><input type="text" name="nivel" class="form-control"></div>
                    <div class="mb-3 col-md-12"><label>Observaciones</label><textarea name="observaciones" class="form-control"></textarea></div>
                    <div class="mb-3 col-md-6"><label>Imagen</label><input type="file" name="imagen" class="form-control"></div>
                    <div class="mb-3 col-md-6"><label>Última Revisión</label><input type="datetime-local" name="ultima_revision" class="form-control"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="agregar_equipo" class="btn btn-success">Agregar</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Equipos -->
<div class="container mt-5">
    <h3 class="mb-4">📋 Lista de Equipos</h3>
    <div class="row mb-3">
        <div class="col-md-3">
            <label>Estado</label>
            <select id="filtro_estado" class="form-select">
                <option value="">Todos</option>
                <option value="En uso">En uso</option>
                <option value="En reparación">En reparación</option>
                <option value="Dañado">Dañado</option>
                <option value="Dado de baja">Dado de baja</option>
            </select>
        </div>
        <div class="col-md-3">
            <label>Categoría</label>
            <select id="filtro_categoria" class="form-select">
                <option value="">Todas</option>
                <?php foreach($categorias as $c): ?>
                    <option value="<?= $c['nombre_categoria'] ?>"><?= htmlspecialchars($c['nombre_categoria']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label>Ubicación</label>
            <select id="filtro_ubicacion" class="form-select">
                <option value="">Todas</option>
                <?php foreach($ubicaciones as $u): ?>
                    <option value="<?= $u['nombre'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label>Departamento</label>
            <select id="filtro_departamento" class="form-select">
                <option value="">Todos</option>
                <?php foreach($anexos as $a): ?>
                    <option value="<?= $a['nombre_dep'] ?>"><?= htmlspecialchars($a['nombre_dep']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <table class="table table-striped table-hover table-sm align-middle" id="tabla_equipos">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Imagen</th>
                <th>Código</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Serie</th>
                <th>Estado</th>
                <th>Departamento</th>
                <th>Categoría</th>
                <th>Ubicación</th>
                <th>Observaciones</th>
                <th>Última Revisión</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $equipos_list = $conn->query("SELECT e.*, d.nombre_dep, c.nombre_categoria, u.nombre AS ubicacion_nombre 
                                          FROM equipos e
                                          LEFT JOIN departamentos d ON e.id_dep=d.id_dep
                                          LEFT JOIN categorias c ON e.id_categoria=c.id_categoria
                                          LEFT JOIN ubicaciones u ON e.id_ubicacion=u.id_ubicacion
                                          ORDER BY e.id_equipo DESC");
            while($eq = $equipos_list->fetch_assoc()):
            ?>
            <tr>
                <td><?= $eq['id_equipo'] ?></td>
                <td>
                    <?php if($eq['imagen']): ?>
                        <img src="<?= $eq['imagen'] ?>" width="50" height="50" style="object-fit:cover;">
                    <?php else: ?>
                        <span class="text-muted">Sin imagen</span>
                    <?php endif; ?>
                </td>
                <td><?= $eq['codigo_equipo'] ?></td>
                <td><?= $eq['marca'] ?></td>
                <td><?= $eq['modelo'] ?></td>
                <td><?= $eq['serie'] ?></td>
                <td>
                    <?php
                    $estado = $eq['estado'];
                    $badge = match($estado) {
                        'En uso' => 'success',
                        'En reparación' => 'warning',
                        'Dañado' => 'danger',
                        'Dado de baja' => 'secondary',
                        default => 'info'
                    };
                    ?>
                    <span class="badge bg-<?= $badge ?>"><?= $estado ?></span>
                </td>
                <td><?= $eq['nombre_dep'] ?></td>
                <td><?= $eq['nombre_categoria'] ?></td>
                <td><?= $eq['ubicacion_nombre'] ?></td>
                <td><?= $eq['observaciones'] ?></td>
                <td><?= $eq['ultima_revision'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Filtros JS -->
<script>
document.querySelectorAll('#filtro_estado, #filtro_categoria, #filtro_ubicacion, #filtro_departamento').forEach(select => {
    select.addEventListener('change', function() {
        const estado = document.getElementById('filtro_estado').value.toLowerCase();
        const categoria = document.getElementById('filtro_categoria').value.toLowerCase();
        const ubicacion = document.getElementById('filtro_ubicacion').value.toLowerCase();
        const departamento = document.getElementById('filtro_departamento').value.toLowerCase();
        document.querySelectorAll('#tabla_equipos tbody tr').forEach(row => {
            const rowEstado = row.cells[6].innerText.toLowerCase();
            const rowCategoria = row.cells[8].innerText.toLowerCase();
            const rowUbicacion = row.cells[9].innerText.toLowerCase();
            const rowDep = row.cells[7].innerText.toLowerCase();
            if ((estado === '' || rowEstado === estado) &&
                (categoria === '' || rowCategoria === categoria) &&
                (ubicacion === '' || rowUbicacion === ubicacion) &&
                (departamento === '' || rowDep === departamento)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>

<!-- Generar código automático -->
<script>
function generarCodigo() {
    const nivel = document.querySelector('input[name="nivel"]').value.trim();
    const id_dep = document.querySelector('select[name="id_dep"]').value;
    const id_categoria = document.querySelector('select[name="id_categoria"]').value;

    if(nivel && id_dep && id_categoria){
        fetch(`equipos.php?accion=generar_codigo&nivel=${encodeURIComponent(nivel)}&id_dep=${id_dep}&id_categoria=${id_categoria}`)
            .then(res => res.text())
            .then(data => {
                const input = document.getElementById('codigo_equipo');
                if(input) input.value = data;
            })
            .catch(err => console.error(err));
    } else {
        const input = document.getElementById('codigo_equipo');
        if(input) input.value = '';
    }
}

const inputNivel = document.querySelector('input[name="nivel"]');
const selectDep = document.querySelector('select[name="id_dep"]');
const selectCat = document.querySelector('select[name="id_categoria"]');

if(inputNivel) inputNivel.addEventListener('input', generarCodigo);
if(selectDep) selectDep.addEventListener('change', generarCodigo);
if(selectCat) selectCat.addEventListener('change', generarCodigo);
</script>
