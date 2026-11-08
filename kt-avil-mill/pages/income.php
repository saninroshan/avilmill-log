<?php
// pages/income.php
// Other income entries (ash sales, scrap, etc.) — separate from sales.

$pageTitle = 'Other Income';
$activePage = 'income';
require __DIR__ . '/../includes/header.php';

$rows = db()->query(
  "SELECT i.*, u.username FROM income_entries i JOIN users u ON u.id = i.created_by
   ORDER BY i.income_date DESC, i.id DESC LIMIT 200"
)->fetchAll();
?>

<div class="grid two">
  <section class="panel">
    <h2>New income entry</h2>
    <form method="post" action="api/income_save.php" class="form">
      <?= csrf_field() ?>
      <label>Date <input type="date" name="income_date" required value="<?= date('Y-m-d') ?>"></label>
      <label>Source
        <select name="source" id="incomeSource">
          <option>Ash Sale</option>
          <option>Scrap Sale</option>
          <option>Other</option>
        </select>
      </label>
      <label>Description <input type="text" name="description" maxlength="255"></label>
      <div class="row">
        <label>Quantity <input type="number" step="0.001" name="quantity"></label>
        <label>Unit <input type="text" name="unit" value="bag"></label>
        <label>Unit price (₹) <input type="number" step="0.01" name="unit_price"></label>
      </div>
      <label>Total amount (₹) <input type="number" step="0.01" name="amount" required></label>
      <label>Amount received (₹) <input type="number" step="0.01" name="amount_received" value="0.00"></label>
      <label>Payment method
        <select name="payment_method">
          <option>cash</option><option>bank</option><option>upi</option><option>cheque</option><option>other</option>
        </select>
      </label>
      <label>Notes <input type="text" name="notes" maxlength="255"></label>
      <button class="btn primary" type="submit">Save income</button>
    </form>
  </section>

  <section class="panel">
    <h2>Recent income</h2>
    <?php if (!$rows): ?><p class="empty">No income entries yet.</p><?php else: ?>
    <table class="table">
      <thead><tr><th>Date</th><th>Source</th><th>Description</th><th>Amount</th><th>Received</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= esc($r['income_date']) ?></td>
          <td><?= esc($r['source']) ?></td>
          <td><?= esc($r['description']) ?></td>
          <td><?= money((float)$r['amount']) ?></td>
          <td><?= money((float)$r['amount_received']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
