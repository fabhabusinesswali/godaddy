<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['userId'] ?? null; // In real app, get from session
    
    switch ($action) {
        case 'add':
            $domainName = $_POST['domain'] ?? '';
            $extension = $_POST['extension'] ?? '';
            $price = $_POST['price'] ?? 0;

            // First, add domain to domains table if it doesn't exist
            $stmt = $pdo->prepare("INSERT IGNORE INTO domains (domain_name, extension, price) VALUES (?, ?, ?)");
            $stmt->execute([$domainName, $extension, $price]);
            
            $domainId = $pdo->lastInsertId();
            
            // Add to cart
            $stmt = $pdo->prepare("CALL add_to_cart(?, ?)");
            $stmt->execute([$userId, $domainId]);
            
            echo json_encode(['success' => true, 'message' => 'Added to cart']);
            break;

        case 'remove':
            $cartItemId = $_POST['cartItemId'] ?? 0;
            
            $stmt = $pdo->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$cartItemId, $userId]);
            
            echo json_encode(['success' => true, 'message' => 'Removed from cart']);
            break;

        case 'get':
            $stmt = $pdo->prepare("
                SELECT c.cart_id, d.domain_name, d.extension, d.price 
                FROM cart c 
                JOIN domains d ON c.domain_id = d.domain_id 
                WHERE c.user_id = ?
            ");
            $stmt->execute([$userId]);
            $items = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'items' => $items]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 
