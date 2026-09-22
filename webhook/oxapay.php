<?php
// ============================================
// webhook/oxapay.php — Oxapay Webhook Handler
// Auto-credits balance when payment is confirmed
// ============================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/oxapay.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get webhook signature
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? $_SERVER['HTTP_SIGNATURE'] ?? '';

// Get raw POST data
$payload = json_decode(file_get_contents('php://input'), true);

if (!$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

// Verify signature
if ($signature && !verifyOxapayWebhook($payload, $signature)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Extract payment data
$status = $payload['status'] ?? '';
$orderId = $payload['orderId'] ?? '';
$paymentId = $payload['id'] ?? $payload['paymentId'] ?? '';
$txHash = $payload['txHash'] ?? '';

// Log webhook for debugging
error_log("Oxapay Webhook: status=$status, orderId=$orderId, paymentId=$paymentId");

$db = getDB();

// Find the deposit record
$stmt = $db->prepare("SELECT * FROM deposits WHERE oxapay_id=? OR oxapay_address=? ORDER BY id DESC LIMIT 1");
$lookupId = $paymentId ?: $orderId;
$stmt->bind_param("ss", $lookupId, $lookupId);
$stmt->execute();
$deposit = $stmt->get_result()->fetch_assoc();

if (!$deposit) {
    $db->close();
    http_response_code(404);
    echo json_encode(['error' => 'Deposit not found']);
    exit;
}

// Handle payment status
if (in_array($status, ['paid', 'completed', 'confirmed', 'success'])) {
    if ($deposit['status'] !== 'paid') {
        // Credit user balance (amount + bonus)
        $userId = (int)$deposit['user_id'];
        $amount = (float)$deposit['amount'];
        $bonus  = (float)($deposit['bonus'] ?? 0);
        $totalCredit = $amount + $bonus;

        $db->begin_transaction();

        try {
            // Update deposit status
            $upd = $db->prepare("UPDATE deposits SET status='paid', tx_hash=?, paid_at=NOW() WHERE id=?");
            $upd->bind_param("si", $txHash, $deposit['id']);
            $upd->execute();

            // Add to user balance (deposit amount + any bonus)
            $bal = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $bal->bind_param("di", $totalCredit, $userId);
            $bal->execute();

            $db->commit();

            $notifMsg = "$" . number_format($amount, 2) . " has been added to your wallet.";
            if ($bonus > 0) {
                $notifMsg .= " (+$" . number_format($bonus, 2) . " bonus!)";
            }
            createNotification($userId, 'Deposit Received', $notifMsg, 'deposit', '/user/balance.php');
            createNotification(1, 'Deposit Completed', "User #$userId deposited $$amount" . ($bonus > 0 ? " + $$bonus bonus" : "") . " via Oxapay.", 'deposit', '/admin/orders.php');

            error_log("Oxapay: Credited $$totalCredit (amount=$$amount, bonus=$$bonus) to user $userId for deposit #{$deposit['id']}");
        } catch (Exception $e) {
            $db->rollback();
            error_log("Oxapay Webhook Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to process']);
            exit;
        }
    }
} elseif (in_array($status, ['expired', 'cancelled', 'failed'])) {
    // Mark deposit as failed
    $upd = $db->prepare("UPDATE deposits SET status=? WHERE id=?");
    $upd->bind_param("si", $status, $deposit['id']);
    $upd->execute();

    error_log("Oxapay: Deposit #{$deposit['id']} marked as $status");
}

$db->close();

http_response_code(200);
echo json_encode(['success' => true]);
?>