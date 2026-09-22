<?php
// ============================================
// shop.php — Card Shop
// ============================================
$pageTitle = 'Shop — Pepe CC Shop';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/includes/country_flags.php';
if (!isLoggedIn()) redirect('/auth/login.php');

$db = getDB();

// ── Filters ──────────────────────────────────
$brand   = trim($_GET['brand']   ?? '');
$country = trim($_GET['country'] ?? '');
$type    = trim($_GET['type']    ?? '');
$vbv     = trim($_GET['vbv']     ?? '');
$search  = trim($_GET['search']  ?? '');
$sort    = trim($_GET['sort']    ?? 'newest');
$minPrice = (float)($_GET['min_price'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 0);
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 24;

$where = "WHERE p.stock > 0 AND p.vendor_status = 'approved'";
$params = [];
$types  = '';

if ($brand !== '') {
    $where   .= " AND p.card_brand = ?";
    $params[] = $brand;
    $types   .= 's';
}
if ($country !== '') {
    $where   .= " AND p.card_country = ?";
    $params[] = $country;
    $types   .= 's';
}
if ($type !== '') {
    $where   .= " AND p.card_type = ?";
    $params[] = $type;
    $types   .= 's';
}
if ($vbv !== '') {
    $vbvVal   = $vbv === 'vbv' ? 1 : 0;
    $where   .= " AND p.is_vbv = ?";
    $params[] = $vbvVal;
    $types   .= 'i';
}
if ($search !== '') {
    $like     = '%' . $search . '%';
    $where   .= " AND (p.card_bin LIKE ? OR p.card_country LIKE ? OR p.bank_name LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'sss';
}
if ($minPrice > 0) {
    $where   .= " AND p.price >= ?";
    $params[] = $minPrice;
    $types   .= 'd';
}
if ($maxPrice > 0) {
    $where   .= " AND p.price <= ?";
    $params[] = $maxPrice;
    $types   .= 'd';
}

$orderBy = match ($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'popular'    => 'p.sales DESC',
    default      => 'p.created_at DESC',
};

// Count
$countSql = "SELECT COUNT(*) as c FROM products p $where";
if ($types) {
    $cStmt = $db->prepare($countSql);
    $cStmt->bind_param($types, ...$params);
    $cStmt->execute();
    $totalCount = (int)$cStmt->get_result()->fetch_assoc()['c'];
} else {
    $totalCount = (int)$db->query($countSql)->fetch_assoc()['c'];
}

$totalPages = max(1, (int)ceil($totalCount / $perPage));
$offset     = ($page - 1) * $perPage;

$sql = "SELECT p.* FROM products p $where ORDER BY $orderBy LIMIT ? OFFSET ?";
$allParams = array_merge($params, [$perPage, $offset]);
$allTypes  = $types . 'ii';
$stmt = $db->prepare($sql);
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$products = $stmt->get_result();

// Filter options
$brands    = $db->query("SELECT DISTINCT card_brand  FROM products WHERE stock>0 AND vendor_status='approved' AND card_brand  IS NOT NULL ORDER BY card_brand")->fetch_all(MYSQLI_ASSOC);
$countries = $db->query("SELECT DISTINCT card_country FROM products WHERE stock>0 AND vendor_status='approved' AND card_country IS NOT NULL ORDER BY card_country")->fetch_all(MYSQLI_ASSOC);
$db->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>🛍️ Card Shop</h1>
    <p class="card-count"><?= $totalCount ?> card<?= $totalCount !== 1 ? 's' : '' ?> available</p>
  </div>
  <div style="display:flex;gap:8px;align-items:center">
    <button class="filter-toggle-btn btn btn-outline btn-sm" id="filterToggleBtn">☰ Filters</button>
    <select name="sort" onchange="window.location='?'+new URLSearchParams(Object.fromEntries(new URLSearchParams(location.search))).toString().replace(/sort=[^&]*/,'')+'&sort='+this.value" style="background:var(--surface);border:1px solid var(--border);color:var(--text);border-radius:8px;padding:6px 10px;font-size:13px">
      <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest</option>
      <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price ↑</option>
      <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price ↓</option>
      <option value="popular" <?= $sort==='popular'?'selected':'' ?>>Popular</option>
    </select>
  </div>
</div>

<!-- Shop Layout -->
<div class="shop-layout" id="shopLayout">

  <!-- Sidebar overlay (mobile) -->
  <div id="sidebarOverlay" style="display:none;position:fixed;inset:0;z-index:198;background:rgba(0,0,0,.5)" onclick="document.getElementById('shopSidebar').classList.remove('active');this.style.display='none';document.body.style.overflow=''"></div>

  <!-- Sidebar -->
  <aside class="shop-sidebar" id="shopSidebar">
    <form method="GET" action="/shop.php" id="filterForm">

      <?php if ($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>

      <div class="filter-section" style="margin-bottom:16px">
        <h3>🔍 Search</h3>
        <input type="text" name="search" placeholder="BIN, bank, country…" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" style="width:100%;margin-bottom:8px">
      </div>

      <div class="filter-section" style="margin-bottom:16px">
        <h3>💳 Brand</h3>
        <?php foreach ([''=>'All', 'visa'=>'Visa', 'mastercard'=>'Mastercard', 'amex'=>'AMEX', 'discover'=>'Discover'] as $val => $label): ?>
          <label style="display:flex;gap:8px;align-items:center;padding:4px 0;cursor:pointer;font-size:13px;color:var(--text)">
            <input type="radio" name="brand" value="<?= $val ?>" <?= $brand===$val?'checked':'' ?> onchange="this.form.submit()">
            <?= $label ?>
          </label>
        <?php endforeach; ?>
      </div>

      <div class="filter-section" style="margin-bottom:16px">
        <h3>💳 Type</h3>
        <?php foreach ([''=>'All', 'credit'=>'Credit', 'debit'=>'Debit'] as $val => $label): ?>
          <label style="display:flex;gap:8px;align-items:center;padding:4px 0;cursor:pointer;font-size:13px;color:var(--text)">
            <input type="radio" name="type" value="<?= $val ?>" <?= $type===$val?'checked':'' ?> onchange="this.form.submit()">
            <?= $label ?>
          </label>
        <?php endforeach; ?>
      </div>

      <div class="filter-section" style="margin-bottom:16px">
        <h3>🔐 VBV Status</h3>
        <?php foreach ([''=>'All', 'vbv'=>'VBV', 'nonvbv'=>'Non-VBV'] as $val => $label): ?>
          <label style="display:flex;gap:8px;align-items:center;padding:4px 0;cursor:pointer;font-size:13px;color:var(--text)">
            <input type="radio" name="vbv" value="<?= $val ?>" <?= $vbv===$val?'checked':'' ?> onchange="this.form.submit()">
            <?= $label ?>
          </label>
        <?php endforeach; ?>
      </div>

      <div class="filter-section" style="margin-bottom:16px">
        <h3>🌍 Country</h3>
        <select name="country" onchange="this.form.submit()" style="width:100%;margin-top:4px">
          <option value="">All Countries</option>
          <?php foreach ($countries as $c): ?>
            <option value="<?= htmlspecialchars($c['card_country'], ENT_QUOTES, 'UTF-8') ?>" <?= $country===$c['card_country']?'selected':'' ?>>
              <?= getCountryEmoji($c['card_country']) ?> <?= htmlspecialchars($c['card_country'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="filter-section" style="margin-bottom:16px">
        <h3>💰 Price Range</h3>
        <div style="display:flex;gap:8px;margin-top:4px">
          <input type="number" name="min_price" placeholder="Min" value="<?= $minPrice ?: '' ?>" step="0.01" style="flex:1">
          <input type="number" name="max_price" placeholder="Max" value="<?= $maxPrice ?: '' ?>" step="0.01" style="flex:1">
        </div>
        <button type="submit" class="btn btn-outline btn-sm" style="width:100%;margin-top:8px">Apply</button>
      </div>

      <?php if ($brand||$country||$type||$vbv||$search||$minPrice||$maxPrice): ?>
        <a href="/shop.php" class="btn btn-outline btn-sm" style="width:100%;text-align:center">✕ Clear Filters</a>
      <?php endif; ?>

    </form>
  </aside>

  <!-- Cards -->
  <main>
    <?php if ($products && $products->num_rows > 0): ?>
      <div class="card-grid">
        <?php while ($p = $products->fetch_assoc()):
          $cardBrand = $p['card_brand'] ?? 'visa';
          $gradient  = getCardBrandGradient($cardBrand);
          $masked    = maskCard(decryptData($p['card_number']));
        ?>
          <div class="cc-card" style="background:<?= $gradient ?>">
            <div class="cc-card-header">
              <span class="cc-brand"><?= getCardBrandLabel($cardBrand) ?></span>
              <span class="cc-type"><?= ucfirst($p['card_type'] ?? 'credit') ?></span>
            </div>
            <div class="cc-card-number"><?= $masked ?></div>
            <div class="cc-card-footer">
              <div class="cc-info"><span class="cc-label">BIN</span><span class="cc-value"><?= clean($p['card_bin']) ?></span></div>
              <div class="cc-info"><span class="cc-label">Expiry</span><span class="cc-value"><?= clean($p['card_expiry']) ?></span></div>
              <?php if ($p['card_country']): ?>
                <div class="cc-info"><span class="cc-label">Country</span><span class="cc-value"><?= getCountryEmoji($p['card_country']) ?> <?= clean($p['card_country']) ?></span></div>
              <?php endif; ?>
              <?php if (!empty($p['bank_name'])): ?>
                <div class="cc-info" style="max-width:100%;width:100%;"><span class="cc-label">Bank</span><span class="cc-value bank-name"><?= clean($p['bank_name']) ?></span></div>
              <?php endif; ?>
            </div>
            <div class="cc-card-bottom">
              <div>
                <span class="cc-price"><?= price($p['price']) ?></span>
                <?php if ($p['is_vbv']): ?><span class="badge-vbv" style="margin-left:6px">VBV</span><?php else: ?><span class="badge-nonvbv" style="margin-left:6px">Non-VBV</span><?php endif; ?>
              </div>
              <a href="/checkout.php?id=<?= $p['id'] ?>" class="btn btn-buy">Buy Now</a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>

      <?php if ($totalPages > 1): ?>
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-outline btn-sm">← Prev</a>
          <?php endif; ?>
          <span class="page-info">Page <?= $page ?> of <?= $totalPages ?></span>
          <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-outline btn-sm">Next →</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="empty-state">
        <div class="big">🔍</div>
        <p>No cards match your filters.</p>
        <a href="/shop.php" class="btn btn-outline btn-sm" style="margin-top:12px">Clear Filters</a>
      </div>
    <?php endif; ?>
  </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
