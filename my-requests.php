<?php
/**
 * BOOKLOOP - Sent Requests Management
 */

$pageTitle = "My Sent Requests";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = getAuthUserId();

// Handle Request Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
        redirect('my-requests.php');
    }

    $requestId = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel') {
        // Can only cancel own pending requests
        $stmt = $pdo->prepare("SELECT * FROM requests WHERE request_id = ? AND buyer_id = ?");
        $stmt->execute([$requestId, $userId]);
        $req = $stmt->fetch();

        if ($req && $req['status'] === 'Pending') {
            $up = $pdo->prepare("UPDATE requests SET status = 'Cancelled', updated_at = NOW() WHERE request_id = ?");
            $up->execute([$requestId]);
            setFlash('info', 'Your request has been cancelled.');
        } else {
            setFlash('error', 'Cannot cancel this request.');
        }
    }
    redirect('my-requests.php');
}

// Fetch all requests sent by this student
$stmt = $pdo->prepare("
    SELECT r.*, 
           b.title as book_title, b.image as book_image, b.price as book_price,
           ob.title as offered_book_title, ob.image as offered_book_image,
           seller.name as seller_name, seller.college as seller_college,
           rev.review_id as existing_review_id
    FROM requests r
    JOIN books b ON r.book_id = b.book_id
    LEFT JOIN books ob ON r.offered_book_id = ob.book_id
    JOIN users seller ON r.seller_id = seller.user_id
    LEFT JOIN reviews rev ON (rev.request_id = r.request_id AND rev.reviewer_id = ?)
    WHERE r.buyer_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$userId, $userId]);
$myRequests = $stmt->fetchAll();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-paper-plane" style="color:var(--primary);"></i> My Sent Requests</h2>
    <p style="color:var(--text-muted); font-size:14px;">Track your purchase offers and book exchange proposals sent to fellow students.</p>
  </div>
  <a href="<?= BASE_URL ?>received-requests.php" class="btn btn-outline">
    <i class="fas fa-inbox"></i> View Received Requests
  </a>
</div>

<?php if (empty($myRequests)): ?>
  <div class="empty-state">
    <div class="empty-state-icon"><i class="fas fa-envelope-open-text"></i></div>
    <h3 class="empty-state-title">You don't have any requests yet</h3>
    <p class="empty-state-desc">When you find a textbook you like, send a purchase or exchange request to the seller.</p>
    <a href="<?= BASE_URL ?>books.php" class="btn btn-primary btn-lg">Browse Marketplace</a>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Requested Book</th>
            <th>Seller</th>
            <th>Type</th>
            <th>Your Offer</th>
            <th>Meeting Spot / Note</th>
            <th>Date</th>
            <th>Status</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($myRequests as $r): ?>
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:12px;">
                  <img src="<?= BASE_URL ?>uploads/books/<?= e($r['book_image']) ?>" alt="Book" class="table-thumb" onerror="this.src='<?= BASE_URL ?>uploads/books/default_book.svg'">
                  <div>
                    <a href="<?= BASE_URL ?>book-details.php?id=<?= $r['book_id'] ?>" style="font-weight:700; font-size:14px; color:var(--text-main);">
                      <?= e($r['book_title']) ?>
                    </a>
                    <div style="font-size:11px; color:var(--text-muted);">Listed at <?= formatPrice($r['book_price']) ?></div>
                  </div>
                </div>
              </td>

              <td>
                <div style="font-weight:600; font-size:13px;"><?= e($r['seller_name']) ?></div>
                <div style="font-size:11px; color:var(--text-muted);"><?= e($r['seller_college']) ?></div>
              </td>

              <td>
                <span class="badge" style="background:<?= $r['request_type'] === 'buy' ? '#dbeafe' : '#fce7f3' ?>; color:<?= $r['request_type'] === 'buy' ? '#1d4ed8' : '#be185d' ?>;">
                  <?= strtoupper($r['request_type']) ?>
                </span>
              </td>

              <td>
                <?php if ($r['request_type'] === 'buy'): ?>
                  <span style="font-weight:700; color:var(--primary); font-size:14px;"><?= formatPrice($r['offered_price']) ?></span>
                <?php else: ?>
                  <div style="font-size:13px; font-weight:600; color:#7c3aed;">
                    <i class="fas fa-sync-alt"></i> <?= e($r['offered_book_title'] ?? 'Textbook swap') ?>
                  </div>
                <?php endif; ?>
              </td>

              <td style="font-size:12px; max-width:200px;">
                <div style="font-weight:600;"><i class="fas fa-map-marker-alt" style="color:var(--danger); font-size:10px;"></i> <?= e($r['meeting_place']) ?></div>
                <?php if (!empty($r['delivery_option'])): ?>
                  <div style="margin-top:3px;"><span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:10px; padding:2px 6px;"><i class="fas fa-truck"></i> <?= e($r['delivery_option']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($r['message'])): ?>
                  <div style="color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px;" title="<?= e($r['message']) ?>">
                    "<?= e($r['message']) ?>"
                  </div>
                <?php endif; ?>
              </td>

              <td style="font-size:12px; color:var(--text-muted); white-space:nowrap;">
                <?= date('d M Y', strtotime($r['created_at'])) ?>
              </td>

              <td>
                <?php
                  $sClass = 'badge-pending';
                  if ($r['status'] === 'Accepted') $sClass = 'badge-available';
                  if ($r['status'] === 'Completed') $sClass = 'badge-available';
                  if ($r['status'] === 'Rejected' || $r['status'] === 'Cancelled') $sClass = 'badge-sold';
                ?>
                <span class="badge <?= $sClass ?>"><?= e($r['status']) ?></span>
              </td>

              <td>
                <div style="display:flex; justify-content:flex-end; gap:6px;">
                  <a href="<?= BASE_URL ?>conversation.php?seller_id=<?= $r['seller_id'] ?>&book_id=<?= $r['book_id'] ?>" class="btn btn-sm btn-outline-primary" title="Chat with Seller">
                    <i class="fas fa-comment"></i>
                  </a>

                  <?php if ($r['status'] === 'Pending'): ?>
                    <form action="<?= BASE_URL ?>my-requests.php" method="POST" style="display:inline;" onsubmit="return confirm('Cancel this request?');">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="cancel">
                      <input type="hidden" name="request_id" value="<?= $r['request_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger);" title="Cancel Request">
                        <i class="fas fa-times"></i>
                      </button>
                    </form>
                  <?php endif; ?>

                  <?php if ($r['status'] === 'Completed' && empty($r['existing_review_id'])): ?>
                    <a href="<?= BASE_URL ?>my-reviews.php?seller_id=<?= $r['seller_id'] ?>&request_id=<?= $r['request_id'] ?>" class="btn btn-sm btn-outline" style="color:#f59e0b;" title="Rate Seller">
                      <i class="fas fa-star"></i> Rate
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
