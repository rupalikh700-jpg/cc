<?php
$pageTitle = 'Manage Cards — Admin';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
if (!isAdmin()) { redirect('/auth/login.php'); }

$db = getDB();

if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $db->query("DELETE FROM products WHERE id=$delId");
    setFlash('success','Card deleted.');
    redirect('/admin/products.php');
}

if (isset($_GET['approve'])) {
    $aId = (int)$_GET['approve'];
    $db->query("UPDATE products SET vendor_status='approved' WHERE id=$aId");
    setFlash('success','Card approved.');
    redirect('/admin/products.php');
}

if (isset($_GET['decline'])) {
    $dId = (int)$_GET['decline'];
    $db->query("UPDATE products SET vendor_status='declined' WHERE id=$dId");
    setFlash('success','Card declined.');
    redirect('/admin/products.php');
}

$filter = $_GET['filter'] ?? 'all';
$where = $filter === 'pending' ? "WHERE vendor_status='pending'" : ($filter === 'approved' ? "WHERE vendor_status='approved'" : '');
$result = $db->query("SELECT p.*, u.username as vendor_name FROM products p LEFT JOIN users u ON p.vendor_id = u.id $where ORDER BY p.id DESC");
$db->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>📦 Manage Cards</h1></div>
  <a class="btn btn-primary btn-sm" href="/admin/add_product.php">+ Add Card</a>
</div>

<div class="tabs" style="margin-bottom:20px">
  <a class="tab" href="/admin/index.php">Dashboard</a>
  <a class="tab active" href="/admin/products.php">📦 Cards</a>
  <a class="tab" href="/admin/orders.php">🛒 Orders</a>
</div>

<div style="display:flex;gap:8px;margin-bottom:16px">
  <a href="?filter=all" class="btn btn-sm <?= $filter==='all'?'btn-primary':'btn-outline' ?>">All</a>
  <a href="?filter=pending" class="btn btn-sm <?= $filter==='pending'?'btn-primary':'btn-outline' ?>">⏳ Pending</a>
  <a href="?filter=approved" class="btn btn-sm <?= $filter==='approved'?'btn-primary':'btn-outline' ?>">✅ Approved</a>
</div>

<?php if ($result && $result->num_rows > 0): ?>
<div style="overflow-x:auto">
<table class="table" style="width:100%">
  <thead>
    <tr>
      <th>ID</th>
      <th>Card</th>
      <th>Brand</th>
      <th>BIN</th>
      <th>Price</th>
      <th>Status</th>
      <th>Vendor</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($p = $result->fetch_assoc()):
      $cardNum = decryptData($p['card_number']);
      $brand = $p['card_brand'] ?? 'visa';
    ?>
    <tr>
      <td><?= $p['id'] ?></td>
      <td>
        <span style="font-family:monospace;font-size:12px"><?= maskCard($cardNum) ?></span><br>
        <span style="font-size:11px;color:#888"><?= clean($p['card_name']) ?> | <?= clean($p['card_expiry']) ?></span>
      </td>
      <td><span style="color:<?= getCardBrandColor($brand) ?>;font-weight:700"><?= getCardBrandLabel($brand) ?></span></td>
      <td><code><?= clean($p['card_bin']) ?></code></td>
      <td><strong><?= price($p['price']) ?></strong></td>
      <td><span class="badge badge-<?= $p['vendor_status'] ?>"><?= ucfirst($p['vendor_status'] ?: 'direct') ?></span></td>
      <td><?= clean($p['vendor_name'] ?? 'Admin') ?></td>
      <td>
        <div style="display:flex;gap:4px;flex-wrap:wrap">
          <a href="/admin/edit_product.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-xs">Edit</a>
          <?php if ($p['vendor_status'] === 'pending'): ?>
            <a href="?approve=<?= $p['id'] ?>" class="btn btn-success btn-xs">Approve</a>
            <a href="?decline=<?= $p['id'] ?>" class="btn btn-danger btn-xs">Decline</a>
          <?php endif; ?>
          <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger btn-xs" onclick="return confirm('Delete this card?')">Delete</a>
        </div>
      </td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>
</div>
<?php else: ?>
  <div class="empty-state"><p>No cards yet. <a href="/admin/add_product.php">Add one</a></p></div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
