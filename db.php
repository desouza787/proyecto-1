<?php
// Conexión a MySQL
$host = "127.0.0.1";
$port = 3307;
$user = "root";
$pass = "";
$db = "cmrj2";

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>