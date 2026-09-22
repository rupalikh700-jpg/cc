<?php
$pageTitle = 'Settings - Admin';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/oxapay.php';
requireAdmin();
$db = getDB();
$tab = $_GET['tab'] ?? 'general';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'general') {
        updateSetting('site_name', trim($_POST['site_name'] ?? ''));
        updateSetting('site_tagline', trim($_POST['site_tagline'] ?? ''));
        updateSetting('currency', trim($_POST['currency'] ?? 'USD'));
        updateSetting('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
        setFlash('success', 'General settings saved.');
    } elseif ($action === 'payment') {
        updateSetting('oxapay_api_key', trim($_POST['oxapay_api_key'] ?? ''));
        updateSetting('oxapay_api_secret', trim($_POST['oxapay_api_secret'] ?? ''));
        updateSetting('oxapay_webhook_url', trim($_POST['oxapay_webhook_url'] ?? ''));
        updateSetting('deposit_min', (string)(float)($_POST['deposit_min'] ?? 10));
        updateSetting('deposit_max', (string)(float)($_POST['deposit_max'] ?? 10000));
        setFlash('success', 'Payment settings saved.');
    } elseif ($action === 'checker') {
        updateSetting('checker_enabled', isset($_POST['checker_enabled']) ? '1' : '0');
        updateSetting('checker_cost', (string)(float)($_POST['checker_cost'] ?? 0.50));
        updateSetting('checker_api_url', trim($_POST['checker_api_url'] ?? ''));
        updateSetting('checker_api_token', trim($_POST['checker_api_token'] ?? ''));
        setFlash('success', 'Card checker settings saved.');
    } elseif ($action === 'display') {
        updateSetting('auto_fetch_bank_name', isset($_POST['auto_fetch_bank_name']) ? '1' : '0');
        updateSetting('show_balance_range', isset($_POST['show_balance_range']) ? '1' : '0');
        updateSetting('show_vbv_status', isset($_POST['show_vbv_status']) ? '1' : '0');
        updateSetting('show_daily_counter', isset($_POST['show_daily_counter']) ? '1' : '0');
        setFlash('success', 'Display settings saved.');
    } elseif ($action === 'bonuses') {
        updateSetting('deposit_bonuses_enabled', isset($_POST['deposit_bonuses_enabled']) ? '1' : '0');
        $db->query("DELETE FROM deposit_bonuses");
        if (!empty($_POST['bonus_min'])) {
            foreach ($_POST['bonus_min'] as $i => $min) {
                $min = (float)$min; $pct = (int)($_POST['bonus_pct'][$i] ?? 0);
                $max = (float)($_POST['bonus_max'][$i] ?? 100);
                if ($min > 0 && $pct > 0) $db->query("INSERT INTO deposit_bonuses (min_amount, bonus_percent, max_bonus, is_active) VALUES ($min,$pct,$max,1)");
            }
        }
        setFlash('success', 'Bonus settings saved.');
    } elseif ($action === 'packs') {
        updateSetting('packs_enabled', isset($_POST['packs_enabled']) ? '1' : '0');
        setFlash('success', 'Pack settings saved.');
    } elseif ($action === 'referral') {
        updateSetting('referral_enabled', isset($_POST['referral_enabled']) ? '1' : '0');
        updateSetting('referral_bonus', (string)(float)($_POST['referral_bonus'] ?? 5));
        setFlash('success', 'Referral settings saved.');
    } elseif ($action === 'vip') {
        updateSetting('vip_enabled', isset($_POST['vip_enabled']) ? '1' : '0');
        setFlash('success', 'VIP settings saved.');
    } elseif ($action === 'telegram') {
        updateSetting('telegram_bot_token', trim($_POST['telegram_bot_token'] ?? ''));
        updateSetting('telegram_chat_id', trim($_POST['telegram_chat_id'] ?? ''));
        updateSetting('telegram_notify_new_order', isset($_POST['telegram_notify_new_order']) ? '1' : '0');
        updateSetting('telegram_notify_deposit', isset($_POST['telegram_notify_deposit']) ? '1' : '0');
        updateSetting('telegram_notify_checker', isset($_POST['telegram_notify_checker']) ? '1' : '0');
        setFlash('success', 'Telegram settings saved.');
    } elseif ($action === 'api') {
        updateSetting('api_enabled', isset($_POST['api_enabled']) ? '1' : '0');
        setFlash('success', 'API settings saved.');
    } elseif ($action === 'add_bonus') {
        $min=(float)$_POST['new_bonus_min']; $pct=(int)$_POST['new_bonus_pct']; $max=(float)($_POST['new_bonus_max']??100);
        if ($min>0&&$pct>0) { $db->query("INSERT INTO deposit_bonuses (min_amount,bonus_percent,max_bonus,is_active) VALUES ($min,$pct,$max,1)"); setFlash('success','Bonus added.'); }
    } elseif ($action === 'add_bundle') {
        $n=$db->real_escape_string($_POST['bundle_name']??''); $d=$db->real_escape_string($_POST['bundle_desc']??'');
        $p=(float)($_POST['bundle_price']??0); $c=(int)($_POST['bundle_count']??3);
        $br=$_POST['bundle_brand']??null; $co=$db->real_escape_string($_POST['bundle_country']??'');
        if ($n&&$p>0) { $bs=$br?"'$br'":'NULL'; $cs=$co?"'$co'":'NULL';
        $db->query("INSERT INTO card_bundles (name,description,price,card_count,card_brand,card_country,is_active) VALUES ('$n','$d',$p,$c,$bs,$cs,1)"); setFlash('success','Pack added.'); }
    } elseif ($action === 'delete_bonus') { $db->query("DELETE FROM deposit_bonuses WHERE id=".(int)$_POST['bonus_id']); setFlash('success','Deleted.'); }
    elseif ($action === 'delete_bundle') { $db->query("DELETE FROM card_bundles WHERE id=".(int)$_POST['bundle_id']); setFlash('success','Deleted.'); }
    redirect('/admin/settings.php?tab='.$tab);
}

$s=[]; $r=$db->query("SELECT setting_key,setting_value FROM settings");
while($row=$r->fetch_assoc()) $s[$row['setting_key']]=$row['setting_value'];
$bonuses=$db->query("SELECT * FROM deposit_bonuses ORDER BY min_amount")->fetch_all(MYSQLI_ASSOC);
$bundles=$db->query("SELECT * FROM card_bundles ORDER BY price")->fetch_all(MYSQLI_ASSOC);
$levels=$db->query("SELECT * FROM vip_levels ORDER BY min_spent")->fetch_all(MYSQLI_ASSOC);
$db->close();

require_once __DIR__ . '/../includes/header.php';
$tabs=['general'=>'General','payment'=>'Payment','checker'=>'Checker','display'=>'Display','bonuses'=>'Bonuses','packs'=>'Packs','referral'=>'Referral','vip'=>'VIP','telegram'=>'Telegram','api'=>'API'];
?>
<div class="page-header"><div><h1>Admin Settings</h1></div><a class="btn btn-outline btn-sm" href="/admin/index.php">Dashboard</a></div>
<div class="tabs" style="margin-bottom:20px;flex-wrap:wrap">
<?php foreach($tabs as $k=>$v): ?><a class="tab <?=$tab===$k?'active':''?>" href="?tab=<?=$k?>"><?=$v?></a><?php endforeach;?>
</div>

<?php if($tab==='general'):?>
<div class="form-card" style="max-width:600px"><h3 style="margin-bottom:16px">General Settings</h3>
<form method="POST"><input type="hidden" name="action" value="general">
<div class="form-group"><label>Site Name</label><input type="text" name="site_name" value="<?=clean($s['site_name']??'')?>"></div>
<div class="form-group"><label>Tagline</label><input type="text" name="site_tagline" value="<?=clean($s['site_tagline']??'')?>"></div>
<div class="form-group"><label>Currency</label><input type="text" name="currency" value="<?=clean($s['currency']??'USD')?>"></div>
<div class="form-group"><label><input type="checkbox" name="maintenance_mode" <?=($s['maintenance_mode']??'0')==='1'?'checked':''?>> Maintenance Mode</label></div>
<button class="btn btn-primary" type="submit">Save</button></form></div>

<?php elseif($tab==='payment'):?>
<div class="form-card" style="max-width:600px"><h3 style="margin-bottom:16px">Oxapay Payment</h3>
<form method="POST"><input type="hidden" name="action" value="payment">
<div class="form-group"><label>API Key</label><input type="text" name="oxapay_api_key" value="<?=clean($s['oxapay_api_key']??'')?>"></div>
<div class="form-group"><label>API Secret</label><input type="password" name="oxapay_api_secret" value="<?=clean($s['oxapay_api_secret']??'')?>"></div>
<div class="form-group"><label>Webhook URL</label><input type="text" name="oxapay_webhook_url" value="<?=clean($s['oxapay_webhook_url']??'')?>">
<p style="font-size:11px;color:#888">Leave empty to auto-detect</p></div>
<div class="form-row">
<div class="form-group"><label>Min Deposit ($)</label><input type="number" name="deposit_min" step="0.01" value="<?=clean($s['deposit_min']??'10')?>"></div>
<div class="form-group"><label>Max Deposit ($)</label><input type="number" name="deposit_max" step="0.01" value="<?=clean($s['deposit_max']??'10000')?>"></div>
</div>
<button class="btn btn-primary" type="submit">Save</button></form></div>

<?php elseif($tab==='checker'):?>
<div class="form-card" style="max-width:600px"><h3 style="margin-bottom:16px">Card Checker</h3>
<form method="POST"><input type="hidden" name="action" value="checker">
<div class="form-group"><label><input type="checkbox" name="checker_enabled" <?=($s['checker_enabled']??'1')==='1'?'checked':''?>> Enable Card Checker</label></div>
<div class="form-group"><label>Cost per Check ($)</label><input type="number" name="checker_cost" step="0.01" min="0" value="<?=clean($s['checker_cost']??'0.50')?>"></div>
<div class="form-group"><label>API URL</label><input type="text" name="checker_api_url" value="<?=clean($s['checker_api_url']??'https://api.chkr.cc/')?>">
<p style="font-size:11px;color:#888">Default: chkr.cc (free)</p></div>
<div class="form-group"><label>API Token</label><input type="text" name="checker_api_token" value="<?=clean($s['checker_api_token']??'')?>"></div>
<button class="btn btn-primary" type="submit">Save</button></form></div>

<?php elseif($tab==='display'):?>
<div class="form-card" style="max-width:600px"><h3 style="margin-bottom:16px">Display Options</h3>
<form method="POST"><input type="hidden" name="action" value="display">
<div class="form-group"><label><input type="checkbox" name="auto_fetch_bank_name" <?=($s['auto_fetch_bank_name']??'1')==='1'?'checked':''?>> Auto-fetch Bank Name from BIN</label></div>
<div class="form-group"><label><input type="checkbox" name="show_balance_range" <?=($s['show_balance_range']??'1')==='1'?'checked':''?>> Show Balance Range</label></div>
<div class="form-group"><label><input type="checkbox" name="show_vbv_status" <?=($s['show_vbv_status']??'1')==='1'?'checked':''?>> Show VBV Status</label></div>
<div class="form-group"><label><input type="checkbox" name="show_daily_counter" <?=($s['show_daily_counter']??'1')==='1'?'checked':''?>> Show Daily Card Counter</label></div>
<button class="btn btn-primary" type="submit">Save</button></form></div>

<?php elseif($tab==='bonuses'):?>
<div class="form-card" style="max-width:700px"><h3 style="margin-bottom:16px">Deposit Bonuses</h3>
<form method="POST"><input type="hidden" name="action" value="bonuses">
<div class="form-group"><label><input type="checkbox" name="deposit_bonuses_enabled" <?=($s['deposit_bonuses_enabled']??'1')==='1'?'checked':''?>> Enable Deposit Bonuses</label></div>
<hr class="divider"><h4 style="margin-bottom:12px">Current Tiers</h4>
<?php foreach($bonuses as $b):?>
<div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;padding:8px;background:var(--surface2);border-radius:8px">
<span>Min $<?=number_format($b['min_amount'],0)?> | <?=$b['bonus_percent']?>% | Max $<?=number_format($b['max_bonus'],0)?></span>
<input type="hidden" name="bonus_min[]" value="<?=$b['min_amount']?>"><input type="hidden" name="bonus_pct[]" value="<?=$b['bonus_percent']?>"><input type="hidden" name="bonus_max[]" value="<?=$b['max_bonus']?>">
</div><?php endforeach;?>
<button class="btn btn-primary" type="submit">Save</button></form>
<hr class="divider"><h4 style="margin-bottom:12px">Add Tier</h4>
<form method="POST"><input type="hidden" name="action" value="add_bonus">
<div class="form-row"><div class="form-group"><label>Min Amount ($)</label><input type="number" name="new_bonus_min" step="1" required></div>
<div class="form-group"><label>Bonus %</label><input type="number" name="new_bonus_pct" min="1" max="200" required></div></div>
<div class="form-group"><label>Max Bonus ($)</label><input type="number" name="new_bonus_max" step="0.01" value="100"></div>
<button class="btn btn-outline" type="submit">Add Tier</button></form></div>

<?php elseif($tab==='packs'):?>
<div class="form-card" style="max-width:700px"><h3 style="margin-bottom:16px">Card Packs</h3>
<form method="POST"><input type="hidden" name="action" value="packs">
<div class="form-group"><label><input type="checkbox" name="packs_enabled" <?=($s['packs_enabled']??'1')==='1'?'checked':''?>> Enable Card Packs</label></div>
<button class="btn btn-primary" type="submit">Save</button></form>
<hr class="divider"><h4 style="margin-bottom:12px">Current Packs</h4>
<?php foreach($bundles as $b):?>
<div style="display:flex;justify-content:space-between;align-items:center;padding:8px;background:var(--surface2);border-radius:8px;margin-bottom:8px">
<div><strong><?=clean($b['name'])?></strong> - <?=price($b['price'])?> (<?=$b['card_count']?> cards) <?=$b['card_brand']?'['.clean($b['card_brand']).']':''?></div>
<form method="POST" style="margin:0"><input type="hidden" name="action" value="delete_bundle"><input type="hidden" name="bundle_id" value="<?=$b['id']?>"><button class="btn btn-danger btn-xs" type="submit" onclick="return confirm('Delete?')">Delete</button></form>
</div><?php endforeach;?>
<hr class="divider"><h4 style="margin-bottom:12px">Add Pack</h4>
<form method="POST"><input type="hidden" name="action" value="add_bundle">
<div class="form-group"><label>Pack Name</label><input type="text" name="bundle_name" required placeholder="3 USA Visa Pack"></div>
<div class="form-group"><label>Description</label><input type="text" name="bundle_desc" placeholder="3 random Visa cards"></div>
<div class="form-row"><div class="form-group"><label>Price ($)</label><input type="number" name="bundle_price" step="0.01" required></div>
<div class="form-group"><label>Card Count</label><input type="number" name="bundle_count" min="1" value="3" required></div></div>
<div class="form-row"><div class="form-group"><label>Brand</label><select name="bundle_brand"><option value="">Any</option>
<option value="visa">Visa</option><option value="mastercard">Mastercard</option><option value="amex">Amex</option>
<option value="discover">Discover</option><option value="jcb">JCB</option><option value="diners">Diners</option></select></div>
<div class="form-group"><label>Country</label><input type="text" name="bundle_country" placeholder="Leave empty for any"></div></div>
<button class="btn btn-outline" type="submit">Add Pack</button></form></div>

<?php elseif($tab==='referral'):?>
<div class="form-card" style="max-width:600px"><h3 style="margin-bottom:16px">Referral System</h3>
<form method="POST"><input type="hidden" name="action" value="referral">
<div class="form-group"><label><input type="checkbox" name="referral_enabled" <?=($s['referral_enabled']??'1')==='1'?'checked':''?>> Enable Referrals</label></div>
<div class="form-group"><label>Referral Bonus ($)</label><input type="number" name="referral_bonus" step="0.01" min="0" value="<?=clean($s['referral_bonus']??'5.00')?>"></div>
<button class="btn btn-primary" type="submit">Save</button></form></div>

<?php elseif($tab==='vip'):?>
<div class="form-card" style="max-width:600px"><h3 style="margin-bottom:16px">VIP Levels</h3>
<form method="POST"><input type="hidden" name="action" value="vip">
<div class="form-group"><label><input type="checkbox" name="vip_enabled" <?=($s['vip_enabled']??'1')==='1'?'checked':''?>> Enable VIP System</label></div>
<button class="btn btn-primary" type="submit">Save</button></form>
<hr class="divider"><h4 style="margin-bottom:12px">Current Levels</h4>
<?php foreach($levels as $l):?>
<div style="display:flex;gap:12px;align-items:center;padding:10px;background:var(--surface2);border-radius:8px;margin-bottom:8px">
<span style="font-size:20px"><?=$l['badge_icon']?></span><strong><?=clean($l['name'])?></strong>
<span style="color:#888;font-size:13px">Min: $<?=number_format($l['min_spent'],0)?></span>
<span style="color:#00c853;font-size:13px"><?=$l['discount_percent']?>% off</span>
</div><?php endforeach;?>
</div>

<?php elseif($tab==='telegram'):?>
<div class="form-card" style="max-width:600px"><h3 style="margin-bottom:16px">Telegram Bot</h3>
<form method="POST"><input type="hidden" name="action" value="telegram">
<div class="form-group"><label>Bot Token</label><input type="text" name="telegram_bot_token" value="<?=clean($s['telegram_bot_token']??'')?>" placeholder="123456:ABC-DEF..."></div>
<div class="form-group"><label>Chat ID</label><input type="text" name="telegram_chat_id" value="<?=clean($s['telegram_chat_id']??'')?>" placeholder="-100123456789"></div>
<hr class="divider"><h4 style="margin-bottom:12px">Notifications</h4>
<div class="form-group"><label><input type="checkbox" name="telegram_notify_new_order" <?=($s['telegram_notify_new_order']??'1')==='1'?'checked':''?>> New Order</label></div>
<div class="form-group"><label><input type="checkbox" name="telegram_notify_deposit" <?=($s['telegram_notify_deposit']??'1')==='1'?'checked':''?>> Deposit</label></div>
<div class="form-group"><label><input type="checkbox" name="telegram_notify_checker" <?=($s['telegram_notify_checker']??'1')==='1'?'checked':''?>> Card Check</label></div>
<button class="btn btn-primary" type="submit">Save</button></form></div>

<?php elseif($tab==='api'):?>
<div class="form-card" style="max-width:600px"><h3 style="margin-bottom:16px">Reseller API</h3>
<form method="POST"><input type="hidden" name="action" value="api">
<div class="form-group"><label><input type="checkbox" name="api_enabled" <?=($s['api_enabled']??'0')==='1'?'checked':''?>> Enable API Access</label></div>
<p style="font-size:13px;color:#888;margin-bottom:16px">API docs at <code>/api/</code> - Uses token auth via header</p>
<button class="btn btn-primary" type="submit">Save</button></form></div>

<?php endif;?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
