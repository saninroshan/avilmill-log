<?php
// pages/expenses.php
// Expense entry form + list. Distinguishes incurred vs paid.

$pageTitle = 'Expenses';
$activePage = 'expenses';
require __DIR__ . '/../includes/header.php';

$rows = db()->query(
  "SELECT e.*, u.username FROM expenses e JOIN users u ON u.id = e.created_by
   ORDER BY e.expense_date DESC, e.id DESC LIMIT 200"
)->fetchAll();
$cats = expense_categories_list();
?>

<div class="grid two">
  <section class="panel">
    <h2>New expense</h2>
    <form method="post" action="api/expense_save.php" class="form">
      <?= csrf_field() ?>
      <label>Date <input type="date" name="expense_date" required value="<?= date('Y-m-d') ?>"></label>
      <label>Category
        <select name="category_id" id="category_id" required>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= esc($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Description <input type="text" name="description" required maxlength="255"></label>
      <div class="row">
        <label>Quantity (optional) <input type="number" step="0.001" name="quantity"></label>
        <label>Amount incurred (₹) <input type="number" step="0.01" name="amount_incurred" required></label>
        <label>Amount paid (₹) <input type="number" step="0.01" name="amount_paid" value="0.00"></label>
      </div>
      <label>Payment method
        <select name="payment_method">
          <option>cash</option><option>bank</option><option>upi</option><option>cheque</option><option>other</option>
        </select>
      </label>
      <label>Notes <input type="text" name="notes" maxlength="255"></label>
      <button class="btn primary" type="submit">Save expense</button>
    </form>

    <?php if (is_admin()): ?>
    <h3>Add expense category</h3>
    <form method="post" action="api/category_add.php" class="form inline">
      <?= csrf_field() ?>
      <input type="text" name="name" required placeholder="Category name">
      <button class="btn">Add</button>
    </form>
    <?php endif; ?>
  </section>

  <section class="panel">
    <h2>Recent expenses</h2>
    <?php if (!$rows): ?><p class="empty">No expenses recorded yet.</p><?php else: ?>
    <table class="table">
      <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Incurred</th><th>Paid</th><th>Method</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= esc($r['expense_date']) ?></td>
          <td><?= esc($r['category_name']) ?></td>
          <td><?= esc($r['description']) ?></td>
          <td><?= money((float)$r['amount_incurred']) ?></td>
          <td><?= money((float)$r['amount_paid']) ?></td>
          <td><?= esc($r['payment_method']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
