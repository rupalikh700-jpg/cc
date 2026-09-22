<?php
// ============================================
// vendor/register.php — Vendor Registration
// ============================================
$pageTitle = 'Become a Vendor — Pepe CC Shop';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
if (!isLoggedIn()) redirect('/auth/login.php');

$uid = (int)$_SESSION['user_id'];
$db  = getDB();

// Already a vendor?
$existing = $db->query("SELECT * FROM vendor_profiles WHERE user_id=$uid")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing) {
    $shopName = trim($_POST['shop_name'] ?? '');
    $telegram = trim($_POST['telegram']  ?? '');
    $desc     = trim($_POST['description'] ?? '');

    $errors = [];
    if (strlen($shopName) < 3)  $errors[] = 'Shop name must be at least 3 characters.';
    if (strlen($shopName) > 60) $errors[] = 'Shop name must be under 60 characters.';

    if (empty($errors)) {
        $shopName = $db->real_escape_string($shopName);
        $telegram = $db->real_escape_string($telegram);
        $desc     = $db->real_escape_string($desc);

        $db->query("INSERT INTO vendor_profiles (user_id, shop_name, telegram, description, status, created_at) VALUES ($uid, '$shopName', '$telegram', '$desc', 'pending', NOW())");
        $db->query("UPDATE users SET role='vendor' WHERE id=$uid");

        addNotification($uid, 'system', 'Vendor Application Received', 'Your vendor application is under review. We will notify you once approved.', '/vendor/index.php');
        setFlash('success', 'Your vendor application has been submitted! We will review it shortly.');
        $db->close();
        redirect('/vendor/index.php');
    }
}

$db->close();
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width:540px;margin:0 auto">
  <div class="page-header" style="margin-bottom:24px">
    <div><h1>🏪 Become a Vendor</h1><p>Start selling cards on Pepe CC Shop</p></div>
  </div>

  <?php if ($existing): ?>
    <div class="form-card" style="max-width:100%;text-align:center">
      <?php if ($existing['status'] === 'pending'): ?>
        <div style="font-size:48px;margin-bottom:12px">⏳</div>
        <h2>Application Pending</h2>
        <p style="color:var(--muted);margin-top:8px">Your vendor application is under review. We'll notify you once approved.</p>
        <a href="/index.php" class="btn btn-outline btn-sm" style="margin-top:16px">Back to Home</a>
      <?php elseif ($existing['status'] === 'approved'): ?>
        <div style="font-size:48px;margin-bottom:12px">✅</div>
        <h2>You're already a vendor!</h2>
        <a href="/vendor/index.php" class="btn btn-primary" style="margin-top:16px">Go to Vendor Dashboard →</a>
      <?php else: ?>
        <div style="font-size:48px;margin-bottom:12px">❌</div>
        <h2>Application Declined</h2>
        <p style="color:var(--muted);margin-top:8px">Contact support if you believe this is a mistake.</p>
        <a href="/user/tickets.php" class="btn btn-outline btn-sm" style="margin-top:16px">Contact Support</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="form-card" style="max-width:100%">
      <h2 style="margin-bottom:8px">Apply to Sell</h2>
      <p style="color:var(--muted);font-size:13px;margin-bottom:24px">Fill in your shop details. All applications are reviewed by our team.</p>

      <?php if (!empty($errors)): ?>
        <div style="background:rgba(255,68,68,.1);border:1px solid var(--red);border-radius:8px;padding:12px;margin-bottom:16px">
          <?php foreach ($errors as $err): ?>
            <div style="color:var(--red);font-size:13px">⚠ <?= clean($err) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label>Shop Name *</label>
          <input type="text" name="shop_name" placeholder="e.g. Elite Cards Shop" maxlength="60" required value="<?= htmlspecialchars($_POST['shop_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
          <label>Telegram Handle (optional)</label>
          <input type="text" name="telegram" placeholder="@yourhandle" maxlength="64" value="<?= htmlspecialchars($_POST['telegram'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="form-group">
          <label>Brief Description (optional)</label>
          <textarea name="description" rows="3" placeholder="Tell us about your shop…" maxlength="500"><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%">Submit Application</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
