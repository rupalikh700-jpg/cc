<?php
// ============================================
// admin/users.php — Manage Users (Multi-Vendor)
// ============================================
$pageTitle = 'Manage Users — Admin';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
requireAdmin();

$db = getDB();

if (isset($_GET['role'])) {
    $uid  = (int)$_GET['uid'];
    $role = in_array($_GET['role'], ['user','admin','vendor']) ? $_GET['role'] : 'user';
    if ($uid !== (int)$_SESSION['user_id']) {
        $upd = $db->prepare("UPDATE users SET role=? WHERE id=?");
        $upd->bind_param("si",$role,$uid); $upd->execute();
    }
    redirect('/admin/users.php');
}

if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid !== (int)$_SESSION['user_id']) {
        $del = $db->prepare("DELETE FROM users WHERE id=?");
        $del->bind_param("i",$uid); $del->execute();
        setFlash('success','User deleted.');
    } else {
        setFlash('error','Cannot delete yourself.');
    }
    redirect('/admin/users.php');
}

$filter = $_GET['filter'] ?? 'all';
switch($filter) {
    case 'vendors': $where = "WHERE role='vendor'"; break;
    case 'users': $where = "WHERE role='user'"; break;
    case 'admins': $where = "WHERE role='admin'"; break;
    case 'pending': $where = "WHERE role='vendor' AND vendor_status='pending'"; break;
    default: $where = '';
}

$users = $db->query("
    SELECT u.*, COUNT(o.id) AS order_count
    FROM users u
    LEFT JOIN orders o ON o.user_id=u.id
    $where
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$db->close();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>👥 Manage Users</h1></div>
</div>

<div class="tabs" style="margin-bottom:20px">
  <a class="tab" href="/admin/index.php">← Dashboard</a>
  <a class="tab <?=$filter==='all'?'active':''?>" href="?filter=all">All</a>
  <a class="tab <?=$filter==='users'?'active':''?>" href="?filter=users">Customers</a>
  <a class="tab <?=$filter==='vendors'?'active':''?>" href="?filter=vendors">🏪 Vendors</a>
  <a class="tab <?=$filter==='pending'?'active':''?>" href="?filter=pending">⏳ Pending Vendors</a>
  <a class="tab <?=$filter==='admins'?'active':''?>" href="?filter=admins">Admins</a>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr><th>ID</th><th>Name</th><th>Email</th><th>Username</th><th>Role</th><th>Vendor Status</th><th>Orders</th><th>Joined</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php while ($u = $users->fetch_assoc()): $self = ($u['id']==(int)$_SESSION['user_id']); ?>
    <tr>
      <td class="mono muted"><?= $u['id'] ?></td>
      <td><?= clean($u['first_name'].' '.$u['last_name']) ?></td>
      <td style="font-size:12px"><?= clean($u['email']) ?></td>
      <td>@<?= clean($u['username']) ?></td>
      <td>
        <span class="badge" style="<?= $u['role']==='admin'?'background:rgba(255,214,0,.15);color:#ffd600':($u['role']==='vendor'?'background:rgba(0,200,83,.15);color:var(--green)':'') ?>">
          <?= $u['role'] === 'vendor' ? '🏪 '.$u['role'] : $u['role'] ?>
        </span>
      </td>
      <td>
        <?php if ($u['role']==='vendor'): ?>
          <?php if ($u['vendor_status']==='approved'): ?>
            <span class="status status-paid" style="font-size:11px">✅ Approved</span>
          <?php elseif ($u['vendor_status']==='pending'): ?>
            <span class="status status-pending" style="font-size:11px">⏳ Pending</span>
          <?php else: ?>
            <span class="status status-failed" style="font-size:11px">❌ Rejected</span>
          <?php endif; ?>
        <?php else: ?>
          <span class="muted" style="font-size:12px">—</span>
        <?php endif; ?>
      </td>
      <td class="mono"><?= $u['order_count'] ?></td>
      <td class="muted" style="font-size:12px"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
      <td style="display:flex;gap:4px;flex-wrap:wrap">
        <?php if (!$self): ?>
          <?php if ($u['role']==='user'): ?>
            <a class="btn btn-sm btn-outline" href="?role=admin&uid=<?=$u['id']?>">→ Admin</a>
          <?php elseif ($u['role']==='admin'): ?>
            <a class="btn btn-sm btn-outline" href="?role=user&uid=<?=$u['id']?>">→ User</a>
          <?php endif; ?>
          <a class="btn btn-sm btn-danger"
             href="?delete=<?=$u['id']?>"
             onclick="return confirm('Delete user <?= clean($u['username']) ?>?')">Del</a>
        <?php else: ?>
          <span class="muted" style="font-size:12px">(you)</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
