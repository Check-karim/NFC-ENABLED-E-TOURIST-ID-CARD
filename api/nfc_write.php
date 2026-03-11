<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $touristId = (int)($_GET['tourist_id'] ?? 0);
    if ($touristId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid tourist ID']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, first_name, last_name, nationality, passport_number, date_of_birth, gender, visa_type, entry_date, exit_date FROM tourists WHERE id = ?");
    $stmt->bind_param('i', $touristId);
    $stmt->execute();
    $tourist = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$tourist) {
        echo json_encode(['success' => false, 'message' => 'Tourist not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'tourist' => [
            'id'          => $tourist['id'],
            'first_name'  => $tourist['first_name'],
            'last_name'   => $tourist['last_name'],
            'nationality' => $tourist['nationality'],
            'passport'    => $tourist['passport_number'],
            'dob'         => $tourist['date_of_birth'],
            'gender'      => $tourist['gender'],
            'visa'        => $tourist['visa_type'],
            'entry'       => $tourist['entry_date'],
            'exit'        => $tourist['exit_date'],
        ]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $touristId = (int)($input['tourist_id'] ?? 0);
    $cardUid   = trim($input['card_uid'] ?? '');
    $action    = trim($input['action'] ?? 'WRITE');

    if ($touristId > 0 && $cardUid !== '') {
        $stmt = $conn->prepare("UPDATE tourists SET card_uid = ? WHERE id = ?");
        $stmt->bind_param('si', $cardUid, $touristId);
        $stmt->execute();
        $stmt->close();

        $adminId = $_SESSION['admin_id'];
        $stmt = $conn->prepare("INSERT INTO nfc_logs (tourist_id, card_uid, action, details, performed_by) VALUES (?, ?, ?, ?, ?)");
        $details = "Card written for tourist #$touristId";
        $stmt->bind_param('isssi', $touristId, $cardUid, $action, $details, $adminId);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Card UID saved and logged']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
