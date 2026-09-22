<?php
$pageTitle = 'Ticket - Admin';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
requireAdmin();

$tid = (int)($_GET['id'] ?? 0);
if (!$tid) redirect('/admin/tickets.php');

$db = getDB();
$ticket = $db->query("SELECT t.*, u.username, u.email FROM tickets t JOIN users u ON t.user_id=u.id WHERE t.id=$tid")->fetch_assoc();
if (!$ticket) { setFlash('error', 'Ticket not found.'); redirect('/admin/tickets.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $adminId = (int)$_SESSION['user_id'];
    if ($action === 'reply') {
        $msg = trim($_POST['message'] ?? '');
        if ($msg) {
            replyTicket($tid, $adminId, $msg, true);
            createNotification($ticket['user_id'], 'Admin Reply', "Admin replied to your ticket #$tid: " . substr($msg, 0, 80), 'support', "/user/ticket.php?id=$tid");
        }
    } elseif ($action === 'close') {
        $db->query("UPDATE tickets SET status='closed', updated_at=NOW() WHERE id=$tid");
        createNotification($ticket['user_id'], 'Ticket Closed', "Your ticket #$tid has been closed by admin.", 'support', "/user/ticket.php?id=$tid");
    } elseif ($action === 'reopen') {
        $db->query("UPDATE tickets SET status='open', updated_at=NOW() WHERE id=$tid");
    }
    redirect("/admin/ticket.php?id=$tid");
}

$messages = getTicketMessages($tid);
$db->close();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1>Ticket #<?= $ticket['id'] ?> — <?= clean($ticket['subject']) ?></h1>
    <p style="font-size:13px;color:var(--muted)"><?= clean($ticket['username']) ?> (<?= clean($ticket['email']) ?>)</p>
  </div>
  <div style="display:flex;gap:8px">
    <form method="POST" style="margin:0">
      <input type="hidden" name="action" value="close">
      <button class="btn btn-danger btn-sm" type="submit">Close Ticket</button>
    </form>
    <a class="btn btn-outline btn-sm" href="/admin/tickets.php">Back</a>
  </div>
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
    <input type="hidden" name="action" value="reply">
    <input type="text" name="message" placeholder="Type your reply..." required style="flex:1">
    <button class="btn btn-primary" type="submit">Send</button>
  </form>
<?php else: ?>
  <form method="POST" style="text-align:center;padding:16px">
    <input type="hidden" name="action" value="reopen">
    <button class="btn btn-outline btn-sm" type="submit">Reopen Ticket</button>
  </form>
<?php endif; ?>

<script>
var box = document.getElementById('chatBox');
if (box) box.scrollTop = box.scrollHeight;
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
