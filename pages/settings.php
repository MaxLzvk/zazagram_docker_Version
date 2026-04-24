<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me    = get_current_user_data();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio       = trim($_POST['bio'] ?? '');
    $firstname = trim($_POST['first_name'] ?? '');
    $lastname  = trim($_POST['last_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $new_pass  = $_POST['new_password'] ?? '';
    $cur_pass  = $_POST['current_password'] ?? '';

    if (!$firstname || !$email) {
        $error = 'First name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email.';
    } else {
        $users = db_read('users.json');

        // Check email uniqueness (exclude self)
        foreach ($users as $u) {
            if ($u['email'] === $email && $u['id'] !== $me['id']) {
                $error = 'Email already in use.';
                break;
            }
        }

        if (!$error) {
            $changes = [
                'first_name' => $firstname,
                'last_name'  => $lastname,
                'bio'        => $bio,
                'email'      => $email,
                'updated_at' => now(),
            ];

            // Password change
            if ($new_pass) {
                if (!password_verify($cur_pass, $me['password'])) {
                    $error = 'Current password is incorrect.';
                } elseif (strlen($new_pass) < 6) {
                    $error = 'New password must be at least 6 characters.';
                } else {
                    $changes['password'] = password_hash($new_pass, PASSWORD_BCRYPT);
                }
            }

            if (!$error) {
                $users = db_update($users, $me['id'], $changes);
                db_write('users.json', $users);
                $success = 'Profile updated successfully.';
                $me = get_current_user_data(); // reload
            }
        }
    }
}

$page_title = 'Settings';
include __DIR__ . '/../includes/header.php';
?>

<div class="container settings-page">
    <div class="card" style="max-width:600px;margin:0 auto;">
        <h2>Edit Profile</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" class="form-vertical">
            <div class="form-row two-col">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($me['first_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($me['last_name']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($me['email']) ?>" required>
            </div>
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" rows="3" placeholder="Tell people about yourself…"><?= htmlspecialchars($me['bio']) ?></textarea>
            </div>

            <hr>
            <h3>Change Password <small>(leave blank to keep current)</small></h3>
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" placeholder="Enter current password">
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" placeholder="Enter new password (min 6)">
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
