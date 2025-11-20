<?php
session_start();
require_once "db.php";
require_once "includes/header.php";

// Departamentos
$departamentos = $conn->query("SELECT id_dep, nombre FROM departamentos ORDER BY id_dep ASC");

// Ubicaciones
$ubicaciones = $conn->query("SELECT id_ubicacion, nombre FROM ubicaciones ORDER BY id_ubicacion ASC");
?>

<div class="container mt-4">
    <h3>Referencias: Departamentos y Ubicaciones</h3>
    <div class="row mt-3">
        <div class="col-md-6">
            <h5>Departamentos</h5>
            <table class="table table-sm table-striped">
                <thead>
                    <tr><th>ID</th><th>Nombre</th></tr>
                </thead>
                <tbody>
                    <?php
                    if($departamentos && $departamentos->num_rows > 0){
                        while($d = $departamentos->fetch_assoc()){
                            echo "<tr><td>{$d['id_dep']}</td><td>{$d['nombre']}</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>No hay departamentos</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="col-md-6">
            <h5>Ubicaciones</h5>
            <table class="table table-sm table-striped">
                <thead>
                    <tr><th>ID</th><th>Nombre</th></tr>
                </thead>
                <tbody>
                    <?php
                    if($ubicaciones && $ubicaciones->num_rows > 0){
                        while($u = $ubicaciones->fetch_assoc()){
                            echo "<tr><td>{$u['id_ubicacion']}</td><td>{$u['nombre']}</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>No hay ubicaciones</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
