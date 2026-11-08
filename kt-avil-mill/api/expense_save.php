<?php
// api/expense_save.php
// Saves an expense; records a payment_entries row for the paid portion.

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/json');

if (!csrf_verify()) { http_response_code(403); echo json_encode(['error'=>'Bad CSRF']); exit; }

try {
    $date    = post('expense_date');
    $catId   = to_int(post('category_id'));
    $catName = '';
    foreach (expense_categories_list() as $c) if ((int)$c['id']===$catId) { $catName = $c['name']; break; }
    if ($catName === '') throw new Exception('Invalid category.');
    $desc    = post('description');
    $qty     = post('quantity') !== '' ? to_decimal(post('quantity')) : null;
    $incurred= to_decimal(post('amount_incurred'));
    $paid    = to_decimal(post('amount_paid'));
    $method  = post('payment_method');
    $notes   = post('notes') ?: null;
    if ($incurred <= 0) throw new Exception('Amount incurred must be positive.');

    $db = db();
    $db->beginTransaction();
    $st = $db->prepare(
      "INSERT INTO expenses (expense_date,category_id,category_name,description,quantity,amount_incurred,amount_paid,payment_method,notes,created_by)
       VALUES (?,?,?,?,?,?,?,?,?,?)");
    $st->execute([$date,$catId,$catName,$desc,$qty,$incurred,$paid,$method,$notes, current_user()['id']]);
    $eid = (int) $db->lastInsertId();
    if ($paid > 0) {
        $pay = $db->prepare("INSERT INTO payment_entries (pay_date,direction,ref_type,ref_id,amount,method,created_by) VALUES (?,'paid','expense',?,?,?,?)");
        $pay->execute([$date,$eid,$paid,$method, current_user()['id']]);
    }
    $db->commit();
    flash('Expense saved.');
    echo json_encode(['ok'=>true,'id'=>$eid]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(['error'=>$e->getMessage()]);
}
