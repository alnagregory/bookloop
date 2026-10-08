<?php
/**
 * BOOKLOOP - User Profile & Seller Rating Card
 */

require_once __DIR__ . '/includes/header.php';

// Target user ID: from query parameter, or current logged-in user
$targetUserId = isset($_GET['id']) ? (int)$_GET['id'] : getAuthUserId();

if (!$targetUserId) {
    setFlash('warning', 'Please login to view your profile.');
    redirect('login.php');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$targetUserId]);
$profileUser = $stmt->fetch();

if (!$profileUser) {
    setFlash('error', 'Student profile not found.');
    redirect('books.php');
}

$authUserId = getAuthUserId();
$isOwnProfile = ($authUserId && $authUserId == $profileUser['user_id']);

// Statistics
$totalListings = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE user_id = {$targetUserId} AND approval_status = 'Approved'")->fetchColumn();
$booksSold = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE user_id = {$targetUserId} AND status = 'Sold'")->fetchColumn();
$booksExchanged = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE user_id = {$targetUserId} AND status = 'Exchanged'")->fetchColumn();

// Ratings
$ratingStats = getUserRatingStats($pdo, $targetUserId);
$revStmt = $pdo->prepare("
    SELECT r.*, reviewer.name as reviewer_name, reviewer.profile_image as reviewer_avatar
    FROM reviews r
    JOIN users reviewer ON r.reviewer_id = reviewer.user_id
    WHERE r.seller_id = ?
    ORDER BY r.created_at DESC
");
$revStmt->execute([$targetUserId]);
$reviews = $revStmt->fetchAll();

// Books listed by this user
$booksStmt = $pdo->prepare("
    SELECT * FROM books 
    WHERE user_id = ? AND approval_status = 'Approved' 
    ORDER BY created_at DESC
");
$booksStmt->execute([$targetUserId]);
$userBooks = $booksStmt->fetchAll();

$pageTitle = $profileUser['name'] . " - Student Profile";
?>

<div style="max-width: 1000px; margin: 0 auto;">
  <!-- Profile Header Card -->
  <div class="card" style="padding:30px; margin-bottom:28px;">
    <div style="display:flex; gap:24px; align-items:center; flex-wrap:wrap;">
      <img src="<?= BASE_URL ?>uploads/profiles/<?= e($profileUser['profile_image']) ?>" alt="<?= e($profileUser['name']) ?>" 
           style="width:110px; height:110px; border-radius:50%; object-fit:cover; border:3px solid var(--primary-light); box-shadow:var(--shadow-md);"
           onerror="this.src='<?= BASE_URL ?>uploads/profiles/default_avatar.svg'">

      <div style="flex:1; min-width:260px;">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:4px;">
          <h1 style="font-size:24px; margin:0;"><?= e($profileUser['name']) ?></h1>
          <span class="badge" style="background:#e0f2fe; color:#0369a1;"><i class="fas fa-check-circle"></i> Verified Student</span>
        </div>

        <div style="font-size:14px; color:var(--text-muted); margin-bottom:10px;">
          <i class="fas fa-university"></i> <?= e($profileUser['college']) ?> &bull; 
          <strong><?= e($profileUser['department']) ?></strong> (Sem <?= e($profileUser['semester']) ?>)
        </div>

        <div style="display:flex; align-items:center; gap:16px; font-size:13px; color:var(--text-main);">
          <span>
            <i class="fas fa-star" style="color:#f59e0b;"></i> 
            <strong><?= $ratingStats['avg'] > 0 ? $ratingStats['avg'] . ' / 5.0' : 'No ratings yet' ?></strong> 
            (<?= $ratingStats['count'] ?> reviews)
          </span>
          <span>&bull;</span>
          <span><i class="fas fa-calendar-alt" style="color:var(--text-light);"></i> Member since <?= date('M Y', strtotime($profileUser['created_at'])) ?></span>
        </div>

        <!-- Privacy check: only show phone & student ID if own profile -->
        <?php if ($isOwnProfile): ?>
          <div style="margin-top:12px; padding:10px 14px; background:var(--bg-alt); border-radius:var(--radius-sm); font-size:13px; display:inline-flex; gap:20px;">
            <span><i class="fas fa-envelope"></i> <?= e($profileUser['email']) ?></span>
            <span><i class="fas fa-phone"></i> <?= e($profileUser['phone']) ?></span>
            <span><i class="fas fa-id-card"></i> ID: <?= e($profileUser['student_id']) ?></span>
          </div>
        <?php endif; ?>
      </div>

      <div>
        <?php if ($isOwnProfile): ?>
          <a href="<?= BASE_URL ?>edit-profile.php" class="btn btn-outline-primary">
            <i class="fas fa-user-edit"></i> Edit Profile
          </a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>conversation.php?seller_id=<?= $profileUser['user_id'] ?>" class="btn btn-primary">
            <i class="fas fa-comments"></i> Send Message
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Profile Metrics Row -->
  <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; margin-bottom:30px;">
    <div class="stat-box" style="padding:20px;">
      <div class="stat-box-number" style="font-size:28px;"><?= $totalListings ?></div>
      <div class="stat-box-label">Textbooks Listed</div>
    </div>
    <div class="stat-box" style="padding:20px;">
      <div class="stat-box-number" style="font-size:28px; color:var(--success);"><?= $booksSold ?></div>
      <div class="stat-box-label">Books Sold</div>
    </div>
    <div class="stat-box" style="padding:20px;">
      <div class="stat-box-number" style="font-size:28px; color:#7c3aed;"><?= $booksExchanged ?></div>
      <div class="stat-box-label">Books Exchanged</div>
    </div>
  </div>

  <!-- Listings by this user -->
  <div class="card" style="margin-bottom:30px;">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-book" style="color:var(--primary);"></i> Listings by <?= e(explode(' ', $profileUser['name'])[0]) ?></h3>
    </div>
    <div class="card-body">
      <?php if (empty($userBooks)): ?>
        <p style="color:var(--text-muted); text-align:center; padding:20px;">No public listings active right now.</p>
      <?php else: ?>
        <div class="book-grid">
          <?php foreach ($userBooks as $b): ?>
            <div class="book-card">
              <div class="book-card-img-wrapper" style="aspect-ratio: 1/1.1;">
                <div class="book-card-badges">
                  <?= getListingTypeBadge($b['listing_type']) ?>
                  <?= getStatusBadge($b['status']) ?>
                </div>
                <img src="<?= BASE_URL ?>uploads/books/<?= e($b['image']) ?>" alt="<?= e($b['title']) ?>" class="book-card-img" onerror="this.src='<?= BASE_URL ?>uploads/books/default_book.svg'">
              </div>
              <div class="book-card-body" style="padding:14px;">
                <h4 class="book-card-title"><a href="<?= BASE_URL ?>book-details.php?id=<?= $b['book_id'] ?>"><?= e($b['title']) ?></a></h4>
                <div style="font-size:13px; color:var(--primary); font-weight:700; margin-top:4px;">
                  <?= $b['listing_type'] === 'For Exchange' ? 'For Exchange' : formatPrice($b['price']) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Seller Reviews -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-star" style="color:#f59e0b;"></i> Feedback & Reviews (<?= count($reviews) ?>)</h3>
    </div>
    <div class="card-body">
      <?php if (empty($reviews)): ?>
        <div style="text-align:center; padding:20px; color:var(--text-muted);">
          No ratings or reviews received yet. Reviews are posted after successful book purchases or exchanges.
        </div>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:16px;">
          <?php foreach ($reviews as $rev): ?>
            <div style="padding:16px; background:var(--bg-alt); border-radius:var(--radius-md); border:1px solid var(--border-color);">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <div style="display:flex; align-items:center; gap:10px;">
                  <img src="<?= BASE_URL ?>uploads/profiles/<?= e($rev['reviewer_avatar']) ?>" alt="Reviewer" style="width:32px; height:32px; border-radius:50%;" onerror="this.src='<?= BASE_URL ?>uploads/profiles/default_avatar.svg'">
                  <strong style="font-size:14px;"><?= e($rev['reviewer_name']) ?></strong>
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
                <?= e($rev['review']) ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
