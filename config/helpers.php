<?php
// ============================================
// config/helpers.php  — Multi-Vendor Edition
// ============================================

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    $db  = getDB();
    $id  = (int)$_SESSION['user_id'];
    $res = $db->query("SELECT * FROM users WHERE id = $id");
    $user = $res ? $res->fetch_assoc() : null;
    $db->close();
    return $user;
}

function isAdmin() {
    $user = currentUser();
    return $user && $user['role'] === 'admin';
}

// Vendor: must be role=vendor AND vendor_status=approved
function isVendor() {
    $user = currentUser();
    return $user && $user['role'] === 'vendor' && $user['vendor_status'] === 'approved';
}

function isVendorPending() {
    $user = currentUser();
    return $user && $user['role'] === 'vendor' && $user['vendor_status'] === 'pending';
}

function requireAdmin() {
    if (!isAdmin()) {
        setFlash('error', 'Admin access required. 🔒');
        redirect('/auth/login.php');
    }
}

function requireVendor() {
    if (!isVendor()) {
        setFlash('error', 'Vendor access required. 🔒');
        redirect('/auth/login.php');
    }
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function clean($str) {
    return htmlspecialchars(strip_tags(trim((string)$str)), ENT_QUOTES, 'UTF-8');
}

function price($amount) {
    return $amount == 0 ? 'FREE' : '$' . number_format($amount, 2);
}

function generateOrderCode() {
    return 'ORD-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}

function setFlash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function showFlash() {
    $f = getFlash();
    if (!$f) return '';
    $color = $f['type'] === 'success' ? '#00c853' : '#ff4444';
    $bg    = $f['type'] === 'success' ? 'rgba(0,200,83,.12)' : 'rgba(255,68,68,.12)';
    return "<div style='background:$bg;border:1px solid $color;color:$color;
            border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:14px'>
            {$f['msg']}</div>";
}

// ── WALLET / BALANCE FUNCTIONS ────────────────

function getBalance(int $userId): float {
    $db = getDB();
    $stmt = $db->prepare("SELECT balance FROM users WHERE id=?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $db->close();
    return $result ? (float)$result['balance'] : 0.00;
}

function addBalance(int $userId, float $amount): bool {
    if ($amount <= 0) return false;
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $userId);
    $result = $stmt->execute();
    $db->close();
    return $result;
}

function deductBalance(int $userId, float $amount): bool {
    if ($amount <= 0) return false;
    $current = getBalance($userId);
    if ($current < $amount) return false;
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $userId);
    $result = $stmt->execute();
    $db->close();
    return $result;
}

function hasEnoughBalance(int $userId, float $amount): bool {
    return getBalance($userId) >= $amount;
}

function formatBalance(float $amount): string {
    return '$' . number_format($amount, 2);
}

// ── CARD / ENCRYPTION FUNCTIONS ───────────────

define('ENC_KEY', 'pepecc2024secretkey!@#$%^&*()_+ab'); // 32 chars for AES-256
define('ENC_METHOD', 'aes-256-cbc');

function encryptData(string $data): string {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENC_METHOD));
    $encrypted = openssl_encrypt($data, ENC_METHOD, ENC_KEY, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptData(string $data): string {
    $decoded = base64_decode($data);
    $ivLength = openssl_cipher_iv_length(ENC_METHOD);
    $iv = substr($decoded, 0, $ivLength);
    $encrypted = substr($decoded, $ivLength);
    return openssl_decrypt($encrypted, ENC_METHOD, ENC_KEY, OPENSSL_RAW_DATA, $iv);
}

function maskCard(string $number): string {
    $clean = preg_replace('/\D/', '', $number);
    if (strlen($clean) < 8) return $clean;
    $first4 = substr($clean, 0, 4);
    $last4 = substr($clean, -4);
    return $first4 . ' •••• •••• ' . $last4;
}

function maskCVV(string $cvv): string {
    return '***';
}

function maskEmail(string $email): string {
    $parts = explode('@', $email);
    if (count($parts) !== 2) return $email;
    $name = $parts[0];
    $domain = $parts[1];
    $masked = substr($name, 0, 2) . str_repeat('*', max(1, strlen($name) - 2));
    return $masked . '@' . $domain;
}

function getCardBrandColor(string $brand): string {
    switch($brand) {
        case 'visa': return '#1a1f71';
        case 'mastercard': return '#eb001b';
        case 'amex': return '#006fcf';
        case 'discover': return '#ff6600';
        case 'jcb': return '#0066b2';
        case 'diners': return '#0079be';
        default: return '#333333';
    }
}

function getCardBrandGradient(string $brand): string {
    switch($brand) {
        case 'visa': return 'linear-gradient(135deg, #1a1f71, #2557a7)';
        case 'mastercard': return 'linear-gradient(135deg, #eb001b, #f79e1b)';
        case 'amex': return 'linear-gradient(135deg, #006fcf, #00a3e0)';
        case 'discover': return 'linear-gradient(135deg, #ff6600, #ffb347)';
        case 'jcb': return 'linear-gradient(135deg, #0066b2, #00a3e0)';
        case 'diners': return 'linear-gradient(135deg, #0079be, #00a3e0)';
        default: return 'linear-gradient(135deg, #333, #555)';
    }
}

function getCardBrandLabel(string $brand): string {
    switch($brand) {
        case 'visa': return 'VISA';
        case 'mastercard': return 'Mastercard';
        case 'amex': return 'AMEX';
        case 'discover': return 'Discover';
        case 'jcb': return 'JCB';
        case 'diners': return 'Diners Club';
        default: return strtoupper($brand);
    }
}

function getCountryList(): array {
    return [
        'Afghanistan','Albania','Algeria','Andorra','Angola','Argentina','Armenia',
        'Australia','Austria','Azerbaijan','Bahamas','Bahrain','Bangladesh','Barbados',
        'Belarus','Belgium','Belize','Benin','Bhutan','Bolivia','Bosnia and Herzegovina',
        'Botswana','Brazil','Brunei','Bulgaria','Burkina Faso','Burundi','Cambodia',
        'Cameroon','Canada','Central African Republic','Chad','Chile','China','Colombia',
        'Comoros','Congo','Costa Rica','Croatia','Cuba','Cyprus','Czech Republic',
        'Denmark','Djibouti','Dominica','Dominican Republic','Ecuador','Egypt',
        'El Salvador','Equatorial Guinea','Eritrea','Estonia','Ethiopia','Fiji',
        'Finland','France','Gabon','Gambia','Georgia','Germany','Ghana','Greece',
        'Grenada','Guatemala','Guinea','Guinea-Bissau','Guyana','Haiti','Honduras',
        'Hungary','Iceland','India','Indonesia','Iran','Iraq','Ireland','Israel',
        'Italy','Jamaica','Japan','Jordan','Kazakhstan','Kenya','Kiribati','Kosovo',
        'Kuwait','Kyrgyzstan','Laos','Latvia','Lebanon','Lesotho','Liberia','Libya',
        'Liechtenstein','Lithuania','Luxembourg','Madagascar','Malawi','Malaysia',
        'Maldives','Mali','Malta','Marshall Islands','Mauritania','Mauritius','Mexico',
        'Micronesia','Moldova','Monaco','Mongolia','Montenegro','Morocco','Mozambique',
        'Myanmar','Namibia','Nauru','Nepal','Netherlands','New Zealand','Nicaragua',
        'Niger','Nigeria','North Korea','North Macedonia','Norway','Oman','Pakistan',
        'Palau','Palestine','Panama','Papua New Guinea','Paraguay','Peru','Philippines',
        'Poland','Portugal','Qatar','Romania','Russia','Rwanda','Saint Kitts and Nevis',
        'Saint Lucia','Saint Vincent and the Grenadines','Samoa','San Marino',
        'Sao Tome and Principe','Saudi Arabia','Senegal','Serbia','Seychelles',
        'Sierra Leone','Singapore','Slovakia','Slovenia','Solomon Islands','Somalia',
        'South Africa','South Korea','South Sudan','Spain','Sri Lanka','Sudan',
        'Suriname','Sweden','Switzerland','Syria','Taiwan','Tajikistan','Tanzania',
        'Thailand','Timor-Leste','Togo','Tonga','Trinidad and Tobago','Tunisia',
        'Turkey','Turkmenistan','Tuvalu','Uganda','Ukraine','United Arab Emirates',
        'United Kingdom','United States','Uruguay','Uzbekistan','Vanuatu',
        'Vatican City','Venezuela','Vietnam','Yemen','Zambia','Zimbabwe'
    ];
}

// ── REFUND SYSTEM ─────────────────────────────

function refundCard(int $orderItemId, int $userId): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT price FROM order_items WHERE id=? AND check_status='dead' LIMIT 1");
    $stmt->bind_param("i", $orderItemId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if (!$result) { $db->close(); return false; }

    $price = (float)$result['price'];
    addBalance($userId, $price);

    $ins = $db->prepare("INSERT INTO deposits (user_id, amount, currency, network, status) VALUES (?, ?, 'REFUND', 'refund', 'paid')");
    $ins->bind_param("id", $userId, $price);
    $ins->execute();

    $db->query("UPDATE orders o JOIN order_items oi ON o.id=oi.order_id SET o.status='refunded' WHERE oi.id=$orderItemId");
    $db->close();
    return true;
}

// ── VIP SYSTEM ────────────────────────────────

function getVipLevel(int $userId): array {
    $db = getDB();
    $user = $db->query("SELECT total_spent FROM users WHERE id=$userId")->fetch_assoc();
    $spent = (float)($user['total_spent'] ?? 0);
    $levels = $db->query("SELECT * FROM vip_levels ORDER BY min_spent DESC")->fetch_all(MYSQLI_ASSOC);
    $db->close();

    foreach ($levels as $level) {
        if ($spent >= (float)$level['min_spent']) return $level;
    }
    return ['name' => 'None', 'discount_percent' => 0, 'badge_color' => '#666', 'badge_icon' => '-'];
}

function getVipDiscount(int $userId): float {
    $level = getVipLevel($userId);
    return (float)($level['discount_percent'] ?? 0);
}

// ── REFERRAL SYSTEM ───────────────────────────

function generateReferralCode(): string {
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}

function applyReferral(int $referrerId, int $referredId): void {
    $bonus = (float)(getSetting('referral_bonus') ?: '5.00');
    if ($bonus <= 0) return;

    $db = getDB();
    $ins = $db->prepare("INSERT INTO referrals (referrer_id, referred_id, bonus_amount, status) VALUES (?, ?, ?, 'pending')");
    $ins->bind_param("iid", $referrerId, $referredId, $bonus);
    $ins->execute();
    $db->close();
}

function getReferralLink(int $userId): string {
    $db   = getDB();
    $res  = $db->query("SELECT referral_code FROM users WHERE id=$userId");
    $user = $res ? $res->fetch_assoc() : null;
    $db->close();
    $code   = $user['referral_code'] ?? '';
    $scheme = $_SERVER['REQUEST_SCHEME'] ?? 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/auth/register.php?ref=' . urlencode($code);
}

function getReferralCount(int $userId): int {
    $db = getDB();
    $res = $db->query("SELECT COUNT(*) as c FROM referrals WHERE referrer_id=$userId");
    $count = $res->fetch_assoc()['c'] ?? 0;
    $db->close();
    return $count;
}

function getTotalReferralEarnings(int $userId): float {
    $db = getDB();
    $res = $db->query("SELECT COALESCE(SUM(bonus_amount),0) as total FROM referrals WHERE referrer_id=$userId AND status='credited'");
    $total = (float)($res->fetch_assoc()['total'] ?? 0);
    $db->close();
    return $total;
}

// ── CARD PACKS ────────────────────────────────

function getActiveBundles(): array {
    $db = getDB();
    $result = $db->query("SELECT * FROM card_bundles WHERE is_active=1 ORDER BY price ASC");
    $bundles = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $db->close();
    return $bundles;
}

// ── DAILY COUNTER ─────────────────────────────

function getDailyCardCount(): int {
    $db = getDB();
    $res = $db->query("SELECT COUNT(*) as c FROM products WHERE DATE(created_at) = CURDATE() AND stock > 0 AND vendor_status='approved'");
    $count = (int)($res->fetch_assoc()['c'] ?? 0);
    $db->close();
    return $count;
}

// ── CARD STATUS HELPERS ───────────────────────

function getCardStatusColor(string $status): string {
    switch($status) {
        case 'live': return '#00c853';
        case 'dead': return '#ff4444';
        default: return '#ffd600';
    }
}

function getCardStatusLabel(string $status): string {
    switch($status) {
        case 'live': return 'LIVE';
        case 'dead': return 'DEAD';
        default: return 'UNKNOWN';
    }
}

function getVbvBadge(string $vbv): string {
    switch($vbv) {
        case 'yes': return '<span style="background:rgba(0,200,83,.15);color:#00c853;padding:2px 8px;border-radius:4px;font-size:11px">VBV ✓</span>';
        case 'no': return '<span style="background:rgba(255,68,68,.15);color:#ff4444;padding:2px 8px;border-radius:4px;font-size:11px">Non-VBV</span>';
        default: return '<span style="background:rgba(255,214,0,.15);color:#ffd600;padding:2px 8px;border-radius:4px;font-size:11px">Unknown</span>';
    }
}

function formatBalanceRange(?float $min, ?float $max): string {
    if ($min === null && $max === null) return '';
    if ($min === null) return '$' . number_format($max, 0) . '+';
    if ($max === null) return '$' . number_format($min, 0) . '+';
    return '$' . number_format($min / 1000, 0) . 'K-$' . number_format($max / 1000, 0) . 'K';
}

// ── NOTIFICATION FUNCTIONS ────────────────────

function createNotification(int $userId, string $title, string $message, string $type = 'system', string $link = ''): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?,?,?,?,?)");
    $stmt->bind_param("issss", $userId, $title, $message, $type, $link);
    $stmt->execute();
    $db->close();
}

function getUnreadNotificationCount(int $userId): int {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as c FROM notifications WHERE user_id=? AND is_read=0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['c'];
    $db->close();
    return $count;
}

function getNotifications(int $userId, int $limit = 20): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $db->close();
    return $results;
}

function markNotificationRead(int $notifId): void {
    $db = getDB();
    $stmt = $db->prepare("UPDATE notifications SET is_read=1 WHERE id=?");
    $stmt->bind_param("i", $notifId);
    $stmt->execute();
    $db->close();
}

function markAllNotificationsRead(int $userId): void {
    $db = getDB();
    $stmt = $db->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $db->close();
}

// ── TICKET FUNCTIONS ──────────────────────────

function createTicket(int $userId, string $subject, string $message, string $priority = 'medium'): int {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO tickets (user_id, subject, priority) VALUES (?,?,?)");
    $stmt->bind_param("iss", $userId, $subject, $priority);
    $stmt->execute();
    $ticketId = $db->insert_id;
    $msgStmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, is_admin) VALUES (?,?,?,0)");
    $msgStmt->bind_param("iis", $ticketId, $userId, $message);
    $msgStmt->execute();
    $db->close();
    return $ticketId;
}

function replyTicket(int $ticketId, int $senderId, string $message, bool $isAdmin = false): void {
    $db = getDB();
    $adminFlag = $isAdmin ? 1 : 0;
    $stmt = $db->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, is_admin) VALUES (?,?,?,?)");
    $stmt->bind_param("iisi", $ticketId, $senderId, $message, $adminFlag);
    $stmt->execute();
    $status = $isAdmin ? 'replied' : 'open';
    $upd = $db->prepare("UPDATE tickets SET status=?, updated_at=NOW() WHERE id=?");
    $upd->bind_param("si", $status, $ticketId);
    $upd->execute();
    $db->close();
}

function getTicketMessages(int $ticketId): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT tm.*, u.username, u.role FROM ticket_messages tm JOIN users u ON tm.sender_id=u.id WHERE tm.ticket_id=? ORDER BY tm.created_at ASC");
    $stmt->bind_param("i", $ticketId);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $db->close();
    return $results;
}

function getUserTickets(int $userId): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT t.*, (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id=t.id AND is_admin=1 AND created_at > (SELECT COALESCE(MAX(created_at),'1970-01-01') FROM ticket_messages WHERE ticket_id=t.id AND is_admin=0)) as unread_admin FROM tickets t WHERE t.user_id=? ORDER BY t.updated_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $db->close();
    return $results;
}

function getAllTickets(string $status = 'all'): array {
    $db = getDB();
    $where = $status !== 'all' ? "WHERE t.status='$status'" : "";
    $res = $db->query("SELECT t.*, u.username, u.email, (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id=t.id) as msg_count FROM tickets t JOIN users u ON t.user_id=u.id $where ORDER BY t.updated_at DESC");
    $results = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $db->close();
    return $results;
}

function getOpenTicketCount(): int {
    $db = getDB();
    $res = $db->query("SELECT COUNT(*) as c FROM tickets WHERE status='open'");
    $count = (int)$res->fetch_assoc()['c'];
    $db->close();
    return $count;
}
