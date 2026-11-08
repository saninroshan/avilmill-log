<?php
// pages/products.php
// Admin: manage product default prices, units, pricing method, active status.
// Also includes the XML export/import feature (config export).

$pageTitle = 'Products & Prices';
$activePage = 'products';
require_admin();
require __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_product') {
    if (!csrf_verify()) { flash('Invalid session token.', 'error'); }
    else {
        $id = to_int(post('id', '0'));
        $name = post('name');
        $unit = post('unit');
        $method = post('pricing_method');
        $price = to_decimal(post('default_price'));
        $sack = post('sack_size_kg') !== '' ? to_decimal(post('sack_size_kg')) : null;
        $active = isset($_POST['is_active']) ? 1 : 0;
        $ash = isset($_POST['is_ash_income']) ? 1 : 0;
        $notes = post('notes');
        if ($id > 0) {
            $st = db()->prepare("UPDATE products SET name=?,unit=?,pricing_method=?,default_price=?,sack_size_kg=?,is_active=?,is_ash_income=?,notes=? WHERE id=?");
            $st->execute([$name,$unit,$method,$price,$sack,$active,$ash,$notes,$id]);
        } else {
            $st = db()->prepare("INSERT INTO products (name,unit,pricing_method,default_price,sack_size_kg,is_active,is_ash_income,notes) VALUES (?,?,?,?,?,?,?,?)");
            $st->execute([$name,$unit,$method,$price,$sack,$active,$ash,$notes]);
        }
        flash('Product saved.');
    }
    header('Location: index.php?page=products'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import_xml') {
    if (!csrf_verify()) { flash('Invalid session token.', 'error'); }
    else {
        $xml = trim(post('xml'));
        try {
            libxml_use_internal_errors(true);
            $doc = simplexml_load_string($xml);
            if ($doc === false) throw new Exception('Invalid XML.');
            $count = 0;
            foreach ($doc->product as $p) {
                $st = db()->prepare("UPDATE products SET default_price=?, unit=?, pricing_method=?, sack_size_kg=? WHERE name=?");
                $st->execute([
                    (float)$p->default_price, (string)$p->unit, (string)$p->pricing_method,
                    $p->sack_size_kg ? (float)$p->sack_size_kg : null, (string)$p->name
                ]);
                $count++;
            }
            flash("Imported $count product(s) from XML.");
        } catch (Throwable $e) {
            flash('XML import failed: ' . $e->getMessage(), 'error');
        }
    }
    header('Location: index.php?page=products'); exit;
}

$products = products_list(false);
?>

<section class="panel">
  <h2>Products</h2>
  <table class="table">
    <thead><tr><th>Name</th><th>Unit</th><th>Pricing</th><th>Default price</th><th>Sack kg</th><th>Active</th><th>Ash income</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
      <tr>
        <td><?= esc($p['name']) ?></td>
        <td><?= esc($p['unit']) ?></td>
        <td><?= esc($p['pricing_method']) ?></td>
        <td><?= money((float)$p['default_price']) ?></td>
        <td><?= $p['sack_size_kg'] !== null ? esc($p['sack_size_kg']) : '—' ?></td>
        <td><?= $p['is_active'] ? 'Yes' : 'No' ?></td>
        <td><?= $p['is_ash_income'] ? 'Yes' : 'No' ?></td>
        <td><button class="btn ghost small" onclick="editProduct(<?= esc(json_encode($p)) ?>)">Edit</button></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <button class="btn" id="newProductBtn">+ New product</button>
</section>

<section class="panel">
  <h2>XML configuration export</h2>
  <p class="muted small">Exports current product prices/units as XML. You can edit and re-import below. This demonstrates XML parsing (Syllabus: XML).</p>
  <textarea id="xmlBox" class="xml-box" readonly></textarea>
  <form method="post" action="index.php?page=products" class="form">
    <input type="hidden" name="action" value="import_xml">
    <?= csrf_field() ?>
    <label>Edit XML and import</label>
    <textarea name="xml" class="xml-box" rows="8"></textarea>
    <button class="btn primary" type="submit">Import XML</button>
  </form>
</section>

<!-- Modal form for product edit/add -->
<div class="modal" id="prodModal">
  <div class="modal-card">
    <h3 id="prodModalTitle">Product</h3>
    <form method="post" action="index.php?page=products" class="form">
      <input type="hidden" name="action" value="save_product">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="f_id" value="0">
      <label>Name <input type="text" name="name" id="f_name" required></label>
      <div class="row">
        <label>Unit <input type="text" name="unit" id="f_unit" value="kg" required></label>
        <label>Default price <input type="number" step="0.01" name="default_price" id="f_price" required></label>
      </div>
      <label>Pricing method
        <select name="pricing_method" id="f_method">
          <option value="per_unit">per_unit (price per unit)</option>
          <option value="per_kg_from_sack">per_kg_from_sack (e.g. thavid)</option>
        </select>
      </label>
      <label>Sack size kg (only for per_kg_from_sack) <input type="number" step="0.001" name="sack_size_kg" id="f_sack"></label>
      <label class="check"><input type="checkbox" name="is_active" id="f_active" checked> Active</label>
      <label class="check"><input type="checkbox" name="is_ash_income" id="f_ash"> Counts as ash/other income</label>
      <label>Notes <input type="text" name="notes" id="f_notes"></label>
      <div class="row"><button class="btn primary" type="submit">Save</button>
        <button class="btn ghost" type="button" onclick="document.getElementById('prodModal').style.display='none'">Cancel</button></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
