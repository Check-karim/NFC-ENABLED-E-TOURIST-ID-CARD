<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'About Us';
require_once __DIR__ . '/includes/header.php';
?>

<section class="about-hero">
    <div class="container">
        <h1>About Us</h1>
        <p>Learn more about our NFC-Enabled E-Tourist ID Card system and the technology behind it.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="about-grid">
            <div class="about-content">
                <h2>Revolutionizing Tourist Identification</h2>
                <p>The NFC-Enabled E-Tourist ID Card system is a modern digital solution designed to streamline how tourists are identified and managed at entry points, hotels, and tourist attractions.</p>
                <p>Using Near Field Communication (NFC) technology powered by ESP32 microcontrollers and PN532 NFC modules, we enable instant contactless reading and writing of tourist information on smart cards.</p>
                <p>Our system replaces outdated paper-based identification with a fast, secure, and reliable electronic alternative that can be read in milliseconds.</p>
            </div>
            <div class="about-image">
                <div class="icon-block">
                    <i class="fas fa-id-card-clip"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background:#f0fdfa;">
    <div class="container">
        <div class="section-header">
            <h2>How It Works</h2>
            <p>A simple three-step process from registration to card issuance.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon" style="background:#eff6ff;color:var(--info);">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3>1. Register Tourist</h3>
                <p>Admin enters tourist details including personal information, passport data, visa type, and uploads a photograph into the system.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon" style="background:#fef3c7;color:#d97706;">
                    <i class="fas fa-pen-to-square"></i>
                </div>
                <h3>2. Write to NFC Card</h3>
                <p>Connect to the ESP32 + PN532 NFC module via USB, then write the tourist's data onto an NFC smart card with a single click.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon" style="background:#f0fdf4;color:var(--success);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>3. Verify & Use</h3>
                <p>The NFC card can be tapped at any checkpoint to instantly retrieve the tourist's identification details for verification.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Technology Stack</h2>
            <p>Built with proven, reliable technologies.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-microchip"></i></div>
                <h3>ESP32</h3>
                <p>A powerful, low-cost Wi-Fi and Bluetooth enabled microcontroller that bridges the web application to the NFC hardware.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-wifi"></i></div>
                <h3>PN532 NFC Module (v3)</h3>
                <p>A highly integrated NFC transceiver module for 13.56 MHz contactless communication, capable of reading and writing NFC tags and cards.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-code"></i></div>
                <h3>PHP + MySQL + Web Serial</h3>
                <p>A robust PHP backend with MySQL database, paired with the Web Serial API for direct browser-to-hardware communication.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background:#f8fafc;">
    <div class="container">
        <div class="section-header">
            <h2>Our Team</h2>
            <p>Dedicated professionals committed to modern tourism technology.</p>
        </div>
        <div class="team-grid">
            <div class="team-card">
                <div class="team-avatar">PM</div>
                <h3>Project Manager</h3>
                <p>System Architecture & Planning</p>
            </div>
            <div class="team-card">
                <div class="team-avatar">FD</div>
                <h3>Full-Stack Developer</h3>
                <p>Web Application & API</p>
            </div>
            <div class="team-card">
                <div class="team-avatar">HE</div>
                <h3>Hardware Engineer</h3>
                <p>ESP32 & NFC Integration</p>
            </div>
            <div class="team-card">
                <div class="team-avatar">QA</div>
                <h3>QA Specialist</h3>
                <p>Testing & Quality Assurance</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
