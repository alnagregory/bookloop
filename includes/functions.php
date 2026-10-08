<?php
/**
 * BOOKLOOP - Global Helper Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!ob_get_level()) {
    ob_start();
}

/**
 * Sanitize string output for safe HTML display
 */
function e($str) {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Clean user string input
 */
function clean($data) {
    return trim(filter_var($data, FILTER_DEFAULT));
}

/**
 * Redirect helper (safe against headers already sent)
 */
function redirect($path) {
    $target = (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) ? $path : (BASE_URL . ltrim($path, '/'));
    if (!headers_sent()) {
        header("Location: " . $target);
    } else {
        echo "<script>window.location.href=" . json_encode($target) . ";</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . "'></noscript>";
    }
    exit;
}

/**
 * Flash messaging system
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message HTML if present
 */
function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = e($flash['type']);
        $msg = e($flash['message']);
        $icon = 'info-circle';
        if ($type === 'success') $icon = 'check-circle';
        if ($type === 'error' || $type === 'danger') { $type = 'error'; $icon = 'exclamation-circle'; }
        if ($type === 'warning') $icon = 'exclamation-triangle';

        echo "<div class=\"alert alert-{$type}\" id=\"flashAlert\">
                <i class=\"fas fa-{$icon}\"></i>
                <span>{$msg}</span>
                <button type=\"button\" class=\"alert-close\" onclick=\"this.parentElement.remove();\">&times;</button>
              </div>";
    }
}

/**
 * CSRF Protection
 */
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Authentication Helpers
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getAuthUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getAuthUser($pdo) {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Format Currency (Indian Rupees ₹)
 */
function formatPrice($amount) {
    return '₹' . number_format((float)$amount, 2);
}

/**
 * Relative Time Formatter (e.g. 2 hours ago)
 */
function timeAgo($datetime) {
    if (empty($datetime)) return 'Unknown';
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hrs ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('d M Y', $time);
}

/**
 * Badge renderers
 */
function getStatusBadge($status) {
    $status = e($status);
    $class = 'badge-available';
    if ($status === 'Pending') $class = 'badge-pending';
    if ($status === 'Sold') $class = 'badge-sold';
    if ($status === 'Exchanged') $class = 'badge-exchanged';
    return "<span class=\"badge {$class}\">{$status}</span>";
}

function getListingTypeBadge($type) {
    $type = e($type);
    $class = 'badge-sale';
    if ($type === 'For Exchange') $class = 'badge-exchange';
    if ($type === 'Sale or Exchange') $class = 'badge-both';
    return "<span class=\"badge {$class}\">{$type}</span>";
}

function getConditionBadge($condition) {
    $condition = e($condition);
    $class = 'badge-cond-good';
    if ($condition === 'Like New') $class = 'badge-cond-likenew';
    if ($condition === 'Very Good') $class = 'badge-cond-verygood';
    if ($condition === 'Acceptable') $class = 'badge-cond-acceptable';
    return "<span class=\"badge {$class}\">{$condition}</span>";
}

function getApprovalBadge($approval) {
    $approval = e($approval);
    $class = 'badge-pending';
    if ($approval === 'Approved') $class = 'badge-approved';
    if ($approval === 'Rejected') $class = 'badge-rejected';
    return "<span class=\"badge {$class}\">{$approval}</span>";
}

/**
 * Notification Helper
 */
function addNotification($pdo, $userId, $message, $type, $relatedId = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type, related_id, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        return $stmt->execute([$userId, $message, $type, $relatedId]);
    } catch (PDOException $e) {
        return false;
    }
}

function getUnreadNotificationCount($pdo, $userId) {
    if (!$userId) return 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Message Helper
 */
function getUnreadMessageCount($pdo, $userId) {
    if (!$userId) return 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Wishlist Check
 */
function isBookInWishlist($pdo, $userId, $bookId) {
    if (!$userId || !$bookId) return false;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ? AND book_id = ?");
    $stmt->execute([$userId, $bookId]);
    return ((int)$stmt->fetchColumn()) > 0;
}

/**
 * Block check
 */
function isUserBlocked($pdo, $blockerId, $blockedId) {
    if (!$blockerId || !$blockedId) return false;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
    $stmt->execute([$blockerId, $blockedId]);
    return ((int)$stmt->fetchColumn()) > 0;
}

function isBlockedEitherWay($pdo, $user1, $user2) {
    if (!$user1 || !$user2) return false;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM blocked_users WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)");
    $stmt->execute([$user1, $user2, $user2, $user1]);
    return ((int)$stmt->fetchColumn()) > 0;
}

/**
 * User Rating Statistics
 */
function getUserRatingStats($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as review_count, AVG(rating) as avg_rating FROM reviews WHERE seller_id = ?");
    $stmt->execute([$userId]);
    $res = $stmt->fetch();
    return [
        'count' => (int)($res['review_count'] ?? 0),
        'avg' => round((float)($res['avg_rating'] ?? 0), 1)
    ];
}

/**
 * Secure File Upload Handler
 */
function uploadImageFile($file, $targetDir, $prefix = 'img_') {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid upload parameters.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error code: ' . $file['error']];
    }

    // Max 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Image size exceeds maximum limit of 5MB.'];
    }

    // Check extension
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
    $fileInfo = pathinfo($file['name']);
    $ext = strtolower($fileInfo['extension'] ?? '');

    if (!in_array($ext, $allowedExts)) {
        return ['success' => false, 'error' => 'Only JPG, JPEG, PNG, WEBP and SVG images are allowed.'];
    }

    // Check MIME type
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowedMimes)) {
        return ['success' => false, 'error' => 'Invalid image MIME type: ' . $mime];
    }

    // Generate unique file name
    $newFileName = $prefix . uniqid() . '_' . time() . '.' . $ext;
    $destination = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $newFileName;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $newFileName];
    }

    return ['success' => false, 'error' => 'Failed to move uploaded file to target folder.'];
}
