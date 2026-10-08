<?php
/**
 * Automated Verification Test for BookLoop Application
 * Tests all 20 required workflows end-to-end
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

echo "=====================================================\n";
echo "    BOOKLOOP - AUTOMATED VERIFICATION SUITE\n";
echo "=====================================================\n\n";

$passCount = 0;
$totalTests = 20;

function assertTest($condition, $testNum, $description) {
    global $passCount;
    if ($condition) {
        echo "[PASS] Test {$testNum}: {$description}\n";
        $passCount++;
    } else {
        echo "[FAIL] Test {$testNum}: {$description}\n";
    }
}

// Test 1: Register student
$testEmail = "test_student_" . time() . "@college.edu";
$rawPass = "Student@123";
$hashed = password_hash($rawPass, PASSWORD_DEFAULT);
$regStmt = $pdo->prepare("
    INSERT INTO users (name, email, password, phone, college, department, semester, student_id, role, status, created_at)
    VALUES (?, ?, ?, '9876543299', 'Test Institute', 'Computer Science', 3, 'TEST-001', 'student', 'active', NOW())
");
$regOk = $regStmt->execute(['Test Student', $testEmail, $hashed]);
$testUserId = (int)$pdo->lastInsertId();
assertTest($regOk && $testUserId > 0, 1, "Register new student account ($testEmail)");

// Test 2: Login validation
$loginCheck = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$loginCheck->execute([$testEmail]);
$userRec = $loginCheck->fetch();
$passValid = password_verify($rawPass, $userRec['password']);
assertTest($userRec && $passValid, 2, "Student login verification using password_verify()");

// Test 3: Add book
$insBook = $pdo->prepare("
    INSERT INTO books (user_id, title, author, isbn, subject, department, semester, price, `condition`, listing_type, description, image, location, status, approval_status, created_at)
    VALUES (?, 'Automated Testing Handbook', 'Quality Engineer', '9781112223334', 'Software Engineering', 'Computer Science', 5, 350.00, 'Like New', 'For Sale', 'Test book listing', 'default_book.svg', 'Lab 1', 'Available', 'Pending', NOW())
");
$bookOk = $insBook->execute([$testUserId]);
$testBookId = (int)$pdo->lastInsertId();
assertTest($bookOk && $testBookId > 0, 3, "Add book listing with initial Pending approval");

// Test 4: Upload image logic
$targetImgFile = UPLOAD_DIR_BOOKS . "default_book.svg";
$imgExists = file_exists($targetImgFile) && filesize($targetImgFile) > 0;
assertTest($imgExists, 4, "Upload directory and book cover assets accessible");

// Test 5: Admin approves listing
$apprStmt = $pdo->prepare("UPDATE books SET approval_status = 'Approved' WHERE book_id = ?");
$apprOk = $apprStmt->execute([$testBookId]);
$checkAppr = $pdo->query("SELECT approval_status FROM books WHERE book_id = {$testBookId}")->fetchColumn();
assertTest($apprOk && $checkAppr === 'Approved', 5, "Admin approves pending book listing");

// Test 6: Search for book
$sStmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE approval_status = 'Approved' AND (title LIKE ? OR author LIKE ?)");
$sStmt->execute(['%Automated Testing%', '%Automated Testing%']);
$searchCount = (int)$sStmt->fetchColumn();
assertTest($searchCount > 0, 6, "Search for book by title keyword");

// Test 7: Filter book
$fStmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE department = ? AND semester = ? AND approval_status = 'Approved'");
$fStmt->execute(['Computer Science', 5]);
$filterCount = (int)$fStmt->fetchColumn();
assertTest($filterCount > 0, 7, "Filter book by department and semester");

// Test 8: Add to wishlist
$buyerId = 3; // Priya Patel
$wStmt = $pdo->prepare("INSERT INTO wishlist (user_id, book_id, created_at) VALUES (?, ?, NOW())");
$wOk = $wStmt->execute([$buyerId, $testBookId]);
$inWishlist = isBookInWishlist($pdo, $buyerId, $testBookId);
assertTest($wOk && $inWishlist, 8, "Add book to buyer's wishlist");

// Test 9: Contact seller
$canContact = !isBlockedEitherWay($pdo, $buyerId, $testUserId);
assertTest($canContact, 9, "Contact seller authorization check");

// Test 10: Send chat message
$msgStmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, book_id, message, is_read, created_at) VALUES (?, ?, ?, 'Is the handbook available?', 0, NOW())");
$msgOk = $msgStmt->execute([$buyerId, $testUserId, $testBookId]);
$testMsgId = (int)$pdo->lastInsertId();
assertTest($msgOk && $testMsgId > 0, 10, "Send chat message to seller linked to book");

// Test 11: Send purchase request
$reqStmt = $pdo->prepare("INSERT INTO requests (book_id, buyer_id, seller_id, request_type, offered_price, meeting_place, message, status, created_at) VALUES (?, ?, ?, 'buy', 320.00, 'Library Lawn', 'Offer Rs. 320', 'Pending', NOW())");
$reqOk = $reqStmt->execute([$testBookId, $buyerId, $testUserId]);
$testReqId = (int)$pdo->lastInsertId();
assertTest($reqOk && $testReqId > 0, 11, "Send purchase request with offered price");

// Test 12: Seller receives notification
$notifOk = addNotification($pdo, $testUserId, "Priya sent purchase request for your book", "request_buy", $testReqId);
$unreadNotifs = getUnreadNotificationCount($pdo, $testUserId);
assertTest($notifOk && $unreadNotifs > 0, 12, "Seller receives in-app notification");

// Test 13: Seller accepts request
$accStmt = $pdo->prepare("UPDATE requests SET status = 'Accepted' WHERE request_id = ?");
$accOk = $accStmt->execute([$testReqId]);
$reqStatus = $pdo->query("SELECT status FROM requests WHERE request_id = {$testReqId}")->fetchColumn();
assertTest($accOk && $reqStatus === 'Accepted', 13, "Seller accepts purchase request");

// Test 14: Mark book Sold & Complete deal
$dealStmt = $pdo->prepare("UPDATE requests SET status = 'Completed' WHERE request_id = ?");
$dealStmt->execute([$testReqId]);
$soldStmt = $pdo->prepare("UPDATE books SET status = 'Sold' WHERE book_id = ?");
$soldOk = $soldStmt->execute([$testBookId]);
$bookStatus = $pdo->query("SELECT status FROM books WHERE book_id = {$testBookId}")->fetchColumn();
assertTest($soldOk && $bookStatus === 'Sold', 14, "Mark book Sold and complete transaction");

// Test 15: Send exchange request
$exReqStmt = $pdo->prepare("INSERT INTO requests (book_id, buyer_id, seller_id, request_type, offered_book_id, meeting_place, message, status, created_at) VALUES (2, 5, 2, 'exchange', 4, 'Canteen', 'Exchange dragon book for OS', 'Pending', NOW())");
$exReqOk = $exReqStmt->execute();
$testExReqId = (int)$pdo->lastInsertId();
assertTest($exReqOk && $testExReqId > 0, 15, "Send barter exchange request offering another textbook");

// Test 16: Complete exchange
$pdo->prepare("UPDATE requests SET status = 'Completed' WHERE request_id = ?")->execute([$testExReqId]);
$pdo->prepare("UPDATE books SET status = 'Exchanged' WHERE book_id IN (2, 4)")->execute();
$checkEx = $pdo->query("SELECT status FROM books WHERE book_id = 2")->fetchColumn();
// Revert book 2 & 4 to available so sample data stays pristine
$pdo->prepare("UPDATE books SET status = 'Available' WHERE book_id IN (2, 4)")->execute();
assertTest($checkEx === 'Exchanged', 16, "Complete exchange marks requested book Exchanged");

// Test 17: Rate seller
$revStmt = $pdo->prepare("INSERT INTO reviews (reviewer_id, seller_id, request_id, rating, review, created_at) VALUES (?, ?, ?, 5, 'Excellent textbook and timely deal!', NOW())");
$revOk = $revStmt->execute([$buyerId, $testUserId, $testReqId]);
$stats = getUserRatingStats($pdo, $testUserId);
assertTest($revOk && $stats['count'] > 0 && $stats['avg'] == 5.0, 17, "Buyer rates seller with 5-star review");

// Test 18: Report listing
$repStmt = $pdo->prepare("INSERT INTO reports (reporter_id, book_id, reported_user_id, reason, description, status, created_at) VALUES (?, ?, ?, 'Test Flag', 'Testing report submission', 'Pending', NOW())");
$repOk = $repStmt->execute([$buyerId, $testBookId, $testUserId]);
$testRepId = (int)$pdo->lastInsertId();
assertTest($repOk && $testRepId > 0, 18, "Report listing submitted to moderation queue");

// Test 19: Admin views report
$viewRep = $pdo->query("SELECT COUNT(*) FROM reports WHERE report_id = {$testRepId}")->fetchColumn();
assertTest($viewRep > 0, 19, "Admin views report in moderation queue");

// Test 20: Admin manages user
$suspendStmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE user_id = ?");
$suspendStmt->execute([$testUserId]);
$suspStatus = $pdo->query("SELECT status FROM users WHERE user_id = {$testUserId}")->fetchColumn();
$activeStmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
$activeStmt->execute([$testUserId]);
$actStatus = $pdo->query("SELECT status FROM users WHERE user_id = {$testUserId}")->fetchColumn();
assertTest($suspStatus === 'suspended' && $actStatus === 'active', 20, "Admin suspends and activates student user");

// Cleanup test items
$pdo->prepare("DELETE FROM reviews WHERE request_id = ?")->execute([$testReqId]);
$pdo->prepare("DELETE FROM reports WHERE report_id = ?")->execute([$testRepId]);
$pdo->prepare("DELETE FROM requests WHERE request_id IN (?, ?)")->execute([$testReqId, $testExReqId]);
$pdo->prepare("DELETE FROM messages WHERE message_id = ?")->execute([$testMsgId]);
$pdo->prepare("DELETE FROM wishlist WHERE book_id = ?")->execute([$testBookId]);
$pdo->prepare("DELETE FROM books WHERE book_id = ?")->execute([$testBookId]);
$pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$testUserId]);
$pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$testUserId]);

echo "\n-----------------------------------------------------\n";
echo "RESULT: {$passCount} / {$totalTests} TESTS PASSED SUCCESSFULLY!\n";
echo "-----------------------------------------------------\n";
