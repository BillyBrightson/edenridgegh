<?php
/** @var array $c */
use Core\Media;
$plans = rows($c, 'plans');
?>
<section id="floorplans" class="section-pad plans-section">
  <div class="wrap">
    <div class="section-head reveal">
      <?php if (($c['eyebrow'] ?? '') !== ''): ?><div class="eyebrow"><?= e($c['eyebrow']) ?></div><?php endif; ?>
      <h2><?= headline($c['headline'] ?? '') ?></h2>
      <?php if (($c['lede'] ?? '') !== ''): ?><p class="lede"><?= para($c['lede']) ?></p><?php endif; ?>
    </div>

    <div class="plans-tabs reveal" role="tablist" aria-label="Floor plans">
      <?php foreach ($plans as $i => $plan): ?>
        <button class="plan-btn<?= $i === 0 ? ' active' : '' ?>" data-plan="plan-<?= $i ?>"
                role="tab" id="plan-tab-<?= $i ?>" aria-controls="plan-<?= $i ?>"
                aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" tabindex="<?= $i === 0 ? '0' : '-1' ?>">
          <?= e($plan['tab_label'] ?? '') ?>
        </button>
      <?php endforeach; ?>
    </div>

    <?php foreach ($plans as $i => $plan): ?>
      <?php
        // A second image switches the panel to a two-image layout: both plans
        // side by side with a short blurb and optional button beneath, in
        // place of the schedule table. Any plan with no second image keeps
        // the original image-and-table layout exactly as it was.
        $twoImage = !empty($plan['image_right']);
        $title    = (string)($plan['title'] ?? '');
        $subtitle = (string)($plan['subtitle'] ?? '');
      ?>

      <?php if ($twoImage): ?>
        <div class="plan-panel plan-panel-two<?= $i === 0 ? ' active' : '' ?> reveal" id="plan-<?= $i ?>"
             role="tabpanel" aria-labelledby="plan-tab-<?= $i ?>"<?= $i === 0 ? '' : ' hidden' ?>>
          <?php if ($title !== '' || $subtitle !== ''): ?>
            <div class="plan-head">
              <?php if ($title !== ''): ?><h3><?= e($title) ?></h3><?php endif; ?>
              <?php if ($subtitle !== ''): ?><div class="sub"><?= e($subtitle) ?></div><?php endif; ?>
            </div>
          <?php endif; ?>
          <?php
            $imagePairs = [
                'left'  => ['image' => $plan['image'] ?? null,       'label' => (string)($plan['image_label'] ?? '')],
                'right' => ['image' => $plan['image_right'] ?? null, 'label' => (string)($plan['image_right_label'] ?? '')],
            ];
          ?>
          <div class="plan-images">
            <?php foreach ($imagePairs as $side => $pair): ?>
              <div class="plan-img-column">
                <?php if ($pair['label'] !== ''): ?>
                  <h4 class="plan-img-label"><?= e($pair['label']) ?></h4>
                <?php endif; ?>
                <div class="plan-img-wrap">
                  <?php /* Eager: .plan-img-wrap sizes these with width:auto, so a lazy
                           image would have no box to intersect and would never load. */ ?>
                  <?= Media::img($pair['image'], [
                      'sizes'   => '(max-width: 860px) 100vw, 45vw',
                      'loading' => 'eager',
                      'data'    => [
                          'lightbox'         => Media::url($pair['image'], 1920),
                          'lightbox-caption' => trim(($pair['label'] !== '' ? $pair['label'] : $title) . ' — ' . $subtitle, ' —'),
                      ],
                  ]) ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php
            $blurb        = (string)($plan['blurb'] ?? '');
            $buttonLabel  = trim((string)($plan['button_label'] ?? ''));
            $buttonTarget = trim((string)($plan['button_target'] ?? ''));
          ?>
          <?php if ($blurb !== '' || $buttonLabel !== ''): ?>
            <div class="plan-blurb">
              <?php if ($blurb !== ''): ?><p><?= para($blurb) ?></p><?php endif; ?>
              <?php if ($buttonLabel !== ''): ?>
                <a class="btn btn-primary" href="<?= e($buttonTarget !== '' ? $buttonTarget : '#contact') ?>"><?= e($buttonLabel) ?></a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="plan-panel<?= $i === 0 ? ' active' : '' ?> reveal" id="plan-<?= $i ?>"
             role="tabpanel" aria-labelledby="plan-tab-<?= $i ?>"<?= $i === 0 ? '' : ' hidden' ?>>
          <div class="plan-img-wrap">
            <?php /* Eager: .plan-img-wrap sizes these with width:auto, so a lazy
                     image would have no box to intersect and would never load. */ ?>
            <?= Media::img($plan['image'] ?? null, [
                'sizes'   => '(max-width: 860px) 100vw, 55vw',
                'loading' => 'eager',
                'data'  => [
                    'lightbox' => Media::url($plan['image'] ?? null, 1920),
                    'lightbox-caption' => trim($title . ' — ' . $subtitle, ' —'),
                ],
            ]) ?>
          </div>
          <div class="plan-details">
            <h3><?= e($title) ?></h3>
            <?php if ($subtitle !== ''): ?><div class="sub"><?= e($subtitle) ?></div><?php endif; ?>
            <hr class="rule">
            <table class="plan-table">
              <caption class="sr-only"><?= e($title . ' schedule of areas') ?></caption>
              <tbody>
              <?php foreach (rows($plan, 'rows') as $row): ?>
                <tr>
                  <td><?= !empty($row['is_total']) ? '<strong>' . e($row['label'] ?? '') . '</strong>' : e($row['label'] ?? '') ?></td>
                  <td><?= !empty($row['is_total']) ? '<strong>' . e($row['value'] ?? '') . '</strong>' : e($row['value'] ?? '') ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <?php if (($plan['note'] ?? '') !== ''): ?><p class="plan-note"><?= para($plan['note']) ?></p><?php endif; ?>
            <?php if (($plan['cta_text'] ?? '') !== ''): ?><div class="plan-dl"><?= e($plan['cta_text']) ?></div><?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</section>
