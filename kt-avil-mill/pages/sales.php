<?php
// pages/sales.php
// Sales logbook. Multi-item via single product line for simplicity.
// Stock is checked server-side: a sale cannot drive stock below zero.

$pageTitle = 'Sales';
$activePage = 'sales';
require __DIR__ . '/../includes/header.php';

$rows = db()->query(
  "SELECT s.*, u.username FROM sales s JOIN users u ON u.id = s.created_by
   ORDER BY s.sale_date DESC, s.id DESC LIMIT 200"
)->fetchAll();

$products = products_list();
$customers = customers_list();
?>

<div class="grid two">
  <section class="panel">
    <h2>New sale</h2>
    <form id="saleForm" method="post" action="api/sale_save.php" class="form" data-stock-check="1">
      <?= csrf_field() ?>
      <div class="row">
        <label>Date <input type="date" name="sale_date" required value="<?= date('Y-m-d') ?>"></label>
        <label>Customer
          <select name="customer_id" id="customer_id">
            <option value="">— walk-in —</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= esc($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Customer name (if not listed) <input type="text" name="customer_name" id="customer_name"></label>
      </div>
      <div class="row">
        <label>Product
          <select name="product_id" id="product_id" required>
            <?php foreach ($products as $p):
              $stock = current_stock((int)$p['id']); ?>
              <option value="<?= (int)$p['id'] ?>"
                      data-unit="<?= esc($p['unit']) ?>"
                      data-price="<?= esc($p['default_price']) ?>"
                      data-stock="<?= esc($stock) ?>">
                <?= esc($p['name']) ?> (<?= esc($p['unit']) ?>) — stock <?= esc($stock) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Quantity <input type="number" step="0.001" name="quantity" id="quantity" required></label>
        <label>Unit <input type="text" name="unit" id="unit" readonly></label>
      </div>
      <div class="row">
        <label>Unit price (₹) <input type="number" step="0.01" name="unit_price" id="unit_price" required></label>
        <label>Line total <b id="lineTotal">₹0.00</b></label>
        <label>Amount received (₹) <input type="number" step="0.01" name="amount_received" id="amount_received" value="0.00"></label>
      </div>
      <label>Notes <input type="text" name="notes" maxlength="255"></label>
      <p class="stockwarn small" id="stockWarn"></p>
      <button class="btn primary" type="submit">Save sale</button>
    </form>
  </section>

  <section class="panel">
    <h2>Recent sales</h2>
    <?php if (!$rows): ?><p class="empty">No sales recorded yet.</p><?php else: ?>
    <table class="table">
      <thead><tr><th>Date</th><th>Customer</th><th>Amount</th><th>Received</th><th>Balance</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= esc($r['sale_date']) ?></td>
          <td><?= esc($r['customer_name']) ?></td>
          <td><?= money((float)$r['total_amount']) ?></td>
          <td><?= money((float)$r['amount_received']) ?></td>
          <td><?= money((float)$r['balance_due']) ?></td>
          <td><span class="badge <?= $r['payment_status'] ?>"><?= esc($r['payment_status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
