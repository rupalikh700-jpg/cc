<?php
$pageTitle = 'Register';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/oxapay.php';

if (isLoggedIn()) redirect('/shop.php');

$refCode = trim($_GET['ref'] ?? $_POST['referral_code'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fn = trim($_POST['first_name'] ?? '');
    $ln = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $user = trim($_POST['username'] ?? '');
    $pw = $_POST['password'] ?? '';
    $pw2 = $_POST['password2'] ?? '';

    if (!$fn || !$ln || !$email || !$user || !$pw) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($pw) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pw !== $pw2) {
        $error = 'Passwords do not match.';
    } else {
        $db = getDB();
        $check = $db->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $check->bind_param("ss", $email, $user);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email or username already taken.';
            $db->close();
        } else {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $referralCode = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
            $referredBy = null;
            if ($refCode) {
                $refStmt = $db->prepare("SELECT id FROM users WHERE referral_code=?");
                $refStmt->bind_param("s", $refCode);
                $refStmt->execute();
                $refResult = $refStmt->get_result()->fetch_assoc();
                $refStmt->close();
                if ($refResult) $referredBy = (int)$refResult['id'];
            }

            $role = 'user';
            $ins = $db->prepare("INSERT INTO users (first_name,last_name,email,username,password,role,referral_code,referred_by) VALUES (?,?,?,?,?,?,?,?)");
            $ins->bind_param("ssssssss", $fn, $ln, $email, $user, $hash, $role, $referralCode, $referredBy);
            $ins->execute();
            $newId = $db->insert_id;
            $db->close();

            if ($referredBy) applyReferral($referredBy, $newId);

            $_SESSION['user_id'] = $newId;
            setFlash('success', 'Welcome, ' . clean($user) . '! Account created.');
            redirect('/shop.php');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="login-hero">
  <div class="login-brand">
    <div style="font-size:48px;margin-bottom:12px">🐸</div>
    <h1 style="font-family:var(--mono);font-size:28px;color:var(--green);margin-bottom:6px">Create Account</h1>
    <p style="color:var(--muted);font-size:14px;max-width:360px;margin:0 auto 24px">Join the Pepe CC Shop community</p>
  </div>

  <div class="form-card login-card">
    <?php if ($error): ?>
      <div class="form-error" style="margin-bottom:14px;font-size:13px"><?= clean($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-row">
        <div class="form-group">
          <label>First Name</label>
          <input type="text" name="first_name" value="<?= clean($_POST['first_name'] ?? '') ?>" placeholder="Pepe" required>
        </div>
        <div class="form-group">
          <label>Last Name</label>
          <input type="text" name="last_name" value="<?= clean($_POST['last_name'] ?? '') ?>" placeholder="Frog" required>
        </div>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= clean($_POST['email'] ?? '') ?>" placeholder="pepe@feels.good" required>
      </div>
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" value="<?= clean($_POST['username'] ?? '') ?>" placeholder="pepe_frog" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="Min 8 chars" required>
        </div>
        <div class="form-group">
          <label>Confirm Password</label>
          <input type="password" name="password2" placeholder="Repeat" required>
        </div>
      </div>

      <?php if (getSetting('referral_enabled') === '1'): ?>
      <div class="form-group">
        <label>Referral Code <span style="color:var(--muted);font-weight:normal;font-size:12px">(optional)</span></label>
        <input type="text" name="referral_code" value="<?= clean($refCode) ?>" placeholder="Enter referral code" <?= $refCode ? 'readonly style="background:rgba(0,200,83,.05)"' : '' ?>>
      </div>
      <?php endif; ?>

      <button class="btn btn-primary" style="width:100%;margin-top:12px;padding:12px;font-size:15px" type="submit">
        Create Account
      </button>
    </form>

    <hr class="divider">
    <p style="text-align:center;font-size:13px">Have an account? <a href="/auth/login.php" style="font-weight:600">Sign in</a></p>
    <p style="text-align:center;font-size:12px;margin-top:8px"><a href="/vendor/register.php">Want to sell? Become a Vendor</a></p>
  </div>
</div>

<style>
.login-hero { text-align:center; padding:20px 0 0; }
.login-card { max-width:420px; margin:0 auto; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
