<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$me    = get_current_user_data();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = trim($_POST['caption'] ?? '');
    $filter  = $_POST['filter'] ?? 'none';

    if (!in_array($filter, POST_FILTERS)) $filter = 'none';
    if (!$caption && empty($_FILES['image']['name'])) {
        $error = 'A caption or image is required.';
    } else {
        $image_name = '';

        if (!empty($_FILES['image']['name'])) {
            $file = $_FILES['image'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'Image upload failed.';
            } elseif ($file['size'] > MAX_UPLOAD_SIZE) {
                $error = 'Image too large (max 5MB).';
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                    $error = 'Invalid image type.';
                } else {
                    $info = getimagesize($file['tmp_name']);
                    if (!$info) {
                        $error = 'File is not a valid image.';
                    } else {
                        $image_name = 'post_' . time() . '_' . $me['id'] . '.' . $ext;
                        if (!move_uploaded_file($file['tmp_name'], UPLOADS_PATH . '/' . $image_name)) {
                            $error = 'Failed to save image.';
                            $image_name = '';
                        }
                    }
                }
            }
        }

        if (!$error) {
            $posts = db_read('posts.json');
            $new_post = [
                'id'         => db_next_id($posts),
                'user_id'    => $me['id'],
                'caption'    => $caption,
                'image'      => $image_name,
                'filter'     => $filter,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $posts[] = $new_post;
            db_write('posts.json', $posts);
            header('Location: ' . BASE_URL . '/pages/feed.php');
            exit;
        }
    }
}

$page_title = 'New Post';
include __DIR__ . '/../includes/header.php';
?>

<div class="container create-post-page">
    <div class="card" style="max-width:640px;margin:0 auto;">
          <h2>Create New Post</h2>        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="form-vertical">

            <!-- Image Upload -->
            <div class="form-group">
                <label>Photo <small>(optional)</small></label>
                <div class="image-upload-area" id="upload-area" onclick="document.getElementById('image-input').click()">
                      <div id="upload-placeholder">
                          <p>Click to upload a photo</p>
                        <small>JPG, PNG, GIF, WEBP · max 5MB</small>
                    </div>
                    <img id="image-preview" style="display:none; max-width:100%; border-radius:8px;">
                </div>
                <input type="file" id="image-input" name="image" accept="image/*"
                       style="display:none" onchange="previewImage(this)">
            </div>

            <!-- Filter Picker -->
            <div class="form-group" id="filter-section" style="display:none">
                <label>Filter</label>
                <div class="filter-picker">
                    <?php foreach (POST_FILTERS as $f): ?>
                        <label class="filter-option <?= $f === 'none' ? 'selected' : '' ?>">
                            <input type="radio" name="filter" value="<?= $f ?>"
                                   <?= $f === 'none' ? 'checked' : '' ?>
                                   onchange="applyPreviewFilter('<?= $f ?>')">
                            <div class="filter-thumb filter-<?= $f ?>"></div>
                            <span><?= ucfirst($f) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Caption -->
            <div class="form-group">
                <label>Caption</label>
                <textarea name="caption" rows="4" placeholder="Write a caption…"
                          maxlength="2200"><?= htmlspecialchars($_POST['caption'] ?? '') ?></textarea>
                <small class="char-count">0 / 2200</small>
            </div>

            <div class="form-actions">
                <a href="<?= BASE_URL ?>/pages/feed.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Share Post</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/create_post.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
