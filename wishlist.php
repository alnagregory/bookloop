<?php
/**
 * BOOKLOOP - Saved Wishlist
 */

$pageTitle = "My Wishlist";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = getAuthUserId();

// Handle remove item from wishlist via standard POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
        redirect('wishlist.php');
    }

    $bookId = (int)($_POST['book_id'] ?? 0);
    if ($bookId > 0) {
        $del = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND book_id = ?");
        $del->execute([$userId, $bookId]);
        setFlash('info', 'Book removed from your wishlist.');
    }
    redirect('wishlist.php');
}

// Fetch all wishlist items for this user
$stmt = $pdo->prepare("
    SELECT w.wishlist_id, w.created_at as saved_at,
           b.*, u.name as seller_name, u.college as seller_college
    FROM wishlist w
    JOIN books b ON w.book_id = b.book_id
    JOIN users u ON b.user_id = u.user_id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC
");
$stmt->execute([$userId]);
$wishlistItems = $stmt->fetchAll();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-heart" style="color:var(--danger);"></i> Saved Textbooks</h2>
    <p style="color:var(--text-muted); font-size:14px;">Track textbook listings you are interested in buying or swapping.</p>
  </div>
  <a href="<?= BASE_URL ?>books.php" class="btn btn-outline">
    <i class="fas fa-search"></i> Explore More Books
  </a>
</div>

<?php if (empty($wishlistItems)): ?>
  <div class="empty-state">
    <div class="empty-state-icon" style="color:var(--danger);"><i class="far fa-heart"></i></div>
    <h3 class="empty-state-title">Your wishlist is empty</h3>
    <p class="empty-state-desc">Find a book you like and save it here to keep track of its availability and price changes.</p>
    <a href="<?= BASE_URL ?>books.php" class="btn btn-primary btn-lg">Browse Marketplace</a>
  </div>
<?php else: ?>
  <div class="book-grid">
    <?php foreach ($wishlistItems as $b): ?>
      <div class="book-card">
        <div class="book-card-img-wrapper">
          <div class="book-card-badges">
            <?= getListingTypeBadge($b['listing_type']) ?>
            <?= getStatusBadge($b['status']) ?>
          </div>

          <form action="<?= BASE_URL ?>wishlist.php" method="POST" style="position:absolute; top:10px; right:10px; z-index:2;">
            <?= csrfField() ?>
            <input type="hidden" name="book_id" value="<?= $b['book_id'] ?>">
            <button type="submit" class="wishlist-toggle-btn active" title="Remove from wishlist" style="color:var(--danger);">
              <i class="fas fa-heart"></i>
            </button>
          </form>

          <img src="<?= BASE_URL ?>uploads/books/<?= e($b['image']) ?>" alt="<?= e($b['title']) ?>" class="book-card-img" onerror="this.src='<?= BASE_URL ?>uploads/books/default_book.svg'">
        </div>

        <div class="book-card-body">
          <div class="book-card-meta">
            <span><i class="fas fa-graduation-cap"></i> <?= e($b['department']) ?></span>
            <span>&bull;</span>
            <span>Sem <?= e($b['semester']) ?></span>
          </div>

          <h3 class="book-card-title">
            <a href="<?= BASE_URL ?>book-details.php?id=<?= $b['book_id'] ?>"><?= e($b['title']) ?></a>
          </h3>

          <div class="book-card-author">
            <i class="fas fa-user-edit" style="font-size:11px;"></i> <?= e($b['author']) ?>
          </div>

          <div style="margin-bottom:10px; display:flex; justify-content:space-between; align-items:center;">
            <?= getConditionBadge($b['condition']) ?>
            <span style="font-size:11px; color:var(--text-muted);">Seller: <?= e(explode(' ', $b['seller_name'])[0]) ?></span>
          </div>

          <div class="book-card-details-row">
            <div class="book-price-block">
              <?php if ($b['listing_type'] === 'For Exchange'): ?>
                <span class="book-price" style="color:var(--secondary); font-size:16px;">Exchange</span>
              <?php else: ?>
                <span class="book-price"><?= formatPrice($b['price']) ?></span>
              <?php endif; ?>
            </div>

            <div style="font-size:12px; color:var(--text-muted); text-align:right;">
              <i class="fas fa-map-marker-alt"></i> <?= e(explode(',', $b['location'])[0]) ?>
            </div>
          </div>
        </div>

        <div class="book-card-footer" style="display:flex; gap:8px;">
          <a href="<?= BASE_URL ?>book-details.php?id=<?= $b['book_id'] ?>" class="btn btn-sm btn-outline btn-block">
            <i class="fas fa-eye"></i> View Details
          </a>
          <?php if ($b['status'] === 'Available'): ?>
            <a href="<?= BASE_URL ?>conversation.php?seller_id=<?= $b['user_id'] ?>&book_id=<?= $b['book_id'] ?>" class="btn btn-sm btn-outline-primary" title="Contact Seller">
              <i class="fas fa-comment"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
