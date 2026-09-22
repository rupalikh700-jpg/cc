<?php
// ============================================
// auth/login.php — Multi-Vendor Edition
// ============================================
$pageTitle = 'Sign In — Pepe CC Shop';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if (isLoggedIn()) redirect('/shop.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pw    = $_POST['password'] ?? '';

    if (!$email || !$pw) {
        $error = 'Please fill in all fields.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $db->close();

        if ($user && password_verify($pw, $user['password'])) {
            // Vendor pending check
            if ($user['role'] === 'vendor' && $user['vendor_status'] === 'pending') {
                $error = '⏳ Your vendor application is pending admin approval. Please wait for an email confirmation.';
            } elseif ($user['role'] === 'vendor' && $user['vendor_status'] === 'rejected') {
                $error = '❌ Your vendor application was rejected. Please contact support.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                setFlash('success', 'Welcome back, ' . clean($user['username']) . '! 🐸');

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect('/admin/index.php');
                } elseif ($user['role'] === 'vendor') {
                    redirect('/vendor/index.php');
                } else {
                    redirect('/shop.php');
                }
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="login-hero">
  <div class="login-brand">
    <div style="font-size:64px;margin-bottom:16px">🐸</div>
    <h1 style="font-family:var(--mono);font-size:36px;color:var(--green);margin-bottom:8px">Pepe CC Shop</h1>
    <p style="color:var(--muted);font-size:15px;max-width:400px;margin:0 auto 32px">Premium CC cards with instant delivery. Sign in to access the shop, wallet, and support.</p>
  </div>

  <div class="form-card login-card">
    <h2 style="text-align:center;margin-bottom:20px;font-size:20px">Sign In</h2>

    <?php if ($error): ?>
      <div class="form-error" style="margin-bottom:14px;font-size:13px"><?= clean($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="email">Email</label>
        <input id="email" type="email" name="email"
               value="<?= clean($_POST['email'] ?? '') ?>"
               placeholder="pepe@feels.good" required>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input id="password" type="password" name="password"
               placeholder="••••••••" required>
      </div>
      <button class="btn btn-primary" style="width:100%;margin-top:8px;padding:12px" type="submit">
        Sign In
      </button>
    </form>

    <hr class="divider">

    <p class="form-link" style="text-align:center">Don't have an account? <a href="/auth/register.php" style="font-weight:600">Register here</a></p>
    <p style="text-align:center;font-size:12px;margin-top:8px"><a href="/vendor/register.php">Want to sell? Become a Vendor</a></p>
  </div>
</div>

<style>
.login-hero { text-align:center; padding:20px 0 0; }
.login-card { max-width:420px; margin:0 auto; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
