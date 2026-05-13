<?php
session_start();
error_reporting(0);
include("includes/config.php");

$faqs_q = mysqli_query($con, "SELECT * FROM faq WHERE is_active=1 ORDER BY category, sort_order, id");
$cats = [];
while ($faqs_q && $row = mysqli_fetch_assoc($faqs_q)) {
    $cats[$row["category"]][] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Preguntas frecuentes | <?php echo $_SITE_NAME; ?></title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/font-awesome.min.css">
<link rel="stylesheet" href="assets/css/main.css">
<style>
body{background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;padding:40px 0}
.faq-card{max-width:780px;margin:0 auto;background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);overflow:hidden;margin-bottom:24px}
.faq-cat-header{background:#e8233a;color:#fff;padding:14px 24px;font-size:1.05em;font-weight:600}
.faq-item{border-bottom:1px solid #f0f0f0}
.faq-question{width:100%;text-align:left;background:none;border:none;padding:16px 24px;font-size:15px;font-weight:600;color:#333;cursor:pointer;display:flex;justify-content:space-between;align-items:center}
.faq-question:hover{background:#fafafa}
.faq-answer{display:none;padding:0 24px 16px;color:#555;font-size:14px;line-height:1.7}
.faq-answer.open{display:block}
.faq-icon{transition:transform .3s}
.faq-question.active .faq-icon{transform:rotate(180deg)}
</style>
</head>
<body>
<div style="max-width:780px;margin:0 auto">
<h2 style="text-align:center;margin-bottom:32px;color:#333"><i class="fa fa-question-circle" style="color:#e8233a"></i> Preguntas frecuentes</h2>
<?php if (empty($cats)): ?>
<p class="text-center text-muted">No hay preguntas frecuentes disponibles aun.</p>
<?php else: foreach ($cats as $cat => $items): ?>
<div class="faq-card">
<div class="faq-cat-header"><i class="fa fa-folder-o"></i> <?php echo htmlspecialchars($cat); ?></div>
<?php foreach ($items as $f): ?>
<div class="faq-item">
<button class="faq-question" onclick="psToggleFaq(this)">
<?php echo htmlspecialchars($f["question"]); ?>
<i class="fa fa-chevron-down faq-icon"></i>
</button>
<div class="faq-answer"><?php echo nl2br(htmlspecialchars($f["answer"])); ?></div>
</div>
<?php endforeach; ?>
</div>
<?php endforeach; endif; ?>
<div style="text-align:center;margin-top:16px">
<a href="index2.php" style="color:#888;font-size:13px"><i class="fa fa-arrow-left"></i> Volver a la tienda</a>
</div>
</div>
<script src="assets/js/jquery-1.11.1.min.js"></script>
<script>
function psToggleFaq(btn) {
    var ans = btn.nextElementSibling;
    var isOpen = ans.classList.contains('open');
    // Close all
    document.querySelectorAll('.faq-answer.open').forEach(function(a){ a.classList.remove('open'); });
    document.querySelectorAll('.faq-question.active').forEach(function(b){ b.classList.remove('active'); });
    if (!isOpen) { ans.classList.add('open'); btn.classList.add('active'); }
}
</script>
</body>
</html>