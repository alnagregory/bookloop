<?php
/**
 * BOOKLOOP - Notifications Center
 */

$pageTitle = "Notifications";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = getAuthUserId();

// Handle Actions: Mark All as Read, Clear All
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
        redirect('notifications.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'mark_all_read') {
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
        setFlash('success', 'All notifications marked as read.');
    } elseif ($action === 'clear_all') {
        $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$userId]);
        setFlash('info', 'Notifications cleared.');
    }
    redirect('notifications.php');
}

// Fetch all notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

// Mark them as read when viewing this page
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-bell" style="color:var(--primary);"></i> Alerts & Notifications</h2>
    <p style="color:var(--text-muted); font-size:14px;">Stay updated on your purchase requests, book swaps, messages, and admin listing reviews.</p>
  </div>

  <?php if (!empty($notifications)): ?>
    <div style="display:flex; gap:10px;">
      <form action="<?= BASE_URL ?>notifications.php" method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="mark_all_read">
        <button type="submit" class="btn btn-sm btn-outline">
          <i class="fas fa-check-double"></i> Mark All Read
        </button>
      </form>

      <form action="<?= BASE_URL ?>notifications.php" method="POST" onsubmit="return confirm('Clear all notifications?');">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="clear_all">
        <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger);">
          <i class="fas fa-trash-alt"></i> Clear All
        </button>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php if (empty($notifications)): ?>
  <div class="empty-state">
    <div class="empty-state-icon"><i class="far fa-bell"></i></div>
    <h3 class="empty-state-title">No notifications yet</h3>
    <p class="empty-state-desc">You are all caught up! Activity regarding your book listings, offers, and chats will notify you here.</p>
    <a href="<?= BASE_URL ?>books.php" class="btn btn-outline">Explore Books</a>
  </div>
<?php else: ?>
  <div class="card" style="padding:0;">
    <div style="display:flex; flex-direction:column;">
      <?php foreach ($notifications as $n): 
        $icon = 'fa-info-circle';
        $iconColor = 'var(--primary)';
        $targetUrl = BASE_URL . 'dashboard.php';

        if (strpos($n['type'], 'buy') !== false) {
            $icon = 'fa-shopping-cart';
            $iconColor = '#10b981';
            $targetUrl = BASE_URL . 'received-requests.php';
        } elseif (strpos($n['type'], 'exchange') !== false) {
            $icon = 'fa-sync-alt';
            $iconColor = '#7c3aed';
            $targetUrl = BASE_URL . 'received-requests.php';
        } elseif (strpos($n['type'], 'message') !== false) {
            $icon = 'fa-comment';
            $iconColor = '#0284c7';
            $targetUrl = BASE_URL . 'messages.php';
        } elseif (strpos($n['type'], 'accepted') !== false) {
            $icon = 'fa-check-circle';
            $iconColor = '#10b981';
            $targetUrl = BASE_URL . 'my-requests.php';
        } elseif (strpos($n['type'], 'rejected') !== false) {
            $icon = 'fa-times-circle';
            $iconColor = '#ef4444';
            $targetUrl = BASE_URL . 'my-requests.php';
        } elseif (strpos($n['type'], 'deal_completed') !== false) {
            $icon = 'fa-star';
            $iconColor = '#f59e0b';
            $targetUrl = BASE_URL . 'my-reviews.php';
        } elseif (strpos($n['type'], 'approved') !== false) {
            $icon = 'fa-check-circle';
            $iconColor = '#10b981';
            $targetUrl = BASE_URL . 'my-listings.php';
        }
      ?>
        <div style="display:flex; align-items:center; gap:16px; padding:16px 24px; border-bottom:1px solid #f1f5f9; transition:var(--transition); background:<?= $n['is_read'] ? 'white' : '#eff6ff' ?>;">
          <div style="width:38px; height:38px; border-radius:50%; background:var(--bg-alt); display:flex; align-items:center; justify-content:center; color:<?= $iconColor ?>; font-size:16px; flex-shrink:0;">
            <i class="fas <?= $icon ?>"></i>
          </div>

          <div style="flex:1;">
            <div style="font-size:14px; color:var(--text-main); font-weight:<?= $n['is_read'] ? '500' : '700' ?>;">
              <?= e($n['message']) ?>
            </div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">
              <i class="fas fa-clock"></i> <?= timeAgo($n['created_at']) ?>
            </div>
          </div>

          <div>
            <a href="<?= $targetUrl ?>" class="btn btn-sm btn-outline">
              View <i class="fas fa-chevron-right" style="font-size:10px;"></i>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
