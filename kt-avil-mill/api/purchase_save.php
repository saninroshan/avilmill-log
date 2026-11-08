<?php
// api/purchase_save.php
// Saves a paddy purchase with individual sack weights.
// Uses a PDO transaction so purchase + sacks + payment stay consistent.

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/json');

if (!csrf_verify()) { http_response_code(403); echo json_encode(['error'=>'Bad CSRF']); exit; }

try {
    $date     = post('purchase_date');
    $supId    = post('supplier_id') !== '' ? to_int(post('supplier_id')) : null;
    $supName  = post('supplier_name') !== '' ? post('supplier_name') : 'Walk-in';
    if ($supId && $supName === 'Walk-in') {
        $row = db()->prepare('SELECT name FROM suppliers WHERE id=?');
        $row->execute([$supId]); $supName = $row->fetchColumn() ?: 'Walk-in';
    }
    $numSacks = to_int(post('num_sacks'));
    $rate     = to_decimal(post('rate_per_kg'));
    $paid     = to_decimal(post('amount_paid'));
    $notes    = post('notes') ?: null;

    $weights = $_POST['weight'] ?? [];
    if (count($weights) !== $numSacks) throw new Exception('Sack count mismatch.');
    $totalW = 0.0;
    foreach ($weights as $w) {
        $wv = to_decimal($w);
        if ($wv <= 0) throw new Exception('Sack weight must be positive.');
        $totalW += $wv;
    }
    $amount = round($totalW * $rate, 2);
    $outstanding = round($amount - $paid, 2);
    $status = payment_status_for($amount, $paid);

    $db = db();
    $db->beginTransaction();
    $st = $db->prepare(
      "INSERT INTO paddy_purchases
       (purchase_date,supplier_id,supplier_name,num_sacks,total_weight_kg,rate_per_kg,
        purchase_amount,amount_paid,amount_outstanding,payment_status,notes,created_by)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $st->execute([$date,$supId,$supName,$numSacks,$totalW,$rate,$amount,$paid,$outstanding,$status,$notes, current_user()['id']]);
    $pid = (int) $db->lastInsertId();

    $sackSt = $db->prepare("INSERT INTO paddy_purchase_sacks (purchase_id,sack_no,weight_kg) VALUES (?,?,?)");
    $i = 1;
    foreach ($weights as $w) { $sackSt->execute([$pid,$i++,to_decimal($w)]); }

    if ($paid > 0) {
        $pay = $db->prepare("INSERT INTO payment_entries (pay_date,direction,ref_type,ref_id,amount,method,created_by) VALUES (?,'paid','purchase',?,?,'cash',?)");
        $pay->execute([$date,$pid,$paid,current_user()['id']]);
    }
    if ($supId) {
        $db->prepare("UPDATE suppliers SET balance = balance + ? WHERE id=?")->execute([$outstanding,$supId]);
    }
    $db->commit();
    flash('Purchase saved.');
    echo json_encode(['ok'=>true,'id'=>$pid]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(['error'=>$e->getMessage()]);
}
