<?php
$pageTitle = 'Ticket';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
if (!isLoggedIn()) redirect('/auth/login.php');

$uid = (int)$_SESSION['user_id'];
$tid = (int)($_GET['id'] ?? 0);
if (!$tid) redirect('/user/tickets.php');

$db = getDB();
$ticket = $db->query("SELECT * FROM tickets WHERE id=$tid AND user_id=$uid")->fetch_assoc();
if (!$ticket) { setFlash('error', 'Ticket not found.'); redirect('/user/tickets.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['message'] ?? '');
    if ($msg) {
        replyTicket($tid, $uid, $msg, false);
        createNotification(1, 'User Reply', "User replied to ticket #$tid: " . substr($msg, 0, 80), 'support', "/admin/ticket.php?id=$tid");
    }
    redirect("/user/ticket.php?id=$tid");
}

$messages = getTicketMessages($tid);
$db->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>Ticket #<?= $ticket['id'] ?></h1>
    <p style="font-size:13px;color:var(--muted)"><?= clean($ticket['subject']) ?></p>
  </div>
  <a class="btn btn-outline btn-sm" href="/user/tickets.php">Back</a>
</div>

<div style="display:flex;gap:8px;align-items:center;margin-bottom:16px">
  <span class="ticket-status <?= $ticket['status'] ?>"><?= ucfirst($ticket['status']) ?></span>
  <span class="ticket-priority <?= $ticket['priority'] ?>"><?= ucfirst($ticket['priority']) ?></span>
  <span style="font-size:12px;color:var(--muted)">Created <?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?></span>
</div>

<div class="chat-box" id="chatBox">
  <?php foreach ($messages as $m): ?>
    <div class="chat-msg <?= $m['is_admin'] ? 'admin' : 'user' ?>">
      <div class="chat-sender <?= $m['is_admin'] ? 'admin-name' : 'user-name' ?>">
        <?= $m['is_admin'] ? 'Admin' : clean($m['username']) ?>
      </div>
      <div><?= nl2br(clean($m['message'])) ?></div>
      <div class="chat-time"><?= date('M j, g:i A', strtotime($m['created_at'])) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($ticket['status'] !== 'closed'): ?>
  <form method="POST" class="chat-input-row">
    <input type="text" name="message" placeholder="Type your reply..." required style="flex:1">
    <button class="btn btn-primary" type="submit">Send</button>
  </form>
<?php else: ?>
  <div style="text-align:center;padding:16px;color:var(--muted);font-size:13px">This ticket is closed.</div>
<?php endif; ?>

<script>
var box = document.getElementById('chatBox');
if (box) box.scrollTop = box.scrollHeight;
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
