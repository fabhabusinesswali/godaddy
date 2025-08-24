<?php
require_once 'db_connect.php';

header('Content-Type: application/json');

try {
    $searchTerm = $_GET['domain'] ?? '';
    $searchTerm = trim($searchTerm);

    if (empty($searchTerm)) {
        throw new Exception('Search term is required');
    }

    // Get all active domain extensions
    $stmt = $pdo->query("SELECT extension, base_price FROM domain_extensions WHERE is_active = 1");
    $extensions = $stmt->fetchAll();

    $results = [];
    foreach ($extensions as $ext) {
        $domainName = $searchTerm . $ext['extension'];
        
        // Check if domain exists in our database
        $stmt = $pdo->prepare("SELECT * FROM domains WHERE domain_name = ? AND extension = ?");
        $stmt->execute([$searchTerm, $ext['extension']]);
        $domain = $stmt->fetch();

        // Simulate domain availability (in real world, you'd check with domain registrar API)
        $isAvailable = !$domain && rand(0, 1);
        
        $results[] = [
            'domain' => $domainName,
            'price' => $ext['base_price'],
            'isAvailable' => $isAvailable
        ];
    }

    echo json_encode(['success' => true, 'results' => $results]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 
