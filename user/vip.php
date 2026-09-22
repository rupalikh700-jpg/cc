<?php
// ============================================
// user/vip.php — VIP Level Status & Info
// ============================================
$pageTitle = 'VIP Status — Pepe CC Shop';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please sign in to view your VIP status.');
    redirect('/auth/login.php');
}

$uid = (int)$_SESSION['user_id'];
$db = getDB();

$user = $db->query("SELECT total_spent FROM users WHERE id=$uid")->fetch_assoc();
$totalSpent = (float)($user['total_spent'] ?? 0);

$vipLevels = $db->query("SELECT * FROM vip_levels ORDER BY min_spent ASC")->fetch_all(MYSQLI_ASSOC);
$currentVip = getVipLevel($uid);

$nextLevel = null;
foreach ($vipLevels as $level) {
    if ($totalSpent < (float)$level['min_spent']) {
        $nextLevel = $level;
        break;
    }
}

$progress = 0;
if ($nextLevel) {
    $currentMin = 0;
    foreach ($vipLevels as $level) {
        if ((float)$level['min_spent'] <= $totalSpent) {
            $currentMin = (float)$level['min_spent'];
        }
    }
    $needed = (float)$nextLevel['min_spent'] - $currentMin;
    $progress = $needed > 0 ? min(100, (($totalSpent - $currentMin) / $needed) * 100) : 0;
} else {
    $progress = 100;
}

$db->close();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>VIP Status</h1><p class="muted">Your loyalty rewards and level progress</p></div>
</div>

<div class="tabs" style="margin-bottom:20px">
  <a class="tab" href="/user/balance.php">Wallet</a>
  <a class="tab" href="/user/checker.php">Card Checker</a>
  <a class="tab active" href="/user/vip.php">VIP Status</a>
  <a class="tab" href="/user/referral.php">Referrals</a>
</div>

<!-- Current VIP Card -->
<div style="background:linear-gradient(135deg, var(--surface), var(--surface2));border:2px solid <?= $currentVip['badge_color'] ?? '#666' ?>;border-radius:16px;padding:32px;margin-bottom:32px;text-align:center">
  <div style="font-size:48px;margin-bottom:8px"><?= $currentVip['badge_icon'] ?? '-' ?></div>
  <h2 style="color:<?= $currentVip['badge_color'] ?? '#666' ?>;margin:0 0 8px"><?= clean($currentVip['name'] ?? 'None') ?></h2>
  <div style="font-size:14px;color:var(--muted);margin-bottom:16px">
    <?= number_format($currentVip['discount_percent'] ?? 0) ?>% discount on all purchases
  </div>

  <div style="background:rgba(255,255,255,.05);border-radius:8px;padding:12px 16px;margin-bottom:16px;display:inline-block">
    <span style="font-size:13px;color:var(--muted)">Total Spent: </span>
    <span class="mono green" style="font-size:20px;font-weight:700"><?= formatBalance($totalSpent) ?></span>
  </div>

  <?php if ($nextLevel): ?>
  <div style="max-width:400px;margin:0 auto">
    <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-bottom:6px">
      <span><?= clean($currentVip['name'] ?? 'None') ?></span>
      <span><?= clean($nextLevel['name']) ?></span>
    </div>
    <div style="background:rgba(255,255,255,.08);border-radius:8px;height:10px;overflow:hidden">
      <div style="background:<?= $nextLevel['badge_color'] ?>;height:100%;width:<?= round($progress) ?>%;border-radius:8px;transition:width .5s"></div>
    </div>
    <div style="font-size:12px;color:var(--muted);margin-top:6px">
      <?= formatBalance(max(0, (float)$nextLevel['min_spent'] - $totalSpent)) ?> more to reach <strong style="color:<?= $nextLevel['badge_color'] ?>"><?= clean($nextLevel['name']) ?></strong>
    </div>
  </div>
  <?php else: ?>
  <div style="font-size:14px;color:var(--green)">You have reached the highest VIP level!</div>
  <?php endif; ?>
</div>

<!-- All VIP Levels -->
<h3 class="section-title">VIP Levels</h3>
<?php if (!empty($vipLevels)): ?>
<div style="overflow-x:auto">
<table class="table" style="width:100%">
  <thead>
    <tr>
      <th>Level</th>
      <th>Required Spend</th>
      <th>Discount</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach (array_reverse($vipLevels) as $level):
      $minSpent = (float)$level['min_spent'];
      $isCurrent = $totalSpent >= $minSpent;
    ?>
    <tr>
      <td>
        <span style="color:<?= $level['badge_color'] ?>;font-weight:700">
          <?= $level['badge_icon'] ?? '' ?> <?= clean($level['name']) ?>
        </span>
      </td>
      <td class="mono"><?= formatBalance($minSpent) ?></td>
      <td class="mono green"><?= number_format((float)$level['discount_percent']) ?>%</td>
      <td>
        <?php if ($isCurrent && (!$nextLevel || $minSpent < (float)$nextLevel['min_spent'])): ?>
          <span style="background:rgba(0,200,83,.15);color:#00c853;padding:2px 10px;border-radius:4px;font-size:12px;font-weight:600">Current</span>
        <?php elseif ($isCurrent): ?>
          <span style="background:rgba(0,200,83,.1);color:#00c853;padding:2px 10px;border-radius:4px;font-size:12px">Unlocked</span>
        <?php else: ?>
          <span style="background:rgba(255,255,255,.05);color:var(--muted);padding:2px 10px;border-radius:4px;font-size:12px">Locked</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php else: ?>
<div class="empty-state" style="padding:32px">
  <p>No VIP levels configured yet.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
