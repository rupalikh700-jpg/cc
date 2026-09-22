<?php
$pageTitle = 'Support Tickets';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
if (!isLoggedIn()) redirect('/auth/login.php');

$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        if ($subject && $message) {
            $tid = createTicket($uid, $subject, $message, $priority);
            createNotification($uid, 'Ticket Created', "Your ticket #$tid has been opened.", 'support', "/user/ticket.php?id=$tid");
            setFlash('success', "Ticket #$tid created. We'll reply soon!");
        } else {
            setFlash('error', 'Subject and message required.');
        }
        redirect('/user/tickets.php');
    }
}

$tickets = getUserTickets($uid);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>Support Tickets</h1></div>
  <button class="btn btn-primary btn-sm" onclick="document.getElementById('newTicket').style.display='block';this.style.display='none'">New Ticket</button>
</div>

<div id="newTicket" style="display:none;margin-bottom:24px">
  <div class="form-card">
    <h3 style="margin-bottom:16px">Create Ticket</h3>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="form-group">
        <label>Subject</label>
        <input type="text" name="subject" placeholder="What do you need help with?" required>
      </div>
      <div class="form-group">
        <label>Message</label>
        <textarea name="message" rows="4" placeholder="Describe your issue..." required style="width:100%;background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:10px;color:var(--text);font-family:var(--font);resize:vertical"></textarea>
      </div>
      <div class="form-group">
        <label>Priority</label>
        <select name="priority">
          <option value="low">Low</option>
          <option value="medium" selected>Medium</option>
          <option value="high">High</option>
        </select>
      </div>
      <button class="btn btn-primary" type="submit">Submit Ticket</button>
    </form>
  </div>
</div>

<?php if (empty($tickets)): ?>
  <div class="empty-state"><p>No tickets yet. Need help? Create a support ticket.</p></div>
<?php else: ?>
  <div class="ticket-list">
    <?php foreach ($tickets as $t): ?>
      <a href="/user/ticket.php?id=<?= $t['id'] ?>" class="ticket-item">
        <div>
          <div class="ticket-subject"><?= clean($t['subject']) ?></div>
          <div class="ticket-meta">Ticket #<?= $t['id'] ?> &middot; <?= date('M j, Y', strtotime($t['created_at'])) ?></div>
          <div class="ticket-priority <?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></div>
        </div>
        <div style="text-align:right">
          <span class="ticket-status <?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
          <?php if (!empty($t['unread_admin']) && $t['unread_admin'] > 0): ?>
            <div style="margin-top:4px;font-size:11px;color:#00c853">New reply</div>
          <?php endif; ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
