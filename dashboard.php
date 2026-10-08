<?php
/**
 * BOOKLOOP - Student Dashboard
 */

$pageTitle = "Student Dashboard";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = getAuthUserId();
$user = getAuthUser($pdo);

// 1. Fetch Metrics for Current Student
$totalListings = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE user_id = {$userId}")->fetchColumn();
$availableBooks = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE user_id = {$userId} AND status = 'Available'")->fetchColumn();
$soldBooks = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE user_id = {$userId} AND status = 'Sold'")->fetchColumn();
$exchangedBooks = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE user_id = {$userId} AND status = 'Exchanged'")->fetchColumn();

// Pending requests received on user's books
$pendingReceived = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE seller_id = {$userId} AND status = 'Pending'")->fetchColumn();
// Pending requests sent by user
$pendingSent = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE buyer_id = {$userId} AND status = 'Pending'")->fetchColumn();

// Unread messages & notifications
$unreadMessages = getUnreadMessageCount($pdo, $userId);
$unreadNotifs = getUnreadNotificationCount($pdo, $userId);

// Wishlist count
$wishlistTotal = (int)$pdo->query("SELECT COUNT(*) FROM wishlist WHERE user_id = {$userId}")->fetchColumn();

// 2. Fetch Recent Listings by this student (last 4)
$recentListingsStmt = $pdo->prepare("
    SELECT * FROM books 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 4
");
$recentListingsStmt->execute([$userId]);
$recentListings = $recentListingsStmt->fetchAll();

// 3. Fetch Recent Received / Sent Requests (last 5)
$recentRequestsStmt = $pdo->prepare("
    SELECT r.*, b.title as book_title, b.image as book_image,
           buyer.name as buyer_name, seller.name as seller_name
    FROM requests r
    JOIN books b ON r.book_id = b.book_id
    JOIN users buyer ON r.buyer_id = buyer.user_id
    JOIN users seller ON r.seller_id = seller.user_id
    WHERE r.buyer_id = ? OR r.seller_id = ?
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$recentRequestsStmt->execute([$userId, $userId]);
$recentRequests = $recentRequestsStmt->fetchAll();

// 4. Fetch Recent Messages / Conversations (last 4)
$recentMsgsStmt = $pdo->prepare("
    SELECT m.*, 
           CASE WHEN m.sender_id = ? THEN receiver.name ELSE sender.name END as contact_name,
           CASE WHEN m.sender_id = ? THEN receiver.profile_image ELSE sender.profile_image END as contact_avatar,
           CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END as other_user_id,
           b.title as book_title
    FROM messages m
    JOIN users sender ON m.sender_id = sender.user_id
    JOIN users receiver ON m.receiver_id = receiver.user_id
    LEFT JOIN books b ON m.book_id = b.book_id
    WHERE m.message_id IN (
        SELECT MAX(message_id)
        FROM messages
        WHERE sender_id = ? OR receiver_id = ?
        GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id), COALESCE(book_id, 0)
    )
    ORDER BY m.created_at DESC
    LIMIT 4
");
$recentMsgsStmt->execute([$userId, $userId, $userId, $userId, $userId]);
$recentConversations = $recentMsgsStmt->fetchAll();
?>

<!-- Dashboard Welcome Banner -->
<div class="card" style="padding: 24px 28px; background: linear-gradient(135deg, #1e293b, #0f172a); color: white; margin-bottom: 28px;">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
    <div>
      <div style="font-size:13px; color:#93c5fd; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Student Dashboard</div>
      <h1 style="color:white; font-size:26px; margin:4px 0 6px 0;">Welcome, <?= e($user['name']) ?>!</h1>
      <p style="color:#cbd5e1; font-size:14px; margin:0;">
        <?= e($user['college']) ?> &bull; <?= e($user['department']) ?> &bull; Semester <?= e($user['semester']) ?> (ID: <?= e($user['student_id']) ?>)
      </p>
    </div>
    <div style="display:flex; gap:10px;">
      <a href="<?= BASE_URL ?>add-book.php" class="btn btn-primary">
        <i class="fas fa-plus-circle"></i> List a Textbook
      </a>
      <a href="<?= BASE_URL ?>books.php" class="btn btn-outline" style="color:white; border-color:rgba(255,255,255,0.3);">
        <i class="fas fa-search"></i> Browse Books
      </a>
    </div>
  </div>
</div>

<!-- Dashboard Metric Cards Grid -->
<div class="dashboard-grid">
  <div class="dashboard-card">
    <div class="dashboard-card-icon icon-blue">
      <i class="fas fa-book"></i>
    </div>
    <div class="dashboard-card-info">
      <h3><?= $totalListings ?></h3>
      <p>Total Listings</p>
      <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">(<?= $availableBooks ?> active)</div>
    </div>
  </div>

  <div class="dashboard-card">
    <div class="dashboard-card-icon icon-green">
      <i class="fas fa-handshake"></i>
    </div>
    <div class="dashboard-card-info">
      <h3><?= ($soldBooks + $exchangedBooks) ?></h3>
      <p>Deals Closed</p>
      <div style="font-size:11px; color:var(--text-muted); margin-top:2px;"><?= $soldBooks ?> sold &bull; <?= $exchangedBooks ?> swapped</div>
    </div>
  </div>

  <div class="dashboard-card">
    <div class="dashboard-card-icon icon-amber">
      <i class="fas fa-inbox"></i>
    </div>
    <div class="dashboard-card-info">
      <h3><?= $pendingReceived ?></h3>
      <p>Pending Requests</p>
      <div style="font-size:11px; color:var(--text-muted); margin-top:2px;"><?= $pendingSent ?> sent pending</div>
    </div>
  </div>

  <div class="dashboard-card">
    <div class="dashboard-card-icon icon-purple">
      <i class="fas fa-heart"></i>
    </div>
    <div class="dashboard-card-info">
      <h3><?= $wishlistTotal ?></h3>
      <p>Saved in Wishlist</p>
      <div style="font-size:11px; color:var(--text-muted); margin-top:2px;"><?= $unreadMessages ?> unread msgs</div>
    </div>
  </div>
</div>

<!-- Two-Column Layout: Recent Listings & Recent Requests -->
<div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:24px; margin-bottom:28px;">
  <!-- Recent Listings -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-book-reader" style="color:var(--primary);"></i> My Recent Listings</h3>
      <a href="<?= BASE_URL ?>my-listings.php" class="btn btn-sm btn-outline">View All (<?= $totalListings ?>)</a>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if (empty($recentListings)): ?>
        <div style="padding:30px 20px; text-align:center; color:var(--text-muted); font-size:14px;">
          You haven't listed any textbooks yet.<br>
          <a href="<?= BASE_URL ?>add-book.php" class="btn btn-sm btn-primary" style="margin-top:10px;">Create First Listing</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Book</th>
                <th>Price</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentListings as $rb): ?>
                <tr>
                  <td>
                    <div style="display:flex; align-items:center; gap:12px;">
                      <img src="<?= BASE_URL ?>uploads/books/<?= e($rb['image']) ?>" alt="Book" class="table-thumb" onerror="this.src='<?= BASE_URL ?>uploads/books/default_book.svg'">
                      <div>
                        <a href="<?= BASE_URL ?>book-details.php?id=<?= $rb['book_id'] ?>" style="font-weight:600; color:var(--text-main); font-size:13px;"><?= e($rb['title']) ?></a>
                        <div style="font-size:11px; color:var(--text-muted);"><?= e($rb['department']) ?> &bull; Sem <?= e($rb['semester']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span style="font-weight:700; color:var(--primary); font-size:13px;">
                      <?= $rb['listing_type'] === 'For Exchange' ? 'Exchange' : formatPrice($rb['price']) ?>
                    </span>
                  </td>
                  <td>
                    <?= getStatusBadge($rb['status']) ?>
                  </td>
                  <td>
                    <a href="<?= BASE_URL ?>edit-book.php?id=<?= $rb['book_id'] ?>" class="btn btn-sm btn-outline" title="Edit">
                      <i class="fas fa-edit"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Requests -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-exchange-alt" style="color:var(--secondary);"></i> Recent Requests</h3>
      <div style="display:flex; gap:6px;">
        <a href="<?= BASE_URL ?>received-requests.php" class="btn btn-sm btn-outline">Received</a>
        <a href="<?= BASE_URL ?>my-requests.php" class="btn btn-sm btn-outline">Sent</a>
      </div>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if (empty($recentRequests)): ?>
        <div style="padding:30px 20px; text-align:center; color:var(--text-muted); font-size:14px;">
          No requests recorded yet.<br>
          <a href="<?= BASE_URL ?>books.php" class="btn btn-sm btn-outline" style="margin-top:10px;">Browse Marketplace</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Type</th>
                <th>Book / Peer</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentRequests as $rq): 
                $isBuyer = ($rq['buyer_id'] == $userId);
                $peerName = $isBuyer ? $rq['seller_name'] : $rq['buyer_name'];
              ?>
                <tr>
                  <td>
                    <span class="badge" style="background:<?= $rq['request_type'] === 'buy' ? '#dbeafe' : '#fce7f3' ?>; color:<?= $rq['request_type'] === 'buy' ? '#1d4ed8' : '#be185d' ?>;">
                      <?= strtoupper($rq['request_type']) ?>
                    </span>
                    <div style="font-size:10px; color:var(--text-muted); margin-top:2px;">
                      <?= $isBuyer ? 'Sent to' : 'From' ?>
                    </div>
                  </td>
                  <td>
                    <div style="font-size:13px; font-weight:600;"><?= e($rq['book_title']) ?></div>
                    <div style="font-size:12px; color:var(--text-muted);"><?= e($peerName) ?></div>
                  </td>
                  <td>
                    <?php
                      $statClass = 'badge-pending';
                      if ($rq['status'] === 'Accepted') $statClass = 'badge-available';
                      if ($rq['status'] === 'Completed') $statClass = 'badge-available';
                      if ($rq['status'] === 'Rejected' || $rq['status'] === 'Cancelled') $statClass = 'badge-sold';
                    ?>
                    <span class="badge <?= $statClass ?>"><?= e($rq['status']) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Recent Messages -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-comments" style="color:var(--info);"></i> Recent Conversations</h3>
    <a href="<?= BASE_URL ?>messages.php" class="btn btn-sm btn-outline">Open Inbox</a>
  </div>
  <div class="card-body">
    <?php if (empty($recentConversations)): ?>
      <div style="text-align:center; padding:20px; color:var(--text-muted); font-size:14px;">
        No conversations yet. When you contact a seller or a student messages you, the chat appears here!
      </div>
    <?php else: ?>
      <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:16px;">
        <?php foreach ($recentConversations as $conv): ?>
          <a href="<?= BASE_URL ?>conversation.php?seller_id=<?= $conv['other_user_id'] ?><?= $conv['book_id'] ? '&book_id=' . $conv['book_id'] : '' ?>" 
             class="card" style="padding:14px; margin:0; text-decoration:none; color:inherit; border:1px solid var(--border-color); display:flex; align-items:center; gap:12px; transition:var(--transition);">
            <img src="<?= BASE_URL ?>uploads/profiles/<?= e($conv['contact_avatar']) ?>" alt="Avatar" style="width:40px; height:40px; border-radius:50%; object-fit:cover;" onerror="this.src='<?= BASE_URL ?>uploads/profiles/default_avatar.svg'">
            <div style="flex:1; min-width:0;">
              <div style="display:flex; justify-content:space-between; align-items:baseline;">
                <strong style="font-size:13px; color:var(--text-main);"><?= e($conv['contact_name']) ?></strong>
                <span style="font-size:10px; color:var(--text-light);"><?= timeAgo($conv['created_at']) ?></span>
              </div>
              <?php if (!empty($conv['book_title'])): ?>
                <div style="font-size:11px; color:var(--primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                  <i class="fas fa-book"></i> <?= e($conv['book_title']) ?>
                </div>
              <?php endif; ?>
              <div style="font-size:12px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                <?= e($conv['message']) ?>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
