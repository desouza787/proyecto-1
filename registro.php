<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'db.php';
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['correo']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Verificar si existe usuario o correo
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE nombre=? OR email=?");
    if (!$stmt) die("Error SQL: ".$conn->error);

    $stmt->bind_param("ss", $nombre, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $message = "<div class='alert error'>❌ Usuario o correo ya existe</div>";
    } else {
        $stmt2 = $conn->prepare("INSERT INTO usuarios (nombre,email,password) VALUES (?,?,?)");
        if (!$stmt2) die("Error SQL: ".$conn->error);

        $stmt2->bind_param("sss", $nombre, $email, $password);
        if ($stmt2->execute()) {
            $message = "<div class='alert success'>✅ Registro exitoso. <a href='index.php'>Inicia sesión</a></div>";
        } else {
            $message = "<div class='alert error'>❌ Error al registrar usuario: ".$stmt2->error."</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro - Colegio Robert Johnson</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
:root {
  --azul: #1e88e5;
  --azul-oscuro: #1565c0;
  --blanco: #ffffff;
  --gris: #f4f4f4;
  --error: #e53935;
  --exito: #43a047;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, var(--azul) 30%, var(--azul-oscuro) 90%);
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  margin: 0;
  color: #333;
}

.container {
  background: var(--blanco);
  width: 450px;
  border-radius: 20px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.2);
  padding: 40px 40px 30px;
  animation: aparecer 0.8s ease-in-out;
}

@keyframes aparecer {
  from { opacity: 0; transform: translateY(-15px); }
  to { opacity: 1; transform: translateY(0); }
}

.logo {
  display: block;
  margin: 0 auto 20px;
  width: 130px;
  height: auto;
}

h2 {
  text-align: center;
  color: var(--azul-oscuro);
  margin-bottom: 25px;
  font-size: 1.5rem;
}

form {
  display: flex;
  flex-direction: column;
  align-items: center;
}

input {
  width: 90%;
  max-width: 350px;
  padding: 12px 14px;
  margin: 10px 0;
  border: 1px solid #ccc;
  border-radius: 8px;
  transition: border-color 0.3s, box-shadow 0.3s;
  font-size: 15px;
}

input:focus {
  border-color: var(--azul);
  outline: none;
  box-shadow: 0 0 6px rgba(30,136,229,0.3);
}

button {
  width: 90%;
  max-width: 350px;
  padding: 13px;
  margin-top: 10px;
  background: var(--azul);
  color: var(--blanco);
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-weight: bold;
  font-size: 15px;
  transition: background 0.3s, transform 0.2s;
}

button:hover {
  background: var(--azul-oscuro);
  transform: scale(1.03);
}

.alert {
  padding: 10px;
  margin-top: 15px;
  border-radius: 6px;
  font-weight: bold;
  text-align: center;
}

.alert.success {
  background-color: #e8f5e9;
  color: var(--exito);
  border: 1px solid var(--exito);
}

.alert.error {
  background-color: #ffebee;
  color: var(--error);
  border: 1px solid var(--error);
}

p, a {
  text-align: center;
  color: #333;
  font-size: 14px;
  text-decoration: none;
  margin-top: 15px;
}

a:hover {
  color: var(--azul-oscuro);
  text-decoration: underline;
}

footer {
  text-align: center;
  margin-top: 20px;
  font-size: 13px;
  color: #555;
}
</style>
</head>
<body>
  <div class="container">
    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRkaHlDGj77xsH5FrEIqfgMNu8Wg3HWik4Cog&s" alt="Logo Colegio Robert Johnson" class="logo">
    <h2>Registro de Usuario</h2>

    <form method="POST" id="formRegistro" onsubmit="return validarFormulario()">
      <input type="text" name="nombre" id="nombre" placeholder="Nombre de usuario" required>
      <input type="email" name="correo" id="correo" placeholder="Correo electrónico" required>
      <input type="password" name="password" id="password" placeholder="Contraseña" required>
      <button type="submit">Registrarme</button>
    </form>

    <?= $message ?>

    <p>¿Ya tienes cuenta? <a href="index.php">Inicia sesión</a></p>
    <footer>© Colegio Robert Johnson 2025</footer>
  </div>

  <script>
  // Validaciones con JavaScript
  function validarFormulario() {
    const nombre = document.getElementById('nombre').value.trim();
    const correo = document.getElementById('correo').value.trim();
    const pass = document.getElementById('password').value.trim();

    if (nombre.length < 3) {
      alert('El nombre debe tener al menos 3 caracteres');
      return false;
    }

    const emailRegex = /^[\\w-\\.]+@([\\w-]+\\.)+[\\w-]{2,4}$/;
    if (!emailRegex.test(correo)) {
      alert('Por favor, ingresa un correo válido');
      return false;
    }

    if (pass.length < 6) {
      alert('La contraseña debe tener al menos 6 caracteres');
      return false;
    }

    return true;
  }
  </script>
</body>
</html>
