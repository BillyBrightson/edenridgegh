<?php
/** @var array $c */
use Core\{Media, Settings, Video};
$videoUrl  = Video::embedUrl();
$gated     = Settings::bool('video_gate_enabled', true);
$align     = ($c['align'] ?? 'right') === 'left' ? ' left' : '';
$hasVideo  = $videoUrl !== '';
?>
<section class="video-band" id="video">
  <?= Media::img($c['background_image'] ?? null, ['class' => 'video-band-img', 'sizes' => '100vw']) ?>
  <div class="video-band-scrim"></div>
  <div class="wrap reveal">
    <div class="video-inner<?= $align ?>">
      <?php if (($c['eyebrow'] ?? '') !== ''): ?><div class="eyebrow"><?= e($c['eyebrow']) ?></div><?php endif; ?>
      <h2><?= headline($c['headline'] ?? '') ?></h2>
      <?php if (($c['body'] ?? '') !== ''): ?><p><?= para($c['body']) ?></p><?php endif; ?>
      <div class="video-cta-wrap">
        <?php if ($hasVideo && $gated): ?>
          <button type="button" class="btn btn-primary" data-video-request><?= e($c['cta_label'] ?? '') ?></button>
        <?php elseif ($hasVideo): ?>
          <button type="button" class="btn btn-primary" data-video-play="<?= e($videoUrl) ?>"><?= e($c['cta_label'] ?? '') ?></button>
        <?php else: ?>
          <a href="#contact" class="btn btn-primary"><?= e($c['cta_label'] ?? '') ?></a>
        <?php endif; ?>
      </div>
      <?php if ($hasVideo): ?>
        <div class="video-frame" id="videoFrame" hidden></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($hasVideo && $gated): ?>
<div class="modal" id="videoModal" role="dialog" aria-modal="true" aria-labelledby="videoModalTitle" aria-hidden="true">
  <button class="modal-close" type="button" data-modal-close aria-label="Close">&times;</button>
  <div class="modal-card">
    <h3 id="videoModalTitle">Request the video tour</h3>
    <p class="modal-lede">Tell us where to send it and the walkthrough will play here straight away — we will email you the link as well.</p>
    <form method="post" action="/enquiry" data-enquiry>
      <?= \Core\Csrf::deferredField() ?>
      <input type="hidden" name="interest" value="Requesting the video tour">
      <input type="hidden" name="video_request" value="1">
      <input type="hidden" name="source_page" value="#video">
      <input type="hidden" name="form_started" value="<?= time() ?>">
      <div class="hp-field" aria-hidden="true"><label for="vt-website">Website</label><input id="vt-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
      <div class="form-row"><label for="vt-name">Full Name</label><input id="vt-name" name="name" type="text" autocomplete="name" required></div>
      <div class="form-row"><label for="vt-email">Email Address</label><input id="vt-email" name="email" type="email" autocomplete="email" required></div>
      <div class="form-row"><label for="vt-phone">Phone Number <span class="optional">(optional)</span></label><input id="vt-phone" name="phone" type="tel" autocomplete="tel"></div>
      <button type="submit" class="btn btn-primary form-submit">Watch the walkthrough</button>
    </form>
  </div>
</div>
<?php endif; ?>
