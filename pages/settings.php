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

    <!-- Appearance & Language -->
    <div class="card" style="max-width:600px;margin:1.5rem auto 0">
        <h2 style="margin-bottom:1.5rem">Appearance & Language</h2>

        <!-- Theme toggle -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 0;border-bottom:1px solid var(--border)">
            <div>
                <strong>Display Mode</strong>
                <p style="color:var(--text-muted);font-size:0.85rem;margin-top:0.25rem">Switch between dark and light interface</p>
            </div>
            <button onclick="toggleTheme()" id="settings-theme-btn"
                    style="display:flex;align-items:center;gap:0.6rem;background:var(--bg-hover);border:1px solid var(--border-hi);border-radius:10px;padding:0.6rem 1.2rem;color:var(--text);font-size:0.9rem;font-weight:600;cursor:pointer;transition:all .18s ease;min-width:130px;justify-content:center">
                <!-- Moon icon (dark mode) -->
                <svg id="settings-theme-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
                <span id="settings-theme-label">Dark</span>
            </button>
        </div>

        <!-- Language -->
        <div style="padding:1rem 0">
            <div style="margin-bottom:0.75rem">
                <strong>Language</strong>
                <p style="color:var(--text-muted);font-size:0.85rem;margin-top:0.25rem">Translate the interface using Google Translate AI</p>
            </div>
            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center">
                <select id="lang-select"
                        style="flex:1;min-width:180px;background:var(--bg-input);border:1px solid var(--border-hi);border-radius:10px;padding:0.65rem 1rem;color:var(--text);font-size:0.9rem">
                    <option value="en">English (default)</option>
                    <option value="fr">Français</option>
                    <option value="es">Español</option>
                    <option value="de">Deutsch</option>
                    <option value="it">Italiano</option>
                    <option value="pt">Português</option>
                    <option value="ar">العربية</option>
                    <option value="zh-CN">中文 (简体)</option>
                    <option value="ja">日本語</option>
                    <option value="ko">한국어</option>
                    <option value="ru">Русский</option>
                    <option value="nl">Nederlands</option>
                    <option value="pl">Polski</option>
                    <option value="tr">Türkçe</option>
                    <option value="hi">हिन्दी</option>
                    <option value="mg">Malagasy</option>
                </select>
                <button onclick="applyLanguage()"
                        class="btn btn-primary" style="white-space:nowrap">Apply Language</button>
                <button onclick="resetLanguage()"
                        class="btn" style="background:rgba(255,255,255,.06);border:1px solid var(--border);color:var(--text-muted);white-space:nowrap">Reset to English</button>
            </div>
            <p style="color:var(--text-dim);font-size:0.78rem;margin-top:0.6rem">
                Powered by Google Translate. Some content may not be perfectly translated.
            </p>
        </div>
    </div>
</div>

<script>
// SVG paths for moon and sun icons
const MOON_SVG = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
const SUN_SVG  = '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>';

function setThemeIcon(isLight) {
    const icon  = document.getElementById('settings-theme-icon');
    const label = document.getElementById('settings-theme-label');
    if (icon)  icon.innerHTML = isLight ? SUN_SVG : MOON_SVG;
    if (label) label.textContent = isLight ? 'Light' : 'Dark';
}

// Sync theme state on this page
(function() {
    setThemeIcon(document.body.classList.contains('light-mode'));
})();

// Override toggleTheme to also update labels on this page
const _origToggle = window.toggleTheme;
window.toggleTheme = function() {
    if (_origToggle) _origToggle();
    setThemeIcon(document.body.classList.contains('light-mode'));
};

// Restore saved language in the dropdown
(function() {
    const saved = localStorage.getItem('zzgLang') || 'en';
    const sel = document.getElementById('lang-select');
    if (sel) sel.value = saved;
})();

function applyLanguage() {
    const sel = document.getElementById('lang-select');
    const lang = sel.value;
    localStorage.setItem('zzgLang', lang);
    if (lang === 'en') {
        // Clear the translation cookie
        document.cookie = 'googtrans=; path=/; domain=' + location.hostname + '; expires=Thu, 01 Jan 1970 00:00:00 UTC';
        document.cookie = 'googtrans=; path=/; expires=Thu, 01 Jan 1970 00:00:00 UTC';
    } else {
        document.cookie = 'googtrans=/en/' + lang + '; path=/; domain=' + location.hostname;
        document.cookie = 'googtrans=/en/' + lang + '; path=/';
    }
    location.reload();
}

function resetLanguage() {
    localStorage.setItem('zzgLang', 'en');
    document.cookie = 'googtrans=; path=/; domain=' + location.hostname + '; expires=Thu, 01 Jan 1970 00:00:00 UTC';
    document.cookie = 'googtrans=; path=/; expires=Thu, 01 Jan 1970 00:00:00 UTC';
    location.reload();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>