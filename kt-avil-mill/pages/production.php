<?php
// pages/production.php
// Production log: paddy in, products out, processing cost.
// Each saved batch creates 'in' inventory movements for the outputs.

$pageTitle = 'Production';
$activePage = 'production';
require __DIR__ . '/../includes/header.php';

$batches = db()->query(
  "SELECT b.*, u.username FROM production_batches b
   JOIN users u ON u.id = b.created_by
   ORDER BY b.batch_date DESC, b.id DESC"
)->fetchAll();

$products = products_list();
?>

<div class="grid two">
  <section class="panel">
    <h2>New production batch</h2>
    <form id="prodForm" method="post" action="api/production_save.php" class="form">
      <?= csrf_field() ?>
      <label>Date <input type="date" name="batch_date" required value="<?= date('Y-m-d') ?>"></label>
      <label>Paddy consumed (kg) <input type="number" step="0.001" name="paddy_consumed_kg" required></label>
      <label>Processing cost (₹) <input type="number" step="0.01" name="processing_cost" value="0.00"></label>
      <h3>Products produced</h3>
      <div id="outputRows">
        <div class="row out-row">
          <select name="product_id[]" required>
            <?php foreach ($products as $p): ?>
              <option value="<?= (int)$p['id'] ?>" data-unit="<?= esc($p['unit']) ?>"><?= esc($p['name']) ?> (<?= esc($p['unit']) ?>)</option>
            <?php endforeach; ?>
          </select>
          <input type="number" step="0.001" name="qty[]" placeholder="Qty" required>
          <input type="text" name="unit[]" class="u" readonly>
        </div>
      </div>
      <button type="button" class="btn ghost" id="addOutput">+ Add product</button>
      <label>Notes <input type="text" name="notes" maxlength="255"></label>
      <button class="btn primary" type="submit">Save batch</button>
    </form>
  </section>

  <section class="panel">
    <h2>Recent batches</h2>
    <?php if (!$batches): ?><p class="empty">No production recorded yet.</p><?php else: ?>
    <table class="table">
      <thead><tr><th>Date</th><th>Paddy in</th><th>Processing cost</th><th>Outputs</th></tr></thead>
      <tbody>
      <?php
        $outStmt = db()->prepare("SELECT product_name, quantity, unit FROM production_outputs WHERE batch_id = ?");
        foreach ($batches as $b):
          $outStmt->execute([$b['id']]);
          $outs = $outStmt->fetchAll();
          $outStr = implode(', ', array_map(fn($o) => "{$o['quantity']} {$o['unit']} {$o['product_name']}", $outs));
      ?>
        <tr>
          <td><?= esc($b['batch_date']) ?></td>
          <td><?= kg((float)$b['paddy_consumed_kg']) ?></td>
          <td><?= money((float)$b['processing_cost']) ?></td>
          <td><?= esc($outStr) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
