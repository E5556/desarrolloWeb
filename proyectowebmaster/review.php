<?php
session_start();
include('includes/config.php');

// ── Auth ─────────────────────────────────────────────────────────────────────
if (empty($_SESSION['login'])) {
    header('location:login.php'); exit();
}
$uid = intval($_SESSION['id']);
$oid = intval($_GET['oid'] ?? 0);
$pid = intval($_GET['pid'] ?? 0);

// ── Verificar que la orden pertenece al usuario y está Delivered ─────────────
$st = mysqli_prepare($con,
    "SELECT o.id, o.orderStatus, p.productName, p.productImage1
     FROM orders o JOIN products p ON p.id = o.productId
     WHERE o.id=? AND o.userId=? AND o.productId=?");
mysqli_stmt_bind_param($st, 'iii', $oid, $uid, $pid);
mysqli_stmt_execute($st);
$order = mysqli_stmt_get_result($st)->fetch_assoc();
mysqli_stmt_close($st);

if (!$order || $order['orderStatus'] !== 'Delivered') {
    header('location:mis-compras.php'); exit();
}

// ── Verificar que no haya reseña previa ──────────────────────────────────────
$chk = mysqli_prepare($con, "SELECT id FROM productreviews WHERE userId=? AND productId=?");
mysqli_stmt_bind_param($chk, 'ii', $uid, $pid);
mysqli_stmt_execute($chk);
mysqli_stmt_store_result($chk);
if (mysqli_stmt_num_rows($chk) > 0) { header('location:mis-compras.php'); exit(); }
mysqli_stmt_close($chk);

// ── Procesar envío ───────────────────────────────────────────────────────────
$success = false;
$error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $stars   = max(1, min(5, intval($_POST['stars']   ?? 0)));
    $title   = trim($_POST['title']   ?? '');
    $comment = trim($_POST['comment'] ?? '');
    $name    = htmlspecialchars($_SESSION['username']);

    if ($stars < 1)         $error = 'Selecciona una calificación.';
    elseif (!$title)        $error = 'Escribe un título para tu reseña.';
    elseif (!$comment)      $error = 'Escribe tu comentario.';
    else {
        $ins = mysqli_prepare($con,
            "INSERT INTO productreviews(productId, userId, quality, name, title, summary, review, status, verified)
             VALUES(?, ?, ?, ?, ?, ?, ?, 'pending', 1)");
        mysqli_stmt_bind_param($ins, 'iiissss', $pid, $uid, $stars, $name, $title, $title, $comment);
        if (mysqli_stmt_execute($ins)) $success = true;
        else $error = 'Error al guardar. Inténtalo de nuevo.';
        mysqli_stmt_close($ins);
    }
}

$img_path = "admin/productimages/{$pid}/{$order['productImage1']}";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escribir reseña | <?php echo $_SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/green.css">
    <link rel="stylesheet" href="assets/css/cosmetics.css">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <link rel="shortcut icon" href="<?php echo $_SITE_FAVICON; ?>">
    <style>
    body { background: #f7f7f7; font-family: 'Poppins', sans-serif; }

    .rv-wrap { max-width: 520px; margin: 48px auto; padding: 0 16px 80px; }

    /* ── Breadcrumb ── */
    .rv-back { font-size: .8rem; color: #aaa; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 24px; }
    .rv-back:hover { color: #c0396b; }

    /* ── Card ── */
    .rv-card { background: #fff; border-radius: 20px; box-shadow: 0 4px 24px rgba(0,0,0,0.07); overflow: hidden; }

    /* ── Product hero ── */
    .rv-product {
        display: flex; flex-direction: column; align-items: center;
        padding: 36px 28px 24px; border-bottom: 1px solid #f3f3f3;
        text-align: center;
    }
    .rv-product img {
        width: 90px; height: 90px; border-radius: 50%; object-fit: cover;
        box-shadow: 0 4px 16px rgba(0,0,0,0.10); margin-bottom: 16px;
    }
    .rv-product-ph {
        width: 90px; height: 90px; border-radius: 50%; background: #f5f5f5;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 16px;
    }
    .rv-product-ph i { font-size: 36px; color: #ddd; }
    .rv-product h2 { font-size: 1rem; font-weight: 700; color: #1a1a1a; margin: 0 0 4px; font-family: 'Playfair Display', Georgia, serif; }
    .rv-product p  { font-size: .78rem; color: #bbb; margin: 0; }

    /* ── Star rating ── */
    .rv-stars-section { padding: 28px 28px 0; text-align: center; }
    .rv-stars-label { font-size: .78rem; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: #bbb; margin-bottom: 14px; }
    .rv-stars { display: flex; justify-content: center; gap: 6px; direction: rtl; }
    .rv-stars input { display: none; }
    .rv-stars label {
        font-size: 36px; color: #e0e0e0; cursor: pointer;
        transition: color .15s, transform .1s;
        line-height: 1;
    }
    .rv-stars label:hover,
    .rv-stars label:hover ~ label,
    .rv-stars input:checked ~ label { color: #c9a84c; }
    .rv-stars label:hover { transform: scale(1.15); }

    /* ── Form fields ── */
    .rv-form { padding: 24px 28px 32px; }
    .rv-field { margin-bottom: 18px; }
    .rv-field label { font-size: .75rem; font-weight: 600; letter-spacing: .4px; text-transform: uppercase; color: #999; display: block; margin-bottom: 6px; }
    .rv-field input,
    .rv-field textarea {
        width: 100%; border: 1.5px solid #ebebeb; border-radius: 10px;
        padding: 12px 16px; font-size: .9rem; font-family: 'Poppins', sans-serif;
        color: #333; background: #fafafa; outline: none;
        transition: border-color .2s, box-shadow .2s;
        resize: vertical;
    }
    .rv-field input:focus,
    .rv-field textarea:focus { border-color: #c0396b; box-shadow: 0 0 0 3px rgba(192,57,107,0.08); background: #fff; }
    .rv-field textarea { min-height: 120px; }
    .rv-char-count { font-size: .72rem; color: #ccc; text-align: right; margin-top: 4px; }

    /* ── Error / Success ── */
    .rv-error   { background: #fff0f3; border: 1px solid #fcc; color: #c0396b; border-radius: 10px; padding: 12px 16px; font-size: .85rem; margin-bottom: 16px; }
    .rv-success { text-align: center; padding: 48px 28px; }
    .rv-success .rv-check { font-size: 52px; color: #5cb85c; margin-bottom: 16px; }
    .rv-success h3 { font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin: 0 0 8px; font-family: 'Playfair Display', Georgia, serif; }
    .rv-success p  { font-size: .85rem; color: #aaa; margin: 0 0 24px; }

    /* ── Submit button ── */
    .btn-submit {
        width: 100%; padding: 14px; border-radius: 50px; border: none;
        background: #1a1a1a; color: #fff; font-size: .85rem; font-weight: 700;
        letter-spacing: 1px; text-transform: uppercase; cursor: pointer;
        transition: background .2s, transform .15s;
        font-family: 'Poppins', sans-serif;
    }
    .btn-submit:hover { background: #c0396b; transform: translateY(-1px); }

    .rv-cancel { display: block; text-align: center; font-size: .78rem; color: #ccc; text-decoration: none; margin-top: 14px; }
    .rv-cancel:hover { color: #c0396b; }

    @media (max-width: 480px) {
        .rv-stars label { font-size: 30px; }
        .rv-form, .rv-stars-section, .rv-product { padding-left: 18px; padding-right: 18px; }
    }
    </style>
</head>
<body>
<header class="header-style-1">
<?php include('includes/top-header.php'); ?>
<?php include('includes/main-header.php'); ?>
<?php include('includes/menu-bar.php'); ?>
</header>

<div class="rv-wrap">
    <a href="mis-compras.php" class="rv-back"><i class="fa fa-arrow-left"></i> Mis compras</a>

    <div class="rv-card">
        <!-- Product hero -->
        <div class="rv-product">
            <?php if ($order['productImage1'] && file_exists($img_path)): ?>
            <img src="<?php echo $img_path; ?>" alt="<?php echo htmlspecialchars($order['productName']); ?>">
            <?php else: ?>
            <div class="rv-product-ph"><i class="fa fa-image"></i></div>
            <?php endif; ?>
            <h2><?php echo htmlspecialchars($order['productName']); ?></h2>
            <p>Compra verificada</p>
        </div>

        <?php if ($success): ?>
        <!-- Success state -->
        <div class="rv-success">
            <div class="rv-check"><i class="fa fa-check-circle"></i></div>
            <h3>¡Gracias por tu opinión!</h3>
            <p>Tu reseña está siendo revisada por nuestro equipo y se publicará pronto.</p>
            <a href="mis-compras.php" style="display:inline-block;background:#1a1a1a;color:#fff;padding:12px 28px;border-radius:50px;font-size:.82rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;text-decoration:none;">
                Volver a mis compras
            </a>
        </div>
        <?php else: ?>

        <!-- Stars -->
        <div class="rv-stars-section">
            <div class="rv-stars-label">¿Cómo calificarías tu experiencia?</div>
            <div class="rv-stars">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" id="star<?php echo $i; ?>" name="stars_display" value="<?php echo $i; ?>">
                <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> estrellas">★</label>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Form -->
        <form class="rv-form" method="post" id="rv-form">
            <input type="hidden" name="stars" id="stars-hidden" value="0">

            <?php if ($error): ?>
            <div class="rv-error"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="rv-field">
                <label>Título de tu reseña</label>
                <input type="text" name="title" maxlength="120"
                       placeholder="Resume tu experiencia en una frase"
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>

            <div class="rv-field">
                <label>Comparte tu experiencia</label>
                <textarea name="comment" id="rv-comment" maxlength="1500"
                          placeholder="¿Qué te pareció el producto? ¿Lo recomendarías?"><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                <div class="rv-char-count"><span id="rv-count">0</span> / 1500</div>
            </div>

            <button type="submit" name="submit_review" class="btn-submit">Guardar reseña</button>
            <a href="mis-compras.php" class="rv-cancel">Cancelar</a>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php include('includes/footer.php'); ?>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script>
// Sync star radio → hidden input
document.querySelectorAll('.rv-stars input').forEach(function(r){
    r.addEventListener('change', function(){ document.getElementById('stars-hidden').value = this.value; });
});
// Char counter
var ta = document.getElementById('rv-comment');
var cnt = document.getElementById('rv-count');
if (ta && cnt) {
    cnt.textContent = ta.value.length;
    ta.addEventListener('input', function(){ cnt.textContent = this.value.length; });
}
// Validate stars on submit
document.getElementById('rv-form') && document.getElementById('rv-form').addEventListener('submit', function(e){
    if (document.getElementById('stars-hidden').value < 1) {
        e.preventDefault();
        showToast('Por favor selecciona una calificación.','warning');
    }
});
</script>
<script src="assets/js/toast.js"></script>
</body>
</html>
