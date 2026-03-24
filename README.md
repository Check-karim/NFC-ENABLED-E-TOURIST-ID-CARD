# NFC-Enabled E-Tourist ID Card System

A modern PHP web application for managing electronic tourist identification cards using NFC technology. The system integrates with **ESP32** microcontrollers and **PN532 NFC RFID v3** modules to read and write tourist data on contactless smart cards.

---

## Features

- **Home Page** — Public landing page with system overview and live statistics
- **About Us Page** — Information about the system, technology stack, and team
- **Admin Login** — Secure admin-only access (username: `admin`, password: `admin`)
- **Dashboard** — Overview with statistics, recent tourists, and NFC activity logs
- **Tourist Management** — Full CRUD for tourist records with photo upload
- **NFC Card Manager** — Read/write tourist data to NFC cards via ESP32 + PN532 hardware using the Web Serial API

---

## Requirements

- **XAMPP** (PHP 7.4+, Apache, MySQL)
- **Chrome or Edge** browser (Web Serial API support required for NFC features)
- **ESP32** board with **PN532 NFC RFID v3** module (for hardware features)

---

## Installation

### 1. Clone / Copy to XAMPP

Place the project in your XAMPP htdocs folder:

```
C:\xampp\htdocs\nfc\
```

### 2. Create the Database

Open phpMyAdmin at `http://localhost/phpmyadmin` and either:

- **Import** the `database.sql` file, or
- Run the SQL manually:

```sql
-- Open database.sql and execute all statements
```

This creates the `nfc_tourist_db` database with tables `admins`, `tourists`, and `nfc_logs`, plus the default admin account.

### 3. Configure Database Connection

Edit `includes/config.php` if your MySQL credentials differ from the defaults:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nfc_tourist_db');
```

### 4. Start XAMPP

Start **Apache** and **MySQL** from the XAMPP Control Panel.

### 5. Open the App

Navigate to: [http://localhost/nfc/](http://localhost/nfc/)

---

## Default Admin Credentials

| Field    | Value   |
|----------|---------|
| Username | `admin` |
| Password | `admin` |

---

## ESP32 + PN532 Hardware Setup

### Wiring (I2C Mode)

| PN532 Pin | ESP32 Pin |
|-----------|-----------|
| SDA       | GPIO 21   |
| SCL       | GPIO 22   |
| IRQ       | GPIO 2    |
| RSTO      | (not connected) |
| VCC       | 3.3V      |
| GND       | GND       |

> **DIP Switches:** Set the PN532 DIP switches to I2C mode (Switch 1 ON, Switch 2 OFF).

### Buzzer (optional)

| Buzzer Pin | ESP32 Pin |
|------------|-----------|
| (+)        | GPIO 5    |
| (-)        | GND       |

### Arduino Setup

1. Install the **Arduino IDE** and add ESP32 board support
2. Install libraries via Library Manager:
   - **Adafruit PN532** (by Adafruit)
   - **ArduinoJson** (by Benoit Blanchon)
3. Open `esp32_sketch/nfc_tourist_card.ino`
4. Select your ESP32 board and COM port
5. Upload the sketch

### Serial Protocol

The ESP32 communicates via USB serial at **115200 baud**:

| Command | Description | Response |
|---------|-------------|----------|
| `READ\n` | Read NFC card data | `UID:{uid}`, `{json data}`, `DONE` |
| `WRITE:{json}\n` | Write JSON to card | `UID:{uid}`, `DONE` |

Error responses return `ERROR` followed by a description.

---

## Project Structure

```
nfc/
├── .cursor/rules/      # Cursor AI rules
├── api/                # JSON API endpoints
│   ├── nfc_read.php    # Log NFC read operations
│   └── nfc_write.php   # Fetch tourist data & log writes
├── assets/
│   ├── css/style.css   # All styles (CSS custom properties)
│   └── js/app.js       # Navigation, photo preview, Web Serial NFC helper
├── esp32_sketch/       # Arduino sketch for ESP32 + PN532
├── includes/
│   ├── config.php      # DB connection, session, helpers
│   ├── header.php      # HTML head, navbar, flash messages
│   └── footer.php      # Footer, script includes
├── uploads/            # Tourist photo uploads
├── index.php           # Home page
├── about.php           # About us page
├── login.php           # Admin login
├── logout.php          # Session destroy
├── dashboard.php       # Admin dashboard
├── tourists.php        # Tourist list with search/filter/pagination
├── tourist_form.php    # Create/edit tourist form
├── nfc_manager.php     # NFC card read/write interface
├── database.sql        # MySQL schema and seed data
└── README.md           # This file
```

---

## Usage

1. **Login** as admin at `/nfc/login.php`
2. **Add tourists** via the Tourists page — fill in details, upload a photo
3. **Connect ESP32** via USB — go to NFC Manager, click "Connect ESP32"
4. **Write to card** — select a tourist, click "Write Tourist Data to Card", place card on reader
5. **Read a card** — click "Read Card Data", place any written card on reader to view stored info

---

## Browser Compatibility

The NFC Manager uses the **Web Serial API** which is supported in:
- Google Chrome 89+
- Microsoft Edge 89+

Firefox and Safari do not support Web Serial API.

---

## License

This project is for educational and demonstration purposes.
