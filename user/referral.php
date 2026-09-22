<?php
// ============================================
// user/referral.php — Referral Program
// ============================================
$pageTitle = 'Referrals — Pepe CC Shop';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please sign in to view your referrals.');
    redirect('/auth/login.php');
}

$uid = (int)$_SESSION['user_id'];
$db = getDB();

$user = currentUser();
$referralCode = $user['referral_code'] ?? '';
$referralCount = getReferralCount($uid);
$totalEarnings = getTotalReferralEarnings($uid);
$referralLink = ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/auth/register.php?ref=" . urlencode($referralCode);

$recentReferrals = $db->query("
    SELECT r.*, u.username
    FROM referrals r
    LEFT JOIN users u ON r.referred_id = u.id
    WHERE r.referrer_id = $uid
    ORDER BY r.created_at DESC
    LIMIT 20
");
$db->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>Referral Program</h1><p class="muted">Invite friends and earn rewards</p></div>
</div>

<div class="tabs" style="margin-bottom:20px">
  <a class="tab" href="/user/balance.php">Wallet</a>
  <a class="tab" href="/user/checker.php">Card Checker</a>
  <a class="tab" href="/user/vip.php">VIP Status</a>
  <a class="tab active" href="/user/referral.php">Referrals</a>
</div>

<!-- Referral Stats -->
<div class="grid grid-3" style="margin-bottom:32px">
  <div class="admin-stat">
    <div style="font-size:28px;margin-bottom:4px">👥</div>
    <div class="num" style="font-size:24px"><?= $referralCount ?></div>
    <div class="lbl">Total Referrals</div>
  </div>
  <div class="admin-stat">
    <div style="font-size:28px;margin-bottom:4px">💰</div>
    <div class="num" style="font-size:24px;color:var(--green)"><?= formatBalance($totalEarnings) ?></div>
    <div class="lbl">Total Earnings</div>
  </div>
  <div class="admin-stat">
    <div style="font-size:28px;margin-bottom:4px">🎁</div>
    <div class="num" style="font-size:24px"><?= formatBalance((float)(getSetting('referral_bonus') ?: '5.00')) ?></div>
    <div class="lbl">Bonus Per Referral</div>
  </div>
</div>

<!-- Referral Link -->
<div style="background:linear-gradient(135deg, var(--surface), var(--surface2));border:1px solid var(--green);border-radius:16px;padding:32px;margin-bottom:32px;text-align:center">
  <h3 style="margin-bottom:8px">Your Referral Code</h3>
  <div class="mono" style="font-size:32px;font-weight:700;color:var(--green);margin-bottom:16px;letter-spacing:4px"><?= clean($referralCode) ?></div>

  <h4 style="margin-bottom:8px;color:var(--muted)">Share This Link</h4>
  <div style="display:flex;gap:8px;max-width:600px;margin:0 auto">
    <input type="text" id="referralLink" value="<?= clean($referralLink) ?>" readonly
           style="flex:1;background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--text);font-family:var(--mono)">
    <button class="btn btn-primary" onclick="copyLink()" id="copyBtn">📋 Copy</button>
  </div>

  <p style="font-size:12px;color:var(--muted);margin-top:12px">
    Share your link with friends. When they register and make a purchase, you earn a bonus!
  </p>
</div>

<!-- Recent Referrals -->
<h3 class="section-title">Recent Referrals</h3>
<?php if ($recentReferrals && $recentReferrals->num_rows > 0): ?>
<div style="overflow-x:auto">
<table class="table" style="width:100%">
  <thead>
    <tr>
      <th>User</th>
      <th>Bonus</th>
      <th>Status</th>
      <th>Date</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($ref = $recentReferrals->fetch_assoc()): ?>
    <tr>
      <td style="font-weight:500"><?= clean($ref['username'] ?? 'Unknown') ?></td>
      <td class="mono green" style="font-weight:600"><?= formatBalance((float)$ref['bonus_amount']) ?></td>
      <td>
        <?php if ($ref['status'] === 'credited'): ?>
          <span class="status status-paid">✅ Credited</span>
        <?php else: ?>
          <span class="status status-pending">⏳ Pending</span>
        <?php endif; ?>
      </td>
      <td class="muted" style="font-size:12px"><?= date('M j, Y', strtotime($ref['created_at'])) ?></td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>
</div>
<?php else: ?>
<div class="empty-state" style="padding:32px">
  <p>No referrals yet. Share your link to start earning!</p>
</div>
<?php endif; ?>

<script>
function copyLink() {
  const input = document.getElementById('referralLink');
  navigator.clipboard.writeText(input.value).then(() => {
    const btn = document.getElementById('copyBtn');
    btn.innerHTML = '✅ Copied!';
    btn.style.color = '#00c853';
    setTimeout(() => { btn.innerHTML = '📋 Copy'; btn.style.color = ''; }, 1500);
  });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
