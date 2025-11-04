<?php
session_start();
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sesión Cerrada</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background: linear-gradient(135deg, #007bff, #2196f3);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: "Poppins", sans-serif;
    }

    .logout-card {
      background-color: #fff;
      border-radius: 15px;
      box-shadow: 0 4px 25px rgba(0,0,0,0.1);
      width: 400px;
      text-align: center;
      padding: 40px 30px;
      animation: fadeIn 0.8s ease-in-out;
    }

    .logout-card img {
      width: 100px;
      margin-bottom: 20px;
    }

    .logout-card h3 {
      color: #007bff;
      font-weight: 600;
      margin-bottom: 10px;
    }

    .logout-card p {
      color: #555;
      font-size: 1rem;
      margin-bottom: 25px;
    }

    .btn-volver {
      background-color: #007bff;
      color: #fff;
      font-weight: 500;
      border-radius: 50px;
      padding: 10px 30px;
      border: none;
      transition: all 0.3s ease;
    }

    .btn-volver:hover {
      background-color: #0056b3;
      transform: scale(1.05);
    }

    footer {
      margin-top: 25px;
      font-size: 0.9rem;
      color: #888;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(15px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

  <div class="logout-card">
    <!-- LOGO -->
    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRkaHlDGj77xsH5FrEIqfgMNu8Wg3HWik4Cog&s" alt="Logo Colegio"> <!-- usa el mismo logo del login -->
    
    <!-- TITULO -->
    <h3>Sesión Cerrada</h3>
    <p>Has cerrado tu sesión correctamente.<br>¡Gracias por usar el sistema!</p>
    
    <!-- BOTÓN VOLVER -->
    <a href="index.php" class="btn btn-volver">Volver al Inicio de Sesión</a>
    
    <!-- PIE -->
    <footer>
      © Colegio Robert Johnson 2025
    </footer>
  </div>

</body>
</html>
