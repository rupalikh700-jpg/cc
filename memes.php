<?php
// ============================================
// memes.php — Pepe Meme Gallery
// Upload, display, like memes
// ============================================
$pageTitle = 'Meme Gallery — Pepe CC Shop';
require_once 'includes/header.php';

$db  = getDB();
$cat = $_GET['cat'] ?? 'all';

// ── HANDLE LIKE ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_meme'])) {
    if (!isLoggedIn()) { redirect('/auth/login.php'); }
    $memeId = (int)$_POST['like_meme'];
    $uid    = (int)$_SESSION['user_id'];

    // Toggle like
    $chk = $db->prepare("SELECT id FROM meme_likes WHERE meme_id=? AND user_id=?");
    $chk->bind_param("ii", $memeId, $uid);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        // Unlike
        $db->prepare("DELETE FROM meme_likes WHERE meme_id=? AND user_id=?")->bind_param("ii",$memeId,$uid);
        $db->prepare("DELETE FROM meme_likes WHERE meme_id=? AND user_id=?")
           ->execute();
        $del = $db->prepare("DELETE FROM meme_likes WHERE meme_id=? AND user_id=?");
        $del->bind_param("ii",$memeId,$uid); $del->execute();
        $db->prepare("UPDATE memes SET likes=likes-1 WHERE id=?")->bind_param("i",$memeId);
        $upd = $db->prepare("UPDATE memes SET likes=likes-1 WHERE id=?");
        $upd->bind_param("i",$memeId); $upd->execute();
    } else {
        // Like
        $ins = $db->prepare("INSERT IGNORE INTO meme_likes (meme_id,user_id) VALUES (?,?)");
        $ins->bind_param("ii",$memeId,$uid); $ins->execute();
        $upd = $db->prepare("UPDATE memes SET likes=likes+1 WHERE id=?");
        $upd->bind_param("i",$memeId); $upd->execute();
    }
    redirect('/memes.php?cat='.$cat);
}

// ── HANDLE UPLOAD ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_meme'])) {
    if (!isLoggedIn()) { redirect('/auth/login.php'); }
    $name    = trim($_POST['meme_name']  ?? '');
    $memeCat = $_POST['meme_cat']   ?? 'classic';
    $emoji   = $_POST['meme_emoji'] ?? '🐸';
    $uid     = (int)$_SESSION['user_id'];

    if ($name) {
        $ins = $db->prepare("INSERT INTO memes (name,emoji,category,user_id) VALUES (?,?,?,?)");
        $ins->bind_param("sssi", $name, $emoji, $memeCat, $uid);
        $ins->execute();
        setFlash('success','Meme uploaded! Very rare 🐸');
    }
    redirect('/memes.php?cat='.$cat);
}

// ── FETCH MEMES ──────────────────────────────
$likedSet = [];
if (isLoggedIn()) {
    $uid = (int)$_SESSION['user_id'];
    $lres = $db->query("SELECT meme_id FROM meme_likes WHERE user_id=$uid");
    while ($r = $lres->fetch_assoc()) $likedSet[$r['meme_id']] = true;
}

if ($cat === 'all') {
    $memes = $db->query("SELECT m.*,u.username FROM memes m JOIN users u ON u.id=m.user_id ORDER BY m.likes DESC");
} else {
    $stmt = $db->prepare("SELECT m.*,u.username FROM memes m JOIN users u ON u.id=m.user_id WHERE m.category=? ORDER BY m.likes DESC");
    $stmt->bind_param("s",$cat); $stmt->execute();
    $memes = $stmt->get_result();
}
$db->close();
?>

<div class="page-header">
  <div><h1>😂 Meme Gallery</h1><p>The best Pepe memes on the internet</p></div>
  <?php if (isLoggedIn()): ?>
    <button class="btn btn-primary btn-sm"
            onclick="document.getElementById('upload-box').style.display=
                     document.getElementById('upload-box').style.display==='none'?'block':'none'">
      + Upload Meme
    </button>
  <?php else: ?>
    <a class="btn btn-outline btn-sm" href="/auth/login.php">Sign in to upload</a>
  <?php endif; ?>
</div>

<!-- Upload Box -->
<div id="upload-box" style="display:none;background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:24px;margin-bottom:24px">
  <h3 style="margin-bottom:16px">Upload a Meme</h3>
  <form method="POST">
    <div class="form-row">
      <div class="form-group">
        <label>Meme Name</label>
        <input type="text" name="meme_name" placeholder="Sad Pepe 2049" required>
      </div>
      <div class="form-group">
        <label>Category</label>
        <select name="meme_cat">
          <option value="classic">Classic</option>
          <option value="rare">Rare</option>
          <option value="sad">Feels / Sad</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>Pick Emoji (our image host 😅)</label>
      <select name="meme_emoji">
        <option value="🐸">🐸 Classic Pepe</option>
        <option value="😭">😭 Sad Pepe</option>
        <option value="😤">😤 Smug Pepe</option>
        <option value="💚">💚 Rare Pepe</option>
        <option value="😎">😎 Cool Pepe</option>
        <option value="🥺">🥺 Feels Pepe</option>
        <option value="🤢">🤢 Sick Pepe</option>
        <option value="🌈">🌈 Happy Pepe</option>
      </select>
    </div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-primary" type="submit" name="upload_meme" value="1">Upload</button>
      <button class="btn btn-outline" type="button"
              onclick="document.getElementById('upload-box').style.display='none'">Cancel</button>
    </div>
  </form>
</div>

<!-- Category Tabs -->
<div class="tabs">
  <?php foreach (['all'=>'All 🐸','classic'=>'Classic','rare'=>'Rare','sad'=>'Feels'] as $k=>$v): ?>
    <a class="tab <?= $cat===$k?'active':'' ?>" href="/memes.php?cat=<?= $k ?>"><?= $v ?></a>
  <?php endforeach; ?>
</div>

<!-- Gallery -->
<div class="meme-grid">
<?php
$count = 0;
while ($m = $memes->fetch_assoc()):
    $count++;
    $liked = isset($likedSet[$m['id']]);
?>
  <div class="meme-card">
    <div class="meme-emoji"><?= $m['emoji'] ?></div>
    <div class="meme-label">
      <div class="meme-name"><?= clean($m['name']) ?></div>
      <span class="tag"><?= $m['category'] ?></span>
      <div style="margin-top:8px">
        <form method="POST" style="display:inline">
          <input type="hidden" name="like_meme" value="<?= $m['id'] ?>">
          <button type="submit" class="like-btn <?= $liked?'liked':'' ?>">
            💚 <?= $m['likes'] ?>
          </button>
        </form>
      </div>
      <div class="muted" style="font-size:11px;margin-top:6px">by <?= clean($m['username']) ?></div>
    </div>
  </div>
<?php endwhile; ?>

<?php if ($count===0): ?>
  <div class="empty-state" style="grid-column:1/-1">
    <div class="big">😭</div><p>No memes in this category yet.</p>
  </div>
<?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
