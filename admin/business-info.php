<?php
// Sigma Panels & Paint - Business Info Management
// Phase 6 implementation.

$adminPageKey = 'business-info';
require_once __DIR__ . '/../includes/admin-header.php';

$pdo = db();
$error = '';
$success = '';

// Process Form Submission
if (is_post()) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf)) {
        $error = "Invalid form submission. Please try again.";
    } else {
        // Collect fields
        $business_name = trim($_POST['business_name'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $google_map_embed = trim($_POST['google_map_embed'] ?? '');
        $primary_color = trim($_POST['primary_color'] ?? '#F6F4F1');
        $secondary_color = trim($_POST['secondary_color'] ?? '#F95C4B');
        
        $logo_path = $_POST['existing_logo_path'] ?? '';

        if (empty($business_name)) {
            $error = "Business Name is required.";
        } else {
            // Handle File Upload
            if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['logo_upload']['tmp_name'];
                $file_name = $_FILES['logo_upload']['name'];
                $file_size = $_FILES['logo_upload']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
                $max_size = 2 * 1024 * 1024; // 2MB

                if (!in_array($file_ext, $allowed_exts)) {
                    $error = "Invalid file type. Allowed: JPG, PNG, WEBP, SVG.";
                } elseif ($file_size > $max_size) {
                    $error = "File size too large. Max 2MB.";
                } else {
                    $upload_dir = UPLOAD_DIR . '/settings/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $new_filename = 'logo_' . time() . '.' . $file_ext;
                    $dest = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $dest)) {
                        $logo_path = 'uploads/settings/' . $new_filename;
                    } else {
                        $error = "Failed to save uploaded file.";
                    }
                }
            }

            if (!$error) {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE business_settings SET
                            business_name = :business_name,
                            tagline = :tagline,
                            phone = :phone,
                            whatsapp = :whatsapp,
                            email = :email,
                            address = :address,
                            google_map_embed = :google_map_embed,
                            primary_color = :primary_color,
                            secondary_color = :secondary_color,
                            logo_path = :logo_path,
                            updated_at = NOW()
                        WHERE id = 1
                    ");
                    $stmt->execute([
                        'business_name' => $business_name,
                        'tagline' => $tagline,
                        'phone' => $phone,
                        'whatsapp' => $whatsapp,
                        'email' => $email,
                        'address' => $address,
                        'google_map_embed' => $google_map_embed,
                        'primary_color' => $primary_color,
                        'secondary_color' => $secondary_color,
                        'logo_path' => $logo_path
                    ]);
                    $success = "Business settings updated successfully.";
                } catch (PDOException $e) {
                    $error = "Database error updating settings.";
                }
            }
        }
    }
}

// Load Current Settings
$stmt = $pdo->query("SELECT * FROM business_settings LIMIT 1");
$settings = $stmt->fetch();
if (!$settings) {
    // Defaults if missing
    $settings = [
        'business_name' => '', 'tagline' => '', 'phone' => '', 'whatsapp' => '',
        'email' => '', 'address' => '', 'google_map_embed' => '',
        'primary_color' => '#F6F4F1', 'secondary_color' => '#F95C4B', 'logo_path' => ''
    ];
}
?>

<div class="page-header">
    <h1>Business Info</h1>
    <p>Manage your business contact details and branding here.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success" style="background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;padding:12px;margin-bottom:20px;border-radius:4px;"><?= e($success) ?></div>
<?php endif; ?>

<div class="dashboard-card">
    <form method="POST" action="" enctype="multipart/form-data">
        <?= csrf_field() ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="business_name">Business Name *</label>
                <input type="text" id="business_name" name="business_name" value="<?= e($settings['business_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="tagline">Tagline</label>
                <input type="text" id="tagline" name="tagline" value="<?= e($settings['tagline']) ?>">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?= e($settings['phone']) ?>">
            </div>
            
            <div class="form-group">
                <label for="whatsapp">WhatsApp</label>
                <input type="text" id="whatsapp" name="whatsapp" value="<?= e($settings['whatsapp']) ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= e($settings['email']) ?>">
            </div>
            
            <div class="form-group">
                <label for="address">Physical Address</label>
                <input type="text" id="address" name="address" value="<?= e($settings['address']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="google_map_embed">Google Map Embed HTML</label>
            <textarea id="google_map_embed" name="google_map_embed" rows="3"><?= e($settings['google_map_embed']) ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="primary_color">Primary Color (Hex)</label>
                <input type="color" id="primary_color" name="primary_color" value="<?= e($settings['primary_color']) ?>" style="padding:0;height:40px;">
            </div>
            
            <div class="form-group">
                <label for="secondary_color">Secondary Color (Hex)</label>
                <input type="color" id="secondary_color" name="secondary_color" value="<?= e($settings['secondary_color']) ?>" style="padding:0;height:40px;">
            </div>
        </div>

        <div class="form-group">
            <label>Current Logo</label>
            <?php if (!empty($settings['logo_path'])): ?>
                <div style="margin-bottom: 10px;">
                    <img src="<?= e(asset($settings['logo_path'])) ?>" alt="Logo" style="max-height: 80px; background:#ccc; padding:5px; border-radius:4px;">
                </div>
            <?php endif; ?>
            <input type="hidden" name="existing_logo_path" value="<?= e($settings['logo_path']) ?>">
            <label for="logo_upload">Upload New Logo (JPG, PNG, WEBP, SVG - Max 2MB)</label>
            <input type="file" id="logo_upload" name="logo_upload" accept=".jpg,.jpeg,.png,.webp,.svg">
        </div>

        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>

<?php
require_once __DIR__ . '/../includes/admin-footer.php';
