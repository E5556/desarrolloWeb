<?php
session_start();
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

$msg = ''; $mtyp = '';

// ── ELIMINAR ────────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $did = intval($_GET['delete']);
    mysqli_query($con, "DELETE FROM home_carousels WHERE id=$did");
    $msg = 'Carrusel eliminado.'; $mtyp = 'success';
}

// ── GUARDAR ORDEN (AJAX) ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reorder') {
    $ids = $_POST['ids'] ?? [];
    foreach ($ids as $pos => $id) {
        $id = intval($id); $pos = intval($pos) + 1;
        mysqli_query($con, "UPDATE home_carousels SET sort_order=$pos WHERE id=$id");
    }
    echo json_encode(['ok' => true]); exit();
}

// ── TOGGLE ACTIVO ────────────────────────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $tid = intval($_GET['toggle']);
    mysqli_query($con, "UPDATE home_carousels SET active = IF(active=1,0,1) WHERE id=$tid");
    header('location:home-carousels.php'); exit();
}

// ── CREAR ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $title  = trim($_POST['title'] ?? '');
    $stype  = in_array($_POST['search_type'] ?? '', ['category','subcategory','description']) ? $_POST['search_type'] : 'category';
    $sval   = trim($_POST['search_value'] ?? '');
    if ($title === '' || $sval === '') {
        $msg = 'El título y el valor de búsqueda son obligatorios.'; $mtyp = 'danger';
    } else {
        $et = mysqli_real_escape_string($con, $title);
        $es = mysqli_real_escape_string($con, $sval);
        $max = mysqli_fetch_assoc(mysqli_query($con,"SELECT COALESCE(MAX(sort_order),0)+1 n FROM home_carousels"))['n'];
        mysqli_query($con, "INSERT INTO home_carousels (title,search_type,search_value,sort_order) VALUES ('$et','$stype','$es',$max)");
        $msg = "Carrusel <strong>$title</strong> creado."; $mtyp = 'success';
    }
}

// ── EDITAR ───────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $eid   = intval($_POST['edit_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $stype = in_array($_POST['search_type'] ?? '', ['category','subcategory','description']) ? $_POST['search_type'] : 'category';
    $sval  = trim($_POST['search_value'] ?? '');
    if ($title === '' || $sval === '') {
        $msg = 'El título y el valor son obligatorios.'; $mtyp = 'danger';
    } else {
        $et = mysqli_real_escape_string($con, $title);
        $es = mysqli_real_escape_string($con, $sval);
        mysqli_query($con, "UPDATE home_carousels SET title='$et', search_type='$stype', search_value='$es' WHERE id=$eid");
        $msg = 'Carrusel actualizado.'; $mtyp = 'success';
    }
}

// ── Cargar datos auxiliares para selects ─────────────────────────────────────
$cats    = mysqli_fetch_all(mysqli_query($con,"SELECT id, categoryName FROM category ORDER BY id"), MYSQLI_ASSOC);
$subcats = mysqli_fetch_all(mysqli_query($con,"SELECT s.id, s.subcategory, c.categoryName FROM subcategory s JOIN category c ON c.id=s.categoryid ORDER BY s.categoryid, s.id"), MYSQLI_ASSOC);
$carousels = mysqli_fetch_all(mysqli_query($con,"SELECT * FROM home_carousels ORDER BY sort_order ASC"), MYSQLI_ASSOC);

$edit_row = null;
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $er  = mysqli_query($con,"SELECT * FROM home_carousels WHERE id=$eid");
    $edit_row = $er ? mysqli_fetch_assoc($er) : null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_ADMIN_SITE_NAME; ?> | Carruseles inicio</title>
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
    <link rel="stylesheet" href="css/theme.css">
    <link rel="stylesheet" href="images/icons/css/font-awesome.css">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600" rel="stylesheet">
    <style>
        .form-section { background:#fafafa; border:1px solid #e4e4e4; border-radius:6px; padding:20px 24px; margin-bottom:20px; }
        .form-section h4 { margin-top:0; border-bottom:1px solid #e0e0e0; padding-bottom:8px; }
        .drag-handle { cursor:move; color:#bbb; margin-right:8px; font-size:1.1em; }
        .hc-table td { vertical-align:middle !important; font-size:.87em; }
        .hc-table th { background:#f9f9f9; font-size:.82em; }
        .badge-cat  { background:#337ab7; color:#fff; border-radius:10px; padding:2px 7px; font-size:.72em; }
        .badge-sub  { background:#9575cd; color:#fff; border-radius:10px; padding:2px 7px; font-size:.72em; }
        .badge-desc { background:#f0ad4e; color:#fff; border-radius:10px; padding:2px 7px; font-size:.72em; }
        .type-label { display:inline-block; margin-left:4px; }
        #search-value-wrap select, #search-value-wrap input { width:100%; }
    </style>
</head>
<body>
<?php include('include/header.php'); ?>
<div class="wrapper">
<div class="container">
<div class="row">
<?php include('include/sidebar.php'); ?>
<div class="span9">
<div class="content">
<div class="module">
<div class="module-head">
    <h3><i class="icon-film" style="margin-right:6px"></i>Carruseles de la página de inicio</h3>
</div>
<div class="module-body">

<?php if ($msg): ?><div class="alert alert-<?php echo $mtyp; ?>"><?php echo $msg; ?></div><?php endif; ?>

<p class="text-muted" style="font-size:.85em">
    Cada carrusel muestra productos según el criterio que definas. Puedes filtrar por <strong>categoría</strong>, <strong>subcategoría</strong> o buscar por <strong>palabra en la descripción</strong> del producto. Arrastra las filas para reordenar.
</p>

<!-- ── FORMULARIO ── -->
<div class="form-section">
<?php if ($edit_row): ?>
    <h4><i class="icon-edit"></i> Editar carrusel: <strong><?php echo htmlentities($edit_row['title']); ?></strong></h4>
    <form method="post" action="home-carousels.php">
    <input type="hidden" name="action"  value="edit">
    <input type="hidden" name="edit_id" value="<?php echo $edit_row['id']; ?>">
<?php else: ?>
    <h4><i class="icon-plus"></i> Nuevo carrusel</h4>
    <form method="post" action="home-carousels.php">
    <input type="hidden" name="action" value="create">
<?php endif; ?>

    <div class="row-fluid">
        <div class="span4">
            <label>Título del carrusel</label>
            <input type="text" class="input-block-level" name="title"
                   value="<?php echo htmlentities($edit_row['title'] ?? ''); ?>"
                   placeholder="ej: Novedades Mujer" required>
        </div>
        <div class="span4">
            <label>Tipo de búsqueda</label>
            <select class="input-block-level" name="search_type" id="search-type-sel">
                <option value="category"    <?php echo (($edit_row['search_type'] ?? '') === 'category'    ? 'selected' : ''); ?>>Por Categoría</option>
                <option value="subcategory" <?php echo (($edit_row['search_type'] ?? '') === 'subcategory' ? 'selected' : ''); ?>>Por Subcategoría</option>
                <option value="description" <?php echo (($edit_row['search_type'] ?? '') === 'description' ? 'selected' : ''); ?>>Por Descripción (palabra)</option>
            </select>
        </div>
        <div class="span4" id="search-value-wrap">
            <label>Valor de búsqueda</label>
            <!-- se reemplaza dinámicamente con JS -->
            <div id="sv-inner">
                <?php
                $cur_type = $edit_row['search_type'] ?? 'category';
                $cur_val  = $edit_row['search_value'] ?? '';
                if ($cur_type === 'category'):
                ?>
                <select name="search_value" class="input-block-level">
                    <?php foreach ($cats as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($cur_val == $c['id'] ? 'selected' : ''); ?>>
                        <?php echo htmlentities($c['categoryName']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php elseif ($cur_type === 'subcategory'): ?>
                <select name="search_value" class="input-block-level">
                    <?php foreach ($subcats as $s): ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo ($cur_val == $s['id'] ? 'selected' : ''); ?>>
                        <?php echo htmlentities($s['categoryName'] . ' → ' . $s['subcategory']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php else: ?>
                <input type="text" name="search_value" class="input-block-level"
                       placeholder="ej: vestido, denim, casual..."
                       value="<?php echo htmlentities($cur_val); ?>">
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div style="margin-top:14px">
        <button type="submit" class="btn btn-<?php echo $edit_row ? 'primary' : 'success'; ?>">
            <i class="icon-<?php echo $edit_row ? 'save' : 'plus'; ?>"></i>
            <?php echo $edit_row ? 'Guardar cambios' : 'Crear carrusel'; ?>
        </button>
        <?php if ($edit_row): ?>
        <a href="home-carousels.php" class="btn btn-default" style="margin-left:8px">Cancelar</a>
        <?php endif; ?>
    </div>
</form>
</div>

<!-- ── TABLA ── -->
<table class="table table-bordered table-striped hc-table" id="hc-table">
    <thead>
        <tr>
            <th style="width:30px"></th>
            <th>#</th>
            <th>Título</th>
            <th>Tipo</th>
            <th>Valor</th>
            <th>Activo</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody id="hc-tbody">
    <?php foreach ($carousels as $i => $hc):
        // Resolver etiqueta del valor
        $val_label = htmlentities($hc['search_value']);
        if ($hc['search_type'] === 'category') {
            foreach ($cats as $c) { if ($c['id'] == $hc['search_value']) { $val_label = htmlentities($c['categoryName']); break; } }
        } elseif ($hc['search_type'] === 'subcategory') {
            foreach ($subcats as $s) { if ($s['id'] == $hc['search_value']) { $val_label = htmlentities($s['categoryName'].' → '.$s['subcategory']); break; } }
        }
    ?>
    <tr data-id="<?php echo $hc['id']; ?>">
        <td><span class="drag-handle"><i class="icon-reorder"></i></span></td>
        <td><?php echo $i+1; ?></td>
        <td><strong><?php echo htmlentities($hc['title']); ?></strong></td>
        <td>
            <?php if ($hc['search_type'] === 'category'): ?>
                <span class="badge-cat type-label">Categoría</span>
            <?php elseif ($hc['search_type'] === 'subcategory'): ?>
                <span class="badge-sub type-label">Subcategoría</span>
            <?php else: ?>
                <span class="badge-desc type-label">Descripción</span>
            <?php endif; ?>
        </td>
        <td><?php echo $val_label; ?></td>
        <td style="text-align:center">
            <a href="home-carousels.php?toggle=<?php echo $hc['id']; ?>"
               class="btn btn-mini <?php echo $hc['active'] ? 'btn-success' : 'btn-default'; ?>">
                <?php echo $hc['active'] ? '<i class="icon-eye-open"></i> Sí' : '<i class="icon-eye-close"></i> No'; ?>
            </a>
        </td>
        <td>
            <a href="home-carousels.php?edit=<?php echo $hc['id']; ?>" class="btn btn-mini btn-primary">
                <i class="icon-edit"></i> Editar
            </a>
            <a href="home-carousels.php?delete=<?php echo $hc['id']; ?>"
               class="btn btn-mini btn-danger"
               onclick="return confirm('¿Eliminar el carrusel \'<?php echo htmlspecialchars($hc['title'],ENT_QUOTES); ?>\'?')">
                <i class="icon-trash"></i>
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($carousels)): ?>
    <tr><td colspan="7" class="text-muted" style="text-align:center;font-style:italic">No hay carruseles configurados.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

</div>
</div>
</div>
</div>
</div>
</div>
</div>

<?php include('include/footer.php'); ?>
<script src="scripts/jquery-1.9.1.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script src="scripts/jquery-ui-1.10.1.custom.min.js"></script>
<script>
// ── Datos para el select dinámico ──
var cats = <?php echo json_encode(array_map(fn($c)=>['id'=>$c['id'],'name'=>$c['categoryName']], $cats)); ?>;
var subcats = <?php echo json_encode(array_map(fn($s)=>['id'=>$s['id'],'name'=>$s['categoryName'].' → '.$s['subcategory']], $subcats)); ?>;

function buildValueField(type, selected) {
    var html = '';
    if (type === 'category') {
        html = '<select name="search_value" class="input-block-level">';
        cats.forEach(function(c) {
            html += '<option value="'+c.id+'"'+(c.id==selected?' selected':'')+'>'+c.name+'</option>';
        });
        html += '</select>';
    } else if (type === 'subcategory') {
        html = '<select name="search_value" class="input-block-level">';
        subcats.forEach(function(s) {
            html += '<option value="'+s.id+'"'+(s.id==selected?' selected':'')+'>'+s.name+'</option>';
        });
        html += '</select>';
    } else {
        html = '<input type="text" name="search_value" class="input-block-level" placeholder="ej: vestido, casual..." value="'+(selected||'')+'">';
    }
    document.getElementById('sv-inner').innerHTML = html;
}

document.getElementById('search-type-sel').addEventListener('change', function() {
    buildValueField(this.value, '');
});

// ── Drag & drop para reordenar ──
$(function() {
    if (!$.fn.sortable) return;
    $('#hc-tbody').sortable({
        handle: '.drag-handle',
        update: function() {
            var ids = [];
            $('#hc-tbody tr[data-id]').each(function(){ ids.push($(this).data('id')); });
            $.post('home-carousels.php', { action:'reorder', ids: ids });
        }
    });
});
</script>
</body>
</html>
