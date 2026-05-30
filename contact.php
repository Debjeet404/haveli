<?php
require_once 'includes/config.php';
$pageTitle = 'Contact Us';
$siteName  = getSetting('site_name','Haveli');
$phone     = getSetting('site_phone','');
$phone2    = getSetting('site_phone2','');
$email     = getSetting('site_email','');
$address   = getSetting('site_address','');
$hours     = getSetting('restaurant_hours','Mon-Sun: 12PM – 12AM');
$mapsUrl   = getSetting('maps_url','#');
$waNum     = getSetting('whatsapp_number','');
$csrf      = generateCSRF();
$success   = false; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) { $error = 'Security error.'; }
    else {
        $name    = sanitize($_POST['name'] ?? '');
        $cemail  = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        if ($name && $cemail && $message) {
            // In production, send email here
            $success = true;
        } else { $error = 'Please fill in all required fields.'; }
    }
}
require_once 'includes/header.php';
?>

<style>
.contact-page { padding: calc(var(--nav-height)+60px) clamp(16px,4vw,48px) 80px; }
.contact-inner { max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1fr 480px;gap:48px;align-items:start; }
.contact-info-card { background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-xl);padding:36px;backdrop-filter:blur(20px); }
.contact-form-card { background:var(--glass);border:1px solid var(--glass-border);border-radius:var(--radius-xl);padding:36px;backdrop-filter:blur(20px); }
.contact-info-item { display:flex;gap:16px;align-items:flex-start;padding:16px 0;border-bottom:1px solid var(--border); }
.contact-info-item:last-child { border-bottom:none; }
.contact-info-icon { width:44px;height:44px;background:rgba(255,107,0,.1);border:1px solid rgba(255,107,0,.2);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0; }
.contact-info-label { font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-bottom:3px; }
.contact-info-value { font-size:.92rem;color:var(--text-primary);line-height:1.6; }
.contact-info-value a { color:var(--primary);text-decoration:none; }
.success-msg { background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:var(--radius-md);padding:16px 20px;color:#22c55e;margin-bottom:20px;text-align:center; }
@media(max-width:900px){.contact-inner{grid-template-columns:1fr;}}
</style>

<div class="contact-page">
  <div style="max-width:1100px;margin:0 auto;margin-bottom:40px;text-align:center">
    <p class="section-label reveal" style="justify-content:center">Get In Touch</p>
    <h1 class="section-title reveal" style="font-size:clamp(2rem,4vw,3rem)">
      Contact <span class="highlight"><?= htmlspecialchars($siteName) ?></span>
    </h1>
    <p style="color:var(--text-secondary);font-family:var(--font-elegant);font-size:1.1rem;max-width:500px;margin:0 auto" class="reveal">
      We'd love to hear from you. Reach out for orders, feedback, or any queries.
    </p>
  </div>

  <div class="contact-inner">
    <!-- Info -->
    <div>
      <div class="contact-info-card reveal">
        <h2 style="font-family:var(--font-display);font-size:1.2rem;font-weight:700;margin-bottom:8px">Contact Information</h2>
        <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:20px">Reach us through any of these channels.</p>

        <?php if ($phone): ?>
        <div class="contact-info-item">
          <div class="contact-info-icon">📞</div>
          <div><div class="contact-info-label">Phone</div><div class="contact-info-value"><a href="tel:<?= preg_replace('/\s+/','',$phone) ?>"><?= htmlspecialchars($phone) ?></a><?php if ($phone2): ?><br><a href="tel:<?= preg_replace('/\s+/','',$phone2) ?>"><?= htmlspecialchars($phone2) ?></a><?php endif; ?></div></div>
        </div>
        <?php endif; ?>

        <?php if ($email): ?>
        <div class="contact-info-item">
          <div class="contact-info-icon">✉️</div>
          <div><div class="contact-info-label">Email</div><div class="contact-info-value"><a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></div></div>
        </div>
        <?php endif; ?>

        <?php if ($address): ?>
        <div class="contact-info-item">
          <div class="contact-info-icon">📍</div>
          <div><div class="contact-info-label">Address</div><div class="contact-info-value"><?= htmlspecialchars($address) ?></div></div>
        </div>
        <?php endif; ?>

        <?php if ($hours): ?>
        <div class="contact-info-item">
          <div class="contact-info-icon">🕐</div>
          <div><div class="contact-info-label">Hours</div><div class="contact-info-value"><?= htmlspecialchars($hours) ?></div></div>
        </div>
        <?php endif; ?>

        <?php if ($waNum): ?>
        <div class="contact-info-item">
          <div class="contact-info-icon">💬</div>
          <div><div class="contact-info-label">WhatsApp</div><div class="contact-info-value"><a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$waNum) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($waNum) ?></a></div></div>
        </div>
        <?php endif; ?>
      </div>

      <!-- WhatsApp CTA -->
      <?php if ($waNum): ?>
      <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$waNum) ?>?text=Hello%20<?= urlencode($siteName) ?>,%20I%20would%20like%20to%20place%20an%20order." target="_blank" rel="noopener"
         class="btn btn-full reveal"
         style="margin-top:16px;background:linear-gradient(135deg,#25D366,#128C7E);color:#fff;border:none;padding:14px;border-radius:var(--radius-md);justify-content:center;text-decoration:none;display:flex;align-items:center;gap:10px;font-weight:600">
        💬 Chat on WhatsApp
      </a>
      <?php endif; ?>

      <?php if ($mapsUrl && $mapsUrl !== '#'): ?>
      <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener"
         class="btn btn-outline btn-full reveal" style="margin-top:10px">
        🗺️ View on Google Maps
      </a>
      <?php endif; ?>
    </div>

    <!-- Form -->
    <div class="contact-form-card reveal">
      <h2 style="font-family:var(--font-display);font-size:1.2rem;font-weight:700;margin-bottom:20px">Send Us a Message</h2>

      <?php if ($success): ?>
      <div class="success-msg">
        ✅ Thank you! Your message has been sent. We'll respond within 24 hours.
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:var(--radius-md);padding:12px;color:#ef4444;margin-bottom:20px">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (!$success): ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <div class="form-group">
          <label class="form-label">Your Name *</label>
          <input type="text" name="name" class="form-input" required placeholder="Full name" value="<?= htmlspecialchars($_POST['name'] ?? (isLoggedIn() ? $_SESSION['user_name'] ?? '' : '')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-input" required placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? (isLoggedIn() ? $_SESSION['user_email'] ?? '' : '')) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Subject</label>
          <select name="subject" class="form-select">
            <option value="general">General Inquiry</option>
            <option value="order">Order Issue</option>
            <option value="feedback">Feedback</option>
            <option value="catering">Catering / Events</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Message *</label>
          <textarea name="message" class="form-textarea" required rows="5" placeholder="How can we help you?"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg">
          📨 Send Message
        </button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
