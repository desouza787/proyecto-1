<?php
require 'conexion.php'; // si usas DB
session_start();

// Funciones de generación de códigos
function generarCodigoActivo(){
    return "ACT-" . str_pad(rand(1, 9999), 4, "0", STR_PAD_LEFT);
}

function generarCodigoProducto(){
    return "PROD-" . str_pad(rand(1, 9999), 4, "0", STR_PAD_LEFT);
}

// Probar las funciones
echo "Código de Activo: " . generarCodigoActivo() . "<br>";
echo "Código de Producto: " . generarCodigoProducto() . "<br>";
