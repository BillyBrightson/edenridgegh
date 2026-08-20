<?php
/** @var array $c */
use Core\{Icons, Settings};
$year = gmdate('Y');
$socials = [];
if (!empty($c['show_socials'])) {
    foreach (Icons::socialOptions() as $platform => $label) {
        $url = (string)Settings::get('social_' . $platform . '_url', '');
        if ($url !== '') {
            $socials[$platform] = ['label' => $label, 'url' => $url];
        }
    }
}
$waNumber = phone_digits((string)Settings::get('contact_whatsapp', ''));
?>
<footer>
  <div class="wrap">
    <div class="footer-top">
      <div>
        <a href="#home" class="footer-logo">
          <?= Icons::mark() ?>
          <span class="footer-wordmark"><?= e($c['logo_text'] ?? '') ?></span>
        </a>
        <?php if ($socials): ?>
          <div class="footer-socials">
            <?php foreach ($socials as $platform => $social): ?>
              <a href="<?= e($social['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($social['label']) ?>">
                <?= Icons::social($platform) ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="footer-links">
        <?php foreach (rows($c, 'link_groups') as $group): ?>
          <div class="footer-col">
            <h5><?= e($group['heading'] ?? '') ?></h5>
            <?php foreach (rows($group, 'links') as $link): ?>
              <a href="<?= e($link['target'] ?? '#') ?>"><?= e($link['label'] ?? '') ?></a>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="footer-bottom">
      <span><?= e(str_replace('{year}', $year, (string)($c['copyright'] ?? ''))) ?></span>
      <span><?= e($c['address_line'] ?? '') ?></span>
      <?php if (!empty($c['show_legal_links'])): ?>
        <span class="footer-legal">
          <?php foreach (rows($c, 'legal_links') as $link): ?>
            <a href="<?= e($link['target'] ?? '#') ?>"><?= e($link['label'] ?? '') ?></a>
          <?php endforeach; ?>
        </span>
      <?php endif; ?>
    </div>
    <?php if (($c['disclaimer'] ?? '') !== ''): ?>
      <p class="disclaimer"><?= para($c['disclaimer']) ?></p>
    <?php endif; ?>
  </div>
</footer>

<?php if (!empty($c['show_whatsapp_float']) && $waNumber !== ''): ?>
  <a class="wa-float" href="https://wa.me/<?= e($waNumber) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
      <path d="M17.6 6.4A8 8 0 105.7 17.8L4 20l2.3-1.6a8 8 0 0011.3-12z"/>
    </svg>
  </a>
<?php endif; ?>

<?php if (Settings::bool('cookie_consent_enabled', false)): ?>
  <div class="consent" id="consentBar" hidden>
    <div class="wrap consent-inner">
      <p><?= e((string)Settings::get('cookie_consent_text', '')) ?></p>
      <div class="consent-actions">
        <button type="button" class="btn btn-ghost" data-consent="denied">Decline</button>
        <button type="button" class="btn btn-primary" data-consent="granted">Accept</button>
      </div>
    </div>
  </div>
<?php endif; ?>
