<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();
$pageTitle = 'Manage Tourists';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM tourists WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        setFlash('success', 'Tourist deleted successfully.');
    } else {
        setFlash('error', 'Failed to delete tourist.');
    }
    $stmt->close();
    redirect(SITE_URL . '/tourists.php');
}

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = "1=1";
$params = [];
$types = '';

if ($search !== '') {
    $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR passport_number LIKE ? OR card_uid LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s, $s]);
    $types .= 'ssss';
}
if ($statusFilter !== '') {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$countStmt = $conn->prepare("SELECT COUNT(*) c FROM tourists WHERE $where");
if ($types) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['c'];
$totalPages = ceil($totalRows / $perPage);
$countStmt->close();

$sql = "SELECT * FROM tourists WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$tourists = $stmt->get_result();
$stmt->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-users"></i> Manage Tourists</h1>
        <a href="<?= SITE_URL ?>/tourist_form.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Tourist</a>
    </div>

    <div class="card" style="margin-bottom:1.5rem;">
        <form method="GET" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:end;">
            <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0;">
                <label>Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, passport, card UID..." value="<?= sanitize($search) ?>">
            </div>
            <div class="form-group" style="min-width:150px;margin-bottom:0;">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Expired" <?= $statusFilter === 'Expired' ? 'selected' : '' ?>>Expired</option>
                    <option value="Revoked" <?= $statusFilter === 'Revoked' ? 'selected' : '' ?>>Revoked</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Filter</button>
            <a href="<?= SITE_URL ?>/tourists.php" class="btn btn-outline btn-sm">Clear</a>
        </form>
    </div>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Passport</th>
                        <th>Nationality</th>
                        <th>Card UID</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($tourists->num_rows === 0): ?>
                    <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-light);">No tourists found.</td></tr>
                <?php endif; ?>
                <?php while ($t = $tourists->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if ($t['photo']): ?>
                                <img src="<?= SITE_URL ?>/uploads/<?= sanitize($t['photo']) ?>" class="photo-preview" style="width:40px;height:40px;border-radius:50%;" alt="Photo">
                            <?php else: ?>
                                <div style="width:40px;height:40px;border-radius:50%;background:var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-light);"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= sanitize($t['first_name'] . ' ' . $t['last_name']) ?></strong></td>
                        <td><?= sanitize($t['passport_number']) ?></td>
                        <td><?= sanitize($t['nationality']) ?></td>
                        <td><code style="font-size:.8rem;"><?= $t['card_uid'] ? sanitize($t['card_uid']) : '—' ?></code></td>
                        <td><span class="badge badge-<?= strtolower($t['status']) ?>"><?= $t['status'] ?></span></td>
                        <td style="font-size:.8rem;color:var(--text-light);"><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                        <td>
                            <div class="actions">
                                <a href="<?= SITE_URL ?>/tourist_form.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="<?= SITE_URL ?>/nfc_manager.php?tourist_id=<?= $t['id'] ?>" class="btn btn-sm btn-accent" title="Write NFC"><i class="fas fa-wifi"></i></a>
                                <a href="<?= SITE_URL ?>/tourists.php?delete=<?= $t['id'] ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this tourist?')"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">&laquo; Prev</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
