<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, redirect to feed
if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/pages/feed.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $firstname = trim($_POST['first_name'] ?? '');
    $lastname  = trim($_POST['last_name'] ?? '');

    // Validation
    if (!$username || !$email || !$password || !$firstname || !$lastname) {
        $error = 'All fields are required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $error = 'Username must be 3–20 characters (letters, numbers, underscores).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $users = db_read('users.json');

        // Check uniqueness
        if (db_find_one($users, 'username', $username)) {
            $error = 'Username already taken.';
        } elseif (db_find_one($users, 'email', $email)) {
            $error = 'Email already registered.';
        } else {
            $new_user = [
                'id'              => db_next_id($users),
                'username'        => $username,
                'email'           => $email,
                'password'        => password_hash($password, PASSWORD_BCRYPT),
                'first_name'      => $firstname,
                'last_name'       => $lastname,
                'bio'             => '',
                'profile_picture' => 'default_avatar.png',
                'role'            => 'user',
                'is_banned'       => false,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
            $users[] = $new_user;
            db_write('users.json', $users);

            // Auto-login the new user
            session_regenerate_id(true);
            $_SESSION['user_id']  = $new_user['id'];
            $_SESSION['username'] = $new_user['username'];
            $_SESSION['role']     = $new_user['role'];
            header('Location: ' . BASE_URL . '/pages/feed.php');
            exit;
        }
    }
}

$page_title = 'Register';
$body_class = 'no-nav';
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-logo">
            <h1>Zazagram</h1>
            <p>Share your world with friends</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-row two-col">
                <div class="form-group">
                    <input type="text" name="first_name" placeholder="First name"
                           value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <input type="text" name="last_name" placeholder="Last name"
                           value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <input type="text" name="username" placeholder="Username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Email address"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password (min 6 chars)" required>
            </div>
            <div class="form-group">
                <input type="password" name="confirm_password" placeholder="Confirm password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Create Account</button>
        </form>

        <p class="auth-switch">Already have an account? <a href="login.php">Sign in</a></p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
