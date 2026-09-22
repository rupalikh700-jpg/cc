<?php
// ============================================
// admin/index.php — Admin Dashboard (Multi-Vendor)
// ============================================
$pageTitle = 'Admin Dashboard — Pepe CC Shop';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
requireAdmin();

$db = getDB();

$totalProducts    = $db->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];
$pendingProducts  = $db->query("SELECT COUNT(*) AS c FROM products WHERE vendor_status='pending'")->fetch_assoc()['c'];
$totalUsers       = $db->query("SELECT COUNT(*) AS c FROM users WHERE role='user'")->fetch_assoc()['c'];
$totalVendors     = $db->query("SELECT COUNT(*) AS c FROM users WHERE role='vendor' AND vendor_status='approved'")->fetch_assoc()['c'];
$pendingVendors   = $db->query("SELECT COUNT(*) AS c FROM users WHERE role='vendor' AND vendor_status='pending'")->fetch_assoc()['c'];
$totalOrders      = $db->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];
$totalRevenue     = $db->query("SELECT COALESCE(SUM(total_price),0) AS r FROM orders WHERE status='completed'")->fetch_assoc()['r'];
$pendingOrders    = $db->query("SELECT COUNT(*) AS c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
$recentOrders     = $db->query("
    SELECT o.*, u.username FROM orders o
    JOIN users u ON u.id=o.user_id
    ORDER BY o.created_at DESC LIMIT 5
");
$db->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>⚙️ Admin Dashboard</h1><p>Manage your Pepe empire</p></div>
  <span class="badge" style="font-size:12px">Admin Only 🔒</span>
</div>

<?php if ($pendingVendors > 0): ?>
<div style="background:rgba(255,214,0,.12);border:1px solid #ffd600;border-radius:8px;padding:12px 18px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center">
  <span style="color:#ffd600;font-weight:600">⚠️ <?= $pendingVendors ?> vendor application(s) awaiting your approval!</span>
  <a class="btn btn-sm" style="background:#ffd600;color:#000" href="/admin/vendors.php?filter=pending">Review Now →</a>
</div>
<?php endif; ?>

<?php if ($pendingProducts > 0): ?>
<div style="background:rgba(255,153,0,.12);border:1px solid #ff9900;border-radius:8px;padding:12px 18px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center">
  <span style="color:#ff9900;font-weight:600">📦 <?= $pendingProducts ?> product(s) pending approval from vendors!</span>
  <a class="btn btn-sm" style="background:#ff9900;color:#000" href="/admin/vendor_products.php?filter=pending">Review Now →</a>
</div>
<?php endif; ?>

<!-- Stats Row -->
<div class="grid grid-3" style="margin-bottom:32px">
  <?php
  $stats = [
    ['num'=>$totalProducts,                    'lbl'=>'Total Products',    'ico'=>'📦'],
    ['num'=>$pendingProducts,                  'lbl'=>'Pending Products',  'ico'=>'⏳'],
    ['num'=>$totalUsers,                       'lbl'=>'Customers',         'ico'=>'👥'],
    ['num'=>$totalVendors,                     'lbl'=>'Active Vendors',    'ico'=>'🏪'],
    ['num'=>$pendingVendors,                   'lbl'=>'Pending Vendors',   'ico'=>'🔔'],
    ['num'=>$totalOrders,                      'lbl'=>'Orders',            'ico'=>'🛒'],
    ['num'=>'$'.number_format($totalRevenue,0),'lbl'=>'Revenue',           'ico'=>'💰'],
    ['num'=>$pendingOrders,                    'lbl'=>'Pending Orders',    'ico'=>'⌛'],
  ];
  foreach ($stats as $s): ?>
    <div class="admin-stat">
      <div style="font-size:28px;margin-bottom:4px"><?= $s['ico'] ?></div>
      <div class="num"><?= $s['num'] ?></div>
      <div class="lbl"><?= $s['lbl'] ?></div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Quick Nav -->
<div class="tabs" style="margin-bottom:28px">
  <a class="tab active" href="/admin/index.php">📊 Dashboard</a>
  <a class="tab" href="/admin/settings.php">⚙️ Settings</a>
  <a class="tab" href="/admin/vendors.php">🏪 Vendors</a>
  <a class="tab" href="/admin/vendor_products.php">📦 Vendor Products</a>
  <a class="tab" href="/admin/products.php">🛍️ All Products</a>
  <a class="tab" href="/admin/users.php">👥 Users</a>
  <a class="tab" href="/admin/orders.php">🛒 Orders</a>
  <a class="tab" href="/admin/add_product.php">➕ Add Product</a>
  <a class="tab" href="/admin/news.php">📰 News</a>
</div>

<!-- Recent Orders -->
<h3 class="section-title">🕐 Recent Orders</h3>
<div class="table-wrap">
  <table>
    <thead>
      <tr><th>Order ID</th><th>User</th><th>Total</th><th>Status</th><th>Date</th></tr>
    </thead>
    <tbody>
    <?php while ($o = $recentOrders->fetch_assoc()): ?>
      <tr>
        <td class="mono green"><?= clean($o['order_code']) ?></td>
        <td>@<?= clean($o['username']) ?></td>
        <td class="mono">$<?= number_format($o['total_price'],2) ?></td>
        <td><span class="status status-<?= $o['status'] ?>"><?= strtoupper($o['status']) ?></span></td>
        <td class="muted" style="font-size:12px"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
<a class="btn btn-outline btn-sm" href="/admin/orders.php" style="margin-top:12px;display:inline-block">View All Orders →</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
