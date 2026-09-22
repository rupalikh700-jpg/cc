<?php
$pageTitle = 'Manage Orders — Admin';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
if (!isAdmin()) { redirect('/auth/login.php'); }

$db = getDB();

if (isset($_GET['update'])) {
    $oId = (int)$_GET['update'];
    $newStatus = $_GET['status'] ?? '';
    if (in_array($newStatus, ['pending','completed','refunded','cancelled'])) {
        $db->query("UPDATE orders SET status='$newStatus' WHERE id=$oId");
        setFlash('success', 'Order status updated.');
    }
    redirect('/admin/orders.php');
}

$result = $db->query("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
$db->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>🛒 Manage Orders</h1></div>
</div>

<div class="tabs" style="margin-bottom:20px">
  <a class="tab" href="/admin/index.php">Dashboard</a>
  <a class="tab" href="/admin/products.php">📦 Cards</a>
  <a class="tab active" href="/admin/orders.php">🛒 Orders</a>
</div>

<?php if ($result && $result->num_rows > 0): ?>
<div style="overflow-x:auto">
<table class="table" style="width:100%">
  <thead>
    <tr>
      <th>Order</th>
      <th>User</th>
      <th>Items</th>
      <th>Total</th>
      <th>Payment</th>
      <th>Status</th>
      <th>Date</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($o = $result->fetch_assoc()): ?>
    <tr>
      <td><strong><?= clean($o['order_code']) ?></strong></td>
      <td><?= clean($o['username'] ?? 'Guest') ?></td>
      <td><?php
        $db2 = getDB();
        $cnt = $db2->query("SELECT COUNT(*) as c FROM order_items WHERE order_id={$o['id']}")->fetch_assoc()['c'];
        $db2->close();
        echo $cnt;
      ?></td>
      <td><strong><?= price($o['total_price']) ?></strong></td>
      <td><span class="badge"><?= clean($o['payment_method']) ?></span></td>
      <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
      <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
      <td>
        <div style="display:flex;gap:4px">
          <?php foreach (['pending','completed','refunded','cancelled'] as $s): ?>
            <?php if ($s !== $o['status']): ?>
              <a href="?update=<?= $o['id'] ?>&status=<?= $s ?>" class="btn btn-outline btn-xs" onclick="return confirm('Change to <?= $s ?>?')"><?= ucfirst($s) ?></a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>
</div>

<?php
// Card details expand section
$db3 = getDB();
$allOrders = $db3->query("SELECT o.* FROM orders o ORDER BY o.created_at DESC");
while ($o = $allOrders->fetch_assoc()):
  $itemsQ = $db3->prepare("SELECT * FROM order_items WHERE order_id=?");
  $itemsQ->bind_param("i", $o['id']);
  $itemsQ->execute();
  $items = $itemsQ->get_result();
  if ($items && $items->num_rows > 0):
?>
<div class="order-card" style="margin-top:12px">
  <div class="order-header">
    <strong><?= clean($o['order_code']) ?></strong>
    <span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span>
  </div>
  <?php while ($item = $items->fetch_assoc()):
    $cardNum = decryptData($item['card_number']);
    $cvv = decryptData($item['card_cvv']);
    $brand = $item['card_brand'] ?? 'visa';
  ?>
    <div style="font-size:13px;margin-top:8px;padding:8px;background:rgba(255,255,255,.03);border-radius:6px">
      <div style="display:flex;gap:16px;flex-wrap:wrap">
        <span><strong>Card:</strong> <code><?= maskCard($cardNum) ?></code></span>
        <span><strong>Full:</strong> <code><?= $cardNum ?></code></span>
        <span><strong>CVV:</strong> <code><?= $cvv ?></code></span>
        <span><strong>Name:</strong> <?= clean($item['card_name']) ?></span>
        <span><strong>Expiry:</strong> <?= clean($item['card_expiry']) ?></span>
        <span><strong><?= getCardBrandLabel($brand) ?></strong></span>
      </div>
    </div>
  <?php endwhile; ?>
</div>
<?php
  endif;
endwhile;
$db3->close();
?>

<?php else: ?>
  <div class="empty-state"><p>No orders yet.</p></div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
