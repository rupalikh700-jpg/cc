<?php
$pageTitle = 'Support Tickets - Admin';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
requireAdmin();

$filter = $_GET['filter'] ?? 'all';
$tickets = getAllTickets($filter);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div><h1>Support Tickets</h1></div>
</div>

<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
  <?php foreach (['all'=>'All','open'=>'Open','replied'=>'Replied','closed'=>'Closed'] as $k=>$v): ?>
    <a href="?filter=<?= $k ?>" class="btn <?= $filter===$k ? 'btn-primary' : 'btn-outline' ?> btn-sm"><?= $v ?></a>
  <?php endforeach; ?>
</div>

<?php if (empty($tickets)): ?>
  <div class="empty-state"><p>No tickets found.</p></div>
<?php else: ?>
  <div class="ticket-list">
    <?php foreach ($tickets as $t): ?>
      <a href="/admin/ticket.php?id=<?= $t['id'] ?>" class="ticket-item">
        <div>
          <div class="ticket-subject"><?= clean($t['subject']) ?></div>
          <div class="ticket-meta">
            #<?= $t['id'] ?> &middot; <?= clean($t['username']) ?> (<?= clean($t['email']) ?>)
            &middot; <?= $t['msg_count'] ?> messages
            &middot; <?= date('M j, g:i A', strtotime($t['updated_at'])) ?>
          </div>
          <div class="ticket-priority <?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></div>
        </div>
        <span class="ticket-status <?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
