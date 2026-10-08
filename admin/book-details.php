<?php
/**
 * BOOKLOOP - Admin Book Details & Moderation
 */

$pageTitle = "Review Listing";
require_once __DIR__ . '/includes/header.php';

$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($bookId <= 0) {
    setFlash('error', 'Invalid book ID.');
    redirect('admin/books.php');
}

// Fetch Book with Seller
$stmt = $pdo->prepare("
    SELECT b.*, u.name as seller_name, u.email as seller_email, u.phone as seller_phone,
           u.college as seller_college, u.department as seller_department, u.student_id as seller_student_id
    FROM books b
    JOIN users u ON b.user_id = u.user_id
    WHERE b.book_id = ?
");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

if (!$book) {
    setFlash('error', 'Book not found.');
    redirect('admin/books.php');
}

// Handle Admin Actions: Approve, Reject, Change Status, Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
        redirect("admin/book-details.php?id={$bookId}");
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $pdo->prepare("UPDATE books SET approval_status = 'Approved', updated_at = NOW() WHERE book_id = ?")->execute([$bookId]);
        addNotification($pdo, $book['user_id'], "Your listing \"{$book['title']}\" has been approved and is now live!", 'listing_approved', $bookId);
        setFlash('success', 'Listing approved successfully.');
        redirect("admin/book-details.php?id={$bookId}");
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE books SET approval_status = 'Rejected', updated_at = NOW() WHERE book_id = ?")->execute([$bookId]);
        addNotification($pdo, $book['user_id'], "Your listing \"{$book['title']}\" was rejected by administration.", 'listing_rejected', $bookId);
        setFlash('warning', 'Listing rejected.');
        redirect("admin/book-details.php?id={$bookId}");
    } elseif ($action === 'update_status') {
        $newStatus = clean($_POST['status'] ?? 'Available');
        $pdo->prepare("UPDATE books SET status = ?, updated_at = NOW() WHERE book_id = ?")->execute([$newStatus, $bookId]);
        setFlash('success', "Listing availability updated to {$newStatus}.");
        redirect("admin/book-details.php?id={$bookId}");
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM books WHERE book_id = ?")->execute([$bookId]);
        setFlash('success', 'Listing deleted.');
        redirect('admin/books.php');
    }
}

// Fetch Reports for this Book
$repStmt = $pdo->prepare("
    SELECT r.*, u.name as reporter_name 
    FROM reports r 
    JOIN users u ON r.reporter_id = u.user_id 
    WHERE r.book_id = ? 
    ORDER BY r.created_at DESC
");
$repStmt->execute([$bookId]);
$reports = $repStmt->fetchAll();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-book-open" style="color:var(--primary);"></i> Textbook Moderation: <?= e($book['title']) ?></h2>
    <p style="color:var(--text-muted); font-size:14px;">Review student content quality, verify syllabus specs, and enforce policy.</p>
  </div>
  <a href="<?= BASE_URL ?>admin/books.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Books</a>
</div>

<div style="display:grid; grid-template-columns: 320px 1fr; gap:30px; margin-bottom:28px;">
  <!-- Book Cover & Quick Status -->
  <div class="card" style="padding:20px; text-align:center;">
    <img src="<?= BASE_URL ?>uploads/books/<?= e($book['image']) ?>" alt="Cover" style="width:100%; aspect-ratio:1/1.3; object-fit:cover; border-radius:var(--radius-md); margin-bottom:16px;" onerror="this.src='<?= BASE_URL ?>uploads/books/default_book.svg'">
    
    <div style="display:flex; justify-content:center; gap:8px; margin-bottom:16px;">
      <?= getApprovalBadge($book['approval_status']) ?>
      <?= getStatusBadge($book['status']) ?>
    </div>

    <!-- Approval Action Buttons -->
    <div style="display:flex; flex-direction:column; gap:8px;">
      <?php if ($book['approval_status'] !== 'Approved'): ?>
        <form action="<?= BASE_URL ?>admin/book-details.php?id=<?= $bookId ?>" method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="approve">
          <button type="submit" class="btn btn-success btn-block"><i class="fas fa-check"></i> Approve Listing</button>
        </form>
      <?php endif; ?>

      <?php if ($book['approval_status'] !== 'Rejected'): ?>
        <form action="<?= BASE_URL ?>admin/book-details.php?id=<?= $bookId ?>" method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="reject">
          <button type="submit" class="btn btn-outline btn-block" style="color:var(--warning);"><i class="fas fa-times"></i> Reject Listing</button>
        </form>
      <?php endif; ?>

      <form action="<?= BASE_URL ?>admin/book-details.php?id=<?= $bookId ?>" method="POST" onsubmit="return confirm('Permanently remove this book?');">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="delete">
        <button type="submit" class="btn btn-outline btn-block" style="color:var(--danger);"><i class="fas fa-trash-alt"></i> Delete Listing</button>
      </form>
    </div>
  </div>

  <!-- Detailed Specs & Seller Info -->
  <div>
    <div class="card" style="padding:24px; margin-bottom:20px;">
      <h3 style="font-size:20px; margin-bottom:12px;"><?= e($book['title']) ?></h3>
      <table class="book-spec-table">
        <tr><td>Author(s)</td><td><strong><?= e($book['author']) ?></strong></td></tr>
        <tr><td>Subject / Course</td><td><?= e($book['subject']) ?></td></tr>
        <tr><td>Department</td><td><?= e($book['department']) ?> (Semester <?= e($book['semester']) ?>)</td></tr>
        <tr><td>Edition</td><td><?= e($book['edition'] ?: 'Not specified') ?></td></tr>
        <tr><td>Publisher</td><td><?= e($book['publisher'] ?: 'Not specified') ?></td></tr>
        <tr><td>ISBN</td><td><?= e($book['isbn'] ?: 'Not specified') ?></td></tr>
        <tr><td>Price</td><td><strong><?= formatPrice($book['price']) ?></strong> (Original: <?= formatPrice($book['original_price']) ?>)</td></tr>
        <tr><td>Mode & Condition</td><td><?= e($book['listing_type']) ?> &bull; <?= e($book['condition']) ?></td></tr>
        <tr><td>Campus Meeting Spot</td><td><i class="fas fa-map-marker-alt" style="color:var(--danger);"></i> <?= e($book['location']) ?></td></tr>
        <tr><td>Delivery Options</td><td><i class="fas fa-truck" style="color:var(--primary);"></i> <?= e($book['delivery_type'] ?? 'Campus Handover & Hostel Delivery') ?></td></tr>
      </table>

      <h4 style="font-size:14px; margin:16px 0 6px 0;">Seller Description:</h4>
      <div style="background:var(--bg-alt); padding:14px; border-radius:var(--radius-sm); font-size:13px; line-height:1.5;">
        <?= nl2br(e($book['description'])) ?>
      </div>
    </div>

    <!-- Seller Record for Admin -->
    <div class="card" style="padding:20px; background:#f8fafc;">
      <h4 style="font-size:15px; margin-bottom:12px;"><i class="fas fa-user-graduate" style="color:var(--secondary);"></i> Seller Audit Information</h4>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; font-size:13px;">
        <div><strong>Name:</strong> <?= e($book['seller_name']) ?></div>
        <div><strong>Student ID:</strong> <?= e($book['seller_student_id']) ?></div>
        <div><strong>Email:</strong> <?= e($book['seller_email']) ?></div>
        <div><strong>Phone:</strong> <?= e($book['seller_phone']) ?></div>
        <div><strong>College:</strong> <?= e($book['seller_college']) ?></div>
        <div><strong>Department:</strong> <?= e($book['seller_department']) ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Moderation Reports on this book -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-flag" style="color:var(--danger);"></i> Reports on this Listing (<?= count($reports) ?>)</h3>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($reports)): ?>
      <div style="padding:20px; text-align:center; color:var(--text-muted); font-size:13px;">No reports flagged against this listing.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Reporter</th>
              <th>Reason</th>
              <th>Description</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reports as $rp): ?>
              <tr>
                <td><strong><?= e($rp['reporter_name']) ?></strong></td>
                <td style="color:var(--danger); font-weight:600;"><?= e($rp['reason']) ?></td>
                <td style="font-size:12px;"><?= e($rp['description']) ?></td>
                <td style="font-size:12px;"><?= date('d M Y', strtotime($rp['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
