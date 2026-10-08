<?php
/**
 * BOOKLOOP - Received Requests Management
 */

$pageTitle = "Received Requests";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = getAuthUserId();

// Handle Actions: Accept, Reject, Complete Deal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
        redirect('received-requests.php');
    }

    $action = $_POST['action'] ?? '';
    $requestId = (int)($_POST['request_id'] ?? 0);

    // Verify current user is the seller
    $reqStmt = $pdo->prepare("
        SELECT r.*, b.title as book_title, buyer.name as buyer_name 
        FROM requests r
        JOIN books b ON r.book_id = b.book_id
        JOIN users buyer ON r.buyer_id = buyer.user_id
        WHERE r.request_id = ? AND r.seller_id = ?
    ");
    $reqStmt->execute([$requestId, $userId]);
    $req = $reqStmt->fetch();

    if (!$req) {
        setFlash('error', 'Request not found or access unauthorized.');
        redirect('received-requests.php');
    }

    if ($action === 'accept') {
        $up = $pdo->prepare("UPDATE requests SET status = 'Accepted', updated_at = NOW() WHERE request_id = ?");
        $up->execute([$requestId]);

        // Send notification to buyer
        addNotification($pdo, $req['buyer_id'], "Great news! Your request for \"{$req['book_title']}\" was ACCEPTED by the seller. Coordinate via chat to complete the deal.", 'request_accepted', $requestId);

        setFlash('success', 'You accepted the request! You can now chat with the buyer to finalize the campus meeting.');
        redirect('received-requests.php');
    } elseif ($action === 'reject') {
        $up = $pdo->prepare("UPDATE requests SET status = 'Rejected', updated_at = NOW() WHERE request_id = ?");
        $up->execute([$requestId]);

        addNotification($pdo, $req['buyer_id'], "Your request for \"{$req['book_title']}\" was declined by the seller.", 'request_rejected', $requestId);

        setFlash('info', 'Request declined.');
        redirect('received-requests.php');
    } elseif ($action === 'complete_deal') {
        // Complete the transaction
        $upReq = $pdo->prepare("UPDATE requests SET status = 'Completed', updated_at = NOW() WHERE request_id = ?");
        $upReq->execute([$requestId]);

        if ($req['request_type'] === 'exchange') {
            // Mark both requested and offered books as Exchanged
            $pdo->prepare("UPDATE books SET status = 'Exchanged', updated_at = NOW() WHERE book_id = ?")->execute([$req['book_id']]);
            if (!empty($req['offered_book_id'])) {
                $pdo->prepare("UPDATE books SET status = 'Exchanged', updated_at = NOW() WHERE book_id = ?")->execute([$req['offered_book_id']]);
            }
            $dealTypeMsg = 'Exchanged';
        } else {
            // Mark book as Sold
            $pdo->prepare("UPDATE books SET status = 'Sold', updated_at = NOW() WHERE book_id = ?")->execute([$req['book_id']]);
            $dealTypeMsg = 'Sold';
        }

        // Auto reject any other pending requests for this book
        $pdo->prepare("UPDATE requests SET status = 'Rejected', updated_at = NOW() WHERE book_id = ? AND status = 'Pending' AND request_id != ?")->execute([$req['book_id'], $requestId]);

        // Notify buyer that deal is completed and invite them to leave a review
        addNotification($pdo, $req['buyer_id'], "Transaction completed! \"{$req['book_title']}\" is marked as {$dealTypeMsg}. Please take a moment to rate the seller.", 'deal_completed', $requestId);

        setFlash('success', "Deal marked as {$dealTypeMsg}! Listing status updated across the platform.");
        redirect('received-requests.php');
    }
}

// Fetch all received requests
$stmt = $pdo->prepare("
    SELECT r.*,
           b.title as book_title, b.image as book_image, b.price as book_price, b.status as book_status,
           ob.title as offered_book_title, ob.image as offered_book_image,
           buyer.name as buyer_name, buyer.college as buyer_college, buyer.department as buyer_department
    FROM requests r
    JOIN books b ON r.book_id = b.book_id
    LEFT JOIN books ob ON r.offered_book_id = ob.book_id
    JOIN users buyer ON r.buyer_id = buyer.user_id
    WHERE r.seller_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$userId]);
$receivedRequests = $stmt->fetchAll();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-inbox" style="color:var(--secondary);"></i> Received Requests</h2>
    <p style="color:var(--text-muted); font-size:14px;">Review student offers, accept deals, and mark transactions as completed.</p>
  </div>
  <a href="<?= BASE_URL ?>my-requests.php" class="btn btn-outline">
    <i class="fas fa-paper-plane"></i> View Sent Requests
  </a>
</div>

<?php if (empty($receivedRequests)): ?>
  <div class="empty-state">
    <div class="empty-state-icon"><i class="fas fa-folder-open"></i></div>
    <h3 class="empty-state-title">No incoming requests yet</h3>
    <p class="empty-state-desc">When students send purchase or swap proposals for your listings, they will show up here.</p>
    <a href="<?= BASE_URL ?>my-listings.php" class="btn btn-outline">Manage My Listings</a>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Student Buyer</th>
            <th>Your Listing</th>
            <th>Type</th>
            <th>Offer / Trade</th>
            <th>Meeting Spot / Note</th>
            <th>Date</th>
            <th>Status</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($receivedRequests as $r): ?>
            <tr>
              <td>
                <div style="font-weight:700; font-size:14px; color:var(--text-main);">
                  <a href="<?= BASE_URL ?>profile.php?id=<?= $r['buyer_id'] ?>"><?= e($r['buyer_name']) ?></a>
                </div>
                <div style="font-size:11px; color:var(--text-muted);">
                  <?= e($r['buyer_college']) ?> &bull; <?= e($r['buyer_department']) ?>
                </div>
              </td>

              <td>
                <div style="display:flex; align-items:center; gap:12px;">
                  <img src="<?= BASE_URL ?>uploads/books/<?= e($r['book_image']) ?>" alt="Book" class="table-thumb" onerror="this.src='<?= BASE_URL ?>uploads/books/default_book.svg'">
                  <div>
                    <a href="<?= BASE_URL ?>book-details.php?id=<?= $r['book_id'] ?>" style="font-weight:600; font-size:13px; color:var(--text-main);">
                      <?= e($r['book_title']) ?>
                    </a>
                    <div style="font-size:11px;"><?= getStatusBadge($r['book_status']) ?></div>
                  </div>
                </div>
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
                  <div style="color:var(--text-muted); font-style:italic; margin-top:2px;" title="<?= e($r['message']) ?>">
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
                  <!-- Direct message link -->
                  <a href="<?= BASE_URL ?>conversation.php?seller_id=<?= $r['buyer_id'] ?>&book_id=<?= $r['book_id'] ?>" class="btn btn-sm btn-outline-primary" title="Message Buyer">
                    <i class="fas fa-comment"></i>
                  </a>

                  <?php if ($r['status'] === 'Pending'): ?>
                    <!-- Accept Button -->
                    <form action="<?= BASE_URL ?>received-requests.php" method="POST" style="display:inline;">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="accept">
                      <input type="hidden" name="request_id" value="<?= $r['request_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-success" title="Accept Request">
                        <i class="fas fa-check"></i> Accept
                      </button>
                    </form>

                    <!-- Reject Button -->
                    <form action="<?= BASE_URL ?>received-requests.php" method="POST" style="display:inline;" onsubmit="return confirm('Decline this request?');">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="reject">
                      <input type="hidden" name="request_id" value="<?= $r['request_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger);" title="Reject Request">
                        <i class="fas fa-times"></i>
                      </button>
                    </form>
                  <?php elseif ($r['status'] === 'Accepted'): ?>
                    <!-- Complete Deal Button -->
                    <form action="<?= BASE_URL ?>received-requests.php" method="POST" style="display:inline;" onsubmit="return confirm('Confirm deal completed? This will mark the textbook as Sold/Exchanged.');">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="complete_deal">
                      <input type="hidden" name="request_id" value="<?= $r['request_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-primary" title="Complete Transaction">
                        <i class="fas fa-check-double"></i> Complete Deal
                      </button>
                    </form>
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
