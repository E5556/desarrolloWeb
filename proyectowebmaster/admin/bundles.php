<?php
ob_start();
session_start();
error_reporting(0);
include('include/config.php');
if (!isset($_SESSION['alogin'])) { header('location:index.php'); exit(); }

mysqli_query($con, "CREATE TABLE IF NOT EXISTS bundles (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(200) NOT NULL,
    description  TEXT DEFAULT NULL,
    bundle_price DECIMAL(10,2) NOT NULL,
    image        VARCHAR(255) DEFAULT NULL,
    is_active    TINYINT(1) DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($con, "CREATE TABLE IF NOT EXISTS bundle_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    bundle_id  INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT NOT NULL DEFAULT 1,
    FOREIGN KEY (bundle_id) REFERENCES bundles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$msg = $errmsg = '';

// Delete
if (isset($_GET['delete'])) {
    $did = intval($_GET['delete']);
    mysqli_query($con, "DELETE FROM bundles WHERE id=$did");
    header('location:bundles.php?deleted=1'); exit();
}

// Toggle active
if (isset($_GET['toggle'])) {
    $tid = intval($_GET['toggle']);
    mysqli_query($con, "UPDATE bundles SET is_active = 1 - is_active WHERE id=$tid");
    header('location:bundles.php'); exit();
}

// Save
if (isset($_POST['save_bundle'])) {
    $bid   = intval($_POST['bid'] ?? 0);
    $name  = mysqli_real_escape_string($con, trim($_POST['name'] ?? ''));
    $desc  = mysqli_real_escape_string($con, trim($_POST['description'] ?? ''));
    $price = floatval($_POST['bundle_price'] ?? 0);
    $active= intval($_POST['is_active'] ?? 1);
    $pids  = array_map('intval', $_POST['product_ids'] ?? []);
    $qtys  = array_map('intval', $_POST['quantities'] ?? []);
    // Filtrar filas vacías
    $items = [];
    foreach ($pids as $i => $pid) {
        if ($pid > 0) $items[] = ['pid' => $pid, 'qty' => max(1, $qtys[$i] ?? 1)];
    }

    if (empty($name) || $price <= 0 || empty($items)) {
        $errmsg = 'Completa el nombre, precio y al menos un producto.';
    } else {
        if ($bid > 0) {
            mysqli_query($con, "UPDATE bundles SET name='$name', description='$desc', bundle_price=$price, is_active=$active WHERE id=$bid");
            mysqli_query($con, "DELETE FROM bundle_items WHERE bundle_id=$bid");
        } else {
            mysqli_query($con, "INSERT INTO bundles (name,description,bundle_price,is_active) VALUES('$name','$desc',$price,$active)");
            $bid = mysqli_insert_id($con);
        }
        $stmt = mysqli_prepare($con, "INSERT INTO bundle_items (bundle_id,product_id,quantity) VALUES(?,?,?)");
        foreach ($items as $it) {
            mysqli_stmt_bind_param($stmt, 'iii', $bid, $it['pid'], $it['qty']);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
        header('location:bundles.php?saved=1'); exit();
    }
}

// Load for editing
$edit_bundle = null;
$edit_items  = [];
if (isset($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $er  = mysqli_query($con, "SELECT * FROM bundles WHERE id=$eid LIMIT 1");
    if ($er) $edit_bundle = mysqli_fetch_assoc($er);
    $ir = mysqli_query($con, "SELECT bi.*, p.productName FROM bundle_items bi JOIN products p ON p.id=bi.product_id WHERE bi.bundle_id=$eid");
    while ($ir && $row = mysqli_fetch_assoc($ir)) $edit_items[] = $row;
}

// Products list (sin filtrar por stock para no quedarse vacío)
$products_q = mysqli_query($con, "SELECT id, productName, productPrice FROM products ORDER BY productName ASC");
$all_products = [];
while ($products_q && $p = mysqli_fetch_assoc($products_q)) $all_products[] = $p;

// All bundles with item count and savings
$bundles_q = mysqli_query($con,
    "SELECT b.*,
            COUNT(bi.id) AS item_count,
            COALESCE(SUM(p.productPrice * bi.quantity),0) AS original_total
     FROM bundles b
     LEFT JOIN bundle_items bi ON bi.bundle_id = b.id
     LEFT JOIN products p ON p.id = bi.product_id
     GROUP BY b.id
     ORDER BY b.created_at DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bundles / Paquetes | Admin</title>
<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="bootstrap/css/bootstrap-responsive.min.css">
<link rel="stylesheet" href="css/theme.css">
<link rel="stylesheet" href="images/icons/css/font-awesome.css">
<style>
/* ── Layout cards ── */
.ps-card {
    background:#fff;
    border-radius:12px;
    box-shadow:0 2px 16px rgba(0,0,0,.07);
    margin-bottom:28px;
    overflow:hidden;
}
.ps-card-head {
    padding:18px 24px;
    border-bottom:1px solid #f0f0f0;
    display:flex;
    align-items:center;
    gap:10px;
}
.ps-card-head h3 { margin:0; font-size:16px; font-weight:700; color:#2c3e50; }
.ps-card-head .ps-icon {
    width:36px; height:36px; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    font-size:16px; color:#fff;
}
.ps-card-body { padding:24px; }

/* ── Info banner ── */
.ps-info-banner {
    background:linear-gradient(135deg,#6c5ce7,#a29bfe);
    border-radius:10px;
    padding:18px 22px;
    color:#fff;
    margin-bottom:22px;
    display:flex;
    gap:16px;
    align-items:flex-start;
}
.ps-info-banner i { font-size:28px; opacity:.9; margin-top:2px; }
.ps-info-banner h4 { margin:0 0 4px; font-size:15px; font-weight:700; }
.ps-info-banner p  { margin:0; font-size:12px; opacity:.9; line-height:1.5; }

/* ── Formulario ── */
.ps-field label { font-size:12px; font-weight:600; color:#555; margin-bottom:4px; display:block; }
.ps-field input, .ps-field select, .ps-field textarea {
    width:100%; padding:9px 12px;
    border:1px solid #dde; border-radius:7px;
    font-size:13px; background:#fafafa;
    transition:border .2s, box-shadow .2s;
}
.ps-field input:focus, .ps-field select:focus, .ps-field textarea:focus {
    outline:none; border-color:#6c5ce7; background:#fff;
    box-shadow:0 0 0 3px rgba(108,92,231,.12);
}
.ps-field-row { display:flex; gap:14px; margin-bottom:14px; flex-wrap:wrap; }
.ps-field-row .ps-field { flex:1; min-width:160px; }

/* ── Items del bundle ── */
.ps-items-wrap { background:#f8f9ff; border-radius:8px; padding:16px; border:1px solid #e8e8f5; }
.ps-item-row {
    display:flex; gap:10px; align-items:center;
    background:#fff; border-radius:7px; padding:10px 12px;
    margin-bottom:8px; border:1px solid #eee;
    box-shadow:0 1px 4px rgba(0,0,0,.04);
}
.ps-item-row select { flex:1; }
.ps-item-qty { width:72px !important; text-align:center; }
.ps-item-label { font-size:11px; color:#888; white-space:nowrap; }
.ps-btn-remove {
    background:#e74c3c; border:none; color:#fff;
    border-radius:6px; width:32px; height:32px;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; flex-shrink:0; transition:.2s;
    font-size:18px; line-height:1; font-weight:700;
}
.ps-btn-remove:hover { background:#c0392b; }
.btn-add-item {
    background:#f0eeff; border:1px dashed #6c5ce7; color:#6c5ce7;
    border-radius:7px; padding:8px 16px; font-size:12px; font-weight:600;
    cursor:pointer; width:100%; margin-top:4px; transition:.2s;
}
.btn-add-item:hover { background:#6c5ce7; color:#fff; }

/* ── Botón guardar ── */
.btn-save-bundle {
    background:linear-gradient(135deg,#6c5ce7,#a29bfe);
    color:#fff; border:none; border-radius:8px;
    padding:11px 28px; font-size:14px; font-weight:700;
    cursor:pointer; transition:.2s; letter-spacing:.3px;
}
.btn-save-bundle:hover { opacity:.9; transform:translateY(-1px); }

/* ── Lista de bundles ── */
.bundle-card {
    border:1px solid #eee; border-radius:10px; padding:18px;
    margin-bottom:14px; display:flex; gap:16px; align-items:center;
    background:#fff; transition:box-shadow .2s;
}
.bundle-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); }
.bundle-icon {
    width:52px; height:52px; border-radius:10px;
    background:linear-gradient(135deg,#6c5ce7,#a29bfe);
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:22px; flex-shrink:0;
}
.bundle-info { flex:1; }
.bundle-info h5 { margin:0 0 3px; font-size:14px; font-weight:700; color:#2c3e50; }
.bundle-info .meta { font-size:11px; color:#888; }
.bundle-prices { text-align:right; }
.bundle-prices .price-bundle { font-size:18px; font-weight:800; color:#6c5ce7; }
.bundle-prices .price-saving { font-size:11px; color:#27ae60; font-weight:600; }
.bundle-prices .price-original { font-size:11px; color:#aaa; text-decoration:line-through; }
.badge-active   { background:#eafaf1; color:#27ae60; border:1px solid #abebc6; border-radius:20px; padding:3px 10px; font-size:11px; font-weight:600; }
.badge-inactive { background:#f4f6f9; color:#95a5a6; border:1px solid #ddd;    border-radius:20px; padding:3px 10px; font-size:11px; font-weight:600; }
.bundle-actions { display:flex; gap:6px; align-items:center; }
.bundle-actions a { width:32px; height:32px; border-radius:7px; display:flex; align-items:center; justify-content:center; font-size:13px; text-decoration:none; transition:.2s; }
.bundle-actions a:hover { opacity:.8; transform:scale(1.08); }
.btn-edit   { background:#eaf4ff; color:#3498db; border:1px solid #bde; }
.btn-toggle { background:#fffbe6; color:#f39c12; border:1px solid #fde; }
.btn-view   { background:#eafaf1; color:#27ae60; border:1px solid #abe; }
.btn-del    { background:#fdecea; color:#e74c3c; border:1px solid #f5b7b1; }

/* ── Alerts ── */
.ps-alert { border-radius:8px; padding:12px 16px; margin-bottom:18px; font-size:13px; display:flex; gap:10px; align-items:center; }
.ps-alert-success { background:#eafaf1; border:1px solid #abebc6; color:#1e8449; }
.ps-alert-error   { background:#fdecea; border:1px solid #f5b7b1; color:#c0392b; }
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

<?php if (isset($_GET['saved'])): ?>
<div class="ps-alert ps-alert-success"><i class="fa fa-check-circle"></i> Bundle guardado correctamente.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
<div class="ps-alert ps-alert-success"><i class="fa fa-trash"></i> Bundle eliminado.</div>
<?php endif; ?>
<?php if ($errmsg): ?>
<div class="ps-alert ps-alert-error"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($errmsg); ?></div>
<?php endif; ?>

<!-- ══ INFO BANNER ══ -->
<div class="ps-info-banner">
    <i class="fa fa-gift"></i>
    <div>
        <h4>Bundles / Paquetes de productos</h4>
        <p>Un bundle es un paquete de varios productos vendidos juntos a un precio especial. Por ejemplo: "Kit Running" = Zapatillas + Medias + Camiseta por $450.000 en lugar de $580.000 por separado. El cliente obtiene un descuento y tú aumentas el ticket promedio de venta.</p>
    </div>
</div>

<!-- ══ FORMULARIO ══ -->
<div class="ps-card">
    <div class="ps-card-head">
        <div class="ps-icon" style="background:linear-gradient(135deg,#6c5ce7,#a29bfe)">
            <i class="fa fa-<?php echo $edit_bundle ? 'edit' : 'plus'; ?>"></i>
        </div>
        <h3><?php echo $edit_bundle ? 'Editar Bundle' : 'Crear nuevo Bundle'; ?></h3>
    </div>
    <div class="ps-card-body">
    <form method="post" id="bundle-form">
    <input type="hidden" name="bid" value="<?php echo $edit_bundle ? intval($edit_bundle['id']) : 0; ?>">

    <div class="ps-field-row">
        <div class="ps-field" style="flex:2">
            <label><i class="fa fa-tag" style="color:#6c5ce7;margin-right:4px"></i> Nombre del bundle *</label>
            <input type="text" name="name" placeholder="Ej: Kit Running Completo" required
                   value="<?php echo htmlspecialchars($edit_bundle['name'] ?? ''); ?>">
        </div>
        <div class="ps-field">
            <label><i class="fa fa-dollar" style="color:#27ae60;margin-right:4px"></i> Precio especial *</label>
            <input type="number" name="bundle_price" placeholder="450000" min="1" step="1" required
                   value="<?php echo $edit_bundle ? intval($edit_bundle['bundle_price']) : ''; ?>">
        </div>
        <div class="ps-field" style="max-width:130px">
            <label><i class="fa fa-toggle-on" style="color:#f39c12;margin-right:4px"></i> Estado</label>
            <select name="is_active">
                <option value="1" <?php echo (!$edit_bundle || $edit_bundle['is_active']) ? 'selected' : ''; ?>>Activo</option>
                <option value="0" <?php echo ($edit_bundle && !$edit_bundle['is_active']) ? 'selected' : ''; ?>>Inactivo</option>
            </select>
        </div>
    </div>

    <div class="ps-field" style="margin-bottom:18px">
        <label><i class="fa fa-align-left" style="color:#3498db;margin-right:4px"></i> Descripción (opcional)</label>
        <textarea name="description" rows="2" placeholder="Describe qué incluye este paquete y por qué es una buena oferta..."><?php echo htmlspecialchars($edit_bundle['description'] ?? ''); ?></textarea>
    </div>

    <div style="margin-bottom:18px">
        <label style="font-size:12px;font-weight:700;color:#555;display:block;margin-bottom:10px">
            <i class="fa fa-cubes" style="color:#6c5ce7;margin-right:4px"></i> Productos incluidos en el bundle *
        </label>
        <div class="ps-items-wrap">
            <div id="bundle-items">
            <?php
            $render_items = $edit_items ?: [['product_id'=>0,'quantity'=>1]];
            foreach ($render_items as $it):
            ?>
            <div class="ps-item-row">
                <select name="product_ids[]">
                    <option value="">— Selecciona un producto —</option>
                    <?php foreach ($all_products as $ap): ?>
                    <option value="<?php echo $ap['id']; ?>"
                        <?php echo (isset($it['product_id']) && $ap['id']==$it['product_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($ap['productName']); ?>
                        &nbsp;·&nbsp;$<?php echo number_format($ap['productPrice'],0,'.',','); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <span class="ps-item-label">Cant.</span>
                <input type="number" name="quantities[]" value="<?php echo intval($it['quantity'] ?? 1); ?>"
                       min="1" class="ps-item-qty">
                <button type="button" class="ps-btn-remove" title="Quitar producto"
                        onclick="if(document.querySelectorAll('.ps-item-row').length>1) this.closest('.ps-item-row').remove(); else alert('El bundle debe tener al menos un producto.')">
                    &times;
                </button>
            </div>
            <?php endforeach; ?>
            </div>
            <button type="button" class="btn-add-item" id="add-item">
                <i class="fa fa-plus-circle"></i> Agregar otro producto
            </button>
        </div>
        <p style="font-size:11px;color:#aaa;margin-top:8px;margin-bottom:0">
            <i class="fa fa-info-circle"></i> El precio especial del bundle debe ser menor al precio sumado de todos los productos por separado para que tenga sentido como oferta.
        </p>
    </div>

    <div style="display:flex;gap:10px;align-items:center">
        <button type="submit" name="save_bundle" class="btn-save-bundle">
            <i class="fa fa-save"></i> <?php echo $edit_bundle ? 'Actualizar bundle' : 'Crear bundle'; ?>
        </button>
        <?php if ($edit_bundle): ?>
        <a href="bundles.php" style="color:#888;font-size:13px;text-decoration:none"><i class="fa fa-times"></i> Cancelar edición</a>
        <?php endif; ?>
    </div>
    </form>
    </div>
</div>

<!-- ══ LISTA BUNDLES ══ -->
<div class="ps-card">
    <div class="ps-card-head">
        <div class="ps-icon" style="background:linear-gradient(135deg,#00b894,#00cec9)">
            <i class="fa fa-list"></i>
        </div>
        <h3>Bundles existentes</h3>
    </div>
    <div class="ps-card-body">
    <?php if (!$bundles_q || mysqli_num_rows($bundles_q) === 0): ?>
    <div style="text-align:center;padding:40px 0;color:#bbb">
        <i class="fa fa-gift" style="font-size:48px;display:block;margin-bottom:12px"></i>
        <p style="margin:0;font-size:14px">Aún no has creado ningún bundle.</p>
        <p style="margin:4px 0 0;font-size:12px">Crea tu primer paquete usando el formulario de arriba.</p>
    </div>
    <?php else: while ($b = mysqli_fetch_assoc($bundles_q)):
        $saving = floatval($b['original_total']) - floatval($b['bundle_price']);
        $saving_pct = ($b['original_total'] > 0) ? round($saving / $b['original_total'] * 100) : 0;
    ?>
    <div class="bundle-card">
        <div class="bundle-icon"><i class="fa fa-gift"></i></div>
        <div class="bundle-info">
            <h5><?php echo htmlspecialchars($b['name']); ?></h5>
            <div class="meta">
                <?php echo intval($b['item_count']); ?> producto(s)
                <?php if ($b['description']): ?>
                &nbsp;·&nbsp; <?php echo htmlspecialchars(mb_substr($b['description'],0,60)); ?>…
                <?php endif; ?>
            </div>
            <div style="margin-top:6px">
                <?php echo $b['is_active']
                    ? '<span class="badge-active"><i class="fa fa-circle" style="font-size:7px"></i> Activo</span>'
                    : '<span class="badge-inactive"><i class="fa fa-circle" style="font-size:7px"></i> Inactivo</span>'; ?>
            </div>
        </div>
        <div class="bundle-prices">
            <div class="price-bundle">$<?php echo number_format($b['bundle_price'],0,'.',','); ?></div>
            <?php if ($b['original_total'] > 0): ?>
            <div class="price-original">$<?php echo number_format($b['original_total'],0,'.',','); ?></div>
            <?php if ($saving > 0): ?>
            <div class="price-saving"><i class="fa fa-arrow-down"></i> Ahorras $<?php echo number_format($saving,0,'.',','); ?> (<?php echo $saving_pct; ?>%)</div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="bundle-actions">
            <a href="bundles.php?edit=<?php echo $b['id']; ?>" class="btn-edit" title="Editar"><i class="fa fa-pencil"></i></a>
            <a href="bundles.php?toggle=<?php echo $b['id']; ?>" class="btn-toggle" title="<?php echo $b['is_active'] ? 'Desactivar' : 'Activar'; ?>">
                <i class="fa fa-<?php echo $b['is_active'] ? 'pause' : 'play'; ?>"></i>
            </a>
            <a href="../bundle.php?id=<?php echo $b['id']; ?>" class="btn-view" title="Ver en tienda" target="_blank"><i class="fa fa-eye"></i></a>
            <a href="bundles.php?delete=<?php echo $b['id']; ?>" class="btn-del" title="Eliminar"
               onclick="return confirm('¿Eliminar el bundle «<?php echo htmlspecialchars(addslashes($b['name'])); ?>»? Esta acción no se puede deshacer.')">
                <i class="fa fa-trash"></i>
            </a>
        </div>
    </div>
    <?php endwhile; endif; ?>
    </div>
</div>

</div><!-- .content -->
</div><!-- .span9 -->
</div><!-- .row -->
</div><!-- .container -->
</div><!-- .wrapper -->

<?php include('include/footer.php'); ?>
<script>
var allProducts = <?php echo json_encode(array_map(function($p){
    return ['id'=>intval($p['id']), 'name'=>$p['productName'], 'price'=>floatval($p['productPrice'])];
}, $all_products)); ?>;

function buildSelect(selectedId) {
    var opts = '<option value="">— Selecciona un producto —</option>';
    allProducts.forEach(function(p){
        var sel = (p.id === selectedId) ? ' selected' : '';
        opts += '<option value="'+p.id+'"'+sel+'>'+p.name+'&nbsp;·&nbsp;$'+p.price.toLocaleString('es-CO',{maximumFractionDigits:0})+'</option>';
    });
    return opts;
}

document.getElementById('add-item').addEventListener('click', function(){
    var div = document.createElement('div');
    div.className = 'ps-item-row';
    div.innerHTML =
        '<select name="product_ids[]">'+buildSelect(0)+'</select>'+
        '<span class="ps-item-label">Cant.</span>'+
        '<input type="number" name="quantities[]" value="1" min="1" class="ps-item-qty">'+
        '<button type="button" class="ps-btn-remove" title="Quitar" '+
        'onclick="if(document.querySelectorAll(\'.ps-item-row\').length>1) this.closest(\'.ps-item-row\').remove(); else alert(\'El bundle debe tener al menos un producto.\')">'+
        '&times;</button>';
    document.getElementById('bundle-items').appendChild(div);
    div.querySelector('select').focus();
});

// Calcular ahorro en tiempo real
document.getElementById('bundle-form').addEventListener('input', function(){
    var total = 0;
    document.querySelectorAll('#bundle-items .ps-item-row').forEach(function(row){
        var sel = row.querySelector('select');
        var qty = parseInt(row.querySelector('input[type=number]').value) || 1;
        if (!sel.value) return;
        var prod = allProducts.find(function(p){ return p.id === parseInt(sel.value); });
        if (prod) total += prod.price * qty;
    });
    var bundlePrice = parseFloat(document.querySelector('input[name=bundle_price]').value) || 0;
    var hint = document.getElementById('saving-hint');
    if (!hint) {
        hint = document.createElement('p');
        hint.id = 'saving-hint';
        hint.style.cssText = 'font-size:12px;margin-top:6px;font-weight:600';
        document.querySelector('input[name=bundle_price]').closest('.ps-field').appendChild(hint);
    }
    if (total > 0 && bundlePrice > 0) {
        var saving = total - bundlePrice;
        var pct = Math.round(saving / total * 100);
        if (saving > 0) {
            hint.style.color = '#27ae60';
            hint.innerHTML = '<i class="fa fa-check-circle"></i> Precio original: $'+total.toLocaleString('es-CO',{maximumFractionDigits:0})+' — el cliente ahorra $'+saving.toLocaleString('es-CO',{maximumFractionDigits:0})+' ('+pct+'%)';
        } else {
            hint.style.color = '#e74c3c';
            hint.innerHTML = '<i class="fa fa-exclamation-circle"></i> El precio del bundle es mayor o igual al precio original. No representa ahorro para el cliente.';
        }
    } else {
        if (hint) hint.innerHTML = '';
    }
});
</script>
</body>
</html>
