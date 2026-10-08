<?php
/**
 * BOOKLOOP - Admin Requests Monitoring
 */

$pageTitle = "All Requests";
require_once __DIR__ . '/includes/header.php';

$filterType = clean($_GET['type'] ?? '');
$filterStatus = clean($_GET['status'] ?? '');

$sql = "
    SELECT r.*,
           b.title as book_title, b.image as book_image, b.price as book_price,
           ob.title as offered_book_title,
           buyer.name as buyer_name, buyer.college as buyer_college,
           seller.name as seller_name, seller.college as seller_college
    FROM requests r
    JOIN books b ON r.book_id = b.book_id
    LEFT JOIN books ob ON r.offered_book_id = ob.book_id
    JOIN users buyer ON r.buyer_id = buyer.user_id
    JOIN users seller ON r.seller_id = seller.user_id
    WHERE 1=1
";
$params = [];

if (!empty($filterType)) {
    $sql .= " AND r.request_type = ?";
    $params[] = $filterType;
}

if (!empty($filterStatus)) {
    $sql .= " AND r.status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-exchange-alt" style="color:var(--primary);"></i> Platform Transactions & Requests</h2>
    <p style="color:var(--text-muted); font-size:14px;">Monitor textbook purchase proposals and barter exchanges across the campus network.</p>
  </div>
</div>

<!-- Filters -->
<div class="card" style="padding:18px 24px; margin-bottom:24px;">
  <form action="<?= BASE_URL ?>admin/requests.php" method="GET" style="display:flex; gap:14px; align-items:center;">
    <div style="width:200px;">
      <select name="type" class="form-control">
        <option value="">All Request Types</option>
        <option value="buy" <?= ($filterType === 'buy') ? 'selected' : '' ?>>Purchase (Buy)</option>
        <option value="exchange" <?= ($filterType === 'exchange') ? 'selected' : '' ?>>Barter Swap (Exchange)</option>
      </select>
    </div>

    <div style="width:200px;">
      <select name="status" class="form-control">
        <option value="">All Statuses</option>
        <option value="Pending" <?= ($filterStatus === 'Pending') ? 'selected' : '' ?>>Pending</option>
        <option value="Accepted" <?= ($filterStatus === 'Accepted') ? 'selected' : '' ?>>Accepted</option>
        <option value="Completed" <?= ($filterStatus === 'Completed') ? 'selected' : '' ?>>Completed</option>
        <option value="Rejected" <?= ($filterStatus === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
        <option value="Cancelled" <?= ($filterStatus === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
    <a href="<?= BASE_URL ?>admin/requests.php" class="btn btn-outline">Reset</a>
  </form>
</div>

<!-- Requests Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Req ID</th>
          <th>Type</th>
          <th>Requested Book</th>
          <th>Buyer</th>
          <th>Seller</th>
          <th>Offered Terms</th>
          <th>Meeting Spot</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($requests)): ?>
          <tr><td colspan="9" style="text-align:center; padding:30px; color:var(--text-muted);">No requests recorded.</td></tr>
        <?php else: ?>
          <?php foreach ($requests as $r): ?>
            <tr>
              <td style="font-weight:700;">#<?= $r['request_id'] ?></td>
              <td>
                <span class="badge" style="background:<?= $r['request_type'] === 'buy' ? '#dbeafe' : '#fce7f3' ?>; color:<?= $r['request_type'] === 'buy' ? '#1d4ed8' : '#be185d' ?>;">
                  <?= strtoupper($r['request_type']) ?>
                </span>
              </td>
              <td>
                <a href="<?= BASE_URL ?>admin/book-details.php?id=<?= $r['book_id'] ?>" style="font-weight:600; color:var(--text-main); font-size:13px;">
                  <?= e($r['book_title']) ?>
                </a>
              </td>
              <td>
                <div style="font-weight:600; font-size:13px;"><?= e($r['buyer_name']) ?></div>
                <div style="font-size:11px; color:var(--text-muted);"><?= e($r['buyer_college']) ?></div>
              </td>
              <td>
                <div style="font-weight:600; font-size:13px;"><?= e($r['seller_name']) ?></div>
                <div style="font-size:11px; color:var(--text-muted);"><?= e($r['seller_college']) ?></div>
              </td>
              <td>
                <?php if ($r['request_type'] === 'buy'): ?>
                  <span style="font-weight:700; color:var(--primary);"><?= formatPrice($r['offered_price']) ?></span>
                <?php else: ?>
                  <span style="font-weight:600; color:#7c3aed;"><i class="fas fa-sync-alt"></i> <?= e($r['offered_book_title'] ?? 'Book Trade') ?></span>
                <?php endif; ?>
              <td style="font-size:12px;">
                <div style="font-weight:600;"><i class="fas fa-map-marker-alt" style="color:var(--danger); font-size:10px;"></i> <?= e($r['meeting_place']) ?></div>
                <?php if (!empty($r['delivery_option'])): ?>
                  <div style="margin-top:3px;"><span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:10px; padding:2px 6px;"><i class="fas fa-truck"></i> <?= e($r['delivery_option']) ?></span></div>
                <?php endif; ?>
              </td>
              <td style="font-size:12px; color:var(--text-muted); white-space:nowrap;"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
              <td><?= getStatusBadge($r['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
