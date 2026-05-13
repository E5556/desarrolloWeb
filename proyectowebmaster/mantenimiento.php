<?php
// Página mostrada cuando el modo mantenimiento está activo
// Solo accesible directamente (el redirect lo hace config.php)
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sitio en mantenimiento</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    font-family: 'Segoe UI', system-ui, sans-serif; color: #fff;
}
.box {
    text-align: center; max-width: 520px; padding: 60px 40px;
    background: rgba(255,255,255,.05); border-radius: 20px;
    backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,.12);
    box-shadow: 0 20px 60px rgba(0,0,0,.4);
}
.icon { font-size: 64px; margin-bottom: 24px; }
h1 { font-size: 2rem; font-weight: 700; margin-bottom: 12px; }
p { font-size: 1rem; color: rgba(255,255,255,.7); line-height: 1.6; margin-bottom: 28px; }
.badge {
    display: inline-block; background: rgba(232,35,58,.2); border: 1px solid rgba(232,35,58,.5);
    color: #ff6b6b; padding: 6px 18px; border-radius: 999px; font-size: .82rem; font-weight: 600;
}
</style>
</head>
<body>
<div class="box">
    <div class="icon">🔧</div>
    <h1>Estamos mejorando</h1>
    <p>Nuestro sitio está temporalmente en mantenimiento.<br>Volveremos pronto con mejoras para ti.</p>
    <span class="badge">Vuelve en unos momentos</span>
</div>
</body>
</html>
