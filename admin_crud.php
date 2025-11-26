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

    <!-- ====== ESTILOS ESPECÍFICOS PARA LAS TABLAS (SOLO VISUAL) ====== -->
    <style>
    /* Contenedor y header */
    .fancy-table-container { --accent: 38, 80, 185; } /* puedes ajustar color con RGB */

    /* Tabla base */
    table.fancy-table {
        border-collapse: separate;
        border-spacing: 0;
        background: transparent;
        overflow: visible;
        width: 100%;
        --row-elevation: 8px;
    }

    /* Sticky header con degradado elegante */
    table.fancy-table thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: linear-gradient(90deg, rgba(23,31,43,1) 0%, rgba(28,41,58,1) 100%);
        color: #fff;
        border: 0;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        font-weight: 700;
        padding: .9rem .75rem;
        box-shadow: 0 6px 14px rgba(10, 14, 20, 0.25);
    }

    /* Filas: ligera separación, sombra y border-radius en celdas */
    table.fancy-table tbody tr {
        background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(250,250,250,0.98) 100%);
        transition: transform .22s cubic-bezier(.2,.9,.2,1), box-shadow .22s, background .22s;
        transform-origin: left center;
        border-radius: 10px;
        margin-bottom: 8px;
        overflow: hidden;
        box-shadow: 0 0 0 rgba(0,0,0,0);
        display: table-row;
        animation: fadeInUp .45s both;
    }

    /* Hover: mini-zoom y elevación */
    table.fancy-table tbody tr:hover {
        transform: translateY(-6px) scale(1.006);
        box-shadow: 0 18px 35px rgba(22,28,36,0.12);
        background: linear-gradient(90deg, rgba(255,255,255,1) 0%, rgba(247,250,255,1) 100%);
    }

    /* Celdas: padding y separación */
    table.fancy-table tbody td, table.fancy-table thead th {
        padding: .85rem .85rem;
        vertical-align: middle;
        border-bottom: 0;
        font-size: .95rem;
    }

    /* Primera columna ID más ligera */
    table.fancy-table tbody td:first-child {
        font-weight: 600;
        color: #374151;
        width: 60px;
    }

    /* Descripción más clara */
    table.fancy-table tbody td:nth-child(4) {
        color: #4b5563;
        line-height: 1.45;
    }

    /* Badges modernizados */
    table.fancy-table .badge {
        border-radius: 10px;
        padding: .35rem .6rem;
        font-weight: 600;
        box-shadow: 0 6px 14px rgba(2,6,23,0.06);
        transition: transform .18s, box-shadow .18s;
        display: inline-block;
    }
    table.fancy-table .badge.bg-success:hover,
    table.fancy-table .badge.bg-danger:hover {
        transform: translateY(-3px) scale(1.04);
        box-shadow: 0 10px 24px rgba(2,6,23,0.12);
    }

    /* Botones de acciones: animación y estilo */
    .fancy-table .btn {
        transition: transform .16s cubic-bezier(.2,.9,.2,1), box-shadow .16s;
        box-shadow: 0 6px 12px rgba(13,22,36,0.06);
        border-radius: 8px;
        padding: .35rem .5rem;
    }
    .fancy-table .btn:hover { transform: translateY(-3px) scale(1.04); box-shadow: 0 18px 30px rgba(12,18,30,0.12); }

    /* Icon buttons styling (si usas emojis o iconos) */
    .fancy-table .btn-sm { font-size: .86rem; }

    /* Separador entre filas estilo cards (no cambia la estructura) */
    table.fancy-table tbody tr + tr {
        margin-top: 10px;
    }

    /* Zebra sutil */
    table.fancy-table tbody tr:nth-child(odd) { background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(248,249,250,0.98) 100%); }

    /* Animación de entrada */
    @keyframes fadeInUp {
        0% { opacity: 0; transform: translateY(8px) scale(.998); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Header tiny label for table titles area (optional visual) */
    .table-section-title { display:flex; align-items:center; gap:.5rem; }

    /* Ajustes responsivos para pantallas pequeñas */
    @media (max-width: 768px) {
        table.fancy-table thead { display: none; } /* mantén los headers ocultos en móvil si lo prefieres */
        table.fancy-table tbody td {
            display: block;
            padding: .65rem .75rem;
        }
        table.fancy-table tbody tr { margin-bottom: .8rem; display: block; border-radius: 8px; }
        /* reajusta botones a la derecha */
        .fancy-actions { display:flex; gap:.5rem; justify-content:flex-end; }
    }
    </style>

    <!-- Título --> 
    <h2 class="mb-4 text-center text-primary"><i class="fa-solid fa-gear"></i> Administración General</h2> 

    <!-- Tarjetas resumen (SIN CAMBIOS de lógica ni visual mayor) --> 
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
            <table class="table table-hover table-striped fancy-table" id="tableAnexos"> 
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
                        <td class="fancy-actions"> 
                            <button class="btn btn-primary btn-sm editAnexo" data-bs-toggle="modal" data-bs-target="#modalAnexo" data-id="<?= $d['id_dep'] ?>" data-nombre="<?= e($d['nombre_dep']) ?>" data-tipo="<?= e($d['tipo']) ?>" data-desc="<?= e($d['descripcion']) ?>">✏️ </button> 
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
            <table class="table table-hover table-striped fancy-table" id="tableUbicaciones"> 
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
                        <td class="fancy-actions"> 
                            <button class="btn btn-primary btn-sm editUbicacion" data-bs-toggle="modal" data-bs-target="#modalUbicacion" data-id="<?= $u['id_ubicacion'] ?>" data-nombre="<?= e($u['nombre']) ?>" data-nivel="<?= e($u['nivel']) ?>" data-desc="<?= e($u['descripcion']) ?>">✏️ </button> 
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
            <table class="table table-hover table-striped fancy-table" id="tableCategorias"> 
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
                        <td class="fancy-actions"> 
                            <button class="btn btn-primary btn-sm editCategoria" data-bs-toggle="modal" data-bs-target="#modalCategoria" data-id="<?= $c['id_categoria'] ?>" data-nombre="<?= e($c['nombre_categoria']) ?>" data-desc="<?= e($c['descripcion']) ?>">✏️ </button> 
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
                <div class="mb-3"><label>Descripción</label><textarea name="descripcion_categoria" id="cat_desc" class="form-control"></textarea></div> 
            </div> 
            <div class="modal-footer"> 
                <button type="submit" name="guardar_categoria" class="btn btn-success">Guardar</button> 
            </div> 
        </form> 
    </div> 
</div> 

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Anexo
    document.querySelectorAll(".editAnexo").forEach(btn => {
        btn.addEventListener("click", () => {
            document.getElementById("dep_id").value = btn.dataset.id;
            document.getElementById("dep_nombre").value = btn.dataset.nombre;
            document.getElementById("dep_tipo").value = btn.dataset.tipo;
            document.getElementById("dep_desc").value = btn.dataset.desc;
        });
    });
    // Ubicacion
    document.querySelectorAll(".editUbicacion").forEach(btn => {
        btn.addEventListener("click", () => {
            document.getElementById("ubi_id").value = btn.dataset.id;
            document.getElementById("ubi_nombre").value = btn.dataset.nombre;
            document.getElementById("ubi_nivel").value = btn.dataset.nivel;
            document.getElementById("ubi_desc").value = btn.dataset.desc;
        });
    });
    // Categoria
    document.querySelectorAll(".editCategoria").forEach(btn => {
        btn.addEventListener("click", () => {
            document.getElementById("cat_id").value = btn.dataset.id;
            document.getElementById("cat_nombre").value = btn.dataset.nombre;
            document.getElementById("cat_desc").value = btn.dataset.desc;
        });
    });

    // --- STAGGER FADE-IN PARA FILAS (SOLO VISUAL) ---
    document.querySelectorAll('table.fancy-table tbody').forEach(tbody=>{
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.forEach((r, i) => {
            r.style.animationDelay = (i * 70) + 'ms';
        });
    });

    // Pequeña mejora: hover sobre badge para dar feedback (no cambia lógica)
    document.querySelectorAll('table.fancy-table .badge').forEach(b => {
        b.addEventListener('mouseenter', ()=> b.style.transform = 'translateY(-3px) scale(1.04)');
        b.addEventListener('mouseleave', ()=> b.style.transform = '');
    });
});
</script>

<?php require_once "includes/footer.php"; ?> 
<?php include 'asistente/bot.php'; ?>
