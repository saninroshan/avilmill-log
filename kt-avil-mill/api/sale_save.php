<?php
// api/sale_save.php
// Saves a sale (single item for simplicity) with stock check.
// Stock cannot go negative: we verify current stock >= quantity before insert.

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/json');

if (!csrf_verify()) { http_response_code(403); echo json_encode(['error'=>'Bad CSRF']); exit; }

try {
    $date   = post('sale_date');
    $custId = post('customer_id') !== '' ? to_int(post('customer_id')) : null;
    $custNm = post('customer_name') !== '' ? post('customer_name') : 'Walk-in';
    if ($custId && $custNm === 'Walk-in') {
        $row = db()->prepare('SELECT name FROM customers WHERE id=?');
        $row->execute([$custId]); $custNm = $row->fetchColumn() ?: 'Walk-in';
    }
    $pid    = to_int(post('product_id'));
    $prod   = product_by_id($pid);
    if (!$prod) throw new Exception('Product not found.');
    $qty    = to_decimal(post('quantity'));
    $unit   = post('unit');
    $price  = to_decimal(post('unit_price'));
    $recv   = to_decimal(post('amount_received'));
    $notes  = post('notes') ?: null;
    if ($qty <= 0) throw new Exception('Quantity must be positive.');
    if ($price < 0) throw new Exception('Unit price cannot be negative.');

    $lineTotal = round($qty * $price, 2);
    $balance   = round($lineTotal - $recv, 2);
    $status    = payment_status_for($lineTotal, $recv);

    // Stock check (for kg / sack products tracked in inventory).
    $stock = current_stock($pid);
    if ($qty > $stock) {
        throw new Exception("Insufficient stock for {$prod['name']}. Available: $stock {$prod['unit']}.");
    }

    $db = db();
    $db->beginTransaction();
    $st = $db->prepare(
      "INSERT INTO sales (sale_date,customer_id,customer_name,total_amount,amount_received,balance_due,payment_status,notes,created_by)
       VALUES (?,?,?,?,?,?,?,?,?)");
    $st->execute([$date,$custId,$custNm,$lineTotal,$recv,$balance,$status,$notes, current_user()['id']]);
    $sid = (int) $db->lastInsertId();

    $it = $db->prepare("INSERT INTO sale_items (sale_id,product_id,product_name,quantity,unit,unit_price,line_total) VALUES (?,?,?,?,?,?,?)");
    $it->execute([$sid,$pid,$prod['name'],$qty,$unit,$price,$lineTotal]);

    $mv = $db->prepare("INSERT INTO inventory_movements (movement_date,product_id,product_name,direction,quantity,ref_type,ref_id,created_by) VALUES (?,?,?,'out',?,'sale',?,?)");
    $mv->execute([$date,$pid,$prod['name'],$qty,$sid,current_user()['id']]);

    if ($recv > 0) {
        $pay = $db->prepare("INSERT INTO payment_entries (pay_date,direction,ref_type,ref_id,amount,method,created_by) VALUES (?,'received','sale',?,?,'cash',?)");
        $pay->execute([$date,$sid,$recv,current_user()['id']]);
    }
    if ($custId) {
        $db->prepare("UPDATE customers SET balance = balance + ? WHERE id=?")->execute([$balance,$custId]);
    }
    $db->commit();
    flash('Sale saved.');
    echo json_encode(['ok'=>true,'id'=>$sid]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(['error'=>$e->getMessage()]);
}
