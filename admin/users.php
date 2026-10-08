<?php
/**
 * BOOKLOOP - Admin User Management
 */

$pageTitle = "Manage Users";
require_once __DIR__ . '/includes/header.php';

// Handle User Status Actions: Activate, Suspend, Deactivate, Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
        redirect('admin/users.php');
    }

    $action = $_POST['action'] ?? '';
    $targetId = (int)($_POST['user_id'] ?? 0);

    // Prevent action on self!
    if ($targetId == $_SESSION['user_id']) {
        setFlash('error', 'You cannot modify your own administrative account from here.');
        redirect('admin/users.php');
    }

    if ($action === 'activate') {
        $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?")->execute([$targetId]);
        setFlash('success', 'User activated.');
    } elseif ($action === 'suspend') {
        $pdo->prepare("UPDATE users SET status = 'suspended' WHERE user_id = ?")->execute([$targetId]);
        setFlash('warning', 'User account suspended.');
    } elseif ($action === 'deactivate') {
        $pdo->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?")->execute([$targetId]);
        setFlash('info', 'User account deactivated.');
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$targetId]);
        setFlash('success', 'User permanently removed.');
    }
    redirect('admin/users.php');
}

// Search & Filter
$search = clean($_GET['q'] ?? '');
$filterDept = clean($_GET['department'] ?? '');
$filterStatus = clean($_GET['status'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like]);
}

if (!empty($filterDept)) {
    $sql .= " AND department = ?";
    $params[] = $filterDept;
}

if (!empty($filterStatus)) {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$departments = $pdo->query("SELECT category_name FROM categories WHERE category_type = 'Department' AND status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-users" style="color:var(--primary);"></i> Student User Accounts</h2>
    <p style="color:var(--text-muted); font-size:14px;">Review campus student credentials, verify records, or manage suspensions.</p>
  </div>
</div>

<!-- Search & Filters -->
<div class="card" style="padding:18px 24px; margin-bottom:24px;">
  <form action="<?= BASE_URL ?>admin/users.php" method="GET" style="display:flex; gap:14px; flex-wrap:wrap; align-items:center;">
    <div style="flex:1; min-width:220px;">
      <input type="text" name="q" class="form-control" placeholder="Search by name, email or student roll..." value="<?= e($search) ?>">
    </div>

    <div style="width:200px;">
      <select name="department" class="form-control">
        <option value="">All Departments</option>
        <?php foreach ($departments as $dept): ?>
          <option value="<?= e($dept) ?>" <?= ($filterDept === $dept) ? 'selected' : '' ?>><?= e($dept) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="width:160px;">
      <select name="status" class="form-control">
        <option value="">All Statuses</option>
        <option value="active" <?= ($filterStatus === 'active') ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= ($filterStatus === 'inactive') ? 'selected' : '' ?>>Inactive</option>
        <option value="suspended" <?= ($filterStatus === 'suspended') ? 'selected' : '' ?>>Suspended</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">
      <i class="fas fa-filter"></i> Filter
    </button>
    <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-outline">Reset</a>
  </form>
</div>

<!-- Users Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Student Details</th>
          <th>Roll / Student ID</th>
          <th>College / Branch</th>
          <th>Role</th>
          <th>Status</th>
          <th>Joined</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">No users found.</td></tr>
        <?php else: ?>
          <?php foreach ($users as $u): 
            $isSelf = ($u['user_id'] == $_SESSION['user_id']);
          ?>
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:12px;">
                  <img src="<?= BASE_URL ?>uploads/profiles/<?= e($u['profile_image']) ?>" alt="Avatar" class="table-avatar" onerror="this.src='<?= BASE_URL ?>uploads/profiles/default_avatar.svg'">
                  <div>
                    <a href="<?= BASE_URL ?>admin/user-details.php?id=<?= $u['user_id'] ?>" style="font-weight:700; color:var(--text-main); font-size:14px;">
                      <?= e($u['name']) ?>
                    </a>
                    <div style="font-size:11px; color:var(--text-muted);"><?= e($u['email']) ?> &bull; <?= e($u['phone']) ?></div>
                  </div>
                </div>
              </td>

              <td style="font-weight:600; font-size:13px;"><?= e($u['student_id']) ?></td>

              <td style="font-size:12px;">
                <strong><?= e($u['department']) ?></strong> (Sem <?= e($u['semester']) ?>)<br>
                <span style="color:var(--text-muted);"><?= e($u['college']) ?></span>
              </td>

              <td>
                <span class="badge" style="background:<?= $u['role'] === 'admin' ? '#0f172a' : '#e0f2fe' ?>; color:<?= $u['role'] === 'admin' ? 'white' : '#0369a1' ?>;">
                  <?= strtoupper($u['role']) ?>
                </span>
              </td>

              <td>
                <?php
                  $stCol = 'var(--success)';
                  $bgCol = 'var(--success-light)';
                  if ($u['status'] === 'suspended') { $stCol = 'var(--danger)'; $bgCol = 'var(--danger-light)'; }
                  if ($u['status'] === 'inactive') { $stCol = '#64748b'; $bgCol = '#f1f5f9'; }
                ?>
                <span class="badge" style="background:<?= $bgCol ?>; color:<?= $stCol ?>;">
                  <?= ucfirst($u['status']) ?>
                </span>
              </td>

              <td style="font-size:12px; color:var(--text-muted); white-space:nowrap;">
                <?= date('d M Y', strtotime($u['created_at'])) ?>
              </td>

              <td>
                <div style="display:flex; justify-content:flex-end; gap:6px;">
                  <a href="<?= BASE_URL ?>admin/user-details.php?id=<?= $u['user_id'] ?>" class="btn btn-sm btn-outline" title="View Full Details">
                    <i class="fas fa-eye"></i>
                  </a>

                  <?php if (!$isSelf): ?>
                    <?php if ($u['status'] === 'active'): ?>
                      <form action="<?= BASE_URL ?>admin/users.php" method="POST" style="display:inline;" onsubmit="return confirm('Suspend this student account?');">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="suspend">
                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger);" title="Suspend Account">
                          <i class="fas fa-ban"></i>
                        </button>
                      </form>
                    <?php else: ?>
                      <form action="<?= BASE_URL ?>admin/users.php" method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="activate">
                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline" style="color:var(--success);" title="Activate Account">
                          <i class="fas fa-check"></i>
                        </button>
                      </form>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>admin/users.php" method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this user and all their listings?');">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger);" title="Delete User">
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
