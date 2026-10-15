<?php
// ============================================================
// Flowork Agency Software - Invoice view / print / PDF
// ============================================================
if (!defined('FW_APP')) { define('FW_APP', true); }
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$inv = qrow("SELECT i.*, c.name AS cname, c.company, c.phone, c.email, c.address, p.title AS ptitle, m.title AS mtitle
             FROM invoices i
             JOIN clients c ON c.id = i.client_id
             LEFT JOIN projects p ON p.id = i.project_id
             LEFT JOIN milestones m ON m.id = i.milestone_id
             WHERE i.id = ?", [$id]);
if (!$inv) { die('Invoice nahi mili.'); }

// ---------- Record payment ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
  $amt = (float)($_POST['amount'] ?? 0);
  if ($amt <= 0) { flash('Amount zaroori hai.', 'err'); }
  else {
    record_payment([
      'client_id' => $inv['client_id'], 'project_id' => $inv['project_id'], 'invoice_id' => $id,
      'amount' => $amt, 'method' => $_POST['method'] ?? 'Cash',
      'payment_date' => $_POST['payment_date'] ?: today(), 'notes' => trim($_POST['notes'] ?? ''),
    ]);
    flash('Payment record ho gayi. 💰');
  }
  redirect('invoice-view.php?id=' . $id);
}

$page_title = $inv['invoice_no'];
$page_sub = 'Invoice detail — print ya PDF download karein';
$active = 'invoices';
require_once __DIR__ . '/includes/header.php';

$inv = qrow("SELECT i.*, c.name AS cname, c.company, c.phone, c.email, c.address, p.title AS ptitle, m.title AS mtitle
             FROM invoices i
             JOIN clients c ON c.id = i.client_id
             LEFT JOIN projects p ON p.id = i.project_id
             LEFT JOIN milestones m ON m.id = i.milestone_id
             WHERE i.id = ?", [$id]);

$balance = (float)$inv['amount'] - (float)$inv['paid_amount'];
$od = $inv['status'] !== 'paid' && $inv['due_date'] && $inv['due_date'] < today();

// Project-level hisaab (agar project linked hai)
$proj_total = $proj_paid = 0;
$all_ms = [];
if ($inv['project_id']) {
  $pr = qrow("SELECT total_amount FROM projects WHERE id = ?", [(int)$inv['project_id']]);
  $proj_total = (float)($pr['total_amount'] ?? 0);
  $proj_paid = (float)qrow("SELECT COALESCE(SUM(amount),0) s FROM payments WHERE project_id = ?", [(int)$inv['project_id']])['s'];
  $all_ms = qall("SELECT * FROM milestones WHERE project_id = ? ORDER BY id ASC", [(int)$inv['project_id']]);
}
$pay_list = qall("SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC", [$id]);
$methods = ['Cash', 'Bank Transfer', 'Easypaisa', 'JazzCash', 'Other'];
$logo = setting('logo_path', '');
?>

<div class="inv-wrap">
  <div class="inv-actions no-print">
    <a href="invoices.php" class="btn btn-ghost btn-sm">← Back</a>
    <button class="btn btn-ghost btn-sm" onclick="window.print()">🖨 Print</button>
    <button class="btn btn-sm" onclick="window.print()">⬇ Download PDF</button>
  </div>

  <div class="inv-sheet">
    <div class="inv-head">
      <div>
        <?php if ($logo && file_exists(__DIR__ . '/' . $logo)): ?>
          <img src="<?php echo esc($logo); ?>" alt="logo" style="max-height:64px;max-width:220px">
        <?php else: ?>
          <div class="inv-logo"><?php echo esc(strtoupper(setting('agency_name', 'FLOWORK'))); ?></div>
        <?php endif; ?>
        <div class="inv-tagline"><?php echo esc(strtoupper(setting('tagline', 'DIGITAL MARKETING AGENCY'))); ?></div>
      </div>
      <div class="inv-titlebox">
        <h2>INVOICE</h2>
        <div class="inv-num"><?php echo esc($inv['invoice_no']); ?></div>
        <div style="margin-top:6px">
          <?php if ($inv['status'] === 'paid'): ?><span class="badge b-ok">PAID</span>
          <?php elseif ($inv['status'] === 'partial'): ?><span class="badge b-info">PARTIAL</span>
          <?php elseif ($od): ?><span class="badge b-err">OVERDUE</span>
          <?php else: ?><span class="badge b-warn">UNPAID</span><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="inv-meta">
      <div class="billed">
        <h4>Billed To</h4>
        <b><?php echo esc($inv['company'] ?: $inv['cname']); ?></b>
        <p><?php echo esc($inv['cname']); ?><br>
        <?php echo esc($inv['phone']); ?><?php echo $inv['email'] ? ' · ' . esc($inv['email']) : ''; ?><br>
        <?php echo nl2br(esc($inv['address'])); ?></p>
      </div>
      <div class="dates">
        <div><span>Invoice Date:</span> <b><?php echo dmy($inv['issue_date']); ?></b></div>
        <div><span>Due Date:</span> <b><?php echo dmy($inv['due_date']); ?></b></div>
        <?php if ($inv['ptitle']): ?><div><span>Project:</span> <b><?php echo esc($inv['ptitle']); ?></b></div><?php endif; ?>
      </div>
    </div>

    <div class="inv-items">
      <table>
        <tr><th style="width:60%">Description</th><th style="text-align:center">Detail</th><th style="text-align:right">Amount</th></tr>
        <tr>
          <td class="d">
            <b><?php echo esc($inv['ptitle'] ?: 'Service Charges'); ?></b>
            <small><?php echo $inv['mtitle'] ? 'Milestone: ' . esc($inv['mtitle']) : esc($inv['notes'] ?: 'Agency services'); ?></small>
          </td>
          <td style="text-align:center"><?php echo $inv['mtitle'] ? 'Milestone' : '—'; ?></td>
          <td style="text-align:right"><b><?php echo money($inv['amount']); ?></b></td>
        </tr>
      </table>
    </div>

    <?php if ($all_ms): ?>
    <div style="padding:18px 44px 0">
      <h4 style="font-size:11px;text-transform:uppercase;letter-spacing:1.6px;color:#64748b;margin-bottom:10px">Project Payment Plan</h4>
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <?php foreach ($all_ms as $am): ?>
        <tr style="border-bottom:1px solid #eef1f7">
          <td style="padding:8px 4px"><?php echo esc($am['title']); ?></td>
          <td style="padding:8px 4px;text-align:right"><?php echo money($am['amount']); ?></td>
          <td style="padding:8px 4px;text-align:right;width:110px">
            <?php echo $am['status'] === 'paid' ? '<span class="badge b-ok">Paid</span>' : ($am['id'] == $inv['milestone_id'] ? '<span class="badge b-primary">This invoice</span>' : '<span class="badge b-muted">Pending</span>'); ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?php endif; ?>

    <div class="inv-totals">
      <div class="inv-paybox">
        <h4>Payment Details</h4>
        <?php echo nl2br(esc(setting('payment_details', 'Contact us for payment details.'))); ?>
      </div>
      <div class="inv-sum">
        <?php if ($inv['project_id']): ?>
        <div class="ln"><span>Project Total</span><span><?php echo money($proj_total); ?></span></div>
        <div class="ln"><span>Paid So Far</span><span><?php echo money($proj_paid); ?></span></div>
        <?php endif; ?>
        <div class="ln tt"><span>This Invoice</span><span><?php echo money($inv['amount']); ?></span></div>
        <div class="ln"><span>Paid (this invoice)</span><span><?php echo money($inv['paid_amount']); ?></span></div>
        <div class="inv-bal <?php echo $balance > 0 ? 'due' : 'clear'; ?>">
          <span>Balance Due</span><span><?php echo money($balance); ?></span>
        </div>
      </div>
    </div>

    <div class="inv-thanks">
      <h3><?php echo esc(setting('thankyou_note', 'Thank you for choosing Flowork Digital!')); ?></h3>
      <p>We appreciate your business · <?php echo esc(setting('phone', '')); ?><?php echo setting('whatsapp') ? ' · WhatsApp: ' . esc(setting('whatsapp')) : ''; ?></p>
    </div>

    <div class="inv-foot">
      <?php echo esc(setting('agency_name', 'Flowork')); ?> · <?php echo esc(setting('address', '')); ?><br>
      This is a computer-generated invoice and does not require a signature.
    </div>
  </div>

  <!-- Payment recording (screen only) -->
  <div class="card mt no-print">
    <div class="card-head"><div class="card-title">💰 Record Payment</div></div>
    <?php if ($balance <= 0): ?>
      <div class="alert alert-ok">Ye invoice fully paid hai. ✅</div>
    <?php else: ?>
    <form method="post">
      <div class="form-grid">
        <div class="field"><label>Amount (Rs) * — baaki: <?php echo money($balance); ?></label><input type="number" name="amount" class="input" required min="1" step="any" max="<?php echo $balance; ?>" value="<?php echo $balance; ?>"></div>
        <div class="field"><label>Method</label><select name="method" class="input"><?php foreach ($methods as $mm): ?><option><?php echo $mm; ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Date</label><input type="date" name="payment_date" class="input" value="<?php echo today(); ?>"></div>
        <div class="field"><label>Note</label><input name="notes" class="input" placeholder="e.g. Easypaisa TrxID 123"></div>
      </div>
      <div class="form-actions"><button name="add_payment" class="btn btn-sm">Record Payment</button></div>
    </form>
    <?php endif; ?>
    <?php if ($pay_list): ?>
    <div class="card-sub mt">Is invoice ki payments</div>
    <table class="tbl">
      <tr><th>Date</th><th>Method</th><th>Note</th><th class="r">Amount</th></tr>
      <?php foreach ($pay_list as $pl): ?>
      <tr><td><?php echo dmy($pl['payment_date']); ?></td><td><?php echo esc($pl['method']); ?></td><td><?php echo esc($pl['notes']); ?></td><td class="r strong"><?php echo money($pl['amount']); ?></td></tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
