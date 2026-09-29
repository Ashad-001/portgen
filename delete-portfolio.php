<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$portfolioId = (int)($_GET['id'] ?? 0);

if ($portfolioId === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Portfolio ID required']);
    exit;
}

$stmt = $conn->prepare('DELETE FROM portfolios WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $portfolioId);

if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete portfolio']);
}

$stmt->close();
