<?php
// Sigma Panels & Paint - Request a Quote
// Phase 9 public page - Stitch "Premium Quote Request".

$pageKey = 'quote';

// Load helpers early so we can process the POST (and redirect) before output.
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();

// Active services for the dropdown + allow-list validation
$services = $pdo->query("SELECT title, slug FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
$validSlugs = array_column($services, 'slug');

$errors = [];
$old = [
    'customer_name'    => '',
    'phone'            => '',
    'email'            => '',
    'service_interest' => '',
    'project_location' => '',
    'message'          => '',
];
$success = '';

if (is_post()) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf)) {
        $errors[] = 'Your session expired. Please try submitting the form again.';
    } else {
        foreach ($old as $k => $_) {
            $old[$k] = trim($_POST[$k] ?? '');
        }

        if ($old['customer_name'] === '')    { $errors['customer_name'] = 'Please enter your name.'; }
        if ($old['phone'] === '')            { $errors['phone'] = 'Please enter a contact phone number.'; }
        if ($old['service_interest'] === '' || !in_array($old['service_interest'], $validSlugs, true)) {
            $errors['service_interest'] = 'Please choose a service.';
        }
        if ($old['message'] === '')          { $errors['message'] = 'Please tell us a little about the job.'; }
        if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO quote_requests
                        (customer_name, phone, email, service_interest, project_location, message, status, created_at, updated_at)
                     VALUES
                        (:customer_name, :phone, :email, :service_interest, :project_location, :message, 'pending', NOW(), NOW())"
                );
                $stmt->execute([
                    'customer_name'    => $old['customer_name'],
                    'phone'            => $old['phone'],
                    'email'            => $old['email'],
                    'service_interest' => $old['service_interest'],
                    'project_location' => $old['project_location'],
                    'message'          => $old['message'],
                ]);
                $_SESSION['quote_success'] = true;
                redirect('public/quote.php?sent=1');
            } catch (PDOException $e) {
                $errors[] = 'Something went wrong saving your request. Please try again or call us directly.';
            }
        }
    }
}

if (isset($_GET['sent']) && !empty($_SESSION['quote_success'])) {
    unset($_SESSION['quote_success']);
    $success = 'Thanks! Your quote request has been received. Our team will be in touch shortly.';
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ===================== PAGE HERO ===================== -->
<section class="page-hero" style="padding-bottom:40px;">
    <div class="hero-glow"></div>
    <div class="container" data-reveal="up">
        <span class="label-caps">Request a Quote</span>
        <h1>Tell us what happened. <span class="text-coral">We'll guide the repair.</span></h1>
        <p>Share a few details about your vehicle and the damage, and we'll prepare a precise, no-obligation quote.</p>
    </div>
</section>

<!-- ===================== QUOTE FORM ===================== -->
<section class="section-sm bg-white">
    <div class="container" style="max-width:820px;">
        <?php if ($success): ?>
            <div class="alert alert-success" data-reveal="fade"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" data-reveal="fade">
                Please check the highlighted fields and try again.
                <?php foreach ($errors as $key => $msg): if (is_int($key)): ?>
                    <div><?= e($msg) ?></div>
                <?php endif; endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="form-card" data-reveal="fade">
            <form method="POST" action="<?= e(url('public/quote.php')) ?>" data-validate novalidate>
                <?= csrf_field() ?>

                <div class="form-row">
                    <div class="field <?= isset($errors['customer_name']) ? 'has-error' : '' ?>">
                        <label for="customer_name">Your Name *</label>
                        <input type="text" id="customer_name" name="customer_name" value="<?= e($old['customer_name']) ?>" required>
                        <span class="err-msg"><?= e($errors['customer_name'] ?? '') ?></span>
                    </div>
                    <div class="field <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                        <label for="phone">Phone *</label>
                        <input type="text" id="phone" name="phone" value="<?= e($old['phone']) ?>" required>
                        <span class="err-msg"><?= e($errors['phone'] ?? '') ?></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= e($old['email']) ?>">
                        <span class="err-msg"><?= e($errors['email'] ?? '') ?></span>
                    </div>
                    <div class="field <?= isset($errors['service_interest']) ? 'has-error' : '' ?>">
                        <label for="service_interest">Service Needed *</label>
                        <select id="service_interest" name="service_interest" required>
                            <option value="">Select a service&hellip;</option>
                            <?php foreach ($services as $srv): ?>
                                <option value="<?= e($srv['slug']) ?>" <?= $old['service_interest'] === $srv['slug'] ? 'selected' : '' ?>>
                                    <?= e($srv['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="err-msg"><?= e($errors['service_interest'] ?? '') ?></span>
                    </div>
                </div>

                <div class="field">
                    <label for="project_location">Vehicle / Project Location</label>
                    <input type="text" id="project_location" name="project_location" value="<?= e($old['project_location']) ?>" placeholder="e.g. Acacia Ridge QLD">
                </div>

                <div class="field <?= isset($errors['message']) ? 'has-error' : '' ?>">
                    <label for="message">Tell us about the job *</label>
                    <textarea id="message" name="message" required placeholder="Describe the damage, vehicle make/model, and anything else that helps."><?= e($old['message']) ?></textarea>
                    <span class="err-msg"><?= e($errors['message'] ?? '') ?></span>
                </div>

                <button type="submit" class="btn btn-pill btn-coral btn-lg btn-block shine-effect">Send Quote Request</button>
            </form>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
