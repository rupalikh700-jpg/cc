<?php
// ============================================
// user/deposit.php — Deposit via Oxapay (USDT/BTC)
// ============================================
$pageTitle = 'Deposit Funds — Pepe CC Shop';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/oxapay.php';
require_once __DIR__ . '/../config/telegram.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please sign in to deposit funds.');
    redirect('/auth/login.php');
}

$db = getDB();
$userId = (int)$_SESSION['user_id'];
$currentBalance = getBalance($userId);
$depositMin = (float)getSetting('deposit_min') ?: 10;
$depositMax = (float)getSetting('deposit_max') ?: 10000;
$depositBonusesEnabled = getSetting('deposit_bonuses_enabled') === '1';
$error = '';
$paymentData = null;

$depositBonuses = [];
if ($depositBonusesEnabled) {
    $bonusRes = $db->query("SELECT * FROM deposit_bonuses WHERE is_active=1 ORDER BY min_amount ASC");
    $depositBonuses = $bonusRes ? $bonusRes->fetch_all(MYSQLI_ASSOC) : [];
}

// Check for pending deposit
$pendingDeposit = null;
$pendStmt = $db->prepare("SELECT * FROM deposits WHERE user_id=? AND status='pending' ORDER BY created_at DESC LIMIT 1");
$pendStmt->bind_param("i", $userId);
$pendStmt->execute();
$pendingDeposit = $pendStmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pendingDeposit) {
    $amount = (float)($_POST['amount'] ?? 0);
    $currency = $_POST['currency'] ?? 'USDT';

    if (!in_array($currency, ['USDT', 'BTC'])) {
        $error = 'Invalid currency. Select USDT or BTC.';
    } elseif ($amount < $depositMin) {
        $error = "Minimum deposit is $$depositMin";
    } elseif ($amount > $depositMax) {
        $error = "Maximum deposit is $$depositMax";
    } else {
        $orderCode = 'DEP-' . strtoupper(bin2hex(random_bytes(6)));
        $result = createOxapayPayment($amount, $currency, $userId, $orderCode);

        if ($result['success']) {
            $bonusAmount = 0;
            if ($depositBonusesEnabled) {
                foreach ($depositBonuses as $dbonus) {
                    if ($amount >= (float)$dbonus['min_amount']) {
                        $bonusAmount = min($amount * (int)$dbonus['bonus_percent'] / 100, (float)$dbonus['max_bonus']);
                    }
                }
            }

            $ins = $db->prepare(
                "INSERT INTO deposits (user_id, amount, bonus, currency, network, oxapay_id, oxapay_address, status) VALUES (?,?,?,?,?,?,?,'pending')"
            );
            $network = $result['network'] ?? $currency;
            $ins->bind_param("iddssss", $userId, $amount, $bonusAmount, $currency, $network, $result['payment_id'], $result['address']);
            $ins->execute();

            $paymentData = [
                'pay_url' => $result['pay_url'],
                'amount' => $amount,
                'bonus' => $bonusAmount,
                'currency' => $currency,
                'address' => $result['address'],
                'order_id' => $orderCode,
            ];
        } else {
            $error = $result['error'] ?? 'Payment creation failed.';
        }
    }
}

// Get recent deposits
$depStmt = $db->prepare("SELECT * FROM deposits WHERE user_id=? ORDER BY created_at DESC LIMIT 10");
$depStmt->bind_param("i", $userId);
$depStmt->execute();
$deposits = $depStmt->get_result();
$db->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>💰 Deposit Funds</h1><p>Current Balance: <span class="green mono" style="font-size:20px"><?= formatBalance($currentBalance) ?></span></p></div>
  <a class="btn btn-outline btn-sm" href="/user/balance.php">← My Wallet</a>
</div>

<?php if ($error): ?>
  <div class="form-error" style="margin-bottom:16px"><?= clean($error) ?></div>
<?php endif; ?>

<?php if ($paymentData): ?>
  <!-- Payment Instructions -->
  <div class="form-card" style="max-width:550px;border-color:var(--green)">
    <div style="text-align:center;margin-bottom:20px">
      <div style="font-size:48px">🔗</div>
      <h2 style="color:var(--green)">Payment Created!</h2>
      <p class="muted">Complete your payment within 30 minutes</p>
    </div>

    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:16px">
      <div class="summary-row">
        <span>Amount</span>
        <span class="green mono" style="font-size:18px"><?= formatBalance($paymentData['amount']) ?></span>
      </div>
      <?php if (($paymentData['bonus'] ?? 0) > 0): ?>
      <div class="summary-row">
        <span>Bonus</span>
        <span class="green mono" style="color:#ffd600">+<?= formatBalance($paymentData['bonus']) ?></span>
      </div>
      <?php endif; ?>
      <div class="summary-row">
        <span>Currency</span>
        <span class="mono"><?= clean($paymentData['currency']) ?></span>
      </div>
      <div class="summary-row">
        <span>Order ID</span>
        <span class="mono" style="font-size:12px"><?= clean($paymentData['order_id']) ?></span>
      </div>
    </div>

    <div style="background:rgba(0,200,83,.1);border:1px solid var(--green);border-radius:10px;padding:16px;margin-bottom:16px">
      <label style="color:var(--green);font-weight:600;margin-bottom:8px;display:block">Send exactly this amount to:</label>
      <div style="background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:12px;word-break:break-all;font-family:var(--mono);font-size:14px;user-select:all">
        <?= clean($paymentData['address'] ?: 'Address will appear after redirect') ?>
      </div>
    </div>

    <a href="<?= clean($paymentData['pay_url']) ?>" class="btn btn-primary" style="width:100%;text-align:center" target="_blank">
      Open Payment Page →
    </a>

    <p style="font-size:11px;color:var(--muted);text-align:center;margin-top:12px">
      After payment, balance will be credited automatically via webhook.
    </p>
  </div>

<?php elseif ($pendingDeposit): ?>
  <!-- Pending Deposit -->
  <div class="form-card" style="max-width:550px;border-color:var(--yellow)">
    <div style="text-align:center;margin-bottom:20px">
      <div style="font-size:48px">⏳</div>
      <h2 style="color:var(--yellow)">Pending Deposit</h2>
    </div>
    <div style="background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:16px">
      <div class="summary-row">
        <span>Amount</span>
        <span class="green mono"><?= formatBalance((float)$pendingDeposit['amount']) ?></span>
      </div>
      <div class="summary-row">
        <span>Currency</span>
        <span class="mono"><?= clean($pendingDeposit['currency']) ?></span>
      </div>
      <div class="summary-row">
        <span>Order ID</span>
        <span class="mono" style="font-size:12px"><?= clean($pendingDeposit['oxapay_id'] ?? '—') ?></span>
      </div>
      <div class="summary-row">
        <span>Status</span>
        <span class="status status-pending">⏳ PENDING</span>
      </div>
    </div>
    <p style="font-size:12px;color:var(--muted);text-align:center">
      Complete your pending deposit first. Balance will auto-credit after payment confirmation.
    </p>
  </div>

<?php else: ?>

<?php if (!empty($depositBonuses)): ?>
<div style="max-width:550px;margin:0 auto 20px;background:linear-gradient(135deg,rgba(255,214,0,.1),rgba(255,165,0,.1));border:1px solid rgba(255,214,0,.3);border-radius:12px;padding:20px">
  <h3 style="text-align:center;margin-bottom:12px;color:#ffd600;font-size:16px">💰 Active Deposit Bonuses</h3>
  <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
    <?php foreach ($depositBonuses as $b): ?>
      <div style="background:rgba(255,255,255,.05);border:1px solid rgba(255,214,0,.2);border-radius:8px;padding:12px 16px;text-align:center">
        <div style="font-size:20px;font-weight:700;color:#ffd600">+<?= $b['bonus_percent'] ?>%</div>
        <div style="font-size:11px;color:#888">Min $<?= number_format($b['min_amount'], 0) ?></div>
        <div style="font-size:10px;color:#ffd600">Max <?= formatBalance((float)$b['max_bonus']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

  <!-- Deposit Form -->
  <div class="form-card" style="max-width:550px">
    <h3 style="margin-bottom:20px">Create Deposit</h3>
    <form method="POST">
      <div class="form-group">
        <label>Amount (USD)</label>
        <input type="number" name="amount" min="<?= $depositMin ?>" max="<?= $depositMax ?>" step="0.01"
               value="<?= clean($_POST['amount'] ?? '') ?>"
               placeholder="Enter amount (min $<?= $depositMin ?>, max $<?= $depositMax ?>)" required>
      </div>
      <div class="form-group">
        <label>Payment Currency</label>
        <div style="display:flex;gap:12px">
          <label style="flex:1;cursor:pointer;display:block;padding:16px;border:2px solid var(--border);border-radius:10px;text-align:center;transition:all .2s" id="lbl-usdt">
            <input type="radio" name="currency" value="USDT" <?= ($_POST['currency'] ?? 'USDT') === 'USDT' ? 'checked' : '' ?> onchange="selectCurrency(this)" style="display:none">
            <div style="font-size:32px;margin-bottom:6px">💵</div>
            <div style="font-weight:600">USDT</div>
            <div style="font-size:11px;color:var(--muted)">Tether (TRC20/ERC20)</div>
          </label>
          <label style="flex:1;cursor:pointer;display:block;padding:16px;border:2px solid var(--border);border-radius:10px;text-align:center;transition:all .2s" id="lbl-btc">
            <input type="radio" name="currency" value="BTC" <?= ($_POST['currency'] ?? '') === 'BTC' ? 'checked' : '' ?> onchange="selectCurrency(this)" style="display:none">
            <div style="font-size:32px;margin-bottom:6px">₿</div>
            <div style="font-weight:600">BTC</div>
            <div style="font-size:11px;color:var(--muted)">Bitcoin</div>
          </label>
        </div>
      </div>

      <div style="background:rgba(255,214,0,.1);border:1px solid #ffd600;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:12px;color:#ffd600">
        ⚠️ Deposit will be credited automatically after blockchain confirmation. Minimum: $<?= $depositMin ?> | Maximum: $<?= $depositMax ?>
      </div>

      <button class="btn btn-primary" style="width:100%" type="submit">Create Deposit 💰</button>
    </form>
  </div>
<?php endif; ?>

<!-- Deposit History -->
<?php if ($deposits->num_rows > 0): ?>
<hr class="divider">
<h3 class="section-title">📜 Recent Deposits</h3>
<div class="table-wrap">
  <table>
    <thead>
      <tr><th>ID</th><th>Amount</th><th>Bonus</th><th>Currency</th><th>Status</th><th>Date</th></tr>
    </thead>
    <tbody>
    <?php while ($d = $deposits->fetch_assoc()): ?>
      <tr>
        <td class="mono muted" style="font-size:11px"><?= clean(substr($d['oxapay_id'] ?? $d['id'], 0, 12)) ?>…</td>
        <td class="mono green"><?= formatBalance((float)$d['amount']) ?></td>
        <td class="mono" style="color:<?= ($d['bonus'] ?? 0) > 0 ? '#ffd600' : '#888' ?>"><?= ($d['bonus'] ?? 0) > 0 ? '+' . formatBalance((float)$d['bonus']) : '—' ?></td>
        <td><span class="tag"><?= clean($d['currency']) ?></span></td>
        <td>
          <?php if ($d['status'] === 'paid'): ?>
            <span class="status status-paid">✅ PAID</span>
          <?php elseif ($d['status'] === 'pending'): ?>
            <span class="status status-pending">⏳ PENDING</span>
          <?php else: ?>
            <span class="status status-failed"><?= strtoupper($d['status']) ?></span>
          <?php endif; ?>
        </td>
        <td class="muted" style="font-size:12px"><?= date('M j, Y H:i', strtotime($d['created_at'])) ?></td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<script>
function selectCurrency(el) {
  document.getElementById('lbl-usdt').style.borderColor = el.value === 'USDT' ? 'var(--green)' : 'var(--border)';
  document.getElementById('lbl-btc').style.borderColor = el.value === 'BTC' ? 'var(--green)' : 'var(--border)';
}
document.addEventListener('DOMContentLoaded', () => {
  const checked = document.querySelector('input[name=currency]:checked');
  if (checked) selectCurrency(checked);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>