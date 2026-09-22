<?php
// ============================================
// admin/vendors.php — Manage Vendors & Applications
// ============================================
$pageTitle = 'Manage Vendors — Admin';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
requireAdmin();

$db     = getDB();
$filter = $_GET['filter'] ?? 'all'; // all | pending | approved | rejected

// ── APPROVE VENDOR ──────────────────────────
if (isset($_GET['approve'])) {
    $uid = (int)$_GET['approve'];
    $upd = $db->prepare("UPDATE users SET vendor_status='approved' WHERE id=? AND role='vendor'");
    $upd->bind_param("i",$uid); $upd->execute();
    setFlash('success','Vendor approved! They can now log in and upload products. ✅');
    redirect('/admin/vendors.php');
}

// ── REJECT VENDOR ───────────────────────────
if (isset($_GET['reject'])) {
    $uid = (int)$_GET['reject'];
    $upd = $db->prepare("UPDATE users SET vendor_status='rejected' WHERE id=? AND role='vendor'");
    $upd->bind_param("i",$uid); $upd->execute();
    setFlash('error','Vendor rejected.');
    redirect('/admin/vendors.php');
}

// ── DELETE VENDOR ───────────────────────────
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    $del = $db->prepare("DELETE FROM users WHERE id=? AND role='vendor'");
    $del->bind_param("i",$uid); $del->execute();
    setFlash('success','Vendor deleted.');
    redirect('/admin/vendors.php');
}

$where   = ($filter !== 'all') ? "AND vendor_status='$filter'" : '';
$vendors = $db->query("
    SELECT u.*,
           COUNT(DISTINCT p.id) AS product_count,
           COALESCE(SUM(oi.price),0) AS revenue
    FROM users u
    LEFT JOIN products p ON p.vendor_id=u.id AND p.vendor_status='approved'
    LEFT JOIN order_items oi ON oi.vendor_id=u.id
    LEFT JOIN orders o ON o.id=oi.order_id AND o.status='completed'
    WHERE u.role='vendor' $where
    GROUP BY u.id
    ORDER BY u.vendor_applied_at DESC
");
$db->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>🏪 Manage Vendors</h1><p>Approve, reject, or remove vendor accounts</p></div>
</div>

<div class="tabs" style="margin-bottom:20px">
  <a class="tab" href="/admin/index.php">← Dashboard</a>
  <a class="tab <?= $filter==='all'?'active':'' ?>" href="?filter=all">All</a>
  <a class="tab <?= $filter==='pending'?'active':'' ?>" href="?filter=pending">⏳ Pending</a>
  <a class="tab <?= $filter==='approved'?'active':'' ?>" href="?filter=approved">✅ Approved</a>
  <a class="tab <?= $filter==='rejected'?'active':'' ?>" href="?filter=rejected">❌ Rejected</a>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr><th>ID</th><th>Vendor</th><th>Shop Name</th><th>Email</th><th>Products</th><th>Revenue</th><th>Status</th><th>Applied</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php while ($v = $vendors->fetch_assoc()): ?>
    <tr>
      <td class="mono muted"><?= $v['id'] ?></td>
      <td>
        <strong><?= clean($v['first_name'].' '.$v['last_name']) ?></strong><br>
        <span class="muted" style="font-size:12px">@<?= clean($v['username']) ?></span>
      </td>
      <td>
        <strong><?= clean($v['shop_name'] ?? '—') ?></strong>
        <?php if ($v['shop_desc']): ?>
          <br><span class="muted" style="font-size:11px"><?= clean(substr($v['shop_desc'],0,60)) ?>…</span>
        <?php endif; ?>
      </td>
      <td style="font-size:12px"><?= clean($v['email']) ?></td>
      <td class="mono"><?= $v['product_count'] ?></td>
      <td class="mono green">$<?= number_format($v['revenue'],2) ?></td>
      <td>
        <?php if ($v['vendor_status']==='approved'): ?>
          <span class="status status-paid">✅ APPROVED</span>
        <?php elseif ($v['vendor_status']==='pending'): ?>
          <span class="status status-pending">⏳ PENDING</span>
        <?php else: ?>
          <span class="status status-failed">❌ REJECTED</span>
        <?php endif; ?>
      </td>
      <td class="muted" style="font-size:12px">
        <?= $v['vendor_applied_at'] ? date('M j, Y', strtotime($v['vendor_applied_at'])) : '—' ?>
      </td>
      <td style="display:flex;gap:4px;flex-wrap:wrap">
        <?php if ($v['vendor_status']==='pending' || $v['vendor_status']==='rejected'): ?>
          <a class="btn btn-sm" style="background:var(--green);color:#000"
             href="?approve=<?= $v['id'] ?>"
             onclick="return confirm('Approve vendor <?= clean($v['shop_name']) ?>?')">✅ Approve</a>
        <?php endif; ?>
        <?php if ($v['vendor_status']==='pending' || $v['vendor_status']==='approved'): ?>
          <a class="btn btn-sm btn-outline"
             href="?reject=<?= $v['id'] ?>"
             onclick="return confirm('Reject this vendor?')">❌ Reject</a>
        <?php endif; ?>
        <a class="btn btn-sm btn-danger"
           href="?delete=<?= $v['id'] ?>"
           onclick="return confirm('Delete vendor and all their data?')">Del</a>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
