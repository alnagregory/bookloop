<?php
/**
 * BOOKLOOP - AJAX Wishlist Toggle Endpoint
 */

header('Content-Type: application/json');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'unauthorized', 'message' => 'Please log in first.']);
    exit;
}

$userId = getAuthUserId();
$bookId = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;

if ($bookId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid book specified.']);
    exit;
}

// Check if book exists
$stmt = $pdo->prepare("SELECT book_id, user_id FROM books WHERE book_id = ?");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    echo json_encode(['status' => 'error', 'message' => 'Book not found.']);
    exit;
}

if ($book['user_id'] == $userId) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot add your own book to your wishlist.']);
    exit;
}

// Check if already in wishlist
$check = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND book_id = ?");
$check->execute([$userId, $bookId]);
$exists = $check->fetch();

if ($exists) {
    // Remove
    $del = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND book_id = ?");
    $del->execute([$userId, $bookId]);
    $action = 'removed';
} else {
    // Add
    $ins = $pdo->prepare("INSERT INTO wishlist (user_id, book_id, created_at) VALUES (?, ?, NOW())");
    $ins->execute([$userId, $bookId]);
    $action = 'added';
}

// Return current total count for user
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
$countStmt->execute([$userId]);
$total = (int)$countStmt->fetchColumn();

echo json_encode([
    'status' => 'success',
    'action' => $action,
    'total' => $total
]);
exit;
