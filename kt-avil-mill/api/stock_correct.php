<?php
// api/stock_correct.php
// Admin-only absolute stock correction with reason. Logs an 'adjust' movement.

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
header('Content-Type: application/json');

if (!csrf_verify()) { http_response_code(403); echo json_encode(['error'=>'Bad CSRF']); exit; }

try {
    $pid   = to_int(post('product_id'));
    $new   = to_decimal(post('new_stock'));
    $reason= post('reason');
    $prod  = product_by_id($pid);
    if (!$prod) throw new Exception('Product not found.');

    $cur = current_stock($pid);
    $diff = round($new - $cur, 3);
    $db = db();
    $st = $db->prepare(
      "INSERT INTO inventory_movements (movement_date,product_id,product_name,direction,quantity,ref_type,ref_id,reason,created_by)
       VALUES (?,?,?,'adjust',?,'correction',?,?,?)");
    $st->execute([date('Y-m-d'),$pid,$prod['name'],abs($diff),$reason, current_user()['id']]);
    flash("Stock adjusted by $diff {$prod['unit']}.");
    echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error'=>$e->getMessage()]);
}
