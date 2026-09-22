<?php
// ============================================
// admin/news.php — Add / Delete News
// ============================================
$pageTitle = 'Manage News — Admin';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
if (!isAdmin()) { redirect('/auth/login.php'); }

$db    = getDB();
$error = '';

// ── DELETE ────────────────────────────────────
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $del = $db->prepare("DELETE FROM news WHERE id=?");
    $del->bind_param("i",$id); $del->execute();
    setFlash('success','News article deleted.');
    redirect('/admin/news.php');
}

// ── ADD ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body']  ?? '');
    $tag   = trim($_POST['tag']   ?? '');
    if (!$title || !$body) {
        $error = 'Title and body are required.';
    } else {
        $ins = $db->prepare("INSERT INTO news (title,body,tag) VALUES (?,?,?)");
        $ins->bind_param("sss",$title,$body,$tag); $ins->execute();
        setFlash('success','News article published! 🐸');
        redirect('/admin/news.php');
    }
}

$news = $db->query("SELECT * FROM news ORDER BY created_at DESC");
$db->close();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>📰 Manage News</h1></div>
  <a class="btn btn-outline btn-sm" href="/admin/index.php">← Dashboard</a>
</div>

<?php if ($error): ?>
  <div class="form-error" style="margin-bottom:16px"><?= clean($error) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:380px 1fr;gap:24px;align-items:start">

  <!-- Add Form -->
  <div class="form-card" style="max-width:100%">
    <h3 style="margin-bottom:18px">Publish New Article</h3>
    <form method="POST">
      <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" placeholder="Pepe CC launches…" required>
      </div>
      <div class="form-group">
        <label>Tag</label>
        <select name="tag">
          <option value="Product">Product</option>
          <option value="Finance">Finance</option>
          <option value="Community">Community</option>
          <option value="Security">Security</option>
          <option value="Business">Business</option>
          <option value="Event">Event</option>
        </select>
      </div>
      <div class="form-group">
        <label>Body</label>
        <textarea name="body" rows="5" placeholder="Article content…" required></textarea>
      </div>
      <button class="btn btn-primary" type="submit">Publish 📰</button>
    </form>
  </div>

  <!-- Existing News -->
  <div>
    <h3 class="section-title">Published Articles</h3>
    <?php while ($n = $news->fetch_assoc()): ?>
    <div class="news-card" style="margin-bottom:12px">
      <div class="news-accent"></div>
      <div class="news-body" style="flex:1">
        <div class="news-date">
          <?= date('M j, Y', strtotime($n['created_at'])) ?>
          <span class="tag" style="margin-left:6px"><?= clean($n['tag']) ?></span>
        </div>
        <div class="news-title"><?= clean($n['title']) ?></div>
        <div style="font-size:13px;color:var(--muted)"><?= clean(substr($n['body'],0,120)) ?>…</div>
      </div>
      <div style="padding:12px;display:flex;align-items:center">
        <a class="btn btn-sm btn-danger"
           href="?delete=<?= $n['id'] ?>"
           onclick="return confirm('Delete this article?')">Delete</a>
      </div>
    </div>
    <?php endwhile; ?>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
