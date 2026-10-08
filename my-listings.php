<?php
/**
 * BOOKLOOP - My Listings Management
 */

$pageTitle = "My Book Listings";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = getAuthUserId();

// Handle Quick POST Actions: Delete, Mark Sold, Mark Exchanged
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
        redirect('my-listings.php');
    }

    $action = $_POST['action'] ?? '';
    $bookId = (int)($_POST['book_id'] ?? 0);

    // Verify ownership
    $checkStmt = $pdo->prepare("SELECT user_id, title FROM books WHERE book_id = ?");
    $checkStmt->execute([$bookId]);
    $ownedBook = $checkStmt->fetch();

    if (!$ownedBook || ($ownedBook['user_id'] != $userId && !isAdmin())) {
        setFlash('error', 'Unauthorized action on listing.');
        redirect('my-listings.php');
    }

    if ($action === 'delete') {
        $del = $pdo->prepare("DELETE FROM books WHERE book_id = ?");
        $del->execute([$bookId]);
        setFlash('success', 'Listing "' . $ownedBook['title'] . '" deleted successfully.');
        redirect('my-listings.php');
    } elseif ($action === 'mark_sold') {
        $up = $pdo->prepare("UPDATE books SET status = 'Sold', updated_at = NOW() WHERE book_id = ?");
        $up->execute([$bookId]);
        // Close pending requests
        $pdo->prepare("UPDATE requests SET status = 'Rejected' WHERE book_id = ? AND status = 'Pending'")->execute([$bookId]);
        setFlash('success', 'Book marked as Sold! Pending requests have been resolved.');
        redirect('my-listings.php');
    } elseif ($action === 'mark_exchanged') {
        $up = $pdo->prepare("UPDATE books SET status = 'Exchanged', updated_at = NOW() WHERE book_id = ?");
        $up->execute([$bookId]);
        $pdo->prepare("UPDATE requests SET status = 'Rejected' WHERE book_id = ? AND status = 'Pending'")->execute([$bookId]);
        setFlash('success', 'Book marked as Exchanged!');
        redirect('my-listings.php');
    }
}

// Fetch all listings by current student with request counts
$stmt = $pdo->prepare("
    SELECT b.*, COUNT(r.request_id) as request_count 
    FROM books b
    LEFT JOIN requests r ON b.book_id = r.book_id
    WHERE b.user_id = ?
    GROUP BY b.book_id
    ORDER BY b.created_at DESC
");
$stmt->execute([$userId]);
$myBooks = $stmt->fetchAll();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-book" style="color:var(--primary);"></i> My Textbook Listings</h2>
    <p style="color:var(--text-muted); font-size:14px;">Manage your books, track student buyer inquiries, or update inventory status.</p>
  </div>
  <a href="<?= BASE_URL ?>add-book.php" class="btn btn-primary">
    <i class="fas fa-plus"></i> Add New Listing
  </a>
</div>

<?php if (empty($myBooks)): ?>
  <div class="empty-state">
    <div class="empty-state-icon"><i class="fas fa-book-open"></i></div>
    <h3 class="empty-state-title">No listings found</h3>
    <p class="empty-state-desc">You haven't listed any textbooks for sale or exchange yet. Create your first listing and reach fellow students across campus.</p>
    <a href="<?= BASE_URL ?>add-book.php" class="btn btn-primary btn-lg">List a Textbook Now</a>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Book Details</th>
            <th>Pricing / Mode</th>
            <th>Condition</th>
            <th>Approval</th>
            <th>Status</th>
            <th>Requests</th>
            <th>Listed</th>
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($myBooks as $b): ?>
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:14px;">
                  <img src="<?= BASE_URL ?>uploads/books/<?= e($b['image']) ?>" alt="Book Cover" class="table-thumb" onerror="this.src='<?= BASE_URL ?>uploads/books/default_book.svg'">
                  <div>
                    <a href="<?= BASE_URL ?>book-details.php?id=<?= $b['book_id'] ?>" style="font-weight:700; color:var(--text-main); font-size:14px;">
                      <?= e($b['title']) ?>
                    </a>
                    <div style="font-size:12px; color:var(--text-muted);">
                      By <?= e($b['author']) ?> &bull; <?= e($b['department']) ?> (Sem <?= e($b['semester']) ?>)
                    </div>
                  </div>
                </div>
              </td>

              <td>
                <div><?= getListingTypeBadge($b['listing_type']) ?></div>
                <div style="font-weight:700; color:var(--primary); font-size:14px; margin-top:4px;">
                  <?= $b['listing_type'] === 'For Exchange' ? 'Exchange' : formatPrice($b['price']) ?>
                </div>
              </td>

              <td>
                <?= getConditionBadge($b['condition']) ?>
              </td>

              <td>
                <?= getApprovalBadge($b['approval_status']) ?>
              </td>

              <td>
                <?= getStatusBadge($b['status']) ?>
              </td>

              <td>
                <?php if ($b['request_count'] > 0): ?>
                  <a href="<?= BASE_URL ?>received-requests.php" class="badge" style="background:#dbeafe; color:var(--primary); font-size:12px; text-decoration:none;">
                    <?= $b['request_count'] ?> requests
                  </a>
                <?php else: ?>
                  <span style="color:var(--text-light); font-size:13px;">0</span>
                <?php endif; ?>
              </td>

              <td style="font-size:12px; color:var(--text-muted); white-space:nowrap;">
                <?= date('d M Y', strtotime($b['created_at'])) ?>
              </td>

              <td>
                <div style="display:flex; justify-content:flex-end; gap:6px;">
                  <a href="<?= BASE_URL ?>book-details.php?id=<?= $b['book_id'] ?>" class="btn btn-sm btn-outline" title="View Listing">
                    <i class="fas fa-eye"></i>
                  </a>

                  <a href="<?= BASE_URL ?>edit-book.php?id=<?= $b['book_id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Listing">
                    <i class="fas fa-edit"></i>
                  </a>

                  <?php if ($b['status'] === 'Available'): ?>
                    <!-- Quick Mark as Sold -->
                    <form action="<?= BASE_URL ?>my-listings.php" method="POST" style="display:inline;" onsubmit="return confirm('Mark this book as Sold? Any remaining pending requests will be closed.');">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="mark_sold">
                      <input type="hidden" name="book_id" value="<?= $b['book_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline" style="color:var(--success);" title="Mark Sold">
                        <i class="fas fa-check"></i> Sold
                      </button>
                    </form>

                    <!-- Quick Mark as Exchanged -->
                    <form action="<?= BASE_URL ?>my-listings.php" method="POST" style="display:inline;" onsubmit="return confirm('Mark this book as Exchanged?');">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="mark_exchanged">
                      <input type="hidden" name="book_id" value="<?= $b['book_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline" style="color:#7c3aed;" title="Mark Exchanged">
                        <i class="fas fa-sync"></i> Swap
                      </button>
                    </form>
                  <?php endif; ?>

                  <!-- Delete Button -->
                  <form action="<?= BASE_URL ?>my-listings.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this listing?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="book_id" value="<?= $b['book_id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger);" title="Delete Listing">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </form>
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
