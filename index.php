<?php
// ============================================
// index.php — Homepage
// ============================================
$pageTitle = 'Pepe CC Shop - Home';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
if (!isLoggedIn()) redirect('/auth/login.php');
require_once __DIR__ . '/config/oxapay.php';
require_once 'includes/header.php';

$db = getDB();
$featured = $db->query("SELECT * FROM products WHERE stock > 0 AND vendor_status='approved' ORDER BY sales DESC LIMIT 3");

$showDailyCounter = getSetting('show_daily_counter') === '1';
$depositBonusesEnabled = getSetting('deposit_bonuses_enabled') === '1';
$packsEnabled = getSetting('packs_enabled') === '1';

$bonuses = [];
if ($depositBonusesEnabled) {
    $bonusRes = $db->query("SELECT * FROM deposit_bonuses WHERE is_active=1 ORDER BY min_amount ASC");
    $bonuses = $bonusRes ? $bonusRes->fetch_all(MYSQLI_ASSOC) : [];
}

$packs = [];
if ($packsEnabled) {
    $packRes = $db->query("SELECT * FROM card_bundles WHERE is_active=1 ORDER BY price ASC");
    $packs = $packRes ? $packRes->fetch_all(MYSQLI_ASSOC) : [];
}

$db->close();
?>

<div class="hero">
  <div style="font-size:100px;line-height:1;margin-bottom:16px">💳</div>
  <h1>PEPE CC SHOP</h1>
  <p>Premium CC cards with instant delivery. Secure, fast, reliable.</p>
  <?php if ($showDailyCounter): ?>
    <?php $dailyCount = getDailyCardCount(); ?>
    <?php if ($dailyCount > 0): ?>
      <div style="background:rgba(0,200,83,.15);border:1px solid rgba(0,200,83,.3);border-radius:8px;padding:8px 20px;display:inline-block;margin-bottom:16px;font-size:14px;color:#00c853">
        🆕 <?= $dailyCount ?> card<?= $dailyCount !== 1 ? 's' : '' ?> added today
      </div>
    <?php endif; ?>
  <?php endif; ?>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
    <a class="btn btn-primary" href="/shop.php">Browse Cards →</a>
    <?php if (!isLoggedIn()): ?>
      <a class="btn btn-outline" href="/auth/register.php">Create Account</a>
    <?php endif; ?>
  </div>
</div>

<p class="section-title">🔥 Featured Cards</p>
<div class="card-grid" style="max-width:1000px;margin:0 auto">
<?php if ($featured && $featured->num_rows > 0): ?>
  <?php while ($p = $featured->fetch_assoc()):
    $brand = $p['card_brand'] ?? 'visa';
    $gradient = getCardBrandGradient($brand);
    $masked = maskCard(decryptData($p['card_number']));
  ?>
    <div class="cc-card" style="background:<?= $gradient ?>">
      <div class="cc-card-header">
        <span class="cc-brand"><?= getCardBrandLabel($brand) ?></span>
        <span class="cc-type"><?= ucfirst($p['card_type'] ?? 'credit') ?></span>
      </div>
      <div class="cc-card-number"><?= $masked ?></div>
      <div class="cc-card-footer">
        <div class="cc-info"><span class="cc-label">BIN</span><span class="cc-value"><?= clean($p['card_bin']) ?></span></div>
        <div class="cc-info"><span class="cc-label">Expiry</span><span class="cc-value"><?= clean($p['card_expiry']) ?></span></div>
        <?php if ($p['card_country']): ?>
        <div class="cc-info"><span class="cc-label">Country</span><span class="cc-value"><?= clean($p['card_country']) ?></span></div>
        <?php endif; ?>
      </div>
      <div class="cc-card-bottom">
        <span class="cc-price"><?= price($p['price']) ?></span>
        <a href="/checkout.php?id=<?= $p['id'] ?>" class="btn btn-buy">Buy Now</a>
      </div>
    </div>
  <?php endwhile; ?>
<?php endif; ?>
</div>

<?php if (!empty($bonuses)): ?>
<hr class="divider">
<div style="max-width:800px;margin:0 auto 24px;background:linear-gradient(135deg,rgba(255,214,0,.1),rgba(255,165,0,.1));border:1px solid rgba(255,214,0,.3);border-radius:12px;padding:24px">
  <h3 style="text-align:center;margin-bottom:16px;color:#ffd600">💰 Deposit Bonuses</h3>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
    <?php foreach ($bonuses as $b): ?>
      <div style="background:rgba(255,255,255,.05);border:1px solid rgba(255,214,0,.2);border-radius:8px;padding:16px;text-align:center;min-width:140px">
        <div style="font-size:24px;font-weight:700;color:#ffd600">+<?= $b['bonus_percent'] ?>%</div>
        <div style="font-size:12px;color:#888;margin-top:4px">Min. $<?= number_format($b['min_amount'], 0) ?></div>
        <div style="font-size:11px;color:#ffd600;margin-top:2px">Max $<?= number_format($b['max_bonus'], 0) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
  <div style="text-align:center;margin-top:12px">
    <a href="/user/deposit.php" class="btn btn-primary btn-sm">Deposit Now →</a>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($packs)): ?>
<p class="section-title">📦 Card Packs</p>
<div class="card-grid" style="max-width:1000px;margin:0 auto">
  <?php foreach ($packs as $pack): ?>
    <div class="cc-card" style="background:linear-gradient(135deg,#1a1a2e,#16213e)">
      <div style="padding:20px;text-align:center">
        <div style="font-size:36px;margin-bottom:8px">📦</div>
        <h3 style="margin:0 0 8px;font-size:16px"><?= clean($pack['name']) ?></h3>
        <p style="font-size:12px;color:#888;margin:0 0 12px"><?= clean($pack['description'] ?? '') ?></p>
        <div style="font-size:13px;color:#aaa;margin-bottom:8px">
          <?= $pack['card_count'] ?> card<?= $pack['card_count'] !== 1 ? 's' : '' ?>
          <?php if ($pack['card_brand']): ?> • <?= ucfirst($pack['card_brand']) ?><?php endif; ?>
          <?php if ($pack['card_country']): ?> • <?= clean($pack['card_country']) ?><?php endif; ?>
        </div>
        <div style="font-size:24px;font-weight:700;color:#00c853;margin-bottom:12px"><?= price($pack['price']) ?></div>
        <a href="/packs.php?id=<?= $pack['id'] ?>" class="btn btn-buy" style="width:100%;text-align:center">Buy Pack</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<hr class="divider">

<div class="grid grid-3" style="text-align:center;margin-top:8px">
  <div>
    <div style="font-size:40px;margin-bottom:8px">💳</div>
    <div style="font-weight:600;margin-bottom:4px">Visa Cards</div>
    <div class="muted" style="font-size:13px">Premium Visa credit & debit cards</div>
    <a href="/shop.php?brand=visa" class="btn btn-outline btn-sm" style="margin-top:8px">Browse</a>
  </div>
  <div>
    <div style="font-size:40px;margin-bottom:8px">🔴</div>
    <div style="font-weight:600;margin-bottom:4px">Mastercard</div>
    <div class="muted" style="font-size:13px">Premium Mastercard cards</div>
    <a href="/shop.php?brand=mastercard" class="btn btn-outline btn-sm" style="margin-top:8px">Browse</a>
  </div>
  <div>
    <div style="font-size:40px;margin-bottom:8px">🔵</div>
    <div style="font-weight:600;margin-bottom:4px">American Express</div>
    <div class="muted" style="font-size:13px">Premium AMEX cards</div>
    <a href="/shop.php?brand=amex" class="btn btn-outline btn-sm" style="margin-top:8px">Browse</a>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
