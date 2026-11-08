<?php
// pages/dashboard.php
// Shows summary cards for a date range (default = current month).
// All values are database-derived. Cash flow uses payment_entries only.

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require __DIR__ . '/../includes/header.php';

$from = get('from', date('Y-m-01'));
$to   = get('to',   date('Y-m-t'));

$db = db();

// Sales revenue (recorded), excluding what may be unpaid — still counts as revenue.
$s = $db->prepare("SELECT COALESCE(SUM(total_amount),0) AS v, COALESCE(SUM(amount_received),0) AS c
                   FROM sales WHERE sale_date BETWEEN ? AND ?");
$s->execute([$from, $to]); $sales = $s->fetch();

// Other income
$s = $db->prepare("SELECT COALESCE(SUM(amount),0) AS v, COALESCE(SUM(amount_received),0) AS c
                   FROM income_entries WHERE income_date BETWEEN ? AND ?");
$s->execute([$from, $to]); $income = $s->fetch();

// Expenses (incurred vs paid)
$s = $db->prepare("SELECT COALESCE(SUM(amount_incurred),0) AS v, COALESCE(SUM(amount_paid),0) AS c
                   FROM expenses WHERE expense_date BETWEEN ? AND ?");
$s->execute([$from, $to]); $exp = $s->fetch();

// Paddy received vs consumed
$s = $db->prepare("SELECT COALESCE(SUM(total_weight_kg),0) AS w FROM paddy_purchases WHERE purchase_date BETWEEN ? AND ?");
$s->execute([$from, $to]); $paddyIn = (float) $s->fetchColumn();

$s = $db->prepare("SELECT COALESCE(SUM(paddy_consumed_kg),0) AS w FROM production_batches WHERE batch_date BETWEEN ? AND ?");
$s->execute([$from, $to]); $paddyOut = (float) $s->fetchColumn();

// Estimated COGS = average paddy purchase rate × paddy consumed this period
// + processing cost. Labelled "estimated" because cost allocation per product
// is approximate (not per-product yield-based).
$s = $db->prepare("SELECT COALESCE(AVG(rate_per_kg),0) AS r FROM paddy_purchases WHERE purchase_date BETWEEN ? AND ?");
$s->execute([$from, $to]); $avgRate = (float) $s->fetchColumn();
$s = $db->prepare("SELECT COALESCE(SUM(paddy_consumed_kg),0) AS w, COALESCE(SUM(processing_cost),0) AS pc
                   FROM production_batches WHERE batch_date BETWEEN ? AND ?");
$s->execute([$from, $to]); $pb = $s->fetch();
$cogs = $pb['w'] * $avgRate + $pb['pc'];

// Outstanding balances
$custBal = (float) $db->query("SELECT COALESCE(SUM(balance_due),0) FROM sales")->fetchColumn();
$supBal  = (float) $db->query("SELECT COALESCE(SUM(amount_outstanding),0) FROM paddy_purchases")->fetchColumn();

// Cash flow this period
$s = $db->prepare("SELECT
   COALESCE(SUM(CASE WHEN direction='received' THEN amount ELSE 0 END),0) AS rin,
   COALESCE(SUM(CASE WHEN direction='paid' THEN amount ELSE 0 END),0) AS rout
   FROM payment_entries WHERE pay_date BETWEEN ? AND ?");
$s->execute([$from, $to]); $cash = $s->fetch();

$salesRev = (float) $sales['v'];
$otherInc = (float) $income['v'];
$expInc   = (float) $exp['v'];
$gross    = $salesRev - $cogs;
$netOp    = $gross + $otherInc - $expInc;
$cashFlow = (float) $cash['rin'] - (float) $cash['rout'];
?>

<form class="filter-bar" method="get">
  <input type="hidden" name="page" value="dashboard">
  <label>From <input type="date" name="from" value="<?= esc($from) ?>"></label>
  <label>To <input type="date" name="to" value="<?= esc($to) ?>"></label>
  <button class="btn">Apply</button>
</form>

<div class="cards">
  <div class="card"><span>Sales revenue</span><b><?= money($salesRev) ?></b><small>Cash received <?= money($sales['c']) ?></small></div>
  <div class="card"><span>Other income</span><b><?= money($otherInc) ?></b><small>Ash &amp; misc.</small></div>
  <div class="card"><span>Expenses (incurred)</span><b><?= money($expInc) ?></b><small>Paid <?= money($exp['c']) ?></small></div>
  <div class="card"><span>Est. cost of goods sold</span><b><?= money($cogs) ?></b><small>Avg paddy rate × consumed</small></div>
  <div class="card <?= $gross>=0?'good':'bad' ?>"><span>Est. gross profit</span><b><?= money($gross) ?></b><small>Revenue − COGS</small></div>
  <div class="card <?= $netOp>=0?'good':'bad' ?>"><span>Est. net operating profit</span><b><?= money($netOp) ?></b><small>Gross + other income − expenses</small></div>
  <div class="card"><span>Cash flow</span><b><?= money($cashFlow) ?></b><small>Received − Paid</small></div>
  <div class="card"><span>Paddy received</span><b><?= kg($paddyIn) ?></b><small>Consumed <?= kg((float)$pb['w']) ?></small></div>
  <div class="card warn"><span>Customer balances</span><b><?= money($custBal) ?></b><small>Outstanding</small></div>
  <div class="card warn"><span>Supplier balances</span><b><?= money($supBal) ?></b><small>Payable</small></div>
</div>

<div class="note">
  <strong>Note:</strong> Profit figures are <em>estimated</em>. Per-product cost allocation is
  not performed; COGS uses the average paddy purchase rate multiplied by paddy consumed during the period.
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
