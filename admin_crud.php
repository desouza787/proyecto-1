<?php 
session_start(); 
require_once "db.php"; 
require_once "includes/header.php"; 
require_once "includes/funciones.php"; 

if (!isset($_SESSION['usuario'])) { 
    header("Location: index.php"); 
    exit; 
} 

// --- ANEXOS --- 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_anexo'])) { 
    $nombre_dep = $conn->real_escape_string($_POST['nombre_dep']); 
    $tipo = $conn->real_escape_string($_POST['tipo']); 
    $descripcion = $conn->real_escape_string($_POST['descripcion']); 
    $id_dep = intval($_POST['id_dep'] ?? 0); 
    if ($id_dep > 0) { 
        $sql = "UPDATE departamentos SET nombre_dep='$nombre_dep', tipo='$tipo', descripcion='$descripcion' WHERE id_dep=$id_dep"; 
    } else { 
        $sql = "INSERT INTO departamentos (nombre_dep, tipo, descripcion) VALUES ('$nombre_dep','$tipo','$descripcion')"; 
    } 
    if (!$conn->query($sql)) die("Error al guardar anexo: " . $conn->error); 
    $_SESSION['toast'] = "Anexo guardado correctamente."; 
    header("Location: admin_crud.php"); 
    exit; 
} 

if (isset($_GET['eliminar_anexo'])) { 
    $id = intval($_GET['eliminar_anexo']); 
    $equipos = $conn->query("SELECT COUNT(*) AS c FROM equipos WHERE id_dep=$id")->fetch_assoc()['c']; 
    if ($equipos > 0) { 
        $_SESSION['toast_error'] = "No se puede eliminar el anexo, tiene equipos asociados ($equipos)."; 
    } else { 
        $conn->query("DELETE FROM departamentos WHERE id_dep=$id"); 
        $_SESSION['toast'] = "Anexo eliminado correctamente."; 
    } 
    header("Location: admin_crud.php"); 
    exit; 
} 

// --- UBICACIONES --- 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_ubicacion'])) { 
    $nombre = $conn->real_escape_string($_POST['nombre']); 
    $nivel = $conn->real_escape_string($_POST['nivel']); 
    $descripcion = $conn->real_escape_string($_POST['descripcion']); 
    $id_ubicacion = intval($_POST['id_ubicacion'] ?? 0); 
    if ($id_ubicacion > 0) { 
        $sql = "UPDATE ubicaciones SET nombre='$nombre', nivel='$nivel', descripcion='$descripcion' WHERE id_ubicacion=$id_ubicacion"; 
    } else { 
        $sql = "INSERT INTO ubicaciones (nombre, nivel, descripcion) VALUES ('$nombre','$nivel','$descripcion')"; 
    } 
    if (!$conn->query($sql)) die("Error al guardar ubicación: " . $conn->error); 
    $_SESSION['toast'] = "Ubicación guardada correctamente."; 
    header("Location: admin_crud.php"); 
    exit; 
} 

if (isset($_GET['eliminar_ubicacion'])) { 
    $id = intval($_GET['eliminar_ubicacion']); 
    $equipos = $conn->query("SELECT COUNT(*) AS c FROM equipos WHERE id_ubicacion=$id")->fetch_assoc()['c']; 
    if ($equipos > 0) { 
        $_SESSION['toast_error'] = "No se puede eliminar la ubicación, tiene equipos asociados ($equipos)."; 
    } else { 
        $conn->query("DELETE FROM ubicaciones WHERE id_ubicacion=$id"); 
        $_SESSION['toast'] = "Ubicación eliminada correctamente."; 
    } 
    header("Location: admin_crud.php"); 
    exit; 
} 

// --- CATEGORIAS --- 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_categoria'])) { 
    $nombre = $conn->real_escape_string($_POST['nombre_categoria']); 
    $descripcion = $conn->real_escape_string($_POST['descripcion_categoria']); 
    $id_categoria = intval($_POST['id_categoria'] ?? 0); 
    if ($id_categoria > 0) { 
        $sql = "UPDATE categorias SET nombre_categoria='$nombre', descripcion='$descripcion' WHERE id_categoria=$id_categoria"; 
    } else { 
        $sql = "INSERT INTO categorias (nombre_categoria, descripcion) VALUES ('$nombre','$descripcion')"; 
    } 
    if (!$conn->query($sql)) die("Error al guardar categoría: " . $conn->error); 
    $_SESSION['toast'] = "Categoría guardada correctamente."; 
    header("Location: admin_crud.php"); 
    exit; 
} 

if (isset($_GET['eliminar_categoria'])) { 
    $id = intval($_GET['eliminar_categoria']); 
    $equipos = $conn->query("SELECT COUNT(*) AS c FROM equipos WHERE id_categoria=$id")->fetch_assoc()['c']; 
    if ($equipos > 0) { 
        $_SESSION['toast_error'] = "No se puede eliminar la categoría, tiene equipos asociados ($equipos)."; 
    } else { 
        $conn->query("DELETE FROM categorias WHERE id_categoria=$id"); 
        $_SESSION['toast'] = "Categoría eliminada correctamente."; 
    } 
    header("Location: admin_crud.php"); 
    exit; 
} 

// Consultas 
$anexos = $conn->query("SELECT * FROM departamentos ORDER BY id_dep ASC"); 
$ubicaciones = $conn->query("SELECT * FROM ubicaciones ORDER BY id_ubicacion ASC"); 
$categorias = $conn->query("SELECT * FROM categorias ORDER BY id_categoria ASC"); 
$total_equipos = $conn->query("SELECT COUNT(*) AS n FROM equipos")->fetch_assoc()['n'] ?? 0; 
?> 

<div class="container mt-4"> 
    <!-- Título --> 
    <h2 class="mb-4 text-center text-primary"><i class="fa-solid fa-gear"></i> Administración General</h2> 

    <!-- Tarjetas resumen --> 
    <div class="row g-3 mb-4"> 
        <div class="col-md-3"> 
            <div class="card shadow-sm border-primary h-100"> 
                <div class="card-body d-flex align-items-center justify-content-between"> 
                    <div> 
                        <h6>Total Anexos</h6> 
                        <h3><?= $anexos->num_rows ?></h3> 
                    </div> 
                    <i class="fa-solid fa-building fa-2x text-primary"></i> 
                </div> 
            </div> 
        </div> 
        <div class="col-md-3"> 
            <div class="card shadow-sm border-success h-100"> 
                <div class="card-body d-flex align-items-center justify-content-between"> 
                    <div> 
                        <h6>Total Ubicaciones</h6> 
                        <h3><?= $ubicaciones->num_rows ?></h3> 
                    </div> 
                    <i class="fa-solid fa-map-location-dot fa-2x text-success"></i> 
                </div> 
            </div> 
        </div> 
        <div class="col-md-3"> 
            <div class="card shadow-sm border-warning h-100"> 
                <div class="card-body d-flex align-items-center justify-content-between"> 
                    <div> 
                        <h6>Total Categorías</h6> 
                        <h3><?= $categorias->num_rows ?></h3> 
                    </div> 
                    <i class="fa-solid fa-tags fa-2x text-warning"></i> 
                </div> 
            </div> 
        </div> 
        <div class="col-md-3"> 
            <div class="card shadow-sm border-info h-100"> 
                <div class="card-body d-flex align-items-center justify-content-between"> 
                    <div> 
                        <h6>Total Equipos</h6> 
                        <h3><?= $total_equipos ?></h3> 
                    </div> 
                    <i class="fa-solid fa-desktop fa-2x text-info"></i> 
                </div> 
            </div> 
        </div> 
    </div> 

    <!-- Tabs --> 
    <ul class="nav nav-tabs mb-3" id="crudTabs" role="tablist"> 
        <li class="nav-item"> 
            <a class="nav-link active" data-bs-toggle="tab" href="#anexoTab">Anexos <span class="badge bg-primary"><?= $anexos->num_rows ?></span></a> 
        </li> 
        <li class="nav-item"> 
            <a class="nav-link" data-bs-toggle="tab" href="#ubiTab">Ubicaciones <span class="badge bg-success"><?= $ubicaciones->num_rows ?></span></a> 
        </li> 
        <li class="nav-item"> 
            <a class="nav-link" data-bs-toggle="tab" href="#catTab">Categorías <span class="badge bg-warning"><?= $categorias->num_rows ?></span></a> 
        </li> 
    </ul> 

    <div class="tab-content"> 
        <!-- ANEXOS --> 
        <div class="tab-pane fade show active" id="anexoTab"> 
            <div class="d-flex justify-content-between mb-3"> 
                <h5>Gestión de Anexos</h5> 
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAnexo"> 
                    <i class="fa-solid fa-plus"></i> Nuevo Anexo 
                </button> 
            </div> 
            <table class="table table-hover table-striped" id="tableAnexos"> 
                <thead class="table-dark"> 
                    <tr> 
                        <th>ID</th> 
                        <th>Nombre</th> 
                        <th>Tipo</th> 
                        <th>Descripción</th> 
                        <th>Equipos Asociados</th> 
                        <th>Acciones</th> 
                    </tr> 
                </thead> 
                <tbody> 
                    <?php while($d = $anexos->fetch_assoc()): $equiposCount = $conn->query("SELECT COUNT(*) AS c FROM equipos WHERE id_dep=".$d['id_dep'])->fetch_assoc()['c']; ?> 
                    <tr> 
                        <td><?= $d['id_dep'] ?></td> 
                        <td><?= e($d['nombre_dep']) ?></td> 
                        <td><?= e($d['tipo']) ?></td> 
                        <td><?= e($d['descripcion']) ?></td> 
                        <td><span class="badge <?= $equiposCount>0?'bg-danger':'bg-success' ?>"><?= $equiposCount ?></span></td> 
                        <td> 
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAnexo" data-id="<?= $d['id_dep'] ?>" data-nombre="<?= e($d['nombre_dep']) ?>" data-tipo="<?= e($d['tipo']) ?>" data-desc="<?= e($d['descripcion']) ?>">✏️ </button> 
                            <button class="btn btn-danger btn-sm" <?= $equiposCount>0?'disabled title="No se puede eliminar, tiene equipos asociados"':'onclick="if(confirm(\'Eliminar anexo?\')) location.href=\'?eliminar_anexo='.$d['id_dep'].'\'"' ?>> 🗑️ </button> 
                        </td> 
                    </tr> 
                    <?php endwhile; ?> 
                </tbody> 
            </table> 
        </div> 

        <!-- UBICACIONES --> 
        <div class="tab-pane fade" id="ubiTab"> 
            <div class="d-flex justify-content-between mb-3"> 
                <h5>Gestión de Ubicaciones</h5> 
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalUbicacion"> 
                    <i class="fa-solid fa-plus"></i> Nueva Ubicación 
                </button> 
            </div> 
            <table class="table table-hover table-striped" id="tableUbicaciones"> 
                <thead class="table-dark"> 
                    <tr> 
                        <th>ID</th> 
                        <th>Nombre</th> 
                        <th>Nivel</th> 
                        <th>Descripción</th> 
                        <th>Equipos Asociados</th> 
                        <th>Acciones</th> 
                    </tr> 
                </thead> 
                <tbody> 
                    <?php while($u = $ubicaciones->fetch_assoc()): $equiposCount = $conn->query("SELECT COUNT(*) AS c FROM equipos WHERE id_ubicacion=".$u['id_ubicacion'])->fetch_assoc()['c']; ?> 
                    <tr> 
                        <td><?= $u['id_ubicacion'] ?></td> 
                        <td><?= e($u['nombre']) ?></td> 
                        <td><?= e($u['nivel']) ?></td> 
                        <td><?= e($u['descripcion']) ?></td> 
                        <td><span class="badge <?= $equiposCount>0?'bg-danger':'bg-success' ?>"><?= $equiposCount ?></span></td> 
                        <td> 
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUbicacion" data-id="<?= $u['id_ubicacion'] ?>" data-nombre="<?= e($u['nombre']) ?>" data-nivel="<?= e($u['nivel']) ?>" data-desc="<?= e($u['descripcion']) ?>">✏️ </button> 
                            <button class="btn btn-danger btn-sm" <?= $equiposCount>0?'disabled title="No se puede eliminar, tiene equipos asociados"':'onclick="if(confirm(\'Eliminar ubicación?\')) location.href=\'?eliminar_ubicacion='.$u['id_ubicacion'].'\'"' ?>> 🗑️ </button> 
                        </td> 
                    </tr> 
                    <?php endwhile; ?> 
                </tbody> 
            </table> 
        </div> 

        <!-- CATEGORÍAS --> 
        <div class="tab-pane fade" id="catTab"> 
            <div class="d-flex justify-content-between mb-3"> 
                <h5>Gestión de Categorías</h5> 
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCategoria"> 
                    <i class="fa-solid fa-plus"></i> Nueva Categoría 
                </button> 
            </div> 
            <table class="table table-hover table-striped" id="tableCategorias"> 
                <thead class="table-dark"> 
                    <tr> 
                        <th>ID</th> 
                        <th>Nombre</th> 
                        <th>Descripción</th> 
                        <th>Equipos Asociados</th> 
                        <th>Acciones</th> 
                    </tr> 
                </thead> 
                <tbody> 
                    <?php while($c = $categorias->fetch_assoc()): $equiposCount = $conn->query("SELECT COUNT(*) AS c FROM equipos WHERE id_categoria=".$c['id_categoria'])->fetch_assoc()['c']; ?> 
                    <tr> 
                        <td><?= $c['id_categoria'] ?></td> 
                        <td><?= e($c['nombre_categoria']) ?></td> 
                        <td><?= e($c['descripcion']) ?></td> 
                        <td><span class="badge <?= $equiposCount>0?'bg-danger':'bg-success' ?>"><?= $equiposCount ?></span></td> 
                        <td> 
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCategoria" data-id="<?= $c['id_categoria'] ?>" data-nombre="<?= e($c['nombre_categoria']) ?>" data-desc="<?= e($c['descripcion']) ?>">✏️ </button> 
                            <button class="btn btn-danger btn-sm" <?= $equiposCount>0?'disabled title="No se puede eliminar, tiene equipos asociados"':'onclick="if(confirm(\'Eliminar categoría?\')) location.href=\'?eliminar_categoria='.$c['id_categoria'].'\'"' ?>> 🗑️ </button> 
                        </td> 
                    </tr> 
                    <?php endwhile; ?> 
                </tbody> 
            </table> 
        </div> 
    </div> 
</div> 

<!-- MODALES (ANEXO, UBICACION, CATEGORIA) --> 
<!-- ANEXO --> 
<div class="modal fade" id="modalAnexo" tabindex="-1"> 
    <div class="modal-dialog"> 
        <form class="modal-content" method="POST" action=""> 
            <div class="modal-header"><h5 class="modal-title">Anexo</h5></div> 
            <div class="modal-body"> 
                <input type="hidden" name="id_dep" id="dep_id"> 
                <div class="mb-3"><label>Nombre</label><input type="text" name="nombre_dep" id="dep_nombre" class="form-control" required></div> 
                <div class="mb-3"><label>Tipo</label><input type="text" name="tipo" id="dep_tipo" class="form-control"></div> 
                <div class="mb-3"><label>Descripción</label><textarea name="descripcion" id="dep_desc" class="form-control"></textarea></div> 
            </div> 
            <div class="modal-footer"> 
                <button type="submit" name="guardar_anexo" class="btn btn-success">Guardar</button> 
            </div> 
        </form> 
    </div> 
</div> 

<!-- UBICACION --> 
<div class="modal fade" id="modalUbicacion" tabindex="-1"> 
    <div class="modal-dialog"> 
        <form class="modal-content" method="POST" action=""> 
            <div class="modal-header"><h5 class="modal-title">Ubicación</h5></div> 
            <div class="modal-body"> 
                <input type="hidden" name="id_ubicacion" id="ubi_id"> 
                <div class="mb-3"><label>Nombre</label><input type="text" name="nombre" id="ubi_nombre" class="form-control" required></div> 
                <div class="mb-3"><label>Nivel</label><input type="text" name="nivel" id="ubi_nivel" class="form-control"></div> 
                <div class="mb-3"><label>Descripción</label><textarea name="descripcion" id="ubi_desc" class="form-control"></textarea></div> 
            </div> 
            <div class="modal-footer"> 
                <button type="submit" name="guardar_ubicacion" class="btn btn-success">Guardar</button> 
            </div> 
        </form> 
    </div> 
</div> 

<!-- CATEGORIA --> 
<div class="modal fade" id="modalCategoria" tabindex="-1"> 
    <div class="modal-dialog"> 
        <form class="modal-content" method="POST" action=""> 
            <div class="modal-header"><h5 class="modal-title">Categoría</h5></div> 
            <div class="modal-body"> 
                <input type="hidden" name="id_categoria" id="cat_id"> 
                <div class="mb-3"><label>Nombre</label><input type="text" name="nombre_categoria" id="cat_nombre" class="form-control" required></div> 
                <div class="mb-3"><label>Descripción</label><textarea name="descripcion" id="cat_desc" class="form-control"></textarea></div> 
            </div> 
            <div class="modal-footer"> 
                <button type="submit" name="guardar_categoria" class="btn btn-success">Guardar</button> 
            </div> 
        </form> 
    </div> 
</div> 

<!-- TOASTS --> 
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11"> 
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true"> 
        <div class="toast-header"> 
            <strong class="me-auto">Notificación</strong> 
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button> 
        </div> 
        <div class="toast-body" id="toastBody"></div> 
    </div> 
</div> 

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script> 
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css"> 

<script> 
$(document).ready(function(){ 
    $('#tableAnexos').DataTable({ pageLength:5, lengthChange:false }); 
    $('#tableUbicaciones').DataTable({ pageLength:5, lengthChange:false }); 
    $('#tableCategorias').DataTable({ pageLength:5, lengthChange:false }); 

    <?php if(isset($_SESSION['toast'])): ?> 
        var toastEl = document.getElementById('liveToast'); 
        document.getElementById('toastBody').innerText = "<?= $_SESSION['toast'] ?>"; 
        var toast = new bootstrap.Toast(toastEl); 
        toast.show(); 
        <?php unset($_SESSION['toast']); ?> 
    <?php endif; ?> 

    <?php if(isset($_SESSION['toast_error'])): ?> 
        var toastEl = document.getElementById('liveToast'); 
        document.getElementById('toastBody').innerText = "<?= $_SESSION['toast_error'] ?>"; 
        toastEl.querySelector('.toast-header strong').innerText = "Error"; 
        var toast = new bootstrap.Toast(toastEl); 
        toast.show(); 
        <?php unset($_SESSION['toast_error']); ?> 
    <?php endif; ?> 
}); 
</script> 

<?php require_once "includes/footer.php"; ?> 
<?php include 'asistente/bot.php'; ?> 
