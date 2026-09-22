<?php
// ============================================
// user/balance.php — Wallet Balance & History
// ============================================
$pageTitle = 'My Wallet — Pepe CC Shop';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please sign in to view your wallet.');
    redirect('/auth/login.php');
}

$db = getDB();
$userId = (int)$_SESSION['user_id'];
$currentBalance = getBalance($userId);

// Get deposit history
$depStmt = $db->prepare("SELECT * FROM deposits WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
$depStmt->bind_param("i", $userId);
$depStmt->execute();
$deposits = $depStmt->get_result();

// Get order history (spending)
$ordStmt = $db->prepare("
    SELECT o.*, GROUP_CONCAT(p.name SEPARATOR ', ') AS items 
    FROM orders o 
    JOIN order_items oi ON oi.order_id = o.id 
    JOIN products p ON p.id = oi.product_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC LIMIT 20
");
$ordStmt->bind_param("i", $userId);
$ordStmt->execute();
$orders = $ordStmt->get_result();

// Stats
$totalDeposited = 0;
$depCount = 0;
$depAll = $db->prepare("SELECT COALESCE(SUM(amount),0) AS total, COUNT(*) AS cnt FROM deposits WHERE user_id=? AND status='paid'");
$depAll->bind_param("i", $userId);
$depAll->execute();
$depStats = $depAll->get_result()->fetch_assoc();
$totalDeposited = (float)$depStats['total'];
$depCount = $depStats['cnt'];

$totalSpent = 0;
$ordCount = 0;
$ordAll = $db->prepare("SELECT COALESCE(SUM(total_price),0) AS total, COUNT(*) AS cnt FROM orders WHERE user_id=? AND status='completed'");
$ordAll->bind_param("i", $userId);
$ordAll->execute();
$ordStats = $ordAll->get_result()->fetch_assoc();
$totalSpent = (float)$ordStats['total'];
$ordCount = $ordStats['cnt'];

$db->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>💰 My Wallet</h1><p>Manage your balance and view transaction history</p></div>
  <a class="btn btn-primary btn-sm" href="/user/deposit.php">+ Deposit Funds</a>
</div>

<!-- Balance Card -->
<div style="background:linear-gradient(135deg,var(--surface),var(--surface2));border:1px solid var(--green);border-radius:16px;padding:32px;margin-bottom:32px;text-align:center">
  <div style="font-size:14px;color:var(--muted);margin-bottom:8px">Available Balance</div>
  <div class="mono" style="font-size:48px;color:var(--green);font-weight:700"><?= formatBalance($currentBalance) ?></div>
  <div style="margin-top:16px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
    <a class="btn btn-primary" href="/user/deposit.php">💰 Deposit</a>
    <a class="btn btn-outline" href="/shop.php">🛍️ Shop</a>
  </div>
</div>

<!-- Stats -->
<div class="grid grid-3" style="margin-bottom:32px">
  <div class="admin-stat">
    <div style="font-size:28px;margin-bottom:4px">💵</div>
    <div class="num" style="font-size:24px"><?= formatBalance($currentBalance) ?></div>
    <div class="lbl">Current Balance</div>
  </div>
  <div class="admin-stat">
    <div style="font-size:28px;margin-bottom:4px">📈</div>
    <div class="num" style="font-size:24px"><?= formatBalance($totalDeposited) ?></div>
    <div class="lbl">Total Deposited (<?= $depCount ?> txns)</div>
  </div>
  <div class="admin-stat">
    <div style="font-size:28px;margin-bottom:4px">🛒</div>
    <div class="num" style="font-size:24px"><?= formatBalance($totalSpent) ?></div>
    <div class="lbl">Total Spent (<?= $ordCount ?> orders)</div>
  </div>
</div>

<!-- Tabs -->
<div class="tabs" style="margin-bottom:20px">
  <a class="tab active" href="/user/balance.php">💰 Wallet</a>
  <a class="tab" href="/user/deposit.php">💳 Deposit</a>
  <a class="tab" href="/orders.php">📦 Orders</a>
  <a class="tab" href="/shop.php">🛍️ Shop</a>
</div>

<!-- Deposit History -->
<h3 class="section-title">💵 Deposit History</h3>
<?php if ($deposits->num_rows === 0): ?>
  <div class="empty-state" style="padding:32px">
    <p>No deposits yet. <a href="/user/deposit.php">Make your first deposit!</a></p>
  </div>
<?php else: ?>
<div class="table-wrap">
  <table>
    <thead>
      <tr><th>Date</th><th>Amount</th><th>Currency</th><th>Network</th><th>Status</th></tr>
    </thead>
    <tbody>
    <?php while ($d = $deposits->fetch_assoc()): ?>
      <tr>
        <td class="muted" style="font-size:12px"><?= date('M j, Y H:i', strtotime($d['created_at'])) ?></td>
        <td class="mono green" style="font-weight:600"><?= formatBalance((float)$d['amount']) ?></td>
        <td><span class="tag"><?= clean($d['currency']) ?></span></td>
        <td class="muted" style="font-size:12px"><?= clean($d['network'] ?? '—') ?></td>
        <td>
          <?php if ($d['status'] === 'paid'): ?>
            <span class="status status-paid">✅ PAID</span>
          <?php elseif ($d['status'] === 'pending'): ?>
            <span class="status status-pending">⏳ PENDING</span>
          <?php else: ?>
            <span class="status status-failed"><?= strtoupper($d['status']) ?></span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- Order History (Spending) -->
<hr class="divider">
<h3 class="section-title">🛒 Purchase History</h3>
<?php if ($orders->num_rows === 0): ?>
  <div class="empty-state" style="padding:32px">
    <p>No purchases yet. <a href="/shop.php">Browse the shop!</a></p>
  </div>
<?php else: ?>
<div class="table-wrap">
  <table>
    <thead>
      <tr><th>Date</th><th>Order ID</th><th>Items</th><th>Total</th><th>Status</th></tr>
    </thead>
    <tbody>
    <?php while ($o = $orders->fetch_assoc()): ?>
      <tr>
        <td class="muted" style="font-size:12px"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
        <td class="mono green" style="font-size:12px"><?= clean($o['order_code']) ?></td>
        <td style="font-size:12px;color:var(--muted)"><?= clean($o['items']) ?></td>
        <td class="mono" style="font-weight:700;color:var(--red)">-<?= formatBalance((float)$o['total']) ?></td>
        <td><span class="status status-<?= $o['status'] ?>"><?= strtoupper($o['status']) ?></span></td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>