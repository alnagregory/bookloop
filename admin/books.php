<?php
/**
 * BOOKLOOP - Admin Book Management & Approval Queue
 */

$pageTitle = "Manage Book Listings";
require_once __DIR__ . '/includes/header.php';

// Handle POST actions: Approve, Reject, Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
        redirect('admin/books.php');
    }

    $action = $_POST['action'] ?? '';
    $bookId = (int)($_POST['book_id'] ?? 0);

    // Fetch book to get title and seller ID
    if ($action === 'approve_all') {
        $pendingBooks = $pdo->query("SELECT book_id, user_id, title FROM books WHERE approval_status = 'Pending'")->fetchAll();
        if (!empty($pendingBooks)) {
            $pdo->query("UPDATE books SET approval_status = 'Approved', updated_at = NOW() WHERE approval_status = 'Pending'");
            foreach ($pendingBooks as $pb) {
                addNotification($pdo, $pb['user_id'], "Your listing \"{$pb['title']}\" has been approved and is now live on the marketplace!", 'listing_approved', $pb['book_id']);
            }
            setFlash('success', count($pendingBooks) . ' pending book listing(s) approved successfully.');
        } else {
            setFlash('info', 'No pending listings to approve.');
        }
        redirect('admin/books.php');
    }

    $bStmt = $pdo->prepare("SELECT title, user_id FROM books WHERE book_id = ?");
    $bStmt->execute([$bookId]);
    $book = $bStmt->fetch();

    if ($book) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE books SET approval_status = 'Approved', updated_at = NOW() WHERE book_id = ?")->execute([$bookId]);
            addNotification($pdo, $book['user_id'], "Your listing \"{$book['title']}\" has been approved and is now live on the marketplace!", 'listing_approved', $bookId);
            setFlash('success', 'Listing approved.');
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE books SET approval_status = 'Rejected', updated_at = NOW() WHERE book_id = ?")->execute([$bookId]);
            addNotification($pdo, $book['user_id'], "Your listing \"{$book['title']}\" was not approved by campus moderators.", 'listing_rejected', $bookId);
            setFlash('warning', 'Listing rejected.');
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM books WHERE book_id = ?")->execute([$bookId]);
            setFlash('success', 'Listing permanently deleted.');
        }
    }
    redirect('admin/books.php' . (!empty($_GET['approval']) ? '?approval=' . urlencode($_GET['approval']) : ''));
}

// Count total pending listings
$totalPending = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE approval_status = 'Pending'")->fetchColumn();

// Search and Filter
$search = clean($_GET['q'] ?? '');
$filterApproval = clean($_GET['approval'] ?? '');
$filterDept = clean($_GET['department'] ?? '');

$sql = "
    SELECT b.*, u.name as seller_name, u.email as seller_email 
    FROM books b 
    JOIN users u ON b.user_id = u.user_id 
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like]);
}

if (!empty($filterApproval)) {
    $sql .= " AND b.approval_status = ?";
    $params[] = $filterApproval;
}

if (!empty($filterDept)) {
    $sql .= " AND b.department = ?";
    $params[] = $filterDept;
}

$sql .= " ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$departments = $pdo->query("SELECT category_name FROM categories WHERE category_type = 'Department' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-book" style="color:var(--primary);"></i> Textbook Listings & Moderation</h2>
    <p style="color:var(--text-muted); font-size:14px;">Approve new student submissions, inspect textbook condition, and monitor marketplace quality.</p>
  </div>
  <?php if ($totalPending > 0): ?>
    <div>
      <form action="<?= BASE_URL ?>admin/books.php" method="POST" onsubmit="return confirm('Approve all <?= $totalPending ?> pending listing(s)?');">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="approve_all">
        <button type="submit" class="btn btn-success"><i class="fas fa-check-double"></i> Approve All Pending (<?= $totalPending ?>)</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<!-- Filters -->
<div class="card" style="padding:18px 24px; margin-bottom:24px;">
  <form action="<?= BASE_URL ?>admin/books.php" method="GET" style="display:flex; gap:14px; flex-wrap:wrap; align-items:center;">
    <div style="flex:1; min-width:220px;">
      <input type="text" name="q" class="form-control" placeholder="Search by title, author or ISBN..." value="<?= e($search) ?>">
    </div>

    <div style="width:180px;">
      <select name="approval" class="form-control">
        <option value="">All Approvals</option>
        <option value="Pending" <?= ($filterApproval === 'Pending') ? 'selected' : '' ?>>Pending Review</option>
        <option value="Approved" <?= ($filterApproval === 'Approved') ? 'selected' : '' ?>>Approved</option>
        <option value="Rejected" <?= ($filterApproval === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
      </select>
    </div>

    <div style="width:200px;">
      <select name="department" class="form-control">
        <option value="">All Departments</option>
        <?php foreach ($departments as $dept): ?>
          <option value="<?= e($dept) ?>" <?= ($filterDept === $dept) ? 'selected' : '' ?>><?= e($dept) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">
      <i class="fas fa-filter"></i> Filter
    </button>
    <a href="<?= BASE_URL ?>admin/books.php" class="btn btn-outline">Reset</a>
  </form>
</div>

<!-- Listings Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Textbook Info</th>
          <th>Seller</th>
          <th>Type / Price</th>
          <th>Condition</th>
          <th>Approval</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($books)): ?>
          <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">No book listings match this criteria.</td></tr>
        <?php else: ?>
          <?php foreach ($books as $b): ?>
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:12px;">
                  <img src="<?= BASE_URL ?>uploads/books/<?= e($b['image']) ?>" alt="Book" class="table-thumb" onerror="this.src='<?= BASE_URL ?>uploads/books/default_book.svg'">
                  <div>
                    <a href="<?= BASE_URL ?>admin/book-details.php?id=<?= $b['book_id'] ?>" style="font-weight:700; color:var(--text-main); font-size:14px;">
                      <?= e($b['title']) ?>
                    </a>
                    <div style="font-size:11px; color:var(--text-muted);">By <?= e($b['author']) ?> &bull; <?= e($b['department']) ?></div>
                  </div>
                </div>
              </td>

              <td>
                <div style="font-weight:600; font-size:13px;"><?= e($b['seller_name']) ?></div>
                <div style="font-size:11px; color:var(--text-muted);"><?= e($b['seller_email']) ?></div>
              </td>

              <td>
                <div><?= getListingTypeBadge($b['listing_type']) ?></div>
                <div style="font-weight:700; color:var(--primary); font-size:13px; margin-top:2px;">
                  <?= $b['listing_type'] === 'For Exchange' ? 'Exchange' : formatPrice($b['price']) ?>
                </div>
              </td>

              <td><?= getConditionBadge($b['condition']) ?></td>

              <td><?= getApprovalBadge($b['approval_status']) ?></td>

              <td><?= getStatusBadge($b['status']) ?></td>

              <td>
                <div style="display:flex; justify-content:flex-end; gap:6px;">
                  <a href="<?= BASE_URL ?>admin/book-details.php?id=<?= $b['book_id'] ?>" class="btn btn-sm btn-outline" title="View Full Details">
                    <i class="fas fa-eye"></i>
                  </a>

                  <?php if ($b['approval_status'] !== 'Approved'): ?>
                    <form action="<?= BASE_URL ?>admin/books.php" method="POST" style="display:inline;">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="approve">
                      <input type="hidden" name="book_id" value="<?= $b['book_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-success" title="Approve Listing">
                        <i class="fas fa-check"></i>
                      </button>
                    </form>
                  <?php endif; ?>

                  <?php if ($b['approval_status'] !== 'Rejected'): ?>
                    <form action="<?= BASE_URL ?>admin/books.php" method="POST" style="display:inline;">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="reject">
                      <input type="hidden" name="book_id" value="<?= $b['book_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline" style="color:var(--warning);" title="Reject Listing">
                        <i class="fas fa-times"></i>
                      </button>
                    </form>
                  <?php endif; ?>

                  <form action="<?= BASE_URL ?>admin/books.php" method="POST" style="display:inline;" onsubmit="return confirm('Permanently remove this book listing?');">
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
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
