<?php
/**
 * HAVELI — Shared Frontend Footer
 * Include at bottom of every page: require_once 'includes/footer.php';
 */
$footerText   = getSetting('footer_text', '© 2024 Haveli. All rights reserved.');
$fbUrl        = getSetting('facebook_url', '#');
$igUrl        = getSetting('instagram_url', '#');
$twUrl        = getSetting('twitter_url', '#');
$waNum        = getSetting('whatsapp_number', '');
$sitePhone    = getSetting('site_phone', '');
$siteEmail    = getSetting('site_email', '');
$siteAddress  = getSetting('site_address', '');
$restHours    = getSetting('restaurant_hours', 'Mon-Sun: 12PM – 12AM');
$mapsUrl      = getSetting('maps_url', '#');
$gaCode       = getSetting('google_analytics', '');
?>
</main><!-- /main -->

<!-- FOOTER -->
<footer class="footer" role="contentinfo">
  <div class="footer-inner">
    <div class="footer-grid reveal">

      <!-- Brand Column -->
      <div>
        <div class="footer-logo"><?= htmlspecialchars(getSetting('site_name','Haveli')) ?></div>
        <p class="footer-desc"><?= htmlspecialchars(getSetting('site_description','Experience the grandeur of Mughlai cuisine — slow-cooked perfection, royal spices, and timeless traditions.')) ?></p>

        <!-- Social Links -->
        <div class="footer-social">
          <?php if ($fbUrl && $fbUrl !== '#'): ?>
          <a href="<?= htmlspecialchars($fbUrl) ?>" target="_blank" rel="noopener" aria-label="Facebook">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
          </a>
          <?php endif; ?>
          <?php if ($igUrl && $igUrl !== '#'): ?>
          <a href="<?= htmlspecialchars($igUrl) ?>" target="_blank" rel="noopener" aria-label="Instagram">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
          </a>
          <?php endif; ?>
          <?php if ($twUrl && $twUrl !== '#'): ?>
          <a href="<?= htmlspecialchars($twUrl) ?>" target="_blank" rel="noopener" aria-label="Twitter / X">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          </a>
          <?php endif; ?>
          <?php if ($waNum): ?>
          <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$waNum) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.558 4.116 1.532 5.845L.058 23.486a.5.5 0 0 0 .609.61l5.74-1.474A11.943 11.943 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.885 0-3.652-.51-5.17-1.402l-.37-.22-3.404.874.893-3.312-.241-.382A9.944 9.944 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
          </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Quick Links -->
      <div>
        <h3 class="footer-heading">Quick Links</h3>
        <ul class="footer-links">
          <li><a href="<?= BASE_URL ?>/index.php">Home</a></li>
          <li><a href="<?= BASE_URL ?>/menu.php">Our Menu</a></li>
          <li><a href="<?= BASE_URL ?>/offers.php">Offers & Coupons</a></li>
          <li><a href="<?= BASE_URL ?>/about.php">About Haveli</a></li>
          <li><a href="<?= BASE_URL ?>/contact.php">Contact Us</a></li>
        </ul>
      </div>

      <!-- Account -->
      <div>
        <h3 class="footer-heading">Account</h3>
        <ul class="footer-links">
          <?php if (isLoggedIn()): ?>
          <li><a href="<?= BASE_URL ?>/profile.php">My Profile</a></li>
          <li><a href="<?= BASE_URL ?>/orders.php">Order History</a></li>
          <li><a href="<?= BASE_URL ?>/track.php">Track Order</a></li>
          <li><a href="<?= BASE_URL ?>/logout.php">Logout</a></li>
          <?php else: ?>
          <li><a href="<?= BASE_URL ?>/login.php">Login</a></li>
          <li><a href="<?= BASE_URL ?>/register.php">Sign Up</a></li>
          <li><a href="<?= BASE_URL ?>/track.php">Track Order</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- Contact Info -->
      <div>
        <h3 class="footer-heading">Contact</h3>
        <ul class="footer-links">
          <?php if ($sitePhone): ?>
          <li>
            <a href="tel:<?= preg_replace('/\s+/','',$sitePhone) ?>">
              📞 <?= htmlspecialchars($sitePhone) ?>
            </a>
          </li>
          <?php endif; ?>
          <?php if ($siteEmail): ?>
          <li>
            <a href="mailto:<?= htmlspecialchars($siteEmail) ?>">
              ✉️ <?= htmlspecialchars($siteEmail) ?>
            </a>
          </li>
          <?php endif; ?>
          <?php if ($siteAddress): ?>
          <li>
            <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener">
              📍 <?= htmlspecialchars($siteAddress) ?>
            </a>
          </li>
          <?php endif; ?>
          <?php if ($restHours): ?>
          <li style="color:var(--text-muted);font-size:.85rem;margin-top:8px">
            🕐 <?= htmlspecialchars($restHours) ?>
          </li>
          <?php endif; ?>
        </ul>
      </div>

    </div><!-- /footer-grid -->

    <!-- Footer Bottom -->
    <div class="footer-bottom">
      <span><?= htmlspecialchars($footerText) ?></span>
      <div style="display:flex;gap:20px;flex-wrap:wrap">
        <a href="#" style="color:var(--text-muted);text-decoration:none;font-size:.82rem;transition:color .2s" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Privacy Policy</a>
        <a href="#" style="color:var(--text-muted);text-decoration:none;font-size:.82rem;transition:color .2s" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-muted)'">Terms of Service</a>
        <span style="color:var(--text-muted)">Crafted with ♥ in Lahore</span>
      </div>
    </div>
  </div>
</footer>

<!-- Scripts -->
<script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>
<?php if (isset($extraJS)): ?>
<script src="<?= BASE_URL ?>/assets/js/<?= $extraJS ?>" defer></script>
<?php endif; ?>

<?php if ($gaCode): ?>
<!-- Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($gaCode) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= htmlspecialchars($gaCode) ?>');
</script>
<?php endif; ?>
</body>
</html>
