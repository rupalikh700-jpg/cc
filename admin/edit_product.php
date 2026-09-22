<?php
// ============================================
// admin/edit_product.php — Edit a Card
// ============================================
$pageTitle = 'Edit Card — Admin';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
if (!isAdmin()) { redirect('/auth/login.php'); }

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect('/admin/products.php'); }

$stmt = $db->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
$stmt->bind_param("i",$id); $stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) { redirect('/admin/products.php'); }

$error = '';
$countries = getCountryList();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cardNumber = preg_replace('/\s/', '', $_POST['card_number'] ?? '');
    $cardExpiry = trim($_POST['card_expiry'] ?? '');
    $cardCvv = trim($_POST['card_cvv'] ?? '');
    $cardName = strtoupper(trim($_POST['card_name'] ?? ''));
    $cardBin = trim($_POST['card_bin'] ?? '');
    $cardType = $_POST['card_type'] ?? 'credit';
    $cardBrand = $_POST['card_brand'] ?? 'visa';
    $cardEmail = trim($_POST['card_email'] ?? '');
    $cardPhone = trim($_POST['card_phone'] ?? '');
    $cardDob = trim($_POST['card_dob'] ?? '');
    $cardAddress = trim($_POST['card_address'] ?? '');
    $cardCity = trim($_POST['card_city'] ?? '');
    $cardState = trim($_POST['card_state'] ?? '');
    $cardZip = trim($_POST['card_zip'] ?? '');
    $bankName = trim($_POST['bank_name'] ?? '');
    $cardCountry = trim($_POST['card_country'] ?? '');
    $cardBalanceMin = (float)($_POST['card_balance_min'] ?? 0);
    $cardBalanceMax = (float)($_POST['card_balance_max'] ?? 0);
    $cardVbv = $_POST['card_vbv'] ?? 'unknown';
    $cardStatus = $_POST['card_status'] ?? 'unknown';
    $price = (float)($_POST['price'] ?? 0);

    if (strlen(preg_replace('/\D/', '', $cardNumber)) < 13) {
        $error = 'Invalid card number.';
    } elseif (!preg_match('/^\d{2}\/\d{2}$/', $cardExpiry)) {
        $error = 'Invalid expiry (MM/YY).';
    } elseif (strlen($cardCvv) < 3) {
        $error = 'Invalid CVV.';
    } elseif (!$cardName) {
        $error = 'Name on card is required.';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0.';
    } else {
        $encNumber = encryptData($cardNumber);
        $encCvv = encryptData($cardCvv);
        $upd = $db->prepare(
            "UPDATE products SET price=?,card_number=?,card_expiry=?,card_cvv=?,card_name=?,card_bin=?,card_type=?,card_brand=?,card_email=?,card_phone=?,card_dob=?,card_address=?,card_city=?,card_state=?,card_zip=?,card_country=?,bank_name=?,card_balance_min=?,card_balance_max=?,card_vbv=?,card_status=? WHERE id=?"
        );
        $upd->bind_param("dssssssssssssssssddssi",
            $price, $encNumber, $cardExpiry, $encCvv, $cardName, $cardBin,
            $cardType, $cardBrand, $cardEmail, $cardPhone, $cardDob,
            $cardAddress, $cardCity, $cardState, $cardZip, $cardCountry,
            $bankName, $cardBalanceMin, $cardBalanceMax, $cardVbv, $cardStatus, $id
        );
        $upd->execute();
        $db->close();
        setFlash('success', 'Card updated successfully!');
        redirect('/admin/products.php');
    }
}

// Decrypt for display
$dispNumber = $product['card_number'] ? decryptData($product['card_number']) : '';
$dispCvv = $product['card_cvv'] ? decryptData($product['card_cvv']) : '';
$db->close();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>✏️ Edit Card</h1></div>
  <a class="btn btn-outline btn-sm" href="/admin/products.php">← Back</a>
</div>

<?php if ($error): ?>
  <div class="form-error" style="margin-bottom:16px"><?= clean($error) ?></div>
<?php endif; ?>

<form method="POST">
<div class="form-card" style="max-width:700px">

  <!-- CARD DETAILS -->
  <h3 style="margin-bottom:16px">💳 Card Details</h3>

  <div class="form-group">
    <label>Card Number</label>
    <input type="text" name="card_number" maxlength="19" placeholder="4242 4242 4242 4242"
           value="<?= clean($dispNumber) ?>" required oninput="fmtCard(this)">
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Expiry (MM/YY)</label>
      <input type="text" name="card_expiry" maxlength="5" placeholder="12/26"
             value="<?= clean($_POST['card_expiry'] ?? $product['card_expiry']) ?>" required oninput="fmtExp(this)">
    </div>
    <div class="form-group">
      <label>CVV</label>
      <input type="text" name="card_cvv" maxlength="4" placeholder="123"
             value="<?= clean($dispCvv) ?>" required>
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Name on Card</label>
      <input type="text" name="card_name" placeholder="JOHN DOE"
             value="<?= clean($_POST['card_name'] ?? $product['card_name']) ?>" required style="text-transform:uppercase">
    </div>
    <div class="form-group">
      <label>BIN</label>
      <input type="text" name="card_bin" id="cardBin" maxlength="10" placeholder="424242"
             value="<?= clean($_POST['card_bin'] ?? $product['card_bin']) ?>">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Bank Name</label>
      <input type="text" name="bank_name" id="bankName" placeholder="Bank of America"
             value="<?= clean($_POST['bank_name'] ?? $product['bank_name']) ?>">
    </div>
    <div class="form-group">
      <label>Card Country</label>
      <input type="text" name="card_country" id="cardCountry" placeholder="US"
             value="<?= clean($_POST['card_country'] ?? $product['card_country']) ?>">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Balance Min ($)</label>
      <input type="number" name="card_balance_min" min="0" step="0.01" placeholder="0"
             value="<?= clean($_POST['card_balance_min'] ?? $product['card_balance_min']) ?>">
    </div>
    <div class="form-group">
      <label>Balance Max ($)</label>
      <input type="number" name="card_balance_max" min="0" step="0.01" placeholder="0"
             value="<?= clean($_POST['card_balance_max'] ?? $product['card_balance_max']) ?>">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>VBV</label>
      <div style="display:flex;gap:16px;margin-top:8px">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
          <input type="radio" name="card_vbv" value="yes" <?= ($_POST['card_vbv'] ?? $product['card_vbv'] ?? '')==='yes'?'checked':'' ?>> Yes
        </label>
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
          <input type="radio" name="card_vbv" value="no" <?= ($_POST['card_vbv'] ?? $product['card_vbv'] ?? '')==='no'?'checked':'' ?>> No
        </label>
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
          <input type="radio" name="card_vbv" value="unknown" <?= ($_POST['card_vbv'] ?? $product['card_vbv'] ?? 'unknown')==='unknown'?'checked':'' ?>> Unknown
        </label>
      </div>
    </div>
    <div class="form-group">
      <label>Card Status</label>
      <select name="card_status">
        <option value="live" <?= ($_POST['card_status'] ?? $product['card_status'] ?? '')==='live'?'selected':'' ?>>Live</option>
        <option value="dead" <?= ($_POST['card_status'] ?? $product['card_status'] ?? '')==='dead'?'selected':'' ?>>Dead</option>
        <option value="unknown" <?= ($_POST['card_status'] ?? $product['card_status'] ?? 'unknown')==='unknown'?'selected':'' ?>>Unknown</option>
      </select>
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Card Brand</label>
      <select name="card_brand" required>
        <?php foreach(['visa'=>'Visa','mastercard'=>'Mastercard','amex'=>'American Express','discover'=>'Discover','jcb'=>'JCB','diners'=>'Diners Club'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= ($product['card_brand']===$k)?'selected':'' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Card Type</label>
      <div style="display:flex;gap:16px;margin-top:8px">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
          <input type="radio" name="card_type" value="credit" <?= ($product['card_type']==='credit')?'checked':'' ?>> Credit
        </label>
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
          <input type="radio" name="card_type" value="debit" <?= ($product['card_type']==='debit')?'checked':'' ?>> Debit
        </label>
      </div>
    </div>
  </div>

  <div class="form-group">
    <label>Price ($)</label>
    <input type="number" name="price" min="0.01" step="0.01" placeholder="149.99"
           value="<?= clean($_POST['price'] ?? $product['price']) ?>" required>
  </div>

  <hr class="divider">

  <!-- PERSONAL INFO -->
  <h3 style="margin-bottom:16px">👤 Personal Info</h3>

  <div class="form-row">
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="card_email" placeholder="john@email.com"
             value="<?= clean($_POST['card_email'] ?? $product['card_email']) ?>">
    </div>
    <div class="form-group">
      <label>Phone</label>
      <input type="text" name="card_phone" placeholder="+1 555-123-4567"
             value="<?= clean($_POST['card_phone'] ?? $product['card_phone']) ?>">
    </div>
  </div>

  <div class="form-group">
    <label>Date of Birth</label>
    <input type="text" name="card_dob" placeholder="MM/DD/YYYY" maxlength="10"
           value="<?= clean($_POST['card_dob'] ?? $product['card_dob']) ?>">
  </div>

  <hr class="divider">

  <!-- ADDRESS -->
  <h3 style="margin-bottom:16px">📍 Address</h3>

  <div class="form-group">
    <label>Address</label>
    <input type="text" name="card_address" placeholder="123 Main Street"
           value="<?= clean($_POST['card_address'] ?? $product['card_address']) ?>">
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>City</label>
      <input type="text" name="card_city" placeholder="New York"
             value="<?= clean($_POST['card_city'] ?? $product['card_city']) ?>">
    </div>
    <div class="form-group">
      <label>State</label>
      <input type="text" name="card_state" placeholder="NY"
             value="<?= clean($_POST['card_state'] ?? $product['card_state']) ?>">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>ZIP Code</label>
      <input type="text" name="card_zip" placeholder="10001"
             value="<?= clean($_POST['card_zip'] ?? $product['card_zip']) ?>">
    </div>
  </div>

  <div style="display:flex;gap:8px">
    <button class="btn btn-primary" type="submit">💾 Save Changes</button>
    <a class="btn btn-outline" href="/admin/products.php">Cancel</a>
  </div>
</div>
</form>

<script>
function fmtCard(el) {
    let v = el.value.replace(/\D/g,'').slice(0,16);
    el.value = v.match(/.{1,4}/g)?.join(' ') || v;
}
function fmtExp(el) {
    let v = el.value.replace(/\D/g,'').slice(0,4);
    if (v.length >= 3) v = v.slice(0,2)+'/'+v.slice(2);
    el.value = v;
}
document.getElementById('cardBin').addEventListener('blur', function() {
    const bin = this.value.replace(/\D/g,'');
    if (bin.length < 6) return;
    fetch('/ajax/bin_lookup.php?bin=' + bin)
        .then(r => r.json())
        .then(d => {
            if (d.bank_name) document.getElementById('bankName').value = d.bank_name;
            if (d.card_country) document.getElementById('cardCountry').value = d.card_country;
        })
        .catch(() => {});
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>