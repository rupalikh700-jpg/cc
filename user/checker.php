<?php
$pageTitle = 'Card Checker';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/checker.php';
require_once __DIR__ . '/../config/telegram.php';
if (!isLoggedIn()) { redirect('/auth/login.php'); }

$uid = (int)$_SESSION['user_id'];
$db = getDB();
$checkerCost = (float)(getSetting('checker_cost') ?: '0.50');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_item'])) {
    $itemId = (int)$_POST['check_item'];

    $item = $db->query("SELECT oi.*, o.user_id FROM order_items oi JOIN orders o ON oi.order_id=o.id WHERE oi.id=$itemId AND o.user_id=$uid AND oi.check_status='unchecked'")->fetch_assoc();
    if (!$item) {
        setFlash('error', 'Card not found or already checked.');
        redirect('/user/checker.php');
    }

    if (!hasEnoughBalance($uid, $checkerCost)) {
        setFlash('error', 'Insufficient balance for checker. Need $' . number_format($checkerCost, 2));
        redirect('/user/checker.php');
    }

    deductBalance($uid, $checkerCost);

    $cardNum = decryptData($item['card_number']);
    $cvv = decryptData($item['card_cvv']);
    $result = checkCardStatus($cardNum, $item['card_expiry'], $cvv);

    $upd = $db->prepare("UPDATE order_items SET check_status=?, checked_at=NOW() WHERE id=?");
    $upd->bind_param("si", $result['status'], $itemId);
    $upd->execute();

    $ins = $db->prepare("INSERT INTO card_checks (order_item_id, user_id, result, response_data, cost) VALUES (?, ?, ?, ?, ?)");
    $respJson = json_encode($result['raw'] ?? []);
    $ins->bind_param("iissd", $itemId, $uid, $result['status'], $respJson, $checkerCost);
    $ins->execute();

    if ($result['status'] === 'dead') {
        refundCard($itemId, $uid);
        setFlash('success', 'Card is DEAD. $' . number_format($item['price'], 2) . ' refunded to wallet!');
    } else {
        $label = strtoupper($result['status']);
        setFlash('success', "Card check result: {$label}");
    }

    $masked = maskCard($cardNum);
    tgNotifyChecker(['result' => $result['status'], 'cost' => $checkerCost], $masked);

    redirect('/user/checker.php');
}

$myCards = $db->query("
    SELECT oi.*, o.order_code, o.created_at as order_date
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.user_id = $uid
    ORDER BY o.created_at DESC
");
$db->close();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>Card Checker</h1><p class="muted">Check if your cards are live. Cost: <?= price($checkerCost) ?> per check</p></div>
</div>

<div class="tabs" style="margin-bottom:20px">
  <a class="tab" href="/orders.php">My Orders</a>
  <a class="tab active" href="/user/checker.php">Card Checker</a>
  <a class="tab" href="/user/balance.php">Wallet</a>
</div>

<div style="background:rgba(255,214,0,.1);border:1px solid #ffd600;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#ffd600">
  Wallet Balance: <strong><?= formatBalance(getBalance($uid)) ?></strong> | Cost per check: <strong><?= price($checkerCost) ?></strong>
  <?php if ($checkerCost > 0): ?>
    | DEAD cards get <strong>full refund</strong> of card price
  <?php endif; ?>
</div>

<?php if ($myCards && $myCards->num_rows > 0): ?>
<div style="display:flex;flex-direction:column;gap:12px">
  <?php while ($item = $myCards->fetch_assoc()):
    $brand = $item['card_brand'] ?? 'visa';
    $gradient = getCardBrandGradient($brand);
    $masked = maskCard(decryptData($item['card_number']));
    $status = $item['check_status'] ?? 'unchecked';
    $statusColor = getCardStatusColor($status);
    $statusLabel = $status === 'unchecked' ? 'NOT CHECKED' : getCardStatusLabel($status);
  ?>
  <div class="order-card" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
    <div class="cc-card" style="background:<?= $gradient ?>;min-width:260px;flex:0 0 auto">
      <div class="cc-card-header">
        <span class="cc-brand"><?= getCardBrandLabel($brand) ?></span>
      </div>
      <div class="cc-card-number" style="font-size:16px"><?= $masked ?></div>
      <div class="cc-card-footer">
        <div class="cc-info"><span class="cc-label">BIN</span><span class="cc-value"><?= clean($item['card_bin']) ?></span></div>
        <div class="cc-info"><span class="cc-label">Expiry</span><span class="cc-value"><?= clean($item['card_expiry']) ?></span></div>
      </div>
    </div>
    <div style="flex:1;min-width:150px">
      <div style="font-size:13px;color:#888"><?= clean($item['order_code']) ?> | <?= $item['order_date'] ?></div>
      <div style="font-size:13px;color:#888">Name: <?= clean($item['card_name']) ?></div>
      <div style="margin-top:6px">
        <span style="color:<?= $statusColor ?>;font-weight:700;font-size:14px"><?= $statusLabel ?></span>
      </div>
      <?php if ($status !== 'unchecked'): ?>
        <div style="font-size:11px;color:#888;margin-top:4px">Checked: <?= $item['checked_at'] ?></div>
      <?php endif; ?>
    </div>
    <div>
      <?php if ($status === 'unchecked'): ?>
        <form method="POST" onsubmit="return confirm('Check this card for <?= price($checkerCost) ?>?')">
          <input type="hidden" name="check_item" value="<?= $item['id'] ?>">
          <button class="btn btn-primary btn-sm" type="submit">Check Card</button>
        </form>
      <?php elseif ($status === 'live'): ?>
        <span style="font-size:24px">LIVE</span>
      <?php elseif ($status === 'dead'): ?>
        <span style="font-size:24px">REFUNDED</span>
      <?php endif; ?>
    </div>
  </div>
  <?php endwhile; ?>
</div>
<?php else: ?>
  <div class="empty-state"><p>No cards purchased yet. <a href="/shop.php">Browse cards</a></p></div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
