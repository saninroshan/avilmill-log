<?php
// api/production_save.php
// Saves a production batch + outputs and creates 'in' inventory movements.

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/json');

if (!csrf_verify()) { http_response_code(403); echo json_encode(['error'=>'Bad CSRF']); exit; }

try {
    $date    = post('batch_date');
    $paddy   = to_decimal(post('paddy_consumed_kg'));
    $cost    = to_decimal(post('processing_cost'));
    $notes   = post('notes') ?: null;
    if ($paddy <= 0) throw new Exception('Paddy consumed must be positive.');

    $pids = $_POST['product_id'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $units = $_POST['unit'] ?? [];
    if (count($pids) === 0) throw new Exception('Add at least one output product.');

    $db = db();
    $db->beginTransaction();
    $st = $db->prepare("INSERT INTO production_batches (batch_date,paddy_consumed_kg,processing_cost,notes,created_by) VALUES (?,?,?,?,?)");
    $st->execute([$date,$paddy,$cost,$notes, current_user()['id']]);
    $bid = (int) $db->lastInsertId();

    $outSt = $db->prepare("INSERT INTO production_outputs (batch_id,product_id,product_name,quantity,unit) VALUES (?,?,?,?,?)");
    $mvSt  = $db->prepare("INSERT INTO inventory_movements (movement_date,product_id,product_name,direction,quantity,ref_type,ref_id,created_by) VALUES (?,?,?,'in',?,'production',?,?)");
    for ($i=0; $i<count($pids); $i++) {
        $pid = to_int($pids[$i]);
        $prod = product_by_id($pid);
        if (!$prod) throw new Exception("Product id $pid not found.");
        $q = to_decimal($qtys[$i]);
        if ($q <= 0) throw new Exception('Output quantity must be positive.');
        $u = $units[$i] ?: $prod['unit'];
        $outSt->execute([$bid,$pid,$prod['name'],$q,$u]);
        $mvSt->execute([$date,$pid,$prod['name'],$q,$bid, current_user()['id']]);
    }
    $db->commit();
    flash('Production batch saved.');
    echo json_encode(['ok'=>true,'id'=>$bid]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(['error'=>$e->getMessage()]);
}
