<?php
function sendTelegramMessage(string $message): bool {
    $token = getSetting('telegram_bot_token');
    $chatId = getSetting('telegram_chat_id');
    if (!$token || !$chatId) return false;

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result !== false;
}

function tgNotifyNewOrder(array $order, array $items): void {
    if (getSetting('telegram_notify_new_order') !== '1') return;
    $user = currentUser();
    $username = $user ? $user['username'] : 'Guest';
    $code = $order['order_code'] ?? 'N/A';
    $total = number_format($order['total_price'] ?? 0, 2);
    $count = count($items);
    $brands = array_unique(array_column($items, 'card_brand'));
    $brandStr = implode(', ', array_map('ucfirst', $brands));

    $msg = "🛒 <b>New Order</b>\n";
    $msg .= "Order: <code>{$code}</code>\n";
    $msg .= "User: {$username}\n";
    $msg .= "Items: {$count} card(s) [{$brandStr}]\n";
    $msg .= "Total: \${$total}\n";
    $msg .= "Method: " . ($order['payment_method'] ?? 'wallet');
    sendTelegramMessage($msg);
}

function tgNotifyDeposit(array $deposit): void {
    if (getSetting('telegram_notify_deposit') !== '1') return;
    $amount = number_format($deposit['amount'], 2);
    $bonus = number_format($deposit['bonus'] ?? 0, 2);
    $currency = $deposit['currency'] ?? 'USDT';
    $status = $deposit['status'] ?? 'pending';

    $msg = "💰 <b>Deposit</b>\n";
    $msg .= "Amount: {$amount} {$currency}\n";
    if (!empty($deposit['bonus']) && (float)$deposit['bonus'] > 0) $msg .= "Bonus: +{$bonus}\n";
    $msg .= "Status: {$status}";
    sendTelegramMessage($msg);
}

function tgNotifyChecker(array $check, string $cardMasked): void {
    if (getSetting('telegram_notify_checker') !== '1') return;
    $result = strtoupper($check['result'] ?? 'unknown');
    $color = $result === 'LIVE' ? '🟢' : ($result === 'DEAD' ? '🔴' : '🟡');

    $msg = "{$color} <b>Card Check</b>\n";
    $msg .= "Card: <code>{$cardMasked}</code>\n";
    $msg .= "Result: {$result}\n";
    $msg .= "Cost: \$" . number_format($check['cost'] ?? 0, 2);
    sendTelegramMessage($msg);
}
