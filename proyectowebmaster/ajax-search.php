<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/search-engine.php');

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode([]); exit; }

$result = ps_search($con, $q, 8, 0, 'relevance');

$out = [];
foreach ($result['rows'] as $row) {
    $img = 'admin/productimages/' . $row['id'] . '/' . ($row['productImage'] ?? '');
    $out[] = [
        'id'    => (int)$row['id'],
        'name'  => $row['productName'],
        'img'   => $img,
        'price' => (float)$row['productPrice'],
        'avail' => $row['productAvailability'],
        'score' => (int)$row['relevance_score'],
    ];
}

// Si no hay resultados exactos, sugerir alternativa
if (empty($out)) {
    $suggestion = ps_search_did_you_mean($con, $q);
    echo json_encode(['results' => [], 'did_you_mean' => $suggestion]);
} else {
    echo json_encode(['results' => $out, 'did_you_mean' => null]);
}
