<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();
$pageTitle = 'NFC Manager';

$touristId = (int)($_GET['tourist_id'] ?? 0);
$selectedTourist = null;
if ($touristId > 0) {
    $stmt = $conn->prepare("SELECT * FROM tourists WHERE id = ?");
    $stmt->bind_param('i', $touristId);
    $stmt->execute();
    $selectedTourist = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$tourists = $conn->query("SELECT id, first_name, last_name, passport_number, card_uid FROM tourists WHERE status='Active' ORDER BY first_name");

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-wifi"></i> NFC Card Manager</h1>
        <div style="display:flex;gap:.5rem;">
            <button class="btn btn-primary" id="btnConnect" onclick="connectDevice()">
                <i class="fas fa-plug"></i> Connect ESP32
            </button>
            <button class="btn btn-danger" id="btnDisconnect" onclick="disconnectDevice()" style="display:none;">
                <i class="fas fa-unlink"></i> Disconnect
            </button>
        </div>
    </div>

    <div class="nfc-layout">
        <!-- Left: Card Preview & Status -->
        <div class="card">
            <div class="nfc-visual">
                <div class="nfc-card-preview" id="cardPreview">
                    <div class="nfc-card-top">
                        <div class="logo"><i class="fas fa-id-card-clip"></i> E-TOURIST ID</div>
                        <div class="nfc-chip"></div>
                    </div>
                    <div class="nfc-card-bottom">
                        <h4 id="cardName"><?= $selectedTourist ? sanitize($selectedTourist['first_name'] . ' ' . $selectedTourist['last_name']) : 'No Tourist Selected' ?></h4>
                        <p id="cardDetails"><?= $selectedTourist ? sanitize($selectedTourist['passport_number'] . ' | ' . $selectedTourist['nationality']) : 'Select a tourist to begin' ?></p>
                    </div>
                </div>

                <div class="nfc-status waiting" id="nfcStatus">
                    <i class="fas fa-circle-info"></i>
                    Waiting for connection...
                </div>
            </div>

            <div class="serial-log" id="serialLog">
                <div class="log-entry log-info">[System] NFC Manager ready. Connect your ESP32 + PN532 device via USB.</div>
            </div>
        </div>

        <!-- Right: Controls -->
        <div>
            <!-- Tourist Selection -->
            <div class="card" style="margin-bottom:1.25rem;">
                <h3 style="margin-bottom:1rem;font-size:1rem;"><i class="fas fa-user"></i> Select Tourist</h3>
                <div class="form-group" style="margin-bottom:.75rem;">
                    <select id="touristSelect" class="form-control" onchange="onTouristSelect(this.value)">
                        <option value="">-- Choose a tourist --</option>
                        <?php while ($row = $tourists->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>"
                                    data-name="<?= sanitize($row['first_name'] . ' ' . $row['last_name']) ?>"
                                    data-passport="<?= sanitize($row['passport_number']) ?>"
                                    data-uid="<?= sanitize($row['card_uid'] ?? '') ?>"
                                    <?= $touristId === (int)$row['id'] ? 'selected' : '' ?>>
                                <?= sanitize($row['first_name'] . ' ' . $row['last_name']) ?> — <?= sanitize($row['passport_number']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php if ($selectedTourist && $selectedTourist['card_uid']): ?>
                    <p style="font-size:.85rem;color:var(--text-light);">
                        <i class="fas fa-tag"></i> Current Card UID: <code><?= sanitize($selectedTourist['card_uid']) ?></code>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Write to Card -->
            <div class="card" style="margin-bottom:1.25rem;">
                <h3 style="margin-bottom:1rem;font-size:1rem;"><i class="fas fa-pen-to-square"></i> Write to NFC Card</h3>
                <p style="font-size:.85rem;color:var(--text-light);margin-bottom:1rem;">
                    Write the selected tourist's data to an NFC card. Place the card on the PN532 reader when prompted.
                </p>
                <button class="btn btn-accent" id="btnWrite" onclick="writeToCard()" disabled>
                    <i class="fas fa-download"></i> Write Tourist Data to Card
                </button>
            </div>

            <!-- Read from Card -->
            <div class="card" style="margin-bottom:1.25rem;">
                <h3 style="margin-bottom:1rem;font-size:1rem;"><i class="fas fa-eye"></i> Read NFC Card</h3>
                <p style="font-size:.85rem;color:var(--text-light);margin-bottom:1rem;">
                    Read data from an NFC card to view the stored tourist information.
                </p>
                <button class="btn btn-primary" id="btnRead" onclick="readFromCard()" disabled>
                    <i class="fas fa-upload"></i> Read Card Data
                </button>
            </div>

            <!-- Read Result -->
            <div class="card" id="readResult" style="display:none;">
                <h3 style="margin-bottom:1rem;font-size:1rem;"><i class="fas fa-file-lines"></i> Card Data</h3>
                <pre id="readData" style="background:#f1f5f9;padding:1rem;border-radius:var(--radius-sm);font-size:.8rem;overflow-x:auto;white-space:pre-wrap;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
const SITE_URL = '<?= SITE_URL ?>';
let selectedTouristId = <?= $touristId ?: 'null' ?>;

document.addEventListener('DOMContentLoaded', () => {
    NFC.init('serialLog', 'nfcStatus');
    updateButtons();
});

function onTouristSelect(id) {
    selectedTouristId = id ? parseInt(id) : null;
    const opt = document.querySelector(`#touristSelect option[value="${id}"]`);
    if (opt && id) {
        document.getElementById('cardName').textContent = opt.dataset.name;
        document.getElementById('cardDetails').textContent = opt.dataset.passport;
    } else {
        document.getElementById('cardName').textContent = 'No Tourist Selected';
        document.getElementById('cardDetails').textContent = 'Select a tourist to begin';
    }
    updateButtons();
}

function updateButtons() {
    const connected = NFC.isConnected;
    document.getElementById('btnConnect').style.display = connected ? 'none' : '';
    document.getElementById('btnDisconnect').style.display = connected ? '' : 'none';
    document.getElementById('btnWrite').disabled = !connected || !selectedTouristId;
    document.getElementById('btnRead').disabled = !connected;
}

async function connectDevice() {
    const ok = await NFC.connect();
    updateButtons();
}

async function disconnectDevice() {
    await NFC.disconnect();
    updateButtons();
}

async function writeToCard() {
    if (!selectedTouristId) {
        NFC.log('Please select a tourist first', 'error');
        return;
    }

    document.getElementById('btnWrite').disabled = true;
    NFC.log('Fetching tourist data...', 'info');

    try {
        const resp = await fetch(`${SITE_URL}/api/nfc_write.php?tourist_id=${selectedTouristId}`);
        const data = await resp.json();

        if (!data.success) {
            NFC.log('Error: ' + data.message, 'error');
            document.getElementById('btnWrite').disabled = false;
            return;
        }

        NFC.log('Tourist data loaded. Sending to ESP32...', 'info');
        const result = await NFC.writeCard(data.tourist);

        if (result && result.some(l => l.includes('DONE'))) {
            NFC.log('Card written successfully!', 'success');

            const uidLine = result.find(l => l.startsWith('UID:'));
            if (uidLine) {
                const uid = uidLine.replace('UID:', '').trim();
                await fetch(`${SITE_URL}/api/nfc_write.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        tourist_id: selectedTouristId,
                        card_uid: uid,
                        action: 'WRITE'
                    })
                });
                NFC.log(`Card UID saved: ${uid}`, 'success');
            }
        } else {
            NFC.log('Write may have failed. Check the device.', 'error');
        }
    } catch (err) {
        NFC.log('Error: ' + err.message, 'error');
    }

    document.getElementById('btnWrite').disabled = !NFC.isConnected || !selectedTouristId;
}

async function readFromCard() {
    document.getElementById('btnRead').disabled = true;
    document.getElementById('readResult').style.display = 'none';

    try {
        const result = await NFC.readCard();

        if (result && result.length > 0) {
            const jsonLine = result.find(l => l.startsWith('{'));
            if (jsonLine) {
                try {
                    const cardData = JSON.parse(jsonLine);
                    document.getElementById('readData').textContent = JSON.stringify(cardData, null, 2);
                    document.getElementById('readResult').style.display = 'block';
                    NFC.log('Card data read successfully!', 'success');

                    document.getElementById('cardName').textContent = (cardData.first_name || '') + ' ' + (cardData.last_name || '');
                    document.getElementById('cardDetails').textContent = (cardData.passport || '') + ' | ' + (cardData.nationality || '');

                    const uidLine = result.find(l => l.startsWith('UID:'));
                    if (uidLine) {
                        await fetch(`${SITE_URL}/api/nfc_read.php`, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                card_uid: uidLine.replace('UID:', '').trim(),
                                action: 'READ'
                            })
                        });
                    }
                } catch {
                    document.getElementById('readData').textContent = result.join('\n');
                    document.getElementById('readResult').style.display = 'block';
                }
            } else {
                document.getElementById('readData').textContent = result.join('\n');
                document.getElementById('readResult').style.display = 'block';
            }
        } else {
            NFC.log('No data received. Make sure a card is present.', 'error');
        }
    } catch (err) {
        NFC.log('Read error: ' + err.message, 'error');
    }

    document.getElementById('btnRead').disabled = !NFC.isConnected;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
