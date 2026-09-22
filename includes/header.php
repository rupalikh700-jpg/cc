<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') : 'Pepe CC Shop' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<?php
$__loggedIn = isLoggedIn();
$__isAdmin  = $__loggedIn && isAdmin();
$__user     = $__loggedIn ? currentUser() : null;
$__balance  = ($__loggedIn && $__user) ? getBalance((int)$__user['id']) : 0;
$__unread   = ($__loggedIn && $__user) ? getUnreadNotificationCount((int)$__user['id']) : 0;
$__currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
function __navActive(string $path): string {
    global $__currentPath;
    return $__currentPath === $path ? ' active' : '';
}
?>

<!-- ── TOPBAR ───────────────────────────── -->
<header class="topbar">
  <button class="hamburger" id="hamburger" aria-label="Open menu">
    <span></span><span></span><span></span>
  </button>

  <a class="logo" href="/index.php">🐸 PEPE CC</a>

  <nav class="nav-links">
    <a href="/index.php"         class="<?= __navActive('/index.php') ?>">Home</a>
    <a href="/shop.php"          class="<?= __navActive('/shop.php') ?>">Shop</a>
    <a href="/packs.php"         class="<?= __navActive('/packs.php') ?>">Packs</a>
    <a href="/memes.php"         class="<?= __navActive('/memes.php') ?>">Memes</a>
    <a href="/merch.php"         class="<?= __navActive('/merch.php') ?>">Merch</a>
    <?php if ($__loggedIn): ?>
      <a href="/orders.php"      class="<?= __navActive('/orders.php') ?>">Orders</a>
    <?php endif; ?>
    <?php if ($__isAdmin): ?>
      <a href="/admin/index.php" class="admin-link<?= strpos($__currentPath, '/admin') === 0 ? ' active' : '' ?>">Admin</a>
    <?php endif; ?>
  </nav>

  <form class="search-form hide-mobile" action="/shop.php" method="GET">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input type="text" name="search" placeholder="Search cards…" value="<?= htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </form>

  <div class="user-area">
    <?php if ($__loggedIn && $__user): ?>
      <a href="/user/deposit.php" class="wallet-pill hide-mobile">
        💰 <?= formatBalance($__balance) ?>
      </a>
      <a href="/user/notifications.php" class="notif-bell">
        🔔
        <?php if ($__unread > 0): ?>
          <span class="notif-badge"><?= $__unread > 99 ? '99+' : $__unread ?></span>
        <?php endif; ?>
      </a>
      <div class="user-pill">
        <span>👤</span>
        <span class="user-pill-name"><?= htmlspecialchars($__user['username'], ENT_QUOTES, 'UTF-8') ?></span>
        <div class="user-dropdown">
          <a href="/user/balance.php">💰 Wallet (<?= formatBalance($__balance) ?>)</a>
          <a href="/orders.php">📦 My Orders</a>
          <a href="/user/checker.php">🔍 Card Checker</a>
          <a href="/user/vip.php">⭐ VIP Status</a>
          <a href="/user/referral.php">👥 Referrals</a>
          <a href="/user/tickets.php">🎫 Support</a>
          <a href="/user/notifications.php">🔔 Notifications <?= $__unread > 0 ? "({$__unread})" : '' ?></a>
          <?php if ($__isAdmin): ?>
            <hr style="border:none;border-top:1px solid var(--border);margin:4px 0">
            <a href="/admin/index.php" style="color:var(--yellow)">⚙️ Admin Panel</a>
          <?php endif; ?>
          <hr style="border:none;border-top:1px solid var(--border);margin:4px 0">
          <a href="/auth/logout.php">🚪 Sign Out</a>
        </div>
      </div>
    <?php else: ?>
      <a href="/auth/login.php"    class="btn btn-outline btn-sm">Sign In</a>
      <a href="/auth/register.php" class="btn btn-primary btn-sm hide-mobile">Register</a>
    <?php endif; ?>
  </div>
</header>

<!-- ── MOBILE OVERLAY ──────────────────── -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<!-- ── MOBILE MENU ────────────────────── -->
<div class="mobile-menu" id="mobileMenu">
  <div class="mobile-menu-header">
    <a class="logo" href="/index.php">🐸 PEPE CC</a>
    <button class="mobile-close" id="mobileClose" aria-label="Close menu">×</button>
  </div>
  <div class="mobile-menu-links">
    <a href="/index.php">🏠 Home</a>
    <a href="/shop.php">🛍️ Shop</a>
    <a href="/packs.php">📦 Packs</a>
    <a href="/memes.php">🐸 Memes</a>
    <a href="/merch.php">👕 Merch</a>
    <?php if ($__loggedIn && $__user): ?>
      <hr style="border:none;border-top:1px solid rgba(255,255,255,.06);margin:4px 0">
      <a href="/user/balance.php">💰 Wallet: <?= formatBalance($__balance) ?></a>
      <a href="/orders.php">📦 My Orders</a>
      <a href="/user/checker.php">🔍 Card Checker</a>
      <a href="/user/vip.php">⭐ VIP Status</a>
      <a href="/user/referral.php">👥 Referrals</a>
      <a href="/user/tickets.php">🎫 Support</a>
      <a href="/user/notifications.php">🔔 Notifications<?= $__unread > 0 ? " ({$__unread})" : '' ?></a>
      <?php if ($__isAdmin): ?>
        <hr style="border:none;border-top:1px solid rgba(255,255,255,.06);margin:4px 0">
        <a href="/admin/index.php" style="color:var(--yellow)">⚙️ Admin Panel</a>
      <?php endif; ?>
      <hr style="border:none;border-top:1px solid rgba(255,255,255,.06);margin:4px 0">
      <a href="/auth/logout.php">🚪 Sign Out</a>
    <?php else: ?>
      <hr style="border:none;border-top:1px solid rgba(255,255,255,.06);margin:4px 0">
      <a href="/auth/login.php">🔐 Sign In</a>
      <a href="/auth/register.php">📝 Register</a>
    <?php endif; ?>
  </div>
</div>

<div class="container">
<?= showFlash() ?>
