<?php
// pages/inventory.php
// Shows current stock per product and the movement history.
// Admins can record a correction with a reason.

$pageTitle = 'Inventory';
$activePage = 'inventory';
require __DIR__ . '/../includes/header.php';

$products = products_list();
$moves = db()->query(
  "SELECT m.*, u.username FROM inventory_movements m
   JOIN users u ON u.id = m.created_by
   ORDER BY m.movement_date DESC, m.id DESC LIMIT 300"
)->fetchAll();
?>

<div class="grid two">
  <section class="panel">
    <h2>Current stock</h2>
    <table class="table">
      <thead><tr><th>Product</th><th>Unit</th><th>Stock</th></tr></thead>
      <tbody>
      <?php foreach ($products as $p):
        $stk = current_stock((int)$p['id']);
        $cls = $stk < 0 ? 'bad' : ($stk == 0 ? 'warn' : 'good');
      ?>
        <tr>
          <td><?= esc($p['name']) ?></td>
          <td><?= esc($p['unit']) ?></td>
          <td class="<?= $cls ?>"><?= number_format($stk, 3) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <?php if (is_admin()): ?>
    <h3>Stock correction</h3>
    <form method="post" action="api/stock_correct.php" class="form">
      <?= csrf_field() ?>
      <label>Product
        <select name="product_id" required>
          <?php foreach ($products as $p): ?>
            <option value="<?= (int)$p['id'] ?>"><?= esc($p['name']) ?> (<?= esc($p['unit']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>New stock count (absolute)
        <input type="number" step="0.001" name="new_stock" required>
      </label>
      <label>Reason <input type="text" name="reason" maxlength="255" required></label>
      <button class="btn primary" type="submit">Save correction</button>
    </form>
    <?php endif; ?>
  </section>

  <section class="panel">
    <h2>Recent movements</h2>
    <?php if (!$moves): ?><p class="empty">No movements yet.</p><?php else: ?>
    <table class="table">
      <thead><tr><th>Date</th><th>Product</th><th>Dir</th><th>Qty</th><th>Ref</th><th>By</th></tr></thead>
      <tbody>
      <?php foreach ($moves as $m): ?>
        <tr>
          <td><?= esc($m['movement_date']) ?></td>
          <td><?= esc($m['product_name']) ?></td>
          <td><?= esc($m['direction']) ?></td>
          <td><?= number_format((float)$m['quantity'], 3) ?></td>
          <td><?= esc($m['ref_type'] . ' #' . $m['ref_id']) ?></td>
          <td><?= esc($m['username']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
