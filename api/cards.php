<?php
// ============================================
// api/cards.php — List / Get Cards
// ============================================

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed. Use GET.'], 405);
}

$userId = authenticateApi();
if ($userId === null) {
    jsonResponse(['success' => false, 'error' => 'Authentication failed'], 401);
}

$db = getDB();

$where = "WHERE p.stock > 0 AND p.vendor_status='approved'";
$params = [];
$types = '';

$brand = trim($_GET['brand'] ?? '');
if ($brand !== '') {
    $where .= " AND p.card_brand = ?";
    $params[] = $brand;
    $types .= 's';
}

$country = trim($_GET['country'] ?? '');
if ($country !== '') {
    $where .= " AND p.card_country = ?";
    $params[] = $country;
    $types .= 's';
}

$type = trim($_GET['type'] ?? '');
if ($type !== '' && in_array($type, ['credit', 'debit'], true)) {
    $where .= " AND p.card_type = ?";
    $params[] = $type;
    $types .= 's';
}

$bin = trim($_GET['bin'] ?? '');
if ($bin !== '') {
    $where .= " AND p.card_bin LIKE ?";
    $params[] = "%$bin%";
    $types .= 's';
}

$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;

if ($minPrice !== null) {
    $where .= " AND p.price >= ?";
    $params[] = $minPrice;
    $types .= 'd';
}
if ($maxPrice !== null) {
    $where .= " AND p.price <= ?";
    $params[] = $maxPrice;
    $types .= 'd';
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(*) as total FROM products p $where";
if (!empty($params)) {
    $countStmt = $db->prepare($countSql);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total = (int)$countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();
} else {
    $total = (int)$db->query($countSql)->fetch_assoc()['total'];
}
$totalPages = max(1, (int)ceil($total / $limit));

$sql = "SELECT p.id, p.price, p.card_bin, p.card_expiry, p.card_type, p.card_brand,
               p.card_country, p.bank_name, p.card_balance_min, p.card_balance_max,
               p.card_vbv, p.card_status, p.stock, p.sales
        FROM products p $where
        ORDER BY p.id DESC
        LIMIT $limit OFFSET $offset";

if (!empty($params)) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $rows = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}
$db->close();

$cards = [];
foreach ($rows as $row) {
    $expiry = $row['card_expiry'];
    if ($expiry && strpos($expiry, '/') !== false) {
        $parts = explode('/', $expiry);
        $expiry = 'XX/' . ($parts[1] ?? '**');
    } else {
        $expiry = '**/**';
    }

    $cards[] = [
        'id'            => (int)$row['id'],
        'price'         => (float)$row['price'],
        'brand'         => $row['card_brand'],
        'bin'           => $row['card_bin'],
        'expiry'        => $expiry,
        'country'       => $row['card_country'] ?: null,
        'bank_name'     => $row['bank_name'] ?: null,
        'balance_range' => formatBalanceRange(
            $row['card_balance_min'] !== null ? (float)$row['card_balance_min'] : null,
            $row['card_balance_max'] !== null ? (float)$row['card_balance_max'] : null
        ),
        'vbv'           => $row['card_vbv'],
        'card_status'   => $row['card_status'],
        'card_type'     => $row['card_type'],
        'stock'         => (int)$row['stock'],
        'sales'         => (int)$row['sales'],
    ];
}

jsonResponse([
    'success' => true,
    'data' => [
        'cards' => $cards,
        'pagination' => [
            'current_page' => $page,
            'per_page'     => $limit,
            'total'        => $total,
            'total_pages'  => $totalPages,
        ],
    ],
]);
