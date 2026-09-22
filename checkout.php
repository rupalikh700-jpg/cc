<?php
$pageTitle = 'Checkout';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/oxapay.php';
require_once __DIR__ . '/config/bin_lookup.php';
require_once __DIR__ . '/includes/country_flags.php';
require_once __DIR__ . '/config/telegram.php';

$db = getDB();
$pid = (int)($_GET['id'] ?? 0);
if (!$pid) { redirect('/shop.php'); }

$product = $db->query("SELECT * FROM products WHERE id=$pid AND stock > 0 AND vendor_status='approved'")->fetch_assoc();
if (!$product) { setFlash('error','Card not available.'); redirect('/shop.php'); }

$brand = $product['card_brand'] ?? 'visa';
$gradient = getCardBrandGradient($brand);
$masked = maskCard(decryptData($product['card_number']));
$currentUser = currentUser();
$balance = $currentUser ? getBalance((int)$currentUser['id']) : 0;

$vipDiscount = 0;
$finalPrice = (float)$product['price'];
if ($currentUser) {
    $vipDiscount = getVipDiscount((int)$currentUser['id']);
    if ($vipDiscount > 0) {
        $finalPrice = $finalPrice * (1 - $vipDiscount / 100);
    }
}
$showBalanceRange = getSetting('show_balance_range') === '1';
$showVbvStatus = getSetting('show_vbv_status') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $paymentMethod = $_POST['payment_method'] ?? '';
    $uid = (int)$currentUser['id'];
    $price = (float)$product['price'];

    if ($paymentMethod === 'wallet') {
        if (!hasEnoughBalance($uid, $finalPrice)) {
            setFlash('error', 'Insufficient balance. Please deposit via Oxapay.');
            redirect('/checkout.php?id=' . $pid);
        }

        $db2 = getDB();
        $db2->begin_transaction();
        try {
            deductBalance($uid, $finalPrice);

            $orderCode = generateOrderCode();
            $insOrder = $db2->prepare("INSERT INTO orders (user_id, order_code, total_price, payment_method, status) VALUES (?, ?, ?, 'wallet', 'completed')");
            $insOrder->bind_param("isd", $uid, $orderCode, $finalPrice);
            $insOrder->execute();
            $orderId = $db2->insert_id;

            $encNum = $product['card_number'];
            $encCvv = $product['card_cvv'];
            $insItem = $db2->prepare("INSERT INTO order_items (order_id, product_id, price, card_number, card_expiry, card_cvv, card_name, card_bin, card_type, card_brand, card_email, card_phone, card_dob, card_address, card_city, card_state, card_zip, card_country, vendor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insItem->bind_param("iidsssssssssssssssi",
                $orderId, $pid, $finalPrice, $encNum, $product['card_expiry'], $encCvv,
                $product['card_name'], $product['card_bin'], $product['card_type'], $product['card_brand'],
                $product['card_email'], $product['card_phone'], $product['card_dob'],
                $product['card_address'], $product['card_city'], $product['card_state'],
                $product['card_zip'], $product['card_country'], $product['vendor_id']
            );
            $insItem->execute();

            $db2->query("UPDATE products SET stock = stock - 1 WHERE id = $pid");

            $db2->commit();

            $db2->query("UPDATE users SET total_spent = total_spent + $finalPrice WHERE id = $uid");

            $notifyOrder = $db2->query("SELECT * FROM orders WHERE id = $orderId")->fetch_assoc();
            $notifyItems = $db2->query("SELECT * FROM order_items WHERE order_id = $orderId")->fetch_all(MYSQLI_ASSOC);
            $db2->close();

            tgNotifyNewOrder($notifyOrder, $notifyItems);

            createNotification($uid, 'Order Completed', "Your order #$orderCode has been paid! Check your orders for card details.", 'order', '/orders.php');
            createNotification(1, 'New Order', "New order #$orderCode from " . ($currentUser['username'] ?? 'user') . " - $" . number_format($finalPrice, 2), 'order', '/admin/orders.php');

            setFlash('success', 'Payment successful! Your card details are ready.');
            redirect('/orders.php');
        } catch (Exception $e) {
            $db2->rollback();
            $db2->close();
            setFlash('error', 'Payment failed. Please try again.');
            redirect('/checkout.php?id=' . $pid);
        }
    } elseif ($paymentMethod === 'oxapay_deposit') {
        $amount = max((float)$finalPrice, 1.00);
        $uid = (int)$currentUser['id'];
        $orderCode = generateOrderCode();
        $result = createOxapayPayment($amount, 'USDT', $uid, $orderCode);
        if ($result['success']) {
            header('Location: ' . $result['pay_url']);
            exit;
        } else {
            setFlash('error', 'Payment link failed. ' . ($result['error'] ?? 'Please try again.'));
            redirect('/checkout.php?id=' . $pid);
        }
    } else {
        setFlash('error', 'Please select a payment method.');
        redirect('/checkout.php?id=' . $pid);
    }
}

$db->close();
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div><h1>Checkout</h1></div>
  <a class="btn btn-outline btn-sm" href="/shop.php">← Back to Shop</a>
</div>

<div style="display:grid;grid-template-columns: 1fr 1fr;gap:24px;max-width:900px;margin:0 auto">

  <div class="form-card">
    <h3 style="margin-bottom:16px">💳 Card Details</h3>
    <div class="cc-card" style="background:<?= $gradient ?>;margin-bottom:16px">
      <div class="cc-card-header">
        <span class="cc-brand"><?= getCardBrandLabel($brand) ?></span>
        <span class="cc-type"><?= ucfirst($product['card_type'] ?? 'credit') ?></span>
      </div>
      <div class="cc-card-number"><?= $masked ?></div>
      <div class="cc-card-footer">
        <div class="cc-info"><span class="cc-label">BIN</span><span class="cc-value"><?= clean($product['card_bin']) ?></span></div>
        <?php if (!empty($product['bank_name'])): ?>
        <div class="cc-info"><span class="cc-label">Bank</span><span class="cc-value"><?= clean($product['bank_name']) ?></span></div>
        <?php endif; ?>
        <div class="cc-info"><span class="cc-label">Expiry</span><span class="cc-value"><?= clean($product['card_expiry']) ?></span></div>
        <div class="cc-info"><span class="cc-label">Type</span><span class="cc-value"><?= ucfirst($product['card_type']) ?></span></div>
        <?php if ($product['card_country']): ?>
        <div class="cc-info"><span class="cc-label">Country</span><span class="cc-value"><?= getCountryEmoji($product['card_country']) ?> <?= clean($product['card_country']) ?></span></div>
        <?php endif; ?>
      </div>
      <div style="padding:0 16px 8px;display:flex;flex-wrap:wrap;gap:6px">
        <?php if ($showBalanceRange): ?>
          <?= formatBalanceRange($product['card_balance_min'], $product['card_balance_max']) ?>
        <?php endif; ?>
        <?php if ($showVbvStatus): ?>
          <?= getVbvBadge($product['card_vbv'] ?? 'unknown') ?>
        <?php endif; ?>
      </div>
      <div class="cc-card-bottom">
        <span class="cc-price"><?= price($finalPrice) ?></span>
        <?php if ($vipDiscount > 0): ?>
          <span style="font-size:12px;color:#ffd600">VIP -<?= $vipDiscount ?>% off</span>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($product['card_name']): ?>
    <div style="font-size:13px;color:#888;margin-bottom:8px"><strong>Name:</strong> <?= clean($product['card_name']) ?></div>
    <?php endif; ?>
    <?php if ($product['card_address']): ?>
    <div style="font-size:13px;color:#888"><strong>Address:</strong> <?= clean(implode(', ', array_filter([$product['card_address'], $product['card_city'], $product['card_state'], $product['card_zip']]))) ?></div>
    <?php endif; ?>
  </div>

  <div class="form-card">
    <h3 style="margin-bottom:16px">💰 Payment</h3>

    <?php if (!isLoggedIn()): ?>
      <div style="background:rgba(255,214,0,.1);border:1px solid #ffd600;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#ffd600">
        Please <a href="/auth/login.php" style="color:#ffd600;text-decoration:underline">login</a> to purchase.
      </div>
    <?php else: ?>

    <div style="background:rgba(0,200,83,.08);border:1px solid rgba(0,200,83,.3);border-radius:8px;padding:12px 16px;margin-bottom:20px">
      <div style="font-size:13px;color:#888">Wallet Balance</div>
      <div style="font-size:24px;font-weight:700;color:#00c853"><?= formatBalance($balance) ?></div>
    </div>

    <form method="POST">
      <input type="hidden" name="product_id" value="<?= $pid ?>">

      <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px">
        <label class="payment-option <?= $balance >= $product['price'] ? '' : 'disabled' ?>">
          <input type="radio" name="payment_method" value="wallet" <?= $balance >= $product['price'] ? 'checked' : '' ?> <?= $balance >= $product['price'] ? '' : 'disabled' ?>>
          <div>
            <strong>💳 Pay from Wallet</strong>
            <div style="font-size:12px;color:#888">
              <?= $balance >= $product['price'] ? 'Sufficient balance' : 'Insufficient balance' ?>
            </div>
          </div>
        </label>

        <label class="payment-option">
          <input type="radio" name="payment_method" value="oxapay_deposit" <?= $balance < $product['price'] ? 'checked' : '' ?>>
          <div>
            <strong>🪙 Deposit via Oxapay (USDT/BTC)</strong>
            <div style="font-size:12px;color:#888">Top up your wallet first, then pay</div>
          </div>
        </label>
      </div>

      <div style="background:rgba(255,255,255,.05);border-radius:8px;padding:12px 16px;margin-bottom:16px">
        <?php if ($vipDiscount > 0): ?>
        <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px">
          <span style="color:#888">Original Price</span>
          <span style="color:#888;text-decoration:line-through"><?= price($product['price']) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:13px">
          <span style="color:#ffd600">VIP Discount (-<?= $vipDiscount ?>%)</span>
          <span style="color:#ffd600">-<?= price($product['price'] - $finalPrice) ?></span>
        </div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span style="font-size:14px;color:#888">Total</span>
          <span style="font-size:20px;font-weight:700"><?= price($finalPrice) ?></span>
        </div>
      </div>

      <button class="btn btn-primary" type="submit" style="width:100%">Complete Purchase</button>
    </form>

    <?php endif; ?>
  </div>
</div>

<style>
.payment-option {
  display:flex;align-items:center;gap:12px;padding:12px 16px;border:1px solid rgba(255,255,255,.1);border-radius:8px;cursor:pointer;transition:all .2s;
}
.payment-option:hover { border-color:#00c853; }
.payment-option input:checked + div { color:#00c853; }
.payment-option.disabled { opacity:.5;cursor:not-allowed; }
.payment-option.disabled:hover { border-color:rgba(255,255,255,.1); }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
