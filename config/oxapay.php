<?php
// ============================================
// config/oxapay.php — Oxapay Crypto Payment API
// Supports: USDT (TRC20/ERC20), BTC
// ============================================

require_once __DIR__ . '/database.php';

function getOxapaySettings() {
    $db = getDB();
    $res = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'oxapay_%'");
    $settings = [];
    while ($row = $res->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $db->close();
    return $settings;
}

function getSetting($key) {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key=?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $db->close();
    return $result ? $result['setting_value'] : '';
}

function updateSetting($key, $value) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
    $stmt->bind_param("sss", $key, $value, $value);
    $stmt->execute();
    $db->close();
}

// ── CREATE OXAPAY PAYMENT ─────────────────────
function createOxapayPayment(float $amount, string $currency, int $userId, string $orderCode = '') {
    $settings = getOxapaySettings();
    $apiKey = $settings['oxapay_api_key'] ?? '';
    $apiSecret = $settings['oxapay_api_secret'] ?? '';

    if (!$apiKey || !$apiSecret) {
        return ['success' => false, 'error' => 'Oxapay API not configured. Contact admin.'];
    }

    $callbackUrl = ($settings['oxapay_webhook_url'] ?? '') ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');

    // Oxapay API endpoint
    $url = 'https://api.oxapay.com/v1/payment/create';

    $payload = [
        'merchant' => $apiKey,
        'amount' => $amount,
        'currency' => 'USD',
        'crypto' => $currency,
        'callbackUrl' => $callbackUrl,
        'orderId' => $orderCode ?: 'DEP-' . strtoupper(bin2hex(random_bytes(6))),
        'description' => "Deposit $amount USD via $currency",
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiSecret,
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'error' => 'Failed to connect to Oxapay API.'];
    }

    $data = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300 && isset($data['data']['payUrl'])) {
        return [
            'success' => true,
            'payment_id' => $data['data']['id'] ?? '',
            'pay_url' => $data['data']['payUrl'] ?? '',
            'address' => $data['data']['address'] ?? '',
            'network' => $data['data']['network'] ?? '',
            'amount' => $data['data']['amount'] ?? $amount,
            'currency' => $data['data']['crypto'] ?? $currency,
            'order_id' => $payload['orderId'],
        ];
    }

    $errorMsg = $data['message'] ?? $data['error'] ?? 'Unknown error';
    return ['success' => false, 'error' => $errorMsg];
}

// ── VERIFY OXAPAY WEBHOOK ─────────────────────
function verifyOxapayWebhook(array $payload, string $receivedSignature): bool {
    $settings = getOxapaySettings();
    $apiSecret = $settings['oxapay_api_secret'] ?? '';

    if (!$apiSecret) return false;

    $expectedSignature = hash_hmac('sha256', json_encode($payload), $apiSecret);
    return hash_equals($expectedSignature, $receivedSignature);
}

// ── CHECK PAYMENT STATUS ──────────────────────
function checkOxapayStatus(string $paymentId): array {
    $settings = getOxapaySettings();
    $apiKey = $settings['oxapay_api_key'] ?? '';
    $apiSecret = $settings['oxapay_api_secret'] ?? '';

    if (!$apiKey || !$apiSecret) {
        return ['success' => false, 'error' => 'Oxapay API not configured.'];
    }

    $url = 'https://api.oxapay.com/v1/payment/status/' . $paymentId;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiSecret,
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'error' => 'Failed to check status.'];
    }

    $data = json_decode($response, true);
    return ['success' => true, 'status' => $data['data']['status'] ?? 'unknown', 'data' => $data['data'] ?? []];
}
?>