<?php
/**
 * BOOKLOOP - Administrator Dashboard
 */

$pageTitle = "Admin Overview";
require_once __DIR__ . '/includes/header.php';

// Metrics from Database
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active'")->fetchColumn();
$totalBooks = (int)$pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$availableBooks = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE status = 'Available'")->fetchColumn();
$soldBooks = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE status = 'Sold'")->fetchColumn();
$exchangedBooks = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE status = 'Exchanged'")->fetchColumn();
$pendingListings = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE approval_status = 'Pending'")->fetchColumn();
$pendingRequests = (int)$pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Pending'")->fetchColumn();
$totalReports = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'Pending'")->fetchColumn();

// Recent Users
$recentUsers = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Recent Books (with approval status)
$recentBooks = $pdo->query("
    SELECT b.*, u.name as seller_name 
    FROM books b 
    JOIN users u ON b.user_id = u.user_id 
    ORDER BY b.created_at DESC 
    LIMIT 5
")->fetchAll();

// Recent Requests
$recentReqs = $pdo->query("
    SELECT r.*, b.title as book_title, buyer.name as buyer_name, seller.name as seller_name
    FROM requests r
    JOIN books b ON r.book_id = b.book_id
    JOIN users buyer ON r.buyer_id = buyer.user_id
    JOIN users seller ON r.seller_id = seller.user_id
    ORDER BY r.created_at DESC
    LIMIT 5
")->fetchAll();

// Recent Reports
$recentReports = $pdo->query("
    SELECT rep.*, reporter.name as reporter_name, b.title as book_title, reported.name as reported_name
    FROM reports rep
    JOIN users reporter ON rep.reporter_id = reporter.user_id
    LEFT JOIN books b ON rep.book_id = b.book_id
    LEFT JOIN users reported ON rep.reported_user_id = reported.user_id
    ORDER BY rep.created_at DESC
    LIMIT 5
")->fetchAll();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-tachometer-alt" style="color:var(--primary);"></i> Campus System Overview</h2>
    <p style="color:var(--text-muted); font-size:14px;">Monitor student listings, moderation queues, transaction flow, and reports.</p>
  </div>
  <div style="display:flex; gap:10px;">
    <a href="<?= BASE_URL ?>admin/books.php?approval=Pending" class="btn btn-outline" style="background:white;">
      <i class="fas fa-clock"></i> Review Pending Listings (<?= $pendingListings ?>)
    </a>
  </div>
</div>

<!-- Primary Stats Grid -->
<div class="dashboard-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom:28px;">
  <div class="dashboard-card">
    <div class="dashboard-card-icon icon-blue"><i class="fas fa-user-graduate"></i></div>
    <div class="dashboard-card-info">
      <h3><?= $totalUsers ?></h3>
      <p>Student Accounts</p>
      <div style="font-size:11px; color:var(--success);"><?= $activeUsers ?> active</div>
    </div>
  </div>

  <div class="dashboard-card">
    <div class="dashboard-card-icon icon-green"><i class="fas fa-book"></i></div>
    <div class="dashboard-card-info">
      <h3><?= $totalBooks ?></h3>
      <p>Total Textbooks</p>
      <div style="font-size:11px; color:var(--text-muted);"><?= $availableBooks ?> available</div>
    </div>
  </div>

  <div class="dashboard-card">
    <div class="dashboard-card-icon icon-amber"><i class="fas fa-clock"></i></div>
    <div class="dashboard-card-info">
      <h3><?= $pendingListings ?></h3>
      <p>Pending Approvals</p>
      <div style="font-size:11px; color:var(--warning);"><?= $pendingRequests ?> open requests</div>
    </div>
  </div>

  <div class="dashboard-card">
    <div class="dashboard-card-icon icon-red"><i class="fas fa-flag"></i></div>
    <div class="dashboard-card-info">
      <h3><?= $totalReports ?></h3>
      <p>Pending Reports</p>
      <div style="font-size:11px; color:var(--danger);"><?= $soldBooks + $exchangedBooks ?> total deals closed</div>
    </div>
  </div>
</div>

<!-- Row 1: Recent Books & Recent Users -->
<div style="display:grid; grid-template-columns: 1.3fr 1fr; gap:24px; margin-bottom:28px;">
  <!-- Recent Books -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-book" style="color:var(--primary);"></i> Recent Listings Queue</h3>
      <a href="<?= BASE_URL ?>admin/books.php" class="btn btn-sm btn-outline">All Books</a>
    </div>
    <div class="card-body" style="padding:0;">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Title / Seller</th>
              <th>Price</th>
              <th>Approval</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentBooks as $rb): ?>
              <tr>
                <td>
                  <a href="<?= BASE_URL ?>admin/book-details.php?id=<?= $rb['book_id'] ?>" style="font-weight:700; color:var(--text-main); font-size:13px;">
                    <?= e($rb['title']) ?>
                  </a>
                  <div style="font-size:11px; color:var(--text-muted);"><?= e($rb['seller_name']) ?> &bull; <?= e($rb['department']) ?></div>
                </td>
                <td style="font-size:13px; font-weight:600;"><?= formatPrice($rb['price']) ?></td>
                <td><?= getApprovalBadge($rb['approval_status']) ?></td>
                <td>
                  <a href="<?= BASE_URL ?>admin/book-details.php?id=<?= $rb['book_id'] ?>" class="btn btn-sm btn-outline">
                    Review
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Recent Users -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-users" style="color:var(--secondary);"></i> Newly Registered Students</h3>
      <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-sm btn-outline">All Users</a>
    </div>
    <div class="card-body" style="padding:0;">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Dept</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentUsers as $ru): ?>
              <tr>
                <td>
                  <a href="<?= BASE_URL ?>admin/user-details.php?id=<?= $ru['user_id'] ?>" style="font-weight:600; font-size:13px; color:var(--text-main);">
                    <?= e($ru['name']) ?>
                  </a>
                  <div style="font-size:11px; color:var(--text-muted);"><?= e($ru['email']) ?></div>
                </td>
                <td style="font-size:12px;"><?= e($ru['department']) ?></td>
                <td>
                  <span class="badge" style="background:<?= $ru['status'] === 'active' ? '#ecfdf5' : '#fef2f2' ?>; color:<?= $ru['status'] === 'active' ? '#059669' : '#dc2626' ?>;">
                    <?= ucfirst($ru['status']) ?>
                  </span>
                </td>
                <td>
                  <a href="<?= BASE_URL ?>admin/user-details.php?id=<?= $ru['user_id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Row 2: Recent Requests & Recent Reports -->
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px;">
  <!-- Recent Requests -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-exchange-alt" style="color:var(--info);"></i> Transaction Requests</h3>
      <a href="<?= BASE_URL ?>admin/requests.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="card-body" style="padding:0;">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Type</th>
              <th>Book</th>
              <th>Parties</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentReqs as $rq): ?>
              <tr>
                <td>
                  <span class="badge" style="background:<?= $rq['request_type'] === 'buy' ? '#dbeafe' : '#fce7f3' ?>; color:<?= $rq['request_type'] === 'buy' ? '#1d4ed8' : '#be185d' ?>;">
                    <?= strtoupper($rq['request_type']) ?>
                  </span>
                </td>
                <td style="font-size:13px; font-weight:600;"><?= e($rq['book_title']) ?></td>
                <td style="font-size:11px; color:var(--text-muted);">
                  <?= e($rq['buyer_name']) ?> &rarr; <?= e($rq['seller_name']) ?>
                </td>
                <td><?= getStatusBadge($rq['status']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Recent Reports -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-flag" style="color:var(--danger);"></i> Student Reports</h3>
      <a href="<?= BASE_URL ?>admin/reports.php" class="btn btn-sm btn-outline">All Reports</a>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if (empty($recentReports)): ?>
        <div style="padding:30px; text-align:center; color:var(--text-muted); font-size:13px;">No reports flagged.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Reason</th>
                <th>Target</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentReports as $rep): ?>
                <tr>
                  <td style="font-size:13px; font-weight:600; color:var(--danger);"><?= e($rep['reason']) ?></td>
                  <td style="font-size:12px;"><?= e($rep['book_title'] ?: ($rep['reported_name'] ?: 'User')) ?></td>
                  <td>
                    <span class="badge badge-pending"><?= e($rep['status']) ?></span>
                  </td>
                  <td>
                    <a href="<?= BASE_URL ?>admin/reports.php" class="btn btn-sm btn-outline">Review</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
