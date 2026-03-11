<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Home';

$totalTourists = 0;
$activeTourists = 0;
$nfcScans = 0;
$result = $conn->query("SELECT COUNT(*) AS c FROM tourists");
if ($result) { $totalTourists = $result->fetch_assoc()['c']; }
$result = $conn->query("SELECT COUNT(*) AS c FROM tourists WHERE status='Active'");
if ($result) { $activeTourists = $result->fetch_assoc()['c']; }
$result = $conn->query("SELECT COUNT(*) AS c FROM nfc_logs");
if ($result) { $nfcScans = $result->fetch_assoc()['c']; }

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container hero-content">
        <div class="hero-badge">
            <i class="fas fa-wifi"></i>
            NFC-Powered Technology
        </div>
        <h1>NFC-Enabled E-Tourist<br>ID Card System</h1>
        <p>A modern digital identification system for tourists using NFC smart cards. Fast, secure, and contactless.</p>
        <div class="hero-actions">
            <?php if (isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/dashboard.php" class="btn btn-white btn-lg"><i class="fas fa-tachometer-alt"></i> Go to Dashboard</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php" class="btn btn-white btn-lg"><i class="fas fa-sign-in-alt"></i> Admin Login</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/about.php" class="btn btn-outline btn-lg" style="border-color:#fff;color:#fff;"><i class="fas fa-info-circle"></i> Learn More</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="stats-bar">
            <div class="stat-item">
                <h3><?= number_format($totalTourists) ?></h3>
                <p>Registered Tourists</p>
            </div>
            <div class="stat-item">
                <h3><?= number_format($activeTourists) ?></h3>
                <p>Active ID Cards</p>
            </div>
            <div class="stat-item">
                <h3><?= number_format($nfcScans) ?></h3>
                <p>Total NFC Scans</p>
            </div>
            <div class="stat-item">
                <h3>24/7</h3>
                <p>System Availability</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="padding-top:0;">
    <div class="container">
        <div class="section-header">
            <h2>Key Features</h2>
            <p>Everything you need to manage tourist identification with cutting-edge NFC technology.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-id-card"></i></div>
                <h3>Digital Tourist ID</h3>
                <p>Issue and manage electronic tourist identification cards with comprehensive personal and travel details.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-wifi"></i></div>
                <h3>NFC Smart Cards</h3>
                <p>Write tourist data to NFC cards using ESP32 and PN532 modules for instant contactless identification.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-microchip"></i></div>
                <h3>ESP32 Integration</h3>
                <p>Seamlessly connect to ESP32 microcontrollers via Web Serial API for real-time NFC card operations.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
                <h3>Secure Management</h3>
                <p>Admin-only access ensures that tourist data is managed securely with complete audit trails.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-camera"></i></div>
                <h3>Photo Upload</h3>
                <p>Upload and store tourist photographs for complete identification profiles linked to NFC cards.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                <h3>Activity Logging</h3>
                <p>Track all NFC read/write operations with detailed logs for security and auditing purposes.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
