<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();
$pageTitle = 'Dashboard';

$stats = ['total' => 0, 'active' => 0, 'expired' => 0, 'scans' => 0];
$r = $conn->query("SELECT COUNT(*) c FROM tourists"); if ($r) $stats['total'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) c FROM tourists WHERE status='Active'"); if ($r) $stats['active'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) c FROM tourists WHERE status='Expired'"); if ($r) $stats['expired'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) c FROM nfc_logs"); if ($r) $stats['scans'] = $r->fetch_assoc()['c'];

$recentTourists = $conn->query("SELECT id, first_name, last_name, nationality, status, created_at FROM tourists ORDER BY created_at DESC LIMIT 5");
$recentLogs = $conn->query("SELECT l.*, t.first_name, t.last_name FROM nfc_logs l LEFT JOIN tourists t ON l.tourist_id = t.id ORDER BY l.created_at DESC LIMIT 5");

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        <span style="color:var(--text-light);">Welcome, <?= sanitize($_SESSION['admin_name']) ?></span>
    </div>

    <div class="dashboard-grid">
        <div class="dash-card">
            <div class="dash-icon teal"><i class="fas fa-users"></i></div>
            <div class="dash-info">
                <h3><?= $stats['total'] ?></h3>
                <p>Total Tourists</p>
            </div>
        </div>
        <div class="dash-card">
            <div class="dash-icon green"><i class="fas fa-id-badge"></i></div>
            <div class="dash-info">
                <h3><?= $stats['active'] ?></h3>
                <p>Active Cards</p>
            </div>
        </div>
        <div class="dash-card">
            <div class="dash-icon amber"><i class="fas fa-clock"></i></div>
            <div class="dash-info">
                <h3><?= $stats['expired'] ?></h3>
                <p>Expired Cards</p>
            </div>
        </div>
        <div class="dash-card">
            <div class="dash-icon blue"><i class="fas fa-wifi"></i></div>
            <div class="dash-info">
                <h3><?= $stats['scans'] ?></h3>
                <p>NFC Scans</p>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem;">
        <div class="card">
            <div class="card-header">
                <h2>Recent Tourists</h2>
                <a href="<?= SITE_URL ?>/tourists.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <?php if ($recentTourists && $recentTourists->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>Name</th><th>Nationality</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $recentTourists->fetch_assoc()): ?>
                        <tr>
                            <td><?= sanitize($row['first_name'] . ' ' . $row['last_name']) ?></td>
                            <td><?= sanitize($row['nationality']) ?></td>
                            <td><span class="badge badge-<?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p style="color:var(--text-light);text-align:center;padding:2rem;">No tourists registered yet.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Recent NFC Activity</h2>
                <a href="<?= SITE_URL ?>/nfc_manager.php" class="btn btn-sm btn-outline">NFC Manager</a>
            </div>
            <?php if ($recentLogs && $recentLogs->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>Action</th><th>Tourist</th><th>Time</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($log = $recentLogs->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge <?= $log['action'] === 'WRITE' ? 'badge-active' : 'badge-expired' ?>"><?= $log['action'] ?></span></td>
                            <td><?= $log['first_name'] ? sanitize($log['first_name'] . ' ' . $log['last_name']) : 'Unknown' ?></td>
                            <td style="font-size:.8rem;color:var(--text-light);"><?= date('M d, H:i', strtotime($log['created_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <p style="color:var(--text-light);text-align:center;padding:2rem;">No NFC activity yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
