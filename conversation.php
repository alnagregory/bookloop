<?php
/**
 * BOOKLOOP - Private Chat & Conversation View
 */

$pageTitle = "Private Conversation";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = getAuthUserId();
$partnerId = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : (isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0);
$bookId = isset($_GET['book_id']) ? (int)$_GET['book_id'] : null;

if ($partnerId <= 0 || $partnerId == $userId) {
    setFlash('error', 'Invalid conversation partner.');
    redirect('messages.php');
}

// Fetch Partner Information
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$partnerId]);
$partner = $stmt->fetch();

if (!$partner) {
    setFlash('error', 'Student not found.');
    redirect('messages.php');
}

// Fetch Book Information if provided
$book = null;
if ($bookId) {
    $bStmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ?");
    $bStmt->execute([$bookId]);
    $book = $bStmt->fetch();
}

// Check Blocking status
$iBlockedThem = isUserBlocked($pdo, $userId, $partnerId);
$theyBlockedMe = isUserBlocked($pdo, $partnerId, $userId);

// Handle POST actions: Send Message, Block, Unblock, Report
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
        redirect($_SERVER['REQUEST_URI']);
    }

    $action = $_POST['action'] ?? '';

    // Action 1: Send Message
    if ($action === 'send_message') {
        if ($iBlockedThem) {
            setFlash('error', 'You must unblock this student before sending messages.');
        } elseif ($theyBlockedMe) {
            setFlash('error', 'You cannot send messages to this student.');
        } else {
            $msgText = clean($_POST['message'] ?? '');
            if (!empty($msgText)) {
                $insMsg = $pdo->prepare("
                    INSERT INTO messages (sender_id, receiver_id, book_id, message, is_read, created_at)
                    VALUES (?, ?, ?, ?, 0, NOW())
                ");
                $insMsg->execute([$userId, $partnerId, $bookId, $msgText]);

                // Create notification for receiver
                $senderName = $_SESSION['user_name'] ?? 'A student';
                addNotification($pdo, $partnerId, "{$senderName} sent you a message: \"" . substr($msgText, 0, 40) . "...\"", 'new_message', $userId);

                // Redirect to self to prevent form resubmission
                redirect("conversation.php?seller_id={$partnerId}" . ($bookId ? "&book_id={$bookId}" : ""));
            }
        }
    }

    // Action 2: Block User
    elseif ($action === 'block_user') {
        if (!$iBlockedThem) {
            $blkStmt = $pdo->prepare("INSERT INTO blocked_users (blocker_id, blocked_id, created_at) VALUES (?, ?, NOW())");
            $blkStmt->execute([$userId, $partnerId]);
            setFlash('warning', 'You have blocked ' . htmlspecialchars($partner['name']) . '.');
        }
        redirect("conversation.php?seller_id={$partnerId}" . ($bookId ? "&book_id={$bookId}" : ""));
    }

    // Action 3: Unblock User
    elseif ($action === 'unblock_user') {
        $unblk = $pdo->prepare("DELETE FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
        $unblk->execute([$userId, $partnerId]);
        setFlash('success', 'You have unblocked ' . htmlspecialchars($partner['name']) . '.');
        redirect("conversation.php?seller_id={$partnerId}" . ($bookId ? "&book_id={$bookId}" : ""));
    }

    // Action 4: Report User
    elseif ($action === 'report_user') {
        $reason = clean($_POST['reason'] ?? '');
        $description = clean($_POST['description'] ?? '');

        if (!empty($reason)) {
            $rep = $pdo->prepare("
                INSERT INTO reports (reporter_id, book_id, reported_user_id, reason, description, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'Pending', NOW())
            ");
            $rep->execute([$userId, $bookId, $partnerId, $reason, $description]);
            setFlash('success', 'Report submitted to campus moderators.');
        }
        redirect("conversation.php?seller_id={$partnerId}" . ($bookId ? "&book_id={$bookId}" : ""));
    }
}

// Mark unread messages from partner as READ
$markRead = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
$markRead->execute([$userId, $partnerId]);

// Fetch All Messages between these two users
$msgQuery = "
    SELECT * FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) 
       OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
";
$msgStmt = $pdo->prepare($msgQuery);
$msgStmt->execute([$userId, $partnerId, $partnerId, $userId]);
$messages = $msgStmt->fetchAll();

// If book was not passed in GET, try inferring from latest message
if (!$book && !empty($messages)) {
    foreach (array_reverse($messages) as $m) {
        if (!empty($m['book_id'])) {
            $bStmt = $pdo->prepare("SELECT * FROM books WHERE book_id = ?");
            $bStmt->execute([$m['book_id']]);
            $book = $bStmt->fetch();
            if ($book) {
                $bookId = $book['book_id'];
                break;
            }
        }
    }
}

// Fetch all other conversation threads for the left sidebar
$sideThreadsStmt = $pdo->prepare("
    SELECT 
        m.message_id, m.message, m.created_at, m.is_read, m.sender_id, m.receiver_id, m.book_id,
        CASE WHEN m.sender_id = :u1 THEN receiver.user_id ELSE sender.user_id END as other_id,
        CASE WHEN m.sender_id = :u2 THEN receiver.name ELSE sender.name END as other_name,
        CASE WHEN m.sender_id = :u3 THEN receiver.profile_image ELSE sender.profile_image END as other_avatar
    FROM messages m
    JOIN users sender ON m.sender_id = sender.user_id
    JOIN users receiver ON m.receiver_id = receiver.user_id
    WHERE m.message_id IN (
        SELECT MAX(message_id)
        FROM messages
        WHERE sender_id = :u4 OR receiver_id = :u5
        GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)
    )
    ORDER BY m.created_at DESC
");
$sideThreadsStmt->execute([
    'u1' => $userId,
    'u2' => $userId,
    'u3' => $userId,
    'u4' => $userId,
    'u5' => $userId
]);
$sidebarThreads = $sideThreadsStmt->fetchAll();
?>

<div class="chat-container">
  <!-- Left Column: Active Chats Sidebar -->
  <div class="chat-sidebar">
    <div class="chat-sidebar-header">
      <a href="<?= BASE_URL ?>messages.php" style="color:var(--text-main); display:flex; align-items:center; gap:8px;">
        <i class="fas fa-arrow-left"></i> Conversations
      </a>
    </div>

    <div class="conversation-list">
      <?php if (empty($sidebarThreads)): ?>
        <div style="padding:20px; font-size:13px; color:var(--text-muted); text-align:center;">
          No other chats.
        </div>
      <?php else: ?>
        <?php foreach ($sidebarThreads as $st): 
          $isActive = ($st['other_id'] == $partnerId);
        ?>
          <a href="<?= BASE_URL ?>conversation.php?seller_id=<?= $st['other_id'] ?>" 
             class="conversation-item <?= $isActive ? 'active' : '' ?>">
            <img src="<?= BASE_URL ?>uploads/profiles/<?= e($st['other_avatar']) ?>" alt="Avatar" class="conv-avatar" onerror="this.src='<?= BASE_URL ?>uploads/profiles/default_avatar.svg'">
            <div class="conv-info">
              <div class="conv-name"><?= e($st['other_name']) ?></div>
              <div class="conv-preview"><?= e($st['message']) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Column: Current Active Chat -->
  <div class="chat-main">
    <!-- Chat Header -->
    <div class="chat-header">
      <div class="chat-partner-info">
        <img src="<?= BASE_URL ?>uploads/profiles/<?= e($partner['profile_image']) ?>" alt="Avatar" style="width:40px; height:40px; border-radius:50%; object-fit:cover;" onerror="this.src='<?= BASE_URL ?>uploads/profiles/default_avatar.svg'">
        <div>
          <div style="font-weight:700; font-size:15px;">
            <a href="<?= BASE_URL ?>profile.php?id=<?= $partner['user_id'] ?>" style="color:var(--text-main);"><?= e($partner['name']) ?></a>
          </div>
          <div style="font-size:12px; color:var(--text-muted);">
            <?= e($partner['college']) ?> &bull; <?= e($partner['department']) ?>
          </div>
        </div>
      </div>

      <div style="display:flex; align-items:center; gap:8px;">
        <!-- Report Button -->
        <button type="button" class="btn btn-sm btn-outline" style="color:var(--danger);" onclick="openModal('reportUserModal')" title="Report User">
          <i class="fas fa-flag"></i> Report
        </button>

        <!-- Block / Unblock Toggle -->
        <?php if ($iBlockedThem): ?>
          <form action="<?= BASE_URL ?>conversation.php?seller_id=<?= $partnerId ?>" method="POST" style="display:inline;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="unblock_user">
            <button type="submit" class="btn btn-sm btn-outline" title="Unblock Student">
              <i class="fas fa-unlock"></i> Unblock
            </button>
          </form>
        <?php else: ?>
          <form action="<?= BASE_URL ?>conversation.php?seller_id=<?= $partnerId ?>" method="POST" style="display:inline;" onsubmit="return confirm('Block this student? They will not be able to send you messages.');">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="block_user">
            <button type="submit" class="btn btn-sm btn-outline" style="color:var(--text-muted);" title="Block User">
              <i class="fas fa-ban"></i> Block
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Related Book Banner -->
    <?php if ($book): ?>
      <div class="chat-book-banner">
        <div style="display:flex; align-items:center; gap:12px;">
          <img src="<?= BASE_URL ?>uploads/books/<?= e($book['image']) ?>" alt="Book" style="width:36px; height:46px; border-radius:4px; object-fit:cover;" onerror="this.src='<?= BASE_URL ?>uploads/books/default_book.svg'">
          <div>
            <a href="<?= BASE_URL ?>book-details.php?id=<?= $book['book_id'] ?>" style="font-weight:700; color:var(--text-main);">
              <?= e($book['title']) ?>
            </a>
            <div style="font-size:12px; color:var(--text-muted);">
              <?= $book['listing_type'] === 'For Exchange' ? 'For Exchange' : formatPrice($book['price']) ?> &bull; <?= getStatusBadge($book['status']) ?>
            </div>
          </div>
        </div>

        <?php if ($book['user_id'] != $userId && $book['status'] === 'Available'): ?>
          <div>
            <a href="<?= BASE_URL ?>book-details.php?id=<?= $book['book_id'] ?>" class="btn btn-sm btn-primary">
              <i class="fas fa-handshake"></i> Send Formal Request
            </a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Chat Message History -->
    <div class="chat-messages" id="chatMessages">
      <?php if (empty($messages)): ?>
        <div style="text-align:center; margin:auto; color:var(--text-muted); font-size:14px; max-width:360px;">
          <i class="far fa-comments" style="font-size:36px; margin-bottom:10px; display:block; color:var(--text-light);"></i>
          Start your discussion with <strong><?= e($partner['name']) ?></strong> regarding textbook conditions, campus timings, or prices.
        </div>
      <?php else: ?>
        <?php foreach ($messages as $m): 
          $isMine = ($m['sender_id'] == $userId);
        ?>
          <div class="message-bubble <?= $isMine ? 'message-outgoing' : 'message-incoming' ?>">
            <div><?= nl2br(e($m['message'])) ?></div>
            <span class="message-timestamp">
              <?= date('h:i A', strtotime($m['created_at'])) ?>
              <?php if ($isMine): ?>
                <i class="fas fa-check<?= $m['is_read'] ? '-double' : '' ?>" style="margin-left:4px; font-size:9px;"></i>
              <?php endif; ?>
            </span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Chat Input Area -->
    <div class="chat-footer">
      <?php if ($iBlockedThem): ?>
        <div class="alert alert-warning" style="margin:0;">
          <i class="fas fa-ban"></i> You have blocked this user. Unblock them to continue chatting.
        </div>
      <?php elseif ($theyBlockedMe): ?>
        <div class="alert alert-error" style="margin:0;">
          <i class="fas fa-exclamation-circle"></i> You cannot send messages in this conversation.
        </div>
      <?php else: ?>
        <form action="<?= BASE_URL ?>conversation.php?seller_id=<?= $partnerId ?><?= $bookId ? '&book_id=' . $bookId : '' ?>" method="POST" class="chat-input-form">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="send_message">

          <input type="text" name="message" class="form-control" placeholder="Type a message (e.g. Is ₹400 acceptable? When can we meet in the library?)" autocomplete="off" required autofocus>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i> Send
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ==========================================
     REPORT USER MODAL
     ========================================== -->
<div class="modal-overlay" id="reportUserModal">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title" style="color:var(--danger);"><i class="fas fa-flag"></i> Report Student</h3>
      <button type="button" class="modal-close" onclick="closeModal('reportUserModal')">&times;</button>
    </div>
    <form action="<?= BASE_URL ?>conversation.php?seller_id=<?= $partnerId ?>" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="report_user">

      <div class="modal-body">
        <div class="form-group">
          <label class="form-label required" for="rep_user_reason">Reason for Reporting</label>
          <select name="reason" id="rep_user_reason" class="form-control" required>
            <option value="">-- Choose Reason --</option>
            <option value="Offensive / Abusive Language in Chat">Offensive / Abusive Language in Chat</option>
            <option value="Scam Attempt / Fake Deal">Scam Attempt / Fake Deal</option>
            <option value="Spam / Unsolicited Marketing">Spam / Unsolicited Marketing</option>
            <option value="No-show at Arranged Meeting">No-show at Arranged Meeting</option>
            <option value="Other Policy Violation">Other Policy Violation</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="rep_user_desc">Description / Context</label>
          <textarea name="description" id="rep_user_desc" class="form-control" rows="3" placeholder="Explain the incident for admin review."></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('reportUserModal')">Cancel</button>
        <button type="submit" class="btn btn-danger"><i class="fas fa-flag"></i> Submit Report</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
