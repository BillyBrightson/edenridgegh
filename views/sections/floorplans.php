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
                  'lightbox-caption' => trim(($plan['title'] ?? '') . ' — ' . ($plan['subtitle'] ?? ''), ' —'),
              ],
          ]) ?>
        </div>
        <div class="plan-details">
          <h3><?= e($plan['title'] ?? '') ?></h3>
          <?php if (($plan['subtitle'] ?? '') !== ''): ?><div class="sub"><?= e($plan['subtitle']) ?></div><?php endif; ?>
          <hr class="rule">
          <table class="plan-table">
            <caption class="sr-only"><?= e(($plan['title'] ?? '') . ' schedule of areas') ?></caption>
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
    <?php endforeach; ?>
  </div>
</section>
