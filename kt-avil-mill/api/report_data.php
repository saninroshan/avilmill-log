<?php
// api/report_data.php
// AJAX endpoint: returns JSON summary + breakdowns for a date range.
// Called from assets/js/app.js via the Fetch API.

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/json');

$from = get('from', date('Y-m-01'));
$to   = get('to',   date('Y-m-d'));

$db = db();

$sales = $db->prepare("SELECT COALESCE(SUM(total_amount),0) v, COALESCE(SUM(amount_received),0) c FROM sales WHERE sale_date BETWEEN ? AND ?");
$sales->execute([$from,$to]); $s = $sales->fetch();

$income = $db->prepare("SELECT COALESCE(SUM(amount),0) v FROM income_entries WHERE income_date BETWEEN ? AND ?");
$income->execute([$from,$to]); $inc = (float)$income->fetchColumn();

$exp = $db->prepare("SELECT COALESCE(SUM(amount_incurred),0) v FROM expenses WHERE expense_date BETWEEN ? AND ?");
$exp->execute([$from,$to]); $ex = (float)$exp->fetchColumn();

$avg = $db->prepare("SELECT COALESCE(AVG(rate_per_kg),0) FROM paddy_purchases WHERE purchase_date <= ?");
$avg->execute([$to]); $avgRate = (float)$avg->fetchColumn();
$pb = $db->prepare("SELECT COALESCE(SUM(paddy_consumed_kg),0) w, COALESCE(SUM(processing_cost),0) pc FROM production_batches WHERE batch_date BETWEEN ? AND ?");
$pb->execute([$from,$to]); $p = $pb->fetch();
$cogs = (float)$p['w'] * $avgRate + (float)$p['pc'];

$cash = $db->prepare("SELECT COALESCE(SUM(CASE WHEN direction='received' THEN amount ELSE 0 END),0) rin, COALESCE(SUM(CASE WHEN direction='paid' THEN amount ELSE 0 END),0) rout FROM payment_entries WHERE pay_date BETWEEN ? AND ?");
$cash->execute([$from,$to]); $c = $cash->fetch();

$rev = (float)$s['v'];
$gross = $rev - $cogs;
$netOp = $gross + $inc - $ex;
$cashFlow = (float)$c['rin'] - (float)$c['rout'];

// Sales by product
$prodSt = $db->prepare(
  "SELECT si.product_name, si.unit, SUM(si.quantity) qty, SUM(si.line_total) total
   FROM sale_items si JOIN sales s ON s.id = si.sale_id
   WHERE s.sale_date BETWEEN ? AND ?
   GROUP BY si.product_id, si.unit ORDER BY total DESC");
$prodSt->execute([$from,$to]);
$byProduct = $prodSt->fetchAll();

// Transactions (sales + expenses + income) for the table
$txSt = $db->prepare(
  "SELECT sale_date d, 'Sale' type, customer_name party, total_amount amount FROM sales WHERE sale_date BETWEEN ? AND ?
   UNION ALL
   SELECT expense_date, 'Expense', category_name, amount_incurred FROM expenses WHERE expense_date BETWEEN ? AND ?
   UNION ALL
   SELECT income_date, 'Income', source, amount FROM income_entries WHERE income_date BETWEEN ? AND ?
   ORDER BY d");
$txSt->execute([$from,$to,$from,$to,$from,$to]);
$tx = $txSt->fetchAll();

echo json_encode([
  'from'=>$from,'to'=>$to,
  'summary'=>[
    'revenue'=>$rev, 'cash_received'=>(float)$s['c'],
    'other_income'=>$inc, 'expenses'=>$ex, 'cogs'=>$cogs,
    'gross'=>$gross, 'net_op'=>$netOp, 'cash_flow'=>$cashFlow,
    'paddy_in_kg' => (float)(function() use ($db, $from, $to) {
        $st = $db->prepare("SELECT COALESCE(SUM(total_weight_kg),0) FROM paddy_purchases WHERE purchase_date BETWEEN ? AND ?");
        $st->execute([$from, $to]);
        return $st->fetchColumn();
    })(),
    'paddy_consumed_kg' => (float)$p['w'],
  ],
  'by_product' => $byProduct,
  'transactions' => $tx,
], JSON_PRETTY_PRINT);
