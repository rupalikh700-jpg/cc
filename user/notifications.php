<?php
$pageTitle = 'Notifications';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
if (!isLoggedIn()) redirect('/auth/login.php');

$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_all') {
        markAllNotificationsRead($uid);
    } elseif ($action === 'mark_one') {
        markNotificationRead((int)$_POST['notif_id']);
    }
    redirect('/user/notifications.php');
}

$notifications = getNotifications($uid, 50);
$db = getDB();
$db->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid AND is_read=0");
$db->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>Notifications</h1></div>
  <a class="btn btn-outline btn-sm" href="/index.php">Back</a>
</div>

<?php if (empty($notifications)): ?>
  <div class="empty-state"><p>No notifications yet.</p></div>
<?php else: ?>
  <div class="notif-list">
    <?php foreach ($notifications as $n):
      $iconMap = ['order'=>'📦','deposit'=>'💰','system'=>'⚙️','support'=>'💬','alert'=>'🔔'];
      $icon = $iconMap[$n['type']] ?? '🔔';
      $isUnread = !$n['is_read'];
      $timeAgo = time() - strtotime($n['created_at']);
      if ($timeAgo < 60) $time = 'Just now';
      elseif ($timeAgo < 3600) $time = floor($timeAgo/60).'m ago';
      elseif ($timeAgo < 86400) $time = floor($timeAgo/3600).'h ago';
      else $time = date('M j', strtotime($n['created_at']));
    ?>
      <a href="<?= $n['link'] ?: '/user/notifications.php' ?>" class="notif-item <?= $isUnread ? 'unread' : '' ?>">
        <div class="notif-icon <?= $n['type'] ?>"><?= $icon ?></div>
        <div class="notif-content">
          <div class="notif-title"><?= clean($n['title']) ?></div>
          <div class="notif-msg"><?= clean($n['message']) ?></div>
        </div>
        <div class="notif-time"><?= $time ?></div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
