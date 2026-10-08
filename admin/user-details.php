<?php
/**
 * BOOKLOOP - Admin User Details & Audit
 */

$pageTitle = "User Details";
require_once __DIR__ . '/includes/header.php';

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($userId <= 0) {
    setFlash('error', 'Invalid student user ID.');
    redirect('admin/users.php');
}

// Fetch User
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'User not found.');
    redirect('admin/users.php');
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
        redirect("admin/user-details.php?id={$userId}");
    }

    $newStatus = clean($_POST['status'] ?? 'active');
    if ($user['user_id'] != $_SESSION['user_id']) {
        $up = $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $up->execute([$newStatus, $userId]);
        setFlash('success', "User status updated to {$newStatus}.");
        redirect("admin/user-details.php?id={$userId}");
    }
}

// Fetch all books listed by this user
$bStmt = $pdo->prepare("SELECT * FROM books WHERE user_id = ? ORDER BY created_at DESC");
$bStmt->execute([$userId]);
$userBooks = $bStmt->fetchAll();

// Fetch reports concerning this user
$repStmt = $pdo->prepare("
    SELECT r.*, reporter.name as reporter_name 
    FROM reports r 
    JOIN users reporter ON r.reporter_id = reporter.user_id 
    WHERE r.reported_user_id = ? 
    ORDER BY r.created_at DESC
");
$repStmt->execute([$userId]);
$userReports = $repStmt->fetchAll();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-id-badge" style="color:var(--primary);"></i> Student Account: <?= e($user['name']) ?></h2>
    <p style="color:var(--text-muted); font-size:14px;">Audit profile credentials, listings, and report history.</p>
  </div>
  <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Users</a>
</div>

<!-- Profile Info Card -->
<div class="card" style="padding:28px; margin-bottom:28px;">
  <div style="display:flex; gap:24px; align-items:center; flex-wrap:wrap;">
    <img src="<?= BASE_URL ?>uploads/profiles/<?= e($user['profile_image']) ?>" alt="Avatar" style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:2px solid var(--border-color);" onerror="this.src='<?= BASE_URL ?>uploads/profiles/default_avatar.svg'">

    <div style="flex:1;">
      <h3 style="margin:0 0 4px 0; font-size:22px;"><?= e($user['name']) ?></h3>
      <div style="font-size:13px; color:var(--text-muted); margin-bottom:10px;">
        Roll / ID: <strong><?= e($user['student_id']) ?></strong> &bull; <?= e($user['college']) ?>
      </div>
      <div style="display:flex; gap:20px; font-size:13px;">
        <span><i class="fas fa-envelope"></i> <?= e($user['email']) ?></span>
        <span><i class="fas fa-phone"></i> <?= e($user['phone']) ?></span>
        <span><i class="fas fa-graduation-cap"></i> <?= e($user['department']) ?> (Sem <?= e($user['semester']) ?>)</span>
      </div>
    </div>

    <!-- Status Change Widget -->
    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
      <div>
        <form action="<?= BASE_URL ?>admin/user-details.php?id=<?= $userId ?>" method="POST" style="display:flex; align-items:center; gap:8px;">
          <?= csrfField() ?>
          <select name="status" class="form-control" style="width:auto;">
            <option value="active" <?= ($user['status'] === 'active') ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($user['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
            <option value="suspended" <?= ($user['status'] === 'suspended') ? 'selected' : '' ?>>Suspended</option>
          </select>
          <button type="submit" class="btn btn-sm btn-primary">Update Status</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Books Listed by this User -->
<div class="card" style="margin-bottom:28px;">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-book" style="color:var(--primary);"></i> Listings Created (<?= count($userBooks) ?>)</h3>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($userBooks)): ?>
      <div style="padding:24px; text-align:center; color:var(--text-muted);">No listings created by this student.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Price</th>
              <th>Condition</th>
              <th>Approval</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($userBooks as $b): ?>
              <tr>
                <td>
                  <strong><?= e($b['title']) ?></strong>
                  <div style="font-size:11px; color:var(--text-muted);"><?= e($b['author']) ?></div>
                </td>
                <td><?= formatPrice($b['price']) ?></td>
                <td><?= getConditionBadge($b['condition']) ?></td>
                <td><?= getApprovalBadge($b['approval_status']) ?></td>
                <td><?= getStatusBadge($b['status']) ?></td>
                <td>
                  <a href="<?= BASE_URL ?>admin/book-details.php?id=<?= $b['book_id'] ?>" class="btn btn-sm btn-outline">Review</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Moderation Reports Concerning this User -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-flag" style="color:var(--danger);"></i> Reports Filed Against This User (<?= count($userReports) ?>)</h3>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if (empty($userReports)): ?>
      <div style="padding:24px; text-align:center; color:var(--text-muted);">Clean record! No reports flagged against this student.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Reporter</th>
              <th>Reason</th>
              <th>Description</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($userReports as $rep): ?>
              <tr>
                <td><strong><?= e($rep['reporter_name']) ?></strong></td>
                <td style="color:var(--danger); font-weight:600;"><?= e($rep['reason']) ?></td>
                <td style="font-size:12px;"><?= e($rep['description']) ?></td>
                <td><span class="badge badge-pending"><?= e($rep['status']) ?></span></td>
                <td style="font-size:12px;"><?= date('d M Y', strtotime($rep['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
