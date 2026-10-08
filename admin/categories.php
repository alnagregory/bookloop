<?php
/**
 * BOOKLOOP - Admin Category Management
 */

$pageTitle = "Category Management";
require_once __DIR__ . '/includes/header.php';

// Handle Add / Edit / Delete Category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Session expired. Please try again.');
        redirect('admin/categories.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $catName = clean($_POST['category_name'] ?? '');
        $catType = clean($_POST['category_type'] ?? 'Subject');

        if (!empty($catName)) {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name, category_type, status) VALUES (?, ?, 'active')");
            $stmt->execute([$catName, $catType]);
            setFlash('success', "New {$catType} \"{$catName}\" added.");
        } else {
            setFlash('error', 'Category name cannot be empty.');
        }
    } elseif ($action === 'toggle_status') {
        $catId = (int)($_POST['category_id'] ?? 0);
        $currStatus = clean($_POST['current_status'] ?? 'active');
        $newStatus = ($currStatus === 'active') ? 'inactive' : 'active';

        $pdo->prepare("UPDATE categories SET status = ? WHERE category_id = ?")->execute([$newStatus, $catId]);
        setFlash('info', 'Status updated.');
    } elseif ($action === 'delete') {
        $catId = (int)($_POST['category_id'] ?? 0);
        $pdo->prepare("DELETE FROM categories WHERE category_id = ?")->execute([$catId]);
        setFlash('success', 'Category deleted.');
    }
    redirect('admin/categories.php');
}

// Fetch categories grouped by type
$departments = $pdo->query("SELECT * FROM categories WHERE category_type = 'Department' ORDER BY category_name ASC")->fetchAll();
$subjects = $pdo->query("SELECT * FROM categories WHERE category_type = 'Subject' ORDER BY category_name ASC")->fetchAll();
$semesters = $pdo->query("SELECT * FROM categories WHERE category_type = 'Semester' ORDER BY category_id ASC")->fetchAll();
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h2><i class="fas fa-tags" style="color:var(--primary);"></i> Academic Category Management</h2>
    <p style="color:var(--text-muted); font-size:14px;">Configure departments, subjects, and semesters displayed throughout the marketplace.</p>
  </div>
</div>

<!-- Add Category Form Card -->
<div class="card" style="padding:24px; margin-bottom:28px;">
  <h3 style="font-size:16px; margin-bottom:16px;"><i class="fas fa-plus-circle" style="color:var(--primary);"></i> Add New Category</h3>
  <form action="<?= BASE_URL ?>admin/categories.php" method="POST" style="display:flex; gap:16px; align-items:flex-end; flex-wrap:wrap;">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="add">

    <div style="flex:1; min-width:240px;">
      <label class="form-label required" for="cat_name">Category Name</label>
      <input type="text" id="cat_name" name="category_name" class="form-control" placeholder="e.g. Cloud Computing, Cyber Security, etc." required>
    </div>

    <div style="width:200px;">
      <label class="form-label required" for="cat_type">Category Type</label>
      <select id="cat_type" name="category_type" class="form-control" required>
        <option value="Subject">Subject</option>
        <option value="Department">Department</option>
        <option value="Semester">Semester</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary" style="height:42px;">
      <i class="fas fa-plus"></i> Save Category
    </button>
  </form>
</div>

<!-- Three Column Category Breakdown -->
<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:24px;">
  <!-- Departments -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-university" style="color:var(--primary);"></i> Departments (<?= count($departments) ?>)</h3>
    </div>
    <div class="card-body" style="padding:0;">
      <table class="data-table">
        <tbody>
          <?php foreach ($departments as $d): ?>
            <tr>
              <td>
                <span style="font-weight:600; font-size:13px;"><?= e($d['category_name']) ?></span>
                <span class="badge" style="background:<?= $d['status'] === 'active' ? '#ecfdf5' : '#f1f5f9' ?>; color:<?= $d['status'] === 'active' ? '#059669' : '#64748b' ?>; font-size:10px; margin-left:6px;">
                  <?= $d['status'] ?>
                </span>
              </td>
              <td style="text-align:right;">
                <form action="<?= BASE_URL ?>admin/categories.php" method="POST" style="display:inline;">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="category_id" value="<?= $d['category_id'] ?>">
                  <input type="hidden" name="current_status" value="<?= $d['status'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline" title="Toggle Status"><i class="fas fa-power-off"></i></button>
                </form>
                <form action="<?= BASE_URL ?>admin/categories.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete department?');">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="category_id" value="<?= $d['category_id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger);" title="Delete"><i class="fas fa-trash-alt"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Subjects -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-book-reader" style="color:var(--secondary);"></i> Subjects (<?= count($subjects) ?>)</h3>
    </div>
    <div class="card-body" style="padding:0; max-height:420px; overflow-y:auto;">
      <table class="data-table">
        <tbody>
          <?php foreach ($subjects as $s): ?>
            <tr>
              <td>
                <span style="font-weight:600; font-size:13px;"><?= e($s['category_name']) ?></span>
                <span class="badge" style="background:<?= $s['status'] === 'active' ? '#ecfdf5' : '#f1f5f9' ?>; color:<?= $s['status'] === 'active' ? '#059669' : '#64748b' ?>; font-size:10px; margin-left:6px;">
                  <?= $s['status'] ?>
                </span>
              </td>
              <td style="text-align:right;">
                <form action="<?= BASE_URL ?>admin/categories.php" method="POST" style="display:inline;">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="category_id" value="<?= $s['category_id'] ?>">
                  <input type="hidden" name="current_status" value="<?= $s['status'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline"><i class="fas fa-power-off"></i></button>
                </form>
                <form action="<?= BASE_URL ?>admin/categories.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete subject?');">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="category_id" value="<?= $s['category_id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger);"><i class="fas fa-trash-alt"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Semesters -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-layer-group" style="color:var(--warning);"></i> Semesters (<?= count($semesters) ?>)</h3>
    </div>
    <div class="card-body" style="padding:0;">
      <table class="data-table">
        <tbody>
          <?php foreach ($semesters as $sem): ?>
            <tr>
              <td>
                <span style="font-weight:600; font-size:13px;"><?= e($sem['category_name']) ?></span>
              </td>
              <td style="text-align:right;">
                <span class="badge" style="background:#f0fdf4; color:#15803d; font-size:10px;">active</span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
