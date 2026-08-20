<?php
/** @var array $c */
use Core\{Csrf, Icons, Settings};
$turnstile = (string)Settings::get('turnstile_site_key', '');
?>
<section id="contact" class="contact-section section-pad">
  <div class="wrap">
    <div class="contact-grid">
      <div class="contact-info reveal">
        <?php if (($c['eyebrow'] ?? '') !== ''): ?><div class="eyebrow"><?= e($c['eyebrow']) ?></div><?php endif; ?>
        <h2 class="contact-title"><?= headline($c['headline'] ?? '') ?></h2>
        <?php if (($c['body'] ?? '') !== ''): ?><p class="contact-body"><?= para($c['body']) ?></p><?php endif; ?>
        <div class="contact-channels">
          <?php foreach (rows($c, 'contact_items') as $item): ?>
            <?php
            $type  = (string)($item['type'] ?? 'phone');
            $value = (string)($item['value'] ?? '');
            $href  = match ($type) {
                'phone'    => 'tel:+' . phone_digits($value),
                'whatsapp' => 'https://wa.me/' . phone_digits($value),
                'email'    => 'mailto:' . $value,
                default    => '',
            };
            ?>
            <div class="channel">
              <?= Icons::svg($type, 'icon', 1.4) ?>
              <div>
                <div class="label"><?= e($item['label'] ?? '') ?></div>
                <?php if ($href !== ''): ?>
                  <a class="value" href="<?= e($href) ?>"<?= $type === 'whatsapp' ? ' target="_blank" rel="noopener"' : '' ?>><?= e($value) ?></a>
                <?php else: ?>
                  <div class="value"><?= e($value) ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-card reveal">
        <h3><?= e($c['form_title'] ?? '') ?></h3>
        <form id="enquiryForm" method="post" action="/enquiry" data-enquiry novalidate>
          <?= Csrf::deferredField() ?>
          <input type="hidden" name="source_page" value="#contact">
          <input type="hidden" name="form_started" value="<?= time() ?>">
          <div class="hp-field" aria-hidden="true">
            <label for="website">Website</label>
            <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
          </div>
          <div class="form-row">
            <label for="fname"><?= e($c['name_label'] ?? 'Full Name') ?></label>
            <input id="fname" name="name" type="text" autocomplete="name" required>
          </div>
          <div class="form-row">
            <label for="femail"><?= e($c['email_label'] ?? 'Email Address') ?></label>
            <input id="femail" name="email" type="email" autocomplete="email" required>
          </div>
          <div class="form-row">
            <label for="fphone"><?= e($c['phone_label'] ?? 'Phone Number') ?></label>
            <input id="fphone" name="phone" type="tel" autocomplete="tel">
          </div>
          <div class="form-row">
            <label for="finterest"><?= e($c['interest_label'] ?? "I'm Interested In") ?></label>
            <select id="finterest" name="interest">
              <?php foreach (rows($c, 'interest_options') as $option): ?>
                <option value="<?= e($option['label'] ?? '') ?>"><?= e($option['label'] ?? '') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-row">
            <label for="fmsg"><?= e($c['message_label'] ?? 'Message') ?></label>
            <textarea id="fmsg" name="message" rows="3"></textarea>
          </div>
          <?php if ($turnstile !== ''): ?>
            <div class="cf-turnstile" data-sitekey="<?= e($turnstile) ?>"></div>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
          <?php endif; ?>
          <button type="submit" class="btn btn-primary form-submit"><?= e($c['submit_label'] ?? 'Send Enquiry') ?></button>
        </form>
        <?php if (($c['response_note'] ?? '') !== ''): ?>
          <p class="form-note"><?= e($c['response_note']) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
