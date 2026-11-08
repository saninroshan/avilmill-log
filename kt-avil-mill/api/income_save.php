<?php
// api/income_save.php
// Saves an "other income" entry (e.g. ash sale).

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/json');

if (!csrf_verify()) { http_response_code(403); echo json_encode(['error'=>'Bad CSRF']); exit; }

try {
    $date   = post('income_date');
    $source = post('source');
    $desc   = post('description') ?: null;
    $qty    = post('quantity') !== '' ? to_decimal(post('quantity')) : null;
    $unit   = post('unit') ?: null;
    $price  = post('unit_price') !== '' ? to_decimal(post('unit_price')) : null;
    $amount = to_decimal(post('amount'));
    $recv   = to_decimal(post('amount_received'));
    $method = post('payment_method');
    $notes  = post('notes') ?: null;
    if ($amount <= 0) throw new Exception('Amount must be positive.');

    $db = db();
    $db->beginTransaction();
    $st = $db->prepare(
      "INSERT INTO income_entries (income_date,source,description,quantity,unit,unit_price,amount,amount_received,payment_method,notes,created_by)
       VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $st->execute([$date,$source,$desc,$qty,$unit,$price,$amount,$recv,$method,$notes, current_user()['id']]);
    $iid = (int) $db->lastInsertId();
    if ($recv > 0) {
        $pay = $db->prepare("INSERT INTO payment_entries (pay_date,direction,ref_type,ref_id,amount,method,created_by) VALUES (?,'received','income',?,?,?,?)");
        $pay->execute([$date,$iid,$recv,$method, current_user()['id']]);
    }
    $db->commit();
    flash('Income entry saved.');
    echo json_encode(['ok'=>true,'id'=>$iid]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(['error'=>$e->getMessage()]);
}
