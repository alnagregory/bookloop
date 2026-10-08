<?php
/**
 * BOOKLOOP - Reviews & Seller Ratings
 */

$pageTitle = "My Reviews & Ratings";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = getAuthUserId();

$sellerIdToRate = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : 0;
$requestIdToRate = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
$sellerToRate = null;

if ($sellerIdToRate > 0 && $requestIdToRate > 0) {
    // Verify deal was completed and buyer is current user
    $vStmt = $pdo->prepare("
        SELECT r.*, u.name as seller_name, b.title as book_title
        FROM requests r
        JOIN users u ON r.seller_id = u.user_id
        JOIN books b ON r.book_id = b.book_id
        WHERE r.request_id = ? AND r.buyer_id = ? AND r.seller_id = ? AND r.status = 'Completed'
    ");
    $vStmt->execute([$requestIdToRate, $userId, $sellerIdToRate]);
    $sellerToRate = $vStmt->fetch();

    // Check if already reviewed
    if ($sellerToRate) {
        $alreadyReviewed = $pdo->query("SELECT review_id FROM reviews WHERE request_id = {$requestIdToRate} AND reviewer_id = {$userId}")->fetch();
        if ($alreadyReviewed) {
            setFlash('info', 'You have already submitted a review for this transaction.');
            $sellerToRate = null;
        }
    }
}

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
        redirect('my-reviews.php');
    }

    $reqId = (int)($_POST['request_id'] ?? 0);
    $sellerId = (int)($_POST['seller_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 5);
    $reviewText = clean($_POST['review'] ?? '');

    if ($rating < 1 || $rating > 5) {
        setFlash('error', 'Please choose a rating between 1 and 5 stars.');
        redirect("my-reviews.php?seller_id={$sellerId}&request_id={$reqId}");
    }

    // Check existing review
    $checkRev = $pdo->prepare("SELECT review_id FROM reviews WHERE request_id = ? AND reviewer_id = ?");
    $checkRev->execute([$reqId, $userId]);
    if ($checkRev->fetch()) {
        setFlash('warning', 'A review for this transaction has already been posted.');
        redirect('my-reviews.php');
    }

    $insRev = $pdo->prepare("
        INSERT INTO reviews (reviewer_id, seller_id, request_id, rating, review, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $insRev->execute([$userId, $sellerId, $reqId, $rating, $reviewText]);

    // Send notification to seller
    $reviewerName = $_SESSION['user_name'] ?? 'A student';
    addNotification($pdo, $sellerId, "{$reviewerName} gave you a {$rating}-star review for your completed transaction!", 'new_review', $reqId);

    setFlash('success', 'Thank you! Your seller feedback was posted.');
    redirect('my-reviews.php');
}

// Fetch Reviews Received by current user
$receivedStmt = $pdo->prepare("
    SELECT r.*, reviewer.name as reviewer_name, reviewer.profile_image as reviewer_avatar,
           b.title as book_title
    FROM reviews r
    JOIN users reviewer ON r.reviewer_id = reviewer.user_id
    LEFT JOIN requests req ON r.request_id = req.request_id
    LEFT JOIN books b ON req.book_id = b.book_id
    WHERE r.seller_id = ?
    ORDER BY r.created_at DESC
");
$receivedStmt->execute([$userId]);
$receivedReviews = $receivedStmt->fetchAll();

// Fetch overall seller rating for current user
$myStats = getUserRatingStats($pdo, $userId);
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-star" style="color:#f59e0b;"></i> Seller Ratings & Feedback</h2>
    <p style="color:var(--text-muted); font-size:14px;">Reputation and feedback from peers across campus.</p>
  </div>
  <div style="display:flex; align-items:center; gap:10px; background:white; padding:8px 16px; border-radius:var(--radius-full); border:1px solid var(--border-color); box-shadow:var(--shadow-sm);">
    <span style="color:#f59e0b; font-size:18px;"><i class="fas fa-star"></i></span>
    <span style="font-weight:800; font-size:16px;"><?= $myStats['avg'] > 0 ? $myStats['avg'] : '0.0' ?></span>
    <span style="font-size:12px; color:var(--text-muted);">(<?= $myStats['count'] ?> reviews)</span>
  </div>
</div>

<!-- If invited to rate a seller from a completed deal -->
<?php if ($sellerToRate): ?>
  <div class="card" style="padding:24px; border-left:4px solid #f59e0b; margin-bottom:28px;">
    <h3 style="font-size:18px; margin-bottom:6px;"><i class="fas fa-award" style="color:#f59e0b;"></i> Rate Seller: <?= e($sellerToRate['seller_name']) ?></h3>
    <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px;">
      For completed transaction of textbook: <strong><?= e($sellerToRate['book_title']) ?></strong>
    </p>

    <form action="<?= BASE_URL ?>my-reviews.php" method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="request_id" value="<?= $sellerToRate['request_id'] ?>">
      <input type="hidden" name="seller_id" value="<?= $sellerToRate['seller_id'] ?>">

      <div class="form-group">
        <label class="form-label required">Select Rating</label>
        <div class="star-rating" style="justify-content:flex-start;">
          <input type="radio" id="star5" name="rating" value="5" checked><label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
          <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
          <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
          <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
          <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star"><i class="fas fa-star"></i></label>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="review">Written Review (Optional)</label>
        <textarea id="review" name="review" class="form-control" rows="3" placeholder="Share your experience (e.g. Prompt responses, textbook in great condition, punctual meeting)."></textarea>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="fas fa-check"></i> Submit Seller Review
      </button>
    </form>
  </div>
<?php endif; ?>

<!-- Reviews Received List -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-comments" style="color:var(--primary);"></i> Feedback Received from Buyers</h3>
  </div>
  <div class="card-body">
    <?php if (empty($receivedReviews)): ?>
      <div style="text-align:center; padding:30px 20px; color:var(--text-muted);">
        <i class="far fa-star" style="font-size:36px; margin-bottom:12px; display:block; color:var(--text-light);"></i>
        You haven't received any reviews yet. Complete your first textbook sale or swap on campus to earn verified ratings!
      </div>
    <?php else: ?>
      <div style="display:flex; flex-direction:column; gap:16px;">
        <?php foreach ($receivedReviews as $rev): ?>
          <div style="padding:16px; background:var(--bg-alt); border-radius:var(--radius-md); border:1px solid var(--border-color);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
              <div style="display:flex; align-items:center; gap:10px;">
                <img src="<?= BASE_URL ?>uploads/profiles/<?= e($rev['reviewer_avatar']) ?>" alt="Avatar" style="width:36px; height:36px; border-radius:50%; object-fit:cover;" onerror="this.src='<?= BASE_URL ?>uploads/profiles/default_avatar.svg'">
                <div>
                  <strong style="font-size:14px;"><?= e($rev['reviewer_name']) ?></strong>
                  <?php if (!empty($rev['book_title'])): ?>
                    <div style="font-size:11px; color:var(--text-muted);">For: <?= e($rev['book_title']) ?></div>
                  <?php endif; ?>
                </div>
              </div>

              <div style="font-size:12px; color:var(--text-muted);">
                <?= timeAgo($rev['created_at']) ?>
              </div>
            </div>

            <div style="color:#f59e0b; font-size:13px; margin-bottom:6px;">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="<?= ($i <= $rev['rating']) ? 'fas' : 'far' ?> fa-star"></i>
              <?php endfor; ?>
            </div>

            <p style="font-size:13px; color:#334155; margin:0; line-height:1.5;">
              <?= e($rev['review'] ?: 'Left a 5-star rating without written remarks.') ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
