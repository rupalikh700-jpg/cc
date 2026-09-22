<?php
// ============================================
// vendor/index.php — Vendor Dashboard
// ============================================
$pageTitle = 'Vendor Dashboard — Pepe CC Shop';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
if (!isLoggedIn()) redirect('/auth/login.php');

$uid = (int)$_SESSION['user_id'];
$db  = getDB();

$profile = $db->query("SELECT * FROM vendor_profiles WHERE user_id=$uid")->fetch_assoc();
if (!$profile) {
    $db->close();
    redirect('/vendor/register.php');
}

if ($profile['status'] !== 'approved') {
    $db->close();
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div style="max-width:480px;margin:0 auto;text-align:center;padding:64px 0">
      <?php if ($profile['status'] === 'pending'): ?>
        <div style="font-size:64px;margin-bottom:16px">⏳</div>
        <h2>Application Pending</h2>
        <p style="color:var(--muted);margin-top:8px">Your vendor application is under review. You'll be notified once approved.</p>
      <?php else: ?>
        <div style="font-size:64px;margin-bottom:16px">❌</div>
        <h2>Application Declined</h2>
        <p style="color:var(--muted);margin-top:8px">Contact support for more information.</p>
        <a href="/user/tickets.php" class="btn btn-outline btn-sm" style="margin-top:16px">Contact Support</a>
      <?php endif; ?>
      <a href="/index.php" class="btn btn-outline btn-sm" style="margin-top:16px">Back to Home</a>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Stats
$totalProducts = (int)$db->query("SELECT COUNT(*) as c FROM products WHERE vendor_id=$uid")->fetch_assoc()['c'];
$totalSales    = (int)$db->query("SELECT COUNT(*) as c FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE p.vendor_id=$uid")->fetch_assoc()['c'];
$totalRevenue  = (float)$db->query("SELECT COALESCE(SUM(p.price),0) as t FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE p.vendor_id=$uid")->fetch_assoc()['t'];
$pendingCards  = (int)$db->query("SELECT COUNT(*) as c FROM products WHERE vendor_id=$uid AND vendor_status='pending'")->fetch_assoc()['c'];

$recentProducts = $db->query("SELECT * FROM products WHERE vendor_id=$uid ORDER BY created_at DESC LIMIT 10");
$db->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>🏪 <?= clean($profile['shop_name']) ?></h1>
    <p>Vendor Dashboard</p>
  </div>
  <a href="/vendor/upload.php" class="btn btn-primary btn-sm">+ Add Cards</a>
</div>

<!-- Stats -->
<div class="grid grid-3" style="margin-bottom:32px">
  <div class="admin-stat">
    <div class="num"><?= $totalProducts ?></div>
    <div class="lbl">Total Cards</div>
  </div>
  <div class="admin-stat">
    <div class="num"><?= $totalSales ?></div>
    <div class="lbl">Cards Sold</div>
  </div>
  <div class="admin-stat">
    <div class="num" style="font-size:26px"><?= price($totalRevenue) ?></div>
    <div class="lbl">Revenue</div>
  </div>
</div>

<?php if ($pendingCards > 0): ?>
  <div style="background:rgba(255,214,0,.1);border:1px solid rgba(255,214,0,.3);border-radius:10px;padding:14px 18px;margin-bottom:24px;font-size:14px">
    ⏳ You have <?= $pendingCards ?> card<?= $pendingCards !== 1 ? 's' : '' ?> pending admin review.
  </div>
<?php endif; ?>

<!-- Recent products -->
<p class="section-title">Recent Cards</p>
<?php if ($recentProducts && $recentProducts->num_rows > 0): ?>
  <div style="overflow-x:auto">
    <table class="table" style="width:100%">
      <thead>
        <tr>
          <th>BIN</th>
          <th>Brand</th>
          <th>Country</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Status</th>
          <th>Added</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($p = $recentProducts->fetch_assoc()): ?>
          <tr>
            <td><code><?= clean($p['card_bin']) ?></code></td>
            <td><?= clean(ucfirst($p['card_brand'] ?? '')) ?></td>
            <td><?= clean($p['card_country'] ?? '—') ?></td>
            <td><?= price($p['price']) ?></td>
            <td><?= (int)$p['stock'] ?></td>
            <td><span class="badge badge-<?= $p['vendor_status'] ?>"><?= ucfirst($p['vendor_status']) ?></span></td>
            <td><?= date('M d', strtotime($p['created_at'])) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <div class="empty-state">
    <div class="big">📭</div>
    <p>No cards yet.</p>
    <a href="/vendor/upload.php" class="btn btn-primary btn-sm" style="margin-top:12px">+ Add Your First Card</a>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
