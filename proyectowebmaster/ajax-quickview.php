<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/price-display.php');

header('Content-Type: application/json; charset=utf-8');

$pid = intval($_GET['pid'] ?? 0);
if (!$pid) { echo json_encode(['ok'=>false]); exit; }

$res = mysqli_query($con, "SELECT p.*, c.categoryName, s.subcategory AS subcategoryName
    FROM products p
    LEFT JOIN category c ON c.id = p.category
    LEFT JOIN subcategory s ON s.id = p.subCategory
    WHERE p.id = $pid LIMIT 1");

if (!$res || !($row = mysqli_fetch_assoc($res))) {
    echo json_encode(['ok'=>false]);
    exit;
}

// Average rating
$rq = mysqli_query($con, "SELECT AVG(productRating) as avg, COUNT(*) as cnt FROM productreviews WHERE productId=$pid");
$rdata = $rq ? mysqli_fetch_assoc($rq) : ['avg'=>0,'cnt'=>0];

echo json_encode([
    'ok'    => true,
    'id'    => (int)$row['id'],
    'name'  => $row['productName'],
    'img1'  => 'admin/productimages/'.$row['id'].'/'.$row['productImage1'],
    'img2'  => $row['productImage2'] ? 'admin/productimages/'.$row['id'].'/'.$row['productImage2'] : null,
    'price' => (float)$row['productPrice'],
    'ship'  => (float)$row['shippingCharge'],
    'avail' => $row['productAvailability'],
    'brand' => $row['productCompany'] ?? '',
    'cat'   => $row['categoryName'] ?? '',
    'desc'  => strip_tags(substr($row['productDescription'], 0, 300)),
    'rating'=> round((float)$rdata['avg'], 1),
    'reviews' => (int)$rdata['cnt'],
]);
