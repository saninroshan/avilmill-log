<?php
// pages/purchases.php
// Paddy purchase logbook. Form with variable sack weights + list.
// Saving is handled via api/purchase_save.php (AJAX) but also works as
// a normal POST fallback for simplicity in the viva demo.

$pageTitle = 'Paddy Purchases';
$activePage = 'purchases';
require __DIR__ . '/../includes/header.php';

$rows = db()->query(
  "SELECT p.*, u.username FROM paddy_purchases p
   JOIN users u ON u.id = p.created_by
   ORDER BY p.purchase_date DESC, p.id DESC"
)->fetchAll();

$suppliers = suppliers_list();
?>

<div class="grid two">
  <section class="panel">
    <h2>New paddy purchase</h2>
    <form id="purchaseForm" method="post" action="api/purchase_save.php" class="form">
      <?= csrf_field() ?>
      <div class="row">
        <label>Date <input type="date" name="purchase_date" required value="<?= date('Y-m-d') ?>"></label>
        <label>Supplier
          <select name="supplier_id" id="supplier_id">
            <option value="">— walk-in —</option>
            <?php foreach ($suppliers as $sp): ?>
              <option value="<?= (int)$sp['id'] ?>"><?= esc($sp['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Supplier name (if not listed)
          <input type="text" name="supplier_name" id="supplier_name" placeholder="optional">
        </label>
      </div>
      <div class="row">
        <label>Number of sacks <input type="number" name="num_sacks" id="num_sacks" min="1" value="1" required></label>
        <label>Rate per kg (₹) <input type="number" step="0.01" name="rate_per_kg" id="rate_per_kg" required></label>
        <label>Amount paid (₹) <input type="number" step="0.01" name="amount_paid" id="amount_paid" value="0.00"></label>
      </div>

      <label>Sack weights (kg) — enter each sack's actual weight</label>
      <div id="sackRows" class="sack-rows"></div>
      <div class="row totals">
        <span>Total weight: <b id="totalWeight">0.000</b> kg</span>
        <span>Purchase amount: <b id="purchaseAmount">₹0.00</b></span>
      </div>
      <label>Notes <input type="text" name="notes" maxlength="255"></label>
      <button class="btn primary" type="submit">Save purchase</button>
    </form>
  </section>

  <section class="panel">
    <h2>Recent purchases</h2>
    <?php if (!$rows): ?>
      <p class="empty">No purchases recorded yet.</p>
    <?php else: ?>
    <table class="table">
      <thead><tr><th>Date</th><th>Supplier</th><th>Sacks</th><th>Weight</th><th>Rate</th><th>Amount</th><th>Paid</th><th>Due</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= esc($r['purchase_date']) ?></td>
          <td><?= esc($r['supplier_name']) ?></td>
          <td><?= (int)$r['num_sacks'] ?></td>
          <td><?= kg((float)$r['total_weight_kg']) ?></td>
          <td><?= money((float)$r['rate_per_kg']) ?>/kg</td>
          <td><?= money((float)$r['purchase_amount']) ?></td>
          <td><?= money((float)$r['amount_paid']) ?></td>
          <td><?= money((float)$r['amount_outstanding']) ?></td>
          <td><span class="badge <?= $r['payment_status'] ?>"><?= esc($r['payment_status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
