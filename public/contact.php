<?php
// Sigma Panels & Paint - Contact Us
// Phase 9 public page - Stitch "Premium Contact Experience".

$pageKey = 'contact';

// Load helpers early so we can process the POST (and redirect) before output.
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = db();
$settings = get_business_settings();

$errors = [];
$old = ['name' => '', 'phone' => '', 'email' => '', 'subject' => '', 'message' => ''];
$success = '';

if (is_post()) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf)) {
        $errors[] = 'Your session expired. Please try submitting the form again.';
    } else {
        foreach ($old as $k => $_) {
            $old[$k] = trim($_POST[$k] ?? '');
        }

        if ($old['name'] === '')    { $errors['name'] = 'Please enter your name.'; }
        if ($old['phone'] === '')   { $errors['phone'] = 'Please enter a contact phone number.'; }
        if ($old['subject'] === '') { $errors['subject'] = 'Please enter a subject.'; }
        if ($old['message'] === '') { $errors['message'] = 'Please enter your message.'; }
        if ($old['email'] !== '' && !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO contact_messages
                        (name, phone, email, subject, message, status, created_at, updated_at)
                     VALUES
                        (:name, :phone, :email, :subject, :message, 'unread', NOW(), NOW())"
                );
                $stmt->execute([
                    'name'    => $old['name'],
                    'phone'   => $old['phone'],
                    'email'   => $old['email'],
                    'subject' => $old['subject'],
                    'message' => $old['message'],
                ]);
                $_SESSION['contact_success'] = true;
                redirect('public/contact.php?sent=1');
            } catch (PDOException $e) {
                $errors[] = 'Something went wrong sending your message. Please try again or call us directly.';
            }
        }
    }
}

if (isset($_GET['sent']) && !empty($_SESSION['contact_success'])) {
    unset($_SESSION['contact_success']);
    $success = 'Thanks for reaching out! Your message has been received and we\'ll reply soon.';
}

// --- Safe Google Map embed handling ---
// The setting is stored as raw <iframe> HTML by an admin. Only render it if it
// looks like a genuine Google Maps embed and contains no script/event handlers.
$mapRaw = $settings['google_map_embed'] ?? '';
$mapIsSafe = false;
if ($mapRaw !== '') {
    $lower = strtolower($mapRaw);
    $looksLikeIframe = strpos($lower, '<iframe') !== false;
    $isGoogleMap = strpos($lower, 'google.com/maps') !== false;
    $hasScript = strpos($lower, '<script') !== false;
    $hasInlineHandler = preg_match('/on\w+\s*=/', $lower) === 1;
    $mapIsSafe = $looksLikeIframe && $isGoogleMap && !$hasScript && !$hasInlineHandler;
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ===================== PAGE HERO ===================== -->
<section class="page-hero" style="padding-bottom:40px;">
    <div class="hero-glow"></div>
    <div class="container" data-reveal="up">
        <span class="label-caps">Get In Touch</span>
        <h1>Let's talk about <span class="text-coral">your vehicle.</span></h1>
        <p>Questions, bookings or a quick chat &mdash; reach the workshop directly or send us a message.</p>
    </div>
</section>

<!-- ===================== CONTACT SPLIT ===================== -->
<section class="section-sm bg-white">
    <div class="container">
        <div class="contact-split">
            <!-- Left: details + map -->
            <div data-reveal="fade">
                <div class="info-list">
                    <?php if (!empty($settings['address'])): ?>
                        <div class="info-item">
                            <div class="i-icon"><span class="material-symbols-outlined">location_on</span></div>
                            <div>
                                <p class="i-label">Workshop</p>
                                <p class="i-value"><?= e($settings['address']) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($settings['phone'])): ?>
                        <div class="info-item">
                            <div class="i-icon"><span class="material-symbols-outlined">call</span></div>
                            <div>
                                <p class="i-label">Phone</p>
                                <p class="i-value"><a href="tel:<?= e(preg_replace('/[^0-9\+]/', '', $settings['phone'])) ?>"><?= e($settings['phone']) ?></a></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($settings['email'])): ?>
                        <div class="info-item">
                            <div class="i-icon"><span class="material-symbols-outlined">mail</span></div>
                            <div>
                                <p class="i-label">Email</p>
                                <p class="i-value"><a href="mailto:<?= e($settings['email']) ?>"><?= e($settings['email']) ?></a></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($settings['whatsapp'])): ?>
                        <div class="info-item">
                            <div class="i-icon"><span class="material-symbols-outlined">chat</span></div>
                            <div>
                                <p class="i-label">WhatsApp</p>
                                <p class="i-value"><a href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $settings['whatsapp'])) ?>" target="_blank" rel="noopener">Message us</a></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($mapIsSafe): ?>
                    <div class="map-embed"><?= $mapRaw /* validated Google Maps iframe only */ ?></div>
                <?php elseif (!empty($settings['address'])): ?>
                    <div class="map-embed">
                        <a class="btn btn-pill btn-outline" style="margin-top:8px;" target="_blank" rel="noopener"
                           href="https://www.google.com/maps/search/?api=1&query=<?= e(urlencode($settings['address'])) ?>">
                            View on Google Maps <span class="material-symbols-outlined">open_in_new</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: form -->
            <div data-reveal="fade">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= e($success) ?></div>
                <?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        Please check the highlighted fields and try again.
                        <?php foreach ($errors as $key => $msg): if (is_int($key)): ?>
                            <div><?= e($msg) ?></div>
                        <?php endif; endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="form-card">
                    <form method="POST" action="<?= e(url('public/contact.php')) ?>" data-validate novalidate>
                        <?= csrf_field() ?>

                        <div class="field <?= isset($errors['name']) ? 'has-error' : '' ?>">
                            <label for="name">Your Name *</label>
                            <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required>
                            <span class="err-msg"><?= e($errors['name'] ?? '') ?></span>
                        </div>

                        <div class="form-row">
                            <div class="field <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                                <label for="phone">Phone *</label>
                                <input type="text" id="phone" name="phone" value="<?= e($old['phone']) ?>" required>
                                <span class="err-msg"><?= e($errors['phone'] ?? '') ?></span>
                            </div>
                            <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?= e($old['email']) ?>">
                                <span class="err-msg"><?= e($errors['email'] ?? '') ?></span>
                            </div>
                        </div>

                        <div class="field <?= isset($errors['subject']) ? 'has-error' : '' ?>">
                            <label for="subject">Subject *</label>
                            <input type="text" id="subject" name="subject" value="<?= e($old['subject']) ?>" required>
                            <span class="err-msg"><?= e($errors['subject'] ?? '') ?></span>
                        </div>

                        <div class="field <?= isset($errors['message']) ? 'has-error' : '' ?>">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" required><?= e($old['message']) ?></textarea>
                            <span class="err-msg"><?= e($errors['message'] ?? '') ?></span>
                        </div>

                        <button type="submit" class="btn btn-pill btn-coral btn-lg btn-block shine-effect">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
