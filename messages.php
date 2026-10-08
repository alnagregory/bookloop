<?php
/**
 * BOOKLOOP - Messages Inbox Overview
 */

$pageTitle = "Messages & Conversations";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = getAuthUserId();

// Fetch conversation threads grouped by other user and book
// Using distinct pairings
$threadsStmt = $pdo->prepare("
    SELECT 
        m.message_id, m.message, m.created_at, m.is_read, m.sender_id, m.receiver_id, m.book_id,
        CASE WHEN m.sender_id = :u1 THEN receiver.user_id ELSE sender.user_id END as partner_id,
        CASE WHEN m.sender_id = :u2 THEN receiver.name ELSE sender.name END as partner_name,
        CASE WHEN m.sender_id = :u3 THEN receiver.profile_image ELSE sender.profile_image END as partner_avatar,
        CASE WHEN m.sender_id = :u4 THEN receiver.college ELSE sender.college END as partner_college,
        b.title as book_title, b.image as book_image, b.price as book_price
    FROM messages m
    JOIN users sender ON m.sender_id = sender.user_id
    JOIN users receiver ON m.receiver_id = receiver.user_id
    LEFT JOIN books b ON m.book_id = b.book_id
    WHERE m.message_id IN (
        SELECT MAX(message_id)
        FROM messages
        WHERE sender_id = :u5 OR receiver_id = :u6
        GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id), COALESCE(book_id, 0)
    )
    ORDER BY m.created_at DESC
");
$threadsStmt->execute([
    'u1' => $userId,
    'u2' => $userId,
    'u3' => $userId,
    'u4' => $userId,
    'u5' => $userId,
    'u6' => $userId
]);
$threads = $threadsStmt->fetchAll();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-comments" style="color:var(--primary);"></i> Student Messages</h2>
    <p style="color:var(--text-muted); font-size:14px;">Private, safe discussions regarding textbook prices, condition, and campus swaps.</p>
  </div>
  <a href="<?= BASE_URL ?>books.php" class="btn btn-outline">
    <i class="fas fa-search"></i> Browse Textbooks
  </a>
</div>

<?php if (empty($threads)): ?>
  <div class="empty-state">
    <div class="empty-state-icon"><i class="far fa-comments"></i></div>
    <h3 class="empty-state-title">No conversations yet</h3>
    <p class="empty-state-desc">When you click "Contact Seller" on a book listing or someone inquires about your textbooks, your chats appear here.</p>
    <a href="<?= BASE_URL ?>books.php" class="btn btn-primary btn-lg">Find a Book to Discuss</a>
  </div>
<?php else: ?>
  <div class="card" style="padding:0;">
    <div class="conversation-list">
      <?php foreach ($threads as $t): 
        $partnerId = $t['partner_id'];
        $bookId = $t['book_id'];
        $isUnread = ($t['receiver_id'] == $userId && $t['is_read'] == 0);
        $convUrl = BASE_URL . "conversation.php?seller_id=" . $partnerId . ($bookId ? "&book_id=" . $bookId : "");
      ?>
        <a href="<?= $convUrl ?>" class="conversation-item" style="padding:18px 24px; <?= $isUnread ? 'background:#eff6ff;' : '' ?>">
          <img src="<?= BASE_URL ?>uploads/profiles/<?= e($t['partner_avatar']) ?>" alt="Avatar" class="conv-avatar" onerror="this.src='<?= BASE_URL ?>uploads/profiles/default_avatar.svg'">
          
          <div class="conv-info">
            <div class="conv-header">
              <span class="conv-name" style="<?= $isUnread ? 'font-weight:800; color:var(--primary);' : '' ?>">
                <?= e($t['partner_name']) ?>
                <span style="font-size:11px; font-weight:normal; color:var(--text-muted); margin-left:6px;">(<?= e($t['partner_college']) ?>)</span>
              </span>
              <span class="conv-time"><?= timeAgo($t['created_at']) ?></span>
            </div>

            <?php if (!empty($t['book_title'])): ?>
              <div style="margin-bottom:4px;">
                <span class="conv-book-tag">
                  <i class="fas fa-book"></i> <?= e($t['book_title']) ?>
                  <?php if (!empty($t['book_price'])): ?>
                    &bull; <?= formatPrice($t['book_price']) ?>
                  <?php endif; ?>
                </span>
              </div>
            <?php endif; ?>

            <div class="conv-preview <?= $isUnread ? 'unread' : '' ?>">
              <?php if ($t['sender_id'] == $userId): ?>
                <span style="color:var(--text-light);"><i class="fas fa-check"></i> You: </span>
              <?php endif; ?>
              <?= e($t['message']) ?>
            </div>
          </div>

          <?php if ($isUnread): ?>
            <span class="badge" style="background:var(--primary); color:white; border-radius:12px; margin-left:8px; padding:3px 8px; font-size:10px;">
              NEW
            </span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
