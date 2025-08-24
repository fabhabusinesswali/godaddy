<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

try {
    // In real app, get user ID from session
    $userId = $_POST['userId'] ?? null;
    $billingData = $_POST['billing'] ?? [];
    
    if (!$userId || empty($billingData)) {
        throw new Exception('Missing required data');
    }

    $pdo->beginTransaction();

    // Insert billing details
    $stmt = $pdo->prepare("
        INSERT INTO billing_details 
        (user_id, full_name, address, city, state, country, postal_code, phone) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId,
        $billingData['fullName'],
        $billingData['address'],
        $billingData['city'],
        $billingData['state'],
        $billingData['country'],
        $billingData['postalCode'],
        $billingData['phone']
    ]);

    // Calculate total from cart
    $stmt = $pdo->prepare("
        SELECT SUM(d.price) as total 
        FROM cart c 
        JOIN domains d ON c.domain_id = d.domain_id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    $total = $stmt->fetch()['total'];

    // Create order
    $stmt = $pdo->prepare("CALL create_order(?, ?)");
    $stmt->execute([$userId, $total]);
    $orderId = $stmt->fetch()['order_id'];

    // Create transaction record
    $stmt = $pdo->prepare("
        INSERT INTO transactions 
        (order_id, amount, status, payment_method, transaction_reference) 
        VALUES (?, ?, 'completed', ?, ?)
    ");
    $stmt->execute([
        $orderId,
        $total,
        'credit_card',
        'TXN_' . time() . rand(1000, 9999)
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'orderId' => $orderId,
        'message' => 'Order processed successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 
