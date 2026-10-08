<?php
/**
 * BOOKLOOP - Common Header Template
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$pageTitle = isset($pageTitle) ? $pageTitle . " | BookLoop" : "BookLoop - Online Book Exchange & Marketplace";
$user = getAuthUser($pdo);
$unreadNotifCount = isLoggedIn() ? getUnreadNotificationCount($pdo, $_SESSION['user_id']) : 0;
$unreadMsgCount = isLoggedIn() ? getUnreadMessageCount($pdo, $_SESSION['user_id']) : 0;

// Fetch wishlist count
$wishlistCount = 0;
if (isLoggedIn()) {
    $wlStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $wlStmt->execute([$_SESSION['user_id']]);
    $wishlistCount = (int)$wlStmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  
  <!-- Favicon / Brand meta -->
  <meta name="description" content="BookLoop - College-focused online book exchange and marketplace for students to buy, sell, and exchange academic textbooks.">
  
  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>css/responsive.css">

  <script>
    window.BOOKLOOP_BASE_URL = "<?= BASE_URL ?>";
  </script>
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>

<main class="main-content">
  <div class="container">
    <?php displayFlash(); ?>
