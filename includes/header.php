<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' : '' ?><?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container nav-container">
            <a href="<?= SITE_URL ?>/" class="nav-brand">
                <i class="fas fa-id-card-clip"></i>
                <span><?= SITE_NAME ?></span>
            </a>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="<?= SITE_URL ?>/" class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>">Home</a></li>
                <li><a href="<?= SITE_URL ?>/about.php" class="nav-link <?= $currentPage === 'about' ? 'active' : '' ?>">About Us</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?= SITE_URL ?>/dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">Dashboard</a></li>
                    <li><a href="<?= SITE_URL ?>/tourists.php" class="nav-link <?= $currentPage === 'tourists' ? 'active' : '' ?>">Tourists</a></li>
                    <li><a href="<?= SITE_URL ?>/nfc_manager.php" class="nav-link <?= $currentPage === 'nfc_manager' ? 'active' : '' ?>">NFC Manager</a></li>
                    <li><a href="<?= SITE_URL ?>/logout.php" class="nav-link btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="<?= SITE_URL ?>/login.php" class="nav-link btn-login <?= $currentPage === 'login' ? 'active' : '' ?>"><i class="fas fa-sign-in-alt"></i> Admin Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="container">
        <div class="alert alert-<?= $flash['type'] ?>">
            <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
            <?= $flash['message'] ?>
        </div>
    </div>
    <?php endif; ?>

    <main>
