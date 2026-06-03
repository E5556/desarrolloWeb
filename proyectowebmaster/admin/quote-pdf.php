<?php
session_start();
error_reporting(0);
include('include/config.php');
if (empty($_SESSION['alogin'])) { header('location:index.php'); exit(); }

$qid = intval($_GET['id'] ?? 0);
if ($qid <= 0) { header('location:quotations.php'); exit(); }

$q = mysqli_fetch_assoc(mysqli_query($con,
    "SELECT q.*,u.name as client_name,u.email as client_email,
            u.billingAddress,u.billingCity,u.billingState,
            a.username as asesor_name
     FROM quotations q JOIN users u ON u.id=q.client_id JOIN admin a ON a.id=q.admin_id
     WHERE q.id=$qid LIMIT 1"));
if (!$q) { header('location:quotations.php'); exit(); }

$items_q = mysqli_query($con,
    "SELECT qi.*,p.productName,p.productPrice,COALESCE(s.name,'—') as supplier_name
     FROM quotation_items qi JOIN products p ON p.id=qi.product_id
     LEFT JOIN suppliers s ON s.id=qi.supplier_id
     WHERE qi.quotation_id=$qid");
$items=[]; $total=0;
while($r=mysqli_fetch_assoc($items_q)){$items[]=$r; $total+=$r['quantity']*$r['unit_price'];}

$site_q = mysqli_query($con,"SELECT setting_key,setting_value FROM settings WHERE setting_key IN ('site_name','footer_email','footer_phone','footer_address')");
$cfg=[]; while($r=mysqli_fetch_assoc($site_q)) $cfg[$r['setting_key']]=$r['setting_value'];
$site_name = $cfg['site_name']??'Personal Shopper';
$valid_until = date('d/m/Y', strtotime($q['created_at'].' +'.$q['valid_days'].' days'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Cotización <?php echo htmlspecialchars($q['quote_ref']);?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,sans-serif;font-size:12px;color:#333;background:#fff}
.wrap{max-width:780px;margin:30px auto;padding:40px;border:1px solid #ddd}
.header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #8e44ad;padding-bottom:18px;margin-bottom:22px}
.logo{font-size:22px;font-weight:700;color:#8e44ad}
.logo small{display:block;font-size:11px;color:#888;font-weight:400}
.title-box{text-align:right}
.title-box h2{font-size:20px;color:#8e44ad;margin-bottom:4px}
.title-box .ref{font-size:13px;color:#555}
.info-grid{display:flex;gap:30px;margin-bottom:20px}
.info-box{flex:1;background:#f9f9f9;border-radius:6px;padding:12px 14px}
.info-box h4{font-size:11px;text-transform:uppercase;color:#888;margin-bottom:8px;letter-spacing:.5px}
.info-box p{font-size:12px;color:#333;line-height:1.6;margin:0}
table{width:100%;border-collapse:collapse;margin-bottom:18px}
thead tr{background:#8e44ad;color:#fff}
thead th{padding:9px 10px;text-align:left;font-size:11px;font-weight:700}
tbody tr:nth-child(even){background:#f9f6ff}
tbody td{padding:8px 10px;border-bottom:1px solid #eee;font-size:12px}
.total-row{background:#f0e8ff;font-weight:700}
.footer-note{margin-top:20px;padding:12px;background:#f9f9f9;border-radius:6px;font-size:11px;color:#666;border-left:3px solid #8e44ad}
.status-tag{display:inline-block;padding:3px 10px;border-radius:10px;font-size:11px;font-weight:700;color:#fff;background:#8e44ad}
@media print{.no-print{display:none}.wrap{margin:0;border:none;padding:20px}}
</style>
</head>
<body>
<div class="wrap">

<div class="no-print" style="margin-bottom:16px;display:flex;gap:8px">
    <button onclick="window.print()" style="background:#8e44ad;color:#fff;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;font-size:13px">🖨 Imprimir / Guardar PDF</button>
    <a href="quotations.php" style="background:#888;color:#fff;border:none;padding:8px 16px;border-radius:4px;text-decoration:none;font-size:13px">← Volver</a>
</div>

<div class="header">
    <div>
        <div class="logo"><?php echo htmlspecialchars($site_name);?><small><?php echo htmlspecialchars($cfg['footer_email']??'');?></small></div>
        <?php if(!empty($cfg['footer_phone'])):?><p style="font-size:11px;color:#888;margin-top:4px">📞 <?php echo htmlspecialchars($cfg['footer_phone']);?></p><?php endif;?>
    </div>
    <div class="title-box">
        <h2>COTIZACIÓN</h2>
        <div class="ref"><?php echo htmlspecialchars($q['quote_ref']);?></div>
        <div style="margin-top:6px"><span class="status-tag"><?php echo $q['status'];?></span></div>
        <div style="font-size:11px;color:#888;margin-top:4px">Válida hasta: <strong><?php echo $valid_until;?></strong></div>
    </div>
</div>

<div class="info-grid">
    <div class="info-box">
        <h4>Cliente</h4>
        <p><strong><?php echo htmlspecialchars($q['client_name']);?></strong><br>
        <?php echo htmlspecialchars($q['client_email']);?><br>
        <?php if($q['billingCity']):?><?php echo htmlspecialchars($q['billingCity'].', '.$q['billingState']);?><?php endif;?>
        </p>
    </div>
    <div class="info-box">
        <h4>Asesor</h4>
        <p><strong><?php echo htmlspecialchars($q['asesor_name']);?></strong><br>
        Fecha: <?php echo date('d/m/Y', strtotime($q['created_at']));?><br>
        Válida por <?php echo $q['valid_days'];?> días</p>
    </div>
</div>

<table>
    <thead><tr>
        <th>#</th><th>Producto</th><th>Proveedor</th><th style="text-align:center">Cant.</th>
        <th style="text-align:right">Precio unit.</th><th style="text-align:right">Subtotal</th>
    </tr></thead>
    <tbody>
    <?php foreach($items as $i=>$item):?>
    <tr>
        <td><?php echo $i+1;?></td>
        <td><strong><?php echo htmlspecialchars($item['productName']);?></strong></td>
        <td style="color:#8e44ad;font-size:11px"><?php echo htmlspecialchars($item['supplier_name']);?></td>
        <td style="text-align:center"><?php echo $item['quantity'];?></td>
        <td style="text-align:right">$<?php echo number_format($item['unit_price'],0,'.',',');?></td>
        <td style="text-align:right;font-weight:700">$<?php echo number_format($item['quantity']*$item['unit_price'],0,'.',',');?></td>
    </tr>
    <?php endforeach;?>
    <tr class="total-row">
        <td colspan="5" style="text-align:right;padding:10px">TOTAL</td>
        <td style="text-align:right;padding:10px;font-size:14px;color:#8e44ad">$<?php echo number_format($total,0,'.',',');?></td>
    </tr>
    </tbody>
</table>

<?php if($q['notes']):?>
<div class="footer-note">
    <strong>Notas y condiciones:</strong><br><?php echo nl2br(htmlspecialchars($q['notes']));?>
</div>
<?php endif;?>

<div style="margin-top:20px;font-size:11px;color:#aaa;text-align:center;border-top:1px solid #eee;padding-top:14px">
    Cotización generada por <?php echo htmlspecialchars($site_name);?> — <?php echo date('d/m/Y H:i');?>
</div>
</div>
</body></html>
