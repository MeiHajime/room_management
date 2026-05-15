<?php
// search.php — Trang tìm kiếm nâng cao (redirect về index.php với params)
require_once __DIR__ . '/includes/functions.php';
$params = array_filter([
    'q'          => trim($_GET['q'] ?? ''),
    'tinh_thanh' => trim($_GET['tinh_thanh'] ?? ''),
    'loai'       => (int)($_GET['loai'] ?? 0) ?: '',
    'gia_min'    => (int)($_GET['gia_min'] ?? 0) ?: '',
    'gia_max'    => (int)($_GET['gia_max'] ?? 0) ?: '',
]);
redirect(BASE_URL . '/index.php?' . http_build_query($params));
