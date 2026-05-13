<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

// Answer a question
if (isset($_POST['answer_id']) && isset($_POST['answer_text'])) {
    $qid = intval($_POST['answer_id']);
    $ans = trim($_POST['answer_text']);
    if ($ans !== '') {
        $stmt = mysqli_prepare($con,"UPDATE product_questions SET answer=?, answered_at=NOW() WHERE id=?");
        mysqli_stmt_bind_param($stmt,'si',$ans,$qid);
        mysqli_stmt_execute($stmt); mysqli_stmt_close($stmt);
    }
    header('location:product-questions.php?ok=1'); exit();
}
if (isset($_GET['del'])) {
    mysqli_query($con,"DELETE FROM product_questions WHERE id=".intval($_GET['del']));
    header('location:product-questions.php?ok=deleted'); exit();
}

$filter = isset($_GET['unanswered']) ? "WHERE pq.answer IS NULL" : "";
$questions = mysqli_query($con,
    "SELECT pq.*, p.productName, u.name as uname FROM product_questions pq
     LEFT JOIN products p ON p.id=pq.product_id
     LEFT JOIN users u ON u.id=pq.user_id
     $filter ORDER BY pq.answer IS NOT NULL ASC, pq.created_at DESC LIMIT 100");
$pending_q = mysqli_fetch_assoc(mysqli_query($con,"SELECT COUNT(*) n FROM product_questions WHERE answer IS NULL"));
$pending = intval($pending_q['n']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Preguntas de productos | Admin</title>
<link href="../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/font-awesome.min.css" rel="stylesheet">
<link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
<?php include('include/header.php'); ?>
<div class="container-fluid">
<div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9">
<div class="content-area">

<h3><i class="icon-question-sign"></i> Preguntas de productos
    <?php if ($pending > 0): ?><span class="badge" style="background:#f39c12"><?php echo $pending; ?> sin responder</span><?php endif; ?>
</h3>

<?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Operación completada.</div><?php endif; ?>

<div style="margin-bottom:12px">
    <a href="product-questions.php" class="btn btn-default btn-sm <?php echo !isset($_GET['unanswered'])?'active':''; ?>">Todas</a>
    <a href="product-questions.php?unanswered=1" class="btn btn-warning btn-sm <?php echo isset($_GET['unanswered'])?'active':''; ?>">Sin responder (<?php echo $pending; ?>)</a>
</div>

<?php if (!$questions || mysqli_num_rows($questions)===0): ?>
<div class="alert alert-info">No hay preguntas.</div>
<?php else: while ($q = mysqli_fetch_assoc($questions)): ?>
<div class="panel panel-<?php echo $q['answer']?'default':'warning'; ?>" style="margin-bottom:12px">
<div class="panel-heading" style="display:flex;justify-content:space-between;align-items:center">
    <span>
        <strong><?php echo htmlspecialchars($q['productName']??'—'); ?></strong>
        &nbsp;<small class="text-muted">— <?php echo htmlspecialchars($q['uname']??'Anónimo'); ?> · <?php echo date('d/m/Y H:i', strtotime($q['created_at'])); ?></small>
    </span>
    <a href="product-questions.php?del=<?php echo $q['id']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Eliminar?')"><i class="icon-trash"></i></a>
</div>
<div class="panel-body">
    <p><strong><i class="fa fa-question" style="color:#337ab7"></i></strong> <?php echo nl2br(htmlspecialchars($q['question'])); ?></p>
    <?php if ($q['answer']): ?>
    <div style="padding:8px 12px;border-left:3px solid #e8233a;background:#fef9f9;font-size:13px;margin-top:8px">
        <strong style="color:#e8233a">Tu respuesta:</strong><br>
        <?php echo nl2br(htmlspecialchars($q['answer'])); ?><br>
        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($q['answered_at'])); ?></small>
    </div>
    <?php endif; ?>
    <form method="post" style="margin-top:10px">
        <input type="hidden" name="answer_id" value="<?php echo $q['id']; ?>">
        <div class="input-group">
            <input type="text" name="answer_text" class="form-control" placeholder="<?php echo $q['answer']?'Editar respuesta…':'Escribe tu respuesta…'; ?>" value="<?php echo htmlspecialchars($q['answer']??''); ?>">
            <span class="input-group-btn">
                <button type="submit" class="btn btn-primary"><?php echo $q['answer']?'Actualizar':'Responder'; ?></button>
            </span>
        </div>
    </form>
</div>
</div>
<?php endwhile; endif; ?>

</div>
</div>
</div>
</div>
<?php include('include/footer.php'); ?>
<script src="../assets/js/jquery-1.11.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
</body>
</html>
