<?php
include('./includes/config.php');
include('./includes/functions.php');

$account = current_account();
$form = [
    'name' => $account['full_name'] ?? '',
    'email' => $account['email'] ?? '',
    'subject' => '',
    'message' => '',
];
$errors = [];

if (is_post()) {
    csrf_verify();
    foreach ($form as $key => $unused) {
        $value = $_POST[$key] ?? '';
        $form[$key] = is_string($value) ? trim(str_replace("\r\n", "\n", $value)) : '';
    }
    $trap = $_POST['website'] ?? '';

    if ($form['name'] === '') {
        $errors['name'] = 'Enter your name.';
    } elseif (mb_strlen($form['name']) < 2 || mb_strlen($form['name']) > 100) {
        $errors['name'] = 'Your name must be 2 to 100 characters.';
    } elseif (preg_match('/[\x00-\x1F\x7F]/', $form['name'])) {
        $errors['name'] = 'Your name contains characters that are not allowed.';
    }

    if ($form['email'] === '') {
        $errors['email'] = 'Enter your email address.';
    } elseif (!valid_email($form['email'])) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if ($form['subject'] === '') {
        $errors['subject'] = 'Enter a subject.';
    } elseif (mb_strlen($form['subject']) < 3 || mb_strlen($form['subject']) > 150) {
        $errors['subject'] = 'The subject must be 3 to 150 characters.';
    } elseif (preg_match('/[\x00-\x1F\x7F]/', $form['subject'])) {
        $errors['subject'] = 'The subject must be a single line of plain text.';
    }

    if ($form['message'] === '') {
        $errors['message'] = 'Write your message.';
    } elseif (mb_strlen($form['message']) < 10) {
        $errors['message'] = 'Your message must be at least 10 characters.';
    } elseif (mb_strlen($form['message']) > 2000) {
        $errors['message'] = 'Your message must be 2,000 characters or fewer.';
    }

    if (is_string($trap) && $trap !== '') {
        flash('success', 'Thanks! Your message was sent. We will reply by email.');
        redirect('contact.php');
    }

    if ($errors) {
        flash('error', 'Please fix the highlighted fields and send your message again.');
    } else {
        q('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)', [$form['name'], $form['email'], $form['subject'], $form['message']]);
        flash('success', 'Thanks! Your message was sent. We will reply by email.');
        redirect('contact.php');
    }
}

$title = 'Contact Us';
include('./includes/header.php');
?>
<div class="page-head">
    <div>
        <h1>Contact us</h1>
        <p>Questions about a game, an order or your account? Send us a message and we will reply by email.</p>
    </div>
</div>
<div class="contact-layout">
    <div class="card">
        <div class="card-head"><h2>Send us a message</h2></div>
        <form method="post" class="form" novalidate>
            <?= csrf_field() ?>
            <div class="hp-field" aria-hidden="true">
                <label for="website">Leave this field empty</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>
            <div class="row">
                <div class="field">
                    <label for="name">Your name</label>
                    <input type="text" id="name" name="name" maxlength="100" value="<?= e($form['name']) ?>" required autocomplete="name">
                    <?= field_error($errors, 'name') ?>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" maxlength="100" value="<?= e($form['email']) ?>" required autocomplete="email">
                    <?= field_error($errors, 'email') ?>
                </div>
            </div>
            <div class="field">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" maxlength="150" value="<?= e($form['subject']) ?>" required>
                <?= field_error($errors, 'subject') ?>
            </div>
            <div class="field">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="7" maxlength="2000" required><?= e($form['message']) ?></textarea>
                <?= field_error($errors, 'message') ?>
                <small>Up to 2,000 characters. For order questions, include your order number.</small>
            </div>
            <button class="btn btn-primary" type="submit">Send message</button>
        </form>
    </div>

    <aside class="contact-side">
        <div class="card contact-info">
            <h2>Store details</h2>
            <div class="info-item">
                <span class="icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-5.4-7-11a7 7 0 0 1 14 0c0 5.6-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
                <div><strong>Location</strong><span><?= e(CONTACT_ADDRESS) ?></span></div>
            </div>
            <div class="info-item">
                <span class="icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span>
                <div><strong>Email</strong><span><a href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= e(CONTACT_EMAIL) ?></a></span></div>
            </div>
            <div class="info-item">
                <span class="icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg></span>
                <div><strong>Phone</strong><span><?= e(CONTACT_PHONE) ?></span></div>
            </div>
            <div class="info-item">
                <span class="icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                <div><strong>Store hours</strong><span><?= e(CONTACT_HOURS) ?></span></div>
            </div>
        </div>
        <div class="card contact-help">
            <h2>Looking for a quick answer?</h2>
            <p>Many common questions about orders, accounts and reviews are answered in our help centre.</p>
            <p><a class="btn btn-ghost" href="<?= e(url('faq.php')) ?>">Visit the FAQ</a></p>
            <small>Your message is stored so the store can reply. See our <a href="<?= e(url('privacy.php')) ?>">Privacy Policy</a>.</small>
        </div>
    </aside>
</div>
<?php include('./includes/footer.php'); ?>
