<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $cardUid = trim($input['card_uid'] ?? '');

    if ($cardUid === '') {
        echo json_encode(['success' => false, 'message' => 'Card UID required']);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM tourists WHERE card_uid = ?");
    $stmt->bind_param('s', $cardUid);
    $stmt->execute();
    $tourist = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $touristId = $tourist ? $tourist['id'] : null;
    $adminId = $_SESSION['admin_id'];
    $details = $tourist ? "Card read for {$tourist['first_name']} {$tourist['last_name']}" : "Unknown card scanned: $cardUid";

    $action = 'READ';
    $stmt = $conn->prepare("INSERT INTO nfc_logs (tourist_id, card_uid, action, details, performed_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('isssi', $touristId, $cardUid, $action, $details, $adminId);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'found'   => $tourist !== null,
        'tourist' => $tourist ? [
            'id'              => $tourist['id'],
            'first_name'      => $tourist['first_name'],
            'last_name'       => $tourist['last_name'],
            'nationality'     => $tourist['nationality'],
            'passport_number' => $tourist['passport_number'],
            'status'          => $tourist['status'],
        ] : null
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
