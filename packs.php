<?php
// ============================================
// packs.php — Card Packs / Bundles
// ============================================
$pageTitle = 'Card Packs — Pepe CC Shop';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
if (!isLoggedIn()) redirect('/auth/login.php');

$uid = (int)$_SESSION['user_id'];
$db  = getDB();

// ── Handle pack purchase ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_pack'])) {
    $packId = (int)$_POST['pack_id'];

    $packRow = $db->query("SELECT * FROM card_bundles WHERE id=$packId AND is_active=1")->fetch_assoc();
    if (!$packRow) {
        setFlash('error', 'Pack not found or unavailable.');
        redirect('/packs.php');
    }

    $price   = (float)$packRow['price'];
    $balance = getBalance($uid);

    if ($balance < $price) {
        setFlash('error', 'Insufficient balance. Please deposit funds.');
        redirect('/user/deposit.php');
    }

    // Find matching cards
    $whereClause = "WHERE stock>0 AND vendor_status='approved'";
    if ($packRow['card_brand'])   $whereClause .= " AND card_brand='" . $db->real_escape_string($packRow['card_brand']) . "'";
    if ($packRow['card_country']) $whereClause .= " AND card_country='" . $db->real_escape_string($packRow['card_country']) . "'";
    if ($packRow['card_type'])    $whereClause .= " AND card_type='" . $db->real_escape_string($packRow['card_type']) . "'";

    $cardCount = (int)$packRow['card_count'];
    $cards = $db->query("SELECT * FROM products $whereClause ORDER BY RAND() LIMIT $cardCount");

    if (!$cards || $cards->num_rows < $cardCount) {
        setFlash('error', 'Not enough cards available for this pack right now.');
        redirect('/packs.php');
    }

    // Deduct balance
    deductBalance($uid, $price);

    // Create order
    $orderCode = 'PACK-' . strtoupper(substr(md5(uniqid()), 0, 8));
    $db->query("INSERT INTO orders (user_id, order_code, total_price, payment_method, status) VALUES ($uid, '$orderCode', $price, 'balance', 'completed')");
    $orderId = $db->insert_id;

    // Attach cards
    while ($card = $cards->fetch_assoc()) {
        $cardNum  = $db->real_escape_string($card['card_number']);
        $cardCvv  = $db->real_escape_string($card['card_cvv']);
        $cardName = $db->real_escape_string($card['card_name'] ?? '');
        $cardExp  = $db->real_escape_string($card['card_expiry'] ?? '');
        $cardBin  = $db->real_escape_string($card['card_bin'] ?? '');
        $cardBrand= $db->real_escape_string($card['card_brand'] ?? '');
        $cardType = $db->real_escape_string($card['card_type'] ?? '');
        $cardCountry = $db->real_escape_string($card['card_country'] ?? '');
        $bankName = $db->real_escape_string($card['bank_name'] ?? '');

        $db->query("INSERT INTO order_items (order_id, product_id, card_number, card_cvv, card_name, card_expiry, card_bin, card_brand, card_type, card_country, bank_name) VALUES ($orderId, {$card['id']}, '$cardNum', '$cardCvv', '$cardName', '$cardExp', '$cardBin', '$cardBrand', '$cardType', '$cardCountry', '$bankName')");
        $db->query("UPDATE products SET stock=stock-1, sales=sales+1 WHERE id={$card['id']}");
    }

    addNotification($uid, 'order', 'Pack Purchased!', "Your pack \"{$packRow['name']}\" has been delivered.", "/orders.php");

    $db->close();
    setFlash('success', "Pack purchased! Check your orders.");
    redirect('/orders.php');
}

// ── Load packs ────────────────────────────────
$packs = $db->query("SELECT * FROM card_bundles WHERE is_active=1 ORDER BY price ASC");
$db->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div><h1>📦 Card Packs</h1><p>Buy bundles of cards at a discount</p></div>
  <a href="/shop.php" class="btn btn-outline btn-sm">Browse Singles →</a>
</div>

<?php if ($packs && $packs->num_rows > 0): ?>
  <div class="card-grid">
    <?php while ($pack = $packs->fetch_assoc()): ?>
      <div class="pack-card">
        <div style="font-size:48px;margin-bottom:12px">📦</div>
        <h3 style="margin-bottom:8px"><?= clean($pack['name']) ?></h3>
        <?php if ($pack['description']): ?>
          <p class="pack-filters" style="margin-bottom:12px"><?= clean($pack['description']) ?></p>
        <?php endif; ?>
        <div class="pack-count"><?= (int)$pack['card_count'] ?> card<?= $pack['card_count'] != 1 ? 's' : '' ?></div>
        <div class="pack-filters" style="margin-bottom:12px">
          <?php if ($pack['card_brand'])   echo ucfirst($pack['card_brand']) . ' '; ?>
          <?php if ($pack['card_country']) echo '• ' . clean($pack['card_country']) . ' '; ?>
          <?php if ($pack['card_type'])    echo '• ' . ucfirst($pack['card_type']); ?>
        </div>
        <div class="pack-price" style="margin-bottom:16px"><?= price($pack['price']) ?></div>
        <form method="POST">
          <input type="hidden" name="pack_id" value="<?= (int)$pack['id'] ?>">
          <button type="submit" name="buy_pack" value="1" class="btn btn-primary" style="width:100%"
            onclick="return confirm('Buy &quot;<?= htmlspecialchars($pack['name'], ENT_QUOTES, 'UTF-8') ?>&quot; for <?= price($pack['price']) ?>?')">
            Buy Pack
          </button>
        </form>
      </div>
    <?php endwhile; ?>
  </div>
<?php else: ?>
  <div class="empty-state">
    <div class="big">📦</div>
    <p>No card packs available right now.</p>
    <a href="/shop.php" class="btn btn-primary" style="margin-top:16px">Browse Single Cards →</a>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
