<?php
// ============================================
// user/export.php — Export Purchased Cards as CSV
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please sign in to export cards.');
    redirect('/auth/login.php');
}

$uid = (int)$_SESSION['user_id'];
$db = getDB();

$items = $db->query("
    SELECT oi.*, o.order_code, o.created_at as order_date
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.user_id = $uid
    ORDER BY o.created_at DESC
");
$db->close();

$filename = 'cards_export_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Order Code',
    'Card Number',
    'Expiry',
    'CVV',
    'Name',
    'BIN',
    'Brand',
    'Type',
    'Email',
    'Phone',
    'Address',
    'Country',
    'Price',
    'Date',
    'Check Status',
]);

while ($item = $items->fetch_assoc()) {
    $cardNumber = decryptData($item['card_number']);
    $cvv = decryptData($item['card_cvv']);
    $fullAddress = trim(implode(', ', array_filter([
        $item['card_address'],
        $item['card_city'],
        $item['card_state'],
        $item['card_zip'],
    ])));

    fputcsv($output, [
        $item['order_code'],
        $cardNumber,
        $item['card_expiry'],
        $cvv,
        $item['card_name'],
        $item['card_bin'],
        getCardBrandLabel($item['card_brand'] ?? ''),
        ucfirst($item['card_type'] ?? 'Credit'),
        $item['card_email'],
        $item['card_phone'],
        $fullAddress,
        $item['card_country'],
        number_format((float)$item['price'], 2),
        $item['order_date'],
        getCardStatusLabel($item['check_status'] ?? 'unchecked'),
    ]);
}

fclose($output);
exit;
