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
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE nombre=?");
    if (!$stmt) die("Error SQL: " . $conn->error);

    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['usuario'] = $user['nombre'];
            header("Location: dashboard.php");
            exit();
        } else {
            $message = "<div class='alert error'>❌ Contraseña incorrecta</div>";
        }
    } else {
        $message = "<div class='alert error'>❌ Usuario no encontrado</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Iniciar Sesión - Colegio Robert Johnson</title>
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
  width: 420px;
  border-radius: 20px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.2);
  padding: 40px 40px 30px;
  animation: aparecer 0.8s ease-in-out;
  text-align: center;
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
  color: var(--azul-oscuro);
  margin-bottom: 20px;
  font-size: 1.5rem;
}

form {
  display: flex;
  flex-direction: column;
  align-items: center;
}

input {
  width: 90%;
  max-width: 340px;
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

.password-container {
  position: relative;
  width: 90%;
  max-width: 340px;
}

.toggle-password {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  font-size: 18px;
  color: #666;
}

button {
  width: 90%;
  max-width: 340px;
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

.alert.error {
  background-color: #ffebee;
  color: var(--error);
  border: 1px solid var(--error);
}

.alert.success {
  background-color: #e8f5e9;
  color: var(--exito);
  border: 1px solid var(--exito);
}

a {
  color: var(--azul-oscuro);
  text-decoration: none;
  font-weight: bold;
}

a:hover {
  text-decoration: underline;
}

footer {
  text-align: center;
  margin-top: 25px;
  font-size: 13px;
  color: #555;
}
</style>
</head>
<body>
  <div class="container">
    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRkaHlDGj77xsH5FrEIqfgMNu8Wg3HWik4Cog&s" alt="Logo Colegio Robert Johnson" class="logo">
    <h2>Inicio de Sesión</h2>

    <form method="POST" onsubmit="return validarLogin()">
      <input type="text" name="nombre" id="nombre" placeholder="Nombre de usuario" required>
      <div class="password-container">
        <input type="password" name="password" id="password" placeholder="Contraseña" required>
        <span class="toggle-password" onclick="mostrarPassword()">👁️</span>
      </div>
      <button type="submit">Ingresar</button>
    </form>

    <?= $message ?>

    <p>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
    <footer>© Colegio Robert Johnson 2025</footer>
  </div>

  <script>
  function validarLogin() {
    const nombre = document.getElementById('nombre').value.trim();
    const password = document.getElementById('password').value.trim();

    if (nombre.length < 3) {
      alert('El nombre debe tener al menos 3 caracteres');
      return false;
    }
    if (password.length < 6) {
      alert('La contraseña debe tener al menos 6 caracteres');
      return false;
    }
    return true;
  }

  function mostrarPassword() {
    const pass = document.getElementById('password');
    pass.type = (pass.type === 'password') ? 'text' : 'password';
  }
  </script>
  
</body>
</html>
