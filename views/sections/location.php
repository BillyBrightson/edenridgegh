<?php
/** @var array $c */
use Core\Media;
$mapType = (string)($c['map_type'] ?? 'stylised');
?>
<section id="location" class="loc-section section-pad on-dark">
  <div class="wrap">
    <div class="section-head invert reveal">
      <?php if (($c['eyebrow'] ?? '') !== ''): ?><div class="eyebrow on-light"><?= e($c['eyebrow']) ?></div><?php endif; ?>
      <h2><?= headline($c['headline'] ?? '') ?></h2>
      <?php if (($c['lede'] ?? '') !== ''): ?><p class="lede"><?= para($c['lede']) ?></p><?php endif; ?>
    </div>
    <div class="loc-grid reveal">
      <div>
        <?php foreach (rows($c, 'columns') as $column): ?>
          <div class="loc-col">
            <h3><?= e($column['heading'] ?? '') ?></h3>
            <ul>
              <?php foreach (rows($column, 'rows') as $row): ?>
                <li><span><?= e($row['label'] ?? '') ?></span><span><?= e($row['value'] ?? '') ?></span></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($mapType !== 'none'): ?>
        <div>
          <div class="map-frame">
            <?php if ($mapType === 'stylised'): ?>
              <svg viewBox="0 0 400 300" aria-hidden="true">
                <g stroke="var(--line-map)" stroke-width="1">
                  <line x1="0" y1="60" x2="400" y2="60"/>
                  <line x1="0" y1="130" x2="400" y2="130"/>
                  <line x1="0" y1="200" x2="400" y2="200"/>
                  <line x1="80" y1="0" x2="80" y2="300"/>
                  <line x1="200" y1="0" x2="200" y2="300"/>
                  <line x1="320" y1="0" x2="320" y2="300"/>
                </g>
                <path d="M0 180 C 100 150, 180 220, 400 170" stroke="var(--line-map-strong)" stroke-width="2" fill="none"/>
              </svg>
              <div class="map-pin">
                <div class="dot"></div>
                <div class="map-label"><?= e($c['map_pin_label'] ?? '') ?></div>
              </div>
            <?php elseif ($mapType === 'image'): ?>
              <?= Media::img($c['map_image'] ?? null, ['class' => 'map-static', 'sizes' => '(max-width: 900px) 100vw, 50vw']) ?>
              <div class="map-pin">
                <div class="dot"></div>
                <div class="map-label"><?= e($c['map_pin_label'] ?? '') ?></div>
              </div>
            <?php elseif ($mapType === 'iframe' && ($c['map_embed_url'] ?? '') !== ''): ?>
              <iframe class="map-embed" src="<?= e($c['map_embed_url']) ?>" loading="lazy"
                      referrerpolicy="no-referrer-when-downgrade"
                      title="<?= e($c['map_pin_label'] ?: 'Map of Eden Ridge') ?>"></iframe>
            <?php endif; ?>
          </div>
          <?php if (($c['map_link_label'] ?? '') !== '' && ($c['map_link_url'] ?? '') !== ''): ?>
            <a class="map-link" href="<?= e($c['map_link_url']) ?>" target="_blank" rel="noopener"><?= e($c['map_link_label']) ?></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php if (($c['closing_line'] ?? '') !== ''): ?>
      <p class="loc-closing"><?= e($c['closing_line']) ?></p>
    <?php endif; ?>
  </div>
</section>
