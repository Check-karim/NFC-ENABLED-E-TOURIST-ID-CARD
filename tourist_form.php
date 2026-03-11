<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$tourist = null;

if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM tourists WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $tourist = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$tourist) {
        setFlash('error', 'Tourist not found.');
        redirect(SITE_URL . '/tourists.php');
    }
}

$pageTitle = $isEdit ? 'Edit Tourist' : 'Add Tourist';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'first_name'        => trim($_POST['first_name'] ?? ''),
        'last_name'         => trim($_POST['last_name'] ?? ''),
        'email'             => trim($_POST['email'] ?? ''),
        'phone'             => trim($_POST['phone'] ?? ''),
        'nationality'       => trim($_POST['nationality'] ?? ''),
        'passport_number'   => trim($_POST['passport_number'] ?? ''),
        'date_of_birth'     => trim($_POST['date_of_birth'] ?? ''),
        'gender'            => trim($_POST['gender'] ?? 'Male'),
        'address'           => trim($_POST['address'] ?? ''),
        'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
        'emergency_phone'   => trim($_POST['emergency_phone'] ?? ''),
        'visa_type'         => trim($_POST['visa_type'] ?? ''),
        'entry_date'        => trim($_POST['entry_date'] ?? ''),
        'exit_date'         => trim($_POST['exit_date'] ?? ''),
        'status'            => trim($_POST['status'] ?? 'Active'),
        'notes'             => trim($_POST['notes'] ?? ''),
    ];

    if ($data['first_name'] === '') $errors[] = 'First name is required.';
    if ($data['last_name'] === '')  $errors[] = 'Last name is required.';
    if ($data['nationality'] === '') $errors[] = 'Nationality is required.';
    if ($data['passport_number'] === '') $errors[] = 'Passport number is required.';
    if ($data['date_of_birth'] === '') $errors[] = 'Date of birth is required.';

    $photoName = $isEdit ? $tourist['photo'] : null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['photo']['type'], $allowed)) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photoName = 'tourist_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR . $photoName)) {
                $errors[] = 'Failed to upload photo.';
                $photoName = $isEdit ? $tourist['photo'] : null;
            }
        } else {
            $errors[] = 'Invalid image format. Use JPG, PNG, GIF, or WebP.';
        }
    }

    if (empty($errors)) {
        if ($isEdit) {
            $sql = "UPDATE tourists SET first_name=?, last_name=?, email=?, phone=?, nationality=?,
                    passport_number=?, date_of_birth=?, gender=?, address=?, emergency_contact=?,
                    emergency_phone=?, photo=?, visa_type=?, entry_date=?, exit_date=?, status=?, notes=?
                    WHERE id=?";
            $stmt = $conn->prepare($sql);
            $entryDate = $data['entry_date'] ?: null;
            $exitDate  = $data['exit_date'] ?: null;
            $stmt->bind_param('sssssssssssssssssi',
                $data['first_name'], $data['last_name'], $data['email'], $data['phone'],
                $data['nationality'], $data['passport_number'], $data['date_of_birth'],
                $data['gender'], $data['address'], $data['emergency_contact'],
                $data['emergency_phone'], $photoName, $data['visa_type'],
                $entryDate, $exitDate, $data['status'], $data['notes'], $id
            );
        } else {
            $sql = "INSERT INTO tourists (first_name, last_name, email, phone, nationality,
                    passport_number, date_of_birth, gender, address, emergency_contact,
                    emergency_phone, photo, visa_type, entry_date, exit_date, status, notes)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $conn->prepare($sql);
            $entryDate = $data['entry_date'] ?: null;
            $exitDate  = $data['exit_date'] ?: null;
            $stmt->bind_param('sssssssssssssssss',
                $data['first_name'], $data['last_name'], $data['email'], $data['phone'],
                $data['nationality'], $data['passport_number'], $data['date_of_birth'],
                $data['gender'], $data['address'], $data['emergency_contact'],
                $data['emergency_phone'], $photoName, $data['visa_type'],
                $entryDate, $exitDate, $data['status'], $data['notes']
            );
        }

        if ($stmt->execute()) {
            $newId = $isEdit ? $id : $conn->insert_id;
            setFlash('success', $isEdit ? 'Tourist updated successfully.' : 'Tourist created successfully.');
            redirect(SITE_URL . '/tourists.php');
        } else {
            $errors[] = 'Database error: ' . $stmt->error;
        }
        $stmt->close();
    }

    if (!empty($errors)) {
        $tourist = $data;
        $tourist['photo'] = $photoName;
        $tourist['id'] = $id;
    }
}

require_once __DIR__ . '/includes/header.php';
$t = $tourist;
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-<?= $isEdit ? 'edit' : 'user-plus' ?>"></i> <?= $pageTitle ?></h1>
        <a href="<?= SITE_URL ?>/tourists.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="card" style="margin-bottom:2rem;">
        <h3 style="margin-bottom:1.25rem;font-size:1.1rem;"><i class="fas fa-user"></i> Personal Information</h3>

        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" class="form-control" value="<?= sanitize($t['first_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" class="form-control" value="<?= sanitize($t['last_name'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?= sanitize($t['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" class="form-control" value="<?= sanitize($t['phone'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="nationality">Nationality *</label>
                <input type="text" id="nationality" name="nationality" class="form-control" value="<?= sanitize($t['nationality'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="passport_number">Passport Number *</label>
                <input type="text" id="passport_number" name="passport_number" class="form-control" value="<?= sanitize($t['passport_number'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="date_of_birth">Date of Birth *</label>
                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="<?= sanitize($t['date_of_birth'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender *</label>
                <select id="gender" name="gender" class="form-control">
                    <option value="Male" <?= ($t['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= ($t['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= ($t['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" class="form-control" rows="2"><?= sanitize($t['address'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="emergency_contact">Emergency Contact Name</label>
                <input type="text" id="emergency_contact" name="emergency_contact" class="form-control" value="<?= sanitize($t['emergency_contact'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="emergency_phone">Emergency Contact Phone</label>
                <input type="text" id="emergency_phone" name="emergency_phone" class="form-control" value="<?= sanitize($t['emergency_phone'] ?? '') ?>">
            </div>
        </div>

        <h3 style="margin:1.5rem 0 1.25rem;font-size:1.1rem;"><i class="fas fa-passport"></i> Travel Information</h3>

        <div class="form-row">
            <div class="form-group">
                <label for="visa_type">Visa Type</label>
                <select id="visa_type" name="visa_type" class="form-control">
                    <option value="">Select...</option>
                    <?php foreach (['Tourist','Business','Transit','Student','Work','Diplomatic'] as $vt): ?>
                        <option value="<?= $vt ?>" <?= ($t['visa_type'] ?? '') === $vt ? 'selected' : '' ?>><?= $vt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" class="form-control">
                    <option value="Active" <?= ($t['status'] ?? 'Active') === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Expired" <?= ($t['status'] ?? '') === 'Expired' ? 'selected' : '' ?>>Expired</option>
                    <option value="Revoked" <?= ($t['status'] ?? '') === 'Revoked' ? 'selected' : '' ?>>Revoked</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="entry_date">Entry Date</label>
                <input type="date" id="entry_date" name="entry_date" class="form-control" value="<?= sanitize($t['entry_date'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="exit_date">Exit Date</label>
                <input type="date" id="exit_date" name="exit_date" class="form-control" value="<?= sanitize($t['exit_date'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="3"><?= sanitize($t['notes'] ?? '') ?></textarea>
        </div>

        <h3 style="margin:1.5rem 0 1.25rem;font-size:1.1rem;"><i class="fas fa-camera"></i> Photo</h3>

        <div class="form-group">
            <div class="photo-upload-area" id="uploadArea">
                <?php if (!empty($t['photo'])): ?>
                    <img src="<?= SITE_URL ?>/uploads/<?= sanitize($t['photo']) ?>" id="photoPreview" class="photo-preview" alt="Tourist photo">
                <?php else: ?>
                    <div class="upload-placeholder">
                        <i class="fas fa-cloud-upload-alt" style="display:block;"></i>
                        <p>Click to upload a photo</p>
                    </div>
                    <img src="" id="photoPreview" class="photo-preview" style="display:none;" alt="Preview">
                <?php endif; ?>
            </div>
            <input type="file" id="photoInput" name="photo" accept="image/*" style="display:none;">
        </div>

        <div style="display:flex;gap:1rem;margin-top:1.5rem;">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-<?= $isEdit ? 'save' : 'plus' ?>"></i>
                <?= $isEdit ? 'Update Tourist' : 'Create Tourist' ?>
            </button>
            <a href="<?= SITE_URL ?>/tourists.php" class="btn btn-outline btn-lg">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
