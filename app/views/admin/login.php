<?php

use App\Config\App;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(App::name()) ?> — Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(App::asset('css/style.css')) ?>">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="min-height:100vh;">
<div id="loginApp" class="card shadow-lg" style="width:100%;max-width:400px;">
    <div class="card-body p-5">
        <h1 class="text-center fw-bold mb-1"><?= htmlspecialchars(App::name()) ?></h1>
        <p class="text-center text-muted mb-4">Acceso administrador</p>
        <div id="mensaje" class="alert d-none" role="alert"></div>
        <form id="loginForm">
            <div class="mb-3">
                <label class="form-label">Usuario</label>
                <input name="usuario" type="text" class="form-control" placeholder="admin" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label">Contraseña</label>
                <input name="password" type="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100" id="btnLogin">Entrar</button>
        </form>
        <p class="text-center small mt-3 mb-0">
            <a href="<?= htmlspecialchars(App::url('/')) ?>">← Volver al sitio</a>
        </p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
const BASE = <?= json_encode(App::basePath()) ?>;
const form = document.getElementById('loginForm');
const msg = document.getElementById('mensaje');
const btn = document.getElementById('btnLogin');

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  btn.disabled = true;
  msg.classList.add('d-none');
  const data = Object.fromEntries(new FormData(form).entries());
  try {
    const res = await axios.post(BASE + '/api/auth/login', data);
    if (res.data.exito) {
      msg.className = 'alert alert-success';
      msg.textContent = res.data.mensaje;
      msg.classList.remove('d-none');
      setTimeout(() => location.href = BASE + '/admin/dashboard', 500);
    } else {
      msg.className = 'alert alert-danger';
      msg.textContent = res.data.mensaje || 'Error';
      msg.classList.remove('d-none');
    }
  } catch (err) {
    msg.className = 'alert alert-danger';
    msg.textContent = 'Error de conexión';
    msg.classList.remove('d-none');
  } finally {
    btn.disabled = false;
  }
});
</script>
</body>
</html>
