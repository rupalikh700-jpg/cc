<?php
// ============================================
// orders.php — User Orders
// ============================================
$pageTitle = 'My Orders — Pepe CC Shop';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/includes/country_flags.php';
if (!isLoggedIn()) redirect('/auth/login.php');

$uid  = (int)$_SESSION['user_id'];
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$db = getDB();

$countRow = $db->query("SELECT COUNT(*) as c FROM orders WHERE user_id=$uid")->fetch_assoc();
$totalCount = (int)($countRow['c'] ?? 0);
$totalPages = max(1, (int)ceil($totalCount / $perPage));
$offset = ($page - 1) * $perPage;

$orders = $db->query("SELECT * FROM orders WHERE user_id=$uid ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>📦 My Orders</h1>
    <p><?= $totalCount ?> order<?= $totalCount !== 1 ? 's' : '' ?> total</p>
  </div>
</div>

<?php if ($orders && $orders->num_rows > 0): ?>

  <?php while ($order = $orders->fetch_assoc()):
    $db2   = getDB();
    $items = $db2->query("SELECT * FROM order_items WHERE order_id={$order['id']}");
    $db2->close();
  ?>
    <div class="order-card">
      <div class="order-header">
        <div>
          <strong><?= clean($order['order_code']) ?></strong>
          <span style="color:var(--muted);font-size:13px;margin-left:12px">
            <?= date('M d, Y  H:i', strtotime($order['created_at'])) ?>
          </span>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
          <span class="badge badge-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
          <strong style="font-family:var(--mono)"><?= price($order['total_price']) ?></strong>
        </div>
      </div>

      <?php if ($items && $items->num_rows > 0): ?>
        <?php while ($item = $items->fetch_assoc()):
          $cardBrand  = $item['card_brand'] ?? 'visa';
          $gradient   = getCardBrandGradient($cardBrand);
          $cardNumber = decryptData($item['card_number']);
          $cvv        = decryptData($item['card_cvv']);
        ?>
          <div class="purchased-card" style="background:<?= $gradient ?>">
            <div class="cc-card-header">
              <span class="cc-brand"><?= getCardBrandLabel($cardBrand) ?></span>
              <span class="cc-type"><?= ucfirst($item['card_type'] ?? 'credit') ?></span>
            </div>
            <div class="cc-card-number"><?= clean($cardNumber) ?></div>
            <div class="cc-card-footer">
              <div class="cc-info"><span class="cc-label">Cardholder</span><span class="cc-value"><?= clean($item['card_name']) ?></span></div>
              <div class="cc-info"><span class="cc-label">Expiry</span><span class="cc-value"><?= clean($item['card_expiry']) ?></span></div>
              <div class="cc-info"><span class="cc-label">CVV</span><span class="cc-value"><?= clean($cvv) ?></span></div>
              <?php if (!empty($item['card_country'])): ?>
                <div class="cc-info"><span class="cc-label">Country</span><span class="cc-value"><?= getCountryEmoji($item['card_country']) ?> <?= clean($item['card_country']) ?></span></div>
              <?php endif; ?>
              <?php if (!empty($item['bank_name'])): ?>
                <div class="cc-info" style="width:100%"><span class="cc-label">Bank</span><span class="cc-value bank-name"><?= clean($item['bank_name']) ?></span></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="card-details-grid" style="margin-bottom:16px">
            <div class="detail-block">
              <h4>Card Number</h4>
              <div class="detail-row">
                <span class="detail-label">Full Number</span>
                <div class="detail-copy">
                  <span class="detail-value" id="cn-<?= $item['id'] ?>"><?= clean($cardNumber) ?></span>
                  <button class="copy-btn" data-copy="<?= htmlspecialchars($cardNumber, ENT_QUOTES, 'UTF-8') ?>">Copy</button>
                </div>
              </div>
              <div class="detail-row">
                <span class="detail-label">CVV</span>
                <div class="detail-copy">
                  <span class="detail-value"><?= clean($cvv) ?></span>
                  <button class="copy-btn" data-copy="<?= htmlspecialchars($cvv, ENT_QUOTES, 'UTF-8') ?>">Copy</button>
                </div>
              </div>
              <div class="detail-row">
                <span class="detail-label">Expiry</span>
                <span class="detail-value"><?= clean($item['card_expiry']) ?></span>
              </div>
            </div>
            <div class="detail-block">
              <h4>Cardholder Info</h4>
              <div class="detail-row">
                <span class="detail-label">Name</span>
                <span class="detail-value"><?= clean($item['card_name']) ?></span>
              </div>
              <?php if (!empty($item['card_address'])): ?>
              <div class="detail-row">
                <span class="detail-label">Address</span>
                <span class="detail-value" style="font-size:12px"><?= clean($item['card_address']) ?></span>
              </div>
              <?php endif; ?>
              <?php if (!empty($item['card_zip'])): ?>
              <div class="detail-row">
                <span class="detail-label">ZIP</span>
                <span class="detail-value"><?= clean($item['card_zip']) ?></span>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($order['status'] === 'completed'): ?>
            <div style="margin-bottom:16px">
              <a href="/user/checker.php?card=<?= urlencode($cardNumber) ?>&expiry=<?= urlencode($item['card_expiry']) ?>&cvv=<?= urlencode($cvv) ?>" class="btn btn-outline btn-sm">🔍 Check Card</a>
            </div>
          <?php endif; ?>

        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  <?php endwhile; ?>

  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="btn btn-outline btn-sm">← Prev</a>
      <?php endif; ?>
      <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>
      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>" class="btn btn-outline btn-sm">Next →</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

<?php else: ?>
  <div class="empty-state">
    <div class="big">📦</div>
    <p>You haven't placed any orders yet.</p>
    <a href="/shop.php" class="btn btn-primary" style="margin-top:16px">Browse Cards →</a>
  </div>
<?php endif; ?>

<?php
$db->close();
require_once __DIR__ . '/includes/footer.php';
?>
