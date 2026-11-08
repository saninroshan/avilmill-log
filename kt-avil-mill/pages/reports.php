<?php
// pages/reports.php
// Weekly / monthly reports with date filters.
// The report rows are fetched via AJAX (Fetch API) from api/report_data.php.

$pageTitle = 'Reports';
$activePage = 'reports';
require __DIR__ . '/../includes/header.php';

$from = get('from', date('Y-m-01'));
$to   = get('to',   date('Y-m-d'));
?>

<form class="filter-bar" method="get">
  <input type="hidden" name="page" value="reports">
  <label>From <input type="date" name="from" id="rFrom" value="<?= esc($from) ?>"></label>
  <label>To <input type="date" name="to" id="rTo" value="<?= esc($to) ?>"></label>
  <button type="button" class="btn" id="loadReport">Load report</button>
  <button type="button" class="btn ghost" id="weekBtn">This week</button>
  <button type="button" class="btn ghost" id="monthBtn">This month</button>
</form>

<section class="panel">
  <h2>Summary</h2>
  <div id="reportSummary" class="cards"><p class="empty">Click "Load report".</p></div>
</section>

<section class="panel">
  <h2>Sales by product</h2>
  <div id="reportProducts"><p class="empty">No data loaded.</p></div>
</section>

<section class="panel">
  <h2>Transactions</h2>
  <div id="reportTx"><p class="empty">No data loaded.</p></div>
</section>

<section class="panel">
  <h2>Definitions</h2>
  <ul class="defs">
    <li><b>Revenue</b> = recorded sales value (includes unpaid invoices).</li>
    <li><b>Gross profit</b> = revenue − estimated COGS.</li>
    <li><b>Net operating profit</b> = gross profit + other income − expenses.</li>
    <li><b>Cash flow</b> = cash received − cash paid (from payment_entries).</li>
    <li><b>Estimated</b> labels indicate approximate figures; per-product cost allocation is not performed.</li>
  </ul>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
