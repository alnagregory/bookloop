<?php
/**
 * BOOKLOOP - Admin Moderation & Reports Management
 */

$pageTitle = "Reports & Flags";
require_once __DIR__ . '/includes/header.php';

// Handle Report Status Changes & Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
        redirect('admin/reports.php');
    }

    $action = $_POST['action'] ?? '';
    $reportId = (int)($_POST['report_id'] ?? 0);

    if ($action === 'status_change') {
        $newStatus = clean($_POST['status'] ?? 'Reviewed');
        $pdo->prepare("UPDATE reports SET status = ? WHERE report_id = ?")->execute([$newStatus, $reportId]);
        setFlash('success', "Report marked as {$newStatus}.");
    } elseif ($action === 'suspend_user') {
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);
        if ($targetUserId > 0 && $targetUserId != $_SESSION['user_id']) {
            $pdo->prepare("UPDATE users SET status = 'suspended' WHERE user_id = ?")->execute([$targetUserId]);
            $pdo->prepare("UPDATE reports SET status = 'Resolved' WHERE report_id = ?")->execute([$reportId]);
            setFlash('warning', 'Reported student account was suspended and report resolved.');
        }
    } elseif ($action === 'delete_book') {
        $targetBookId = (int)($_POST['target_book_id'] ?? 0);
        if ($targetBookId > 0) {
            $pdo->prepare("DELETE FROM books WHERE book_id = ?")->execute([$targetBookId]);
            $pdo->prepare("UPDATE reports SET status = 'Resolved' WHERE report_id = ?")->execute([$reportId]);
            setFlash('success', 'Flagged book was permanently removed and report resolved.');
        }
    }
    redirect('admin/reports.php');
}

// Fetch Reports
$filterStatus = clean($_GET['status'] ?? '');
$sql = "
    SELECT r.*,
           reporter.name as reporter_name, reporter.email as reporter_email,
           b.title as book_title, b.book_id as flagged_book_id,
           target_u.name as target_user_name, target_u.user_id as target_user_id, target_u.status as target_user_status
    FROM reports r
    JOIN users reporter ON r.reporter_id = reporter.user_id
    LEFT JOIN books b ON r.book_id = b.book_id
    LEFT JOIN users target_u ON r.reported_user_id = target_u.user_id
    WHERE 1=1
";
$params = [];

if (!empty($filterStatus)) {
    $sql .= " AND r.status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-flag" style="color:var(--danger);"></i> Student Incident Reports</h2>
    <p style="color:var(--text-muted); font-size:14px;">Review flagged books, offensive messages, fake listings, and campus safety grievances.</p>
  </div>
</div>

<!-- Filters -->
<div class="card" style="padding:18px 24px; margin-bottom:24px;">
  <form action="<?= BASE_URL ?>admin/reports.php" method="GET" style="display:flex; gap:14px; align-items:center;">
    <div style="width:220px;">
      <select name="status" class="form-control">
        <option value="">All Statuses</option>
        <option value="Pending" <?= ($filterStatus === 'Pending') ? 'selected' : '' ?>>Pending</option>
        <option value="Reviewed" <?= ($filterStatus === 'Reviewed') ? 'selected' : '' ?>>Reviewed</option>
        <option value="Resolved" <?= ($filterStatus === 'Resolved') ? 'selected' : '' ?>>Resolved</option>
        <option value="Dismissed" <?= ($filterStatus === 'Dismissed') ? 'selected' : '' ?>>Dismissed</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
    <a href="<?= BASE_URL ?>admin/reports.php" class="btn btn-outline">Reset</a>
  </form>
</div>

<!-- Reports Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Reporter</th>
          <th>Report Reason</th>
          <th>Target Book / User</th>
          <th>Incident Details</th>
          <th>Date</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reports)): ?>
          <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">No reports found.</td></tr>
        <?php else: ?>
          <?php foreach ($reports as $rp): ?>
            <tr>
              <td>
                <div style="font-weight:600; font-size:13px;"><?= e($rp['reporter_name']) ?></div>
                <div style="font-size:11px; color:var(--text-muted);"><?= e($rp['reporter_email']) ?></div>
              </td>

              <td style="color:var(--danger); font-weight:700; font-size:13px;">
                <?= e($rp['reason']) ?>
              </td>

              <td style="font-size:12px;">
                <?php if (!empty($rp['book_title'])): ?>
                  <div><i class="fas fa-book"></i> <a href="<?= BASE_URL ?>admin/book-details.php?id=<?= $rp['flagged_book_id'] ?>"><strong><?= e($rp['book_title']) ?></strong></a></div>
                <?php endif; ?>
                <?php if (!empty($rp['target_user_name'])): ?>
                  <div><i class="fas fa-user"></i> <a href="<?= BASE_URL ?>admin/user-details.php?id=<?= $rp['target_user_id'] ?>"><?= e($rp['target_user_name']) ?></a></div>
                <?php endif; ?>
              </td>

              <td style="font-size:12px; max-width:240px; color:#334155;">
                <?= nl2br(e($rp['description'])) ?>
              </td>

              <td style="font-size:12px; color:var(--text-muted); white-space:nowrap;">
                <?= date('d M Y', strtotime($rp['created_at'])) ?>
              </td>

              <td>
                <?php
                  $col = 'var(--warning)';
                  if ($rp['status'] === 'Resolved') $col = 'var(--success)';
                  if ($rp['status'] === 'Dismissed') $col = '#64748b';
                ?>
                <span class="badge" style="background:#f1f5f9; color:<?= $col ?>;"><?= e($rp['status']) ?></span>
              </td>

              <td>
                <div style="display:flex; justify-content:flex-end; gap:6px; flex-wrap:wrap;">
                  <!-- Mark Status Form -->
                  <form action="<?= BASE_URL ?>admin/reports.php" method="POST" style="display:inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="status_change">
                    <input type="hidden" name="report_id" value="<?= $rp['report_id'] ?>">
                    <select name="status" onchange="this.form.submit()" class="form-control" style="width:auto; padding:4px 8px; font-size:12px;">
                      <option value="Pending" <?= ($rp['status'] === 'Pending') ? 'selected' : '' ?>>Pending</option>
                      <option value="Reviewed" <?= ($rp['status'] === 'Reviewed') ? 'selected' : '' ?>>Reviewed</option>
                      <option value="Resolved" <?= ($rp['status'] === 'Resolved') ? 'selected' : '' ?>>Resolved</option>
                      <option value="Dismissed" <?= ($rp['status'] === 'Dismissed') ? 'selected' : '' ?>>Dismissed</option>
                    </select>
                  </form>

                  <!-- Direct Actions if target user is active -->
                  <?php if (!empty($rp['target_user_id']) && $rp['target_user_status'] === 'active'): ?>
                    <form action="<?= BASE_URL ?>admin/reports.php" method="POST" style="display:inline;" onsubmit="return confirm('Suspend reported student?');">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="suspend_user">
                      <input type="hidden" name="report_id" value="<?= $rp['report_id'] ?>">
                      <input type="hidden" name="target_user_id" value="<?= $rp['target_user_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger);" title="Suspend User">
                        <i class="fas fa-ban"></i>
                      </button>
                    </form>
                  <?php endif; ?>

                  <?php if (!empty($rp['flagged_book_id'])): ?>
                    <form action="<?= BASE_URL ?>admin/reports.php" method="POST" style="display:inline;" onsubmit="return confirm('Permanently remove flagged book?');">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="delete_book">
                      <input type="hidden" name="report_id" value="<?= $rp['report_id'] ?>">
                      <input type="hidden" name="target_book_id" value="<?= $rp['flagged_book_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger);" title="Delete Flagged Book">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </form>
                  <?php endif; ?>
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
