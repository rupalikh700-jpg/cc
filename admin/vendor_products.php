<?php
// ============================================
// admin/vendor_products.php — Approve Vendor Products
// ============================================
$pageTitle = 'Vendor Products — Admin';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
requireAdmin();

$db     = getDB();
$filter = $_GET['filter'] ?? 'pending';

// ── APPROVE PRODUCT ─────────────────────────
if (isset($_GET['approve'])) {
    $id  = (int)$_GET['approve'];
    $upd = $db->prepare("UPDATE products SET vendor_status='approved' WHERE id=?");
    $upd->bind_param("i",$id); $upd->execute();
    setFlash('success','Product approved and now live in the shop! ✅');
    redirect('/admin/vendor_products.php');
}

// ── REJECT PRODUCT ──────────────────────────
if (isset($_GET['reject'])) {
    $id  = (int)$_GET['reject'];
    $upd = $db->prepare("UPDATE products SET vendor_status='rejected' WHERE id=?");
    $upd->bind_param("i",$id); $upd->execute();
    setFlash('error','Product rejected.');
    redirect('/admin/vendor_products.php');
}

// ── DELETE PRODUCT ──────────────────────────
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $del = $db->prepare("DELETE FROM products WHERE id=? AND vendor_id IS NOT NULL");
    $del->bind_param("i",$id); $del->execute();
    setFlash('success','Product deleted.');
    redirect('/admin/vendor_products.php');
}

$where    = ($filter !== 'all') ? "AND p.vendor_status='$filter'" : "AND p.vendor_id IS NOT NULL";
$products = $db->query("
    SELECT p.*, u.username, u.shop_name
    FROM products p
    JOIN users u ON u.id=p.vendor_id
    WHERE p.vendor_id IS NOT NULL $where
    ORDER BY p.created_at DESC
");
$db->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>📦 Vendor Products</h1><p>Review and approve vendor product submissions</p></div>
</div>

<div class="tabs" style="margin-bottom:20px">
  <a class="tab" href="/admin/index.php">← Dashboard</a>
  <a class="tab <?= $filter==='pending'?'active':'' ?>" href="?filter=pending">⏳ Pending</a>
  <a class="tab <?= $filter==='approved'?'active':'' ?>" href="?filter=approved">✅ Approved</a>
  <a class="tab <?= $filter==='rejected'?'active':'' ?>" href="?filter=rejected">❌ Rejected</a>
  <a class="tab <?= $filter==='all'?'active':'' ?>" href="?filter=all">All</a>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr><th>Product</th><th>Vendor</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Submitted</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php while ($p = $products->fetch_assoc()): ?>
    <tr>
      <td>
        <span style="font-size:22px;margin-right:8px"><?= $p['emoji'] ?></span>
        <strong><?= clean($p['name']) ?></strong><br>
        <span class="muted" style="font-size:11px"><?= clean(substr($p['description'],0,60)) ?>…</span>
      </td>
      <td>
        <strong><?= clean($p['shop_name'] ?? '—') ?></strong><br>
        <span class="muted" style="font-size:12px">@<?= clean($p['username']) ?></span>
      </td>
      <td><span class="tag"><?= $p['category'] ?></span></td>
      <td class="mono green"><?= price($p['price']) ?></td>
      <td class="mono"><?= $p['stock'] ?></td>
      <td>
        <?php if ($p['vendor_status']==='approved'): ?>
          <span class="status status-paid">✅ LIVE</span>
        <?php elseif ($p['vendor_status']==='pending'): ?>
          <span class="status status-pending">⏳ PENDING</span>
        <?php else: ?>
          <span class="status status-failed">❌ REJECTED</span>
        <?php endif; ?>
      </td>
      <td class="muted" style="font-size:12px"><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
      <td style="display:flex;gap:4px;flex-wrap:wrap">
        <?php if ($p['vendor_status'] !== 'approved'): ?>
          <a class="btn btn-sm" style="background:var(--green);color:#000"
             href="?approve=<?= $p['id'] ?>">✅ Approve</a>
        <?php endif; ?>
        <?php if ($p['vendor_status'] !== 'rejected'): ?>
          <a class="btn btn-sm btn-outline"
             href="?reject=<?= $p['id'] ?>"
             onclick="return confirm('Reject this product?')">❌ Reject</a>
        <?php endif; ?>
        <a class="btn btn-sm btn-danger"
           href="?delete=<?= $p['id'] ?>"
           onclick="return confirm('Delete product?')">Del</a>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
