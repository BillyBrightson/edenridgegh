<?php
/** @var array $sections */
/** @var array $section */
/** @var array $schema */
/** @var array $content */
/** @var bool  $hasDraft */
/** @var array $revisions */
use Core\{AdminUi, Config, Csrf, Content, Schema, View};

$siteUrl = rtrim((string)Config::get('site_url', ''), '/');
$anchor  = $schema['anchor'] ?? null;
$preview = $siteUrl . '/' . ($anchor ? '#' . $anchor : '');
?>
<div class="page-head">
  <div>
    <h2>Home page</h2>
    <p>Sixteen sections in the order they appear on the site. Drag to reorder, toggle to hide.</p>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost btn-sm" href="<?= e($preview) ?>" target="_blank" rel="noopener"><?= AdminUi::glyph('external') ?> Preview this section</a>
</div>

<div class="editor">
  <div class="section-list" data-section-order>
    <ul>
      <?php foreach ($sections as $item): ?>
        <?php
        $active  = $item['key'] === $section['key'];
        $isDraft = Content::hasDraft($item);
        ?>
        <li data-id="<?= (int)$item['id'] ?>" class="<?= $active ? 'active' : '' ?> <?= (int)$item['is_published'] === 1 ? '' : 'hidden-section' ?>">
          <button type="button" class="drag" tabindex="-1" aria-hidden="true"><?= AdminUi::glyph('drag') ?></button>
          <a class="tab" href="/pages/home?section=<?= e((string)$item['key']) ?>">
            <span class="label"><?= e(Schema::title((string)$item['key'])) ?></span>
            <?php if ($isDraft): ?><span class="dot-draft" title="Unpublished draft"></span><?php endif; ?>
            <?php if ((int)$item['is_published'] !== 1): ?><span style="font-size:11px;color:var(--a-ink-faint);">hidden</span><?php endif; ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div>
    <form method="post" action="/pages/home/save" data-guard>
      <?= Csrf::field() ?>
      <input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>">

      <div class="card">
        <div class="card-head">
          <div>
            <h3><?= e(Schema::title((string)$section['key'])) ?></h3>
            <?php if (!empty($schema['description'])): ?>
              <p style="font-size:12.5px;color:var(--a-ink-faint);margin:3px 0 0;"><?= e($schema['description']) ?></p>
            <?php endif; ?>
          </div>
          <div class="spacer"></div>
          <?php if ($hasDraft): ?><span class="pill pill-draft">Draft</span><?php else: ?><span class="pill pill-live">Live</span><?php endif; ?>
        </div>
        <div class="card-body">
          <?php foreach ($schema['fields'] as $field): ?>
            <?= View::render('admin/partials/field', [
                'field' => $field,
                'value' => $content[$field['key']] ?? null,
                'name'  => 'content[' . $field['key'] . ']',
                'id'    => 'f-' . $section['key'] . '-' . $field['key'],
                'depth' => 0,
            ]) ?>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="savebar">
        <span class="state" data-dirty-state><?= $hasDraft ? 'Draft saved, not published' : 'All changes published' ?></span>
        <div class="spacer"></div>
        <button type="submit" name="action" value="draft" class="btn btn-ghost">Save draft</button>
        <button type="submit" name="action" value="publish" class="btn btn-accent">Publish</button>
      </div>
    </form>

    <div class="card" style="margin-top:18px;">
      <div class="card-head"><h3>Section options</h3></div>
      <div class="card-body">
        <div class="actions-row">
          <?php if (empty($schema['always_on'])): ?>
            <form method="post" action="/pages/home/toggle">
              <?= Csrf::field() ?>
              <input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>">
              <button type="submit" class="btn btn-ghost btn-sm">
                <?= (int)$section['is_published'] === 1 ? 'Hide this section' : 'Show this section' ?>
              </button>
            </form>
          <?php else: ?>
            <span style="font-size:12.5px;color:var(--a-ink-faint);">This section is always shown — it holds the site's navigation or contact details.</span>
          <?php endif; ?>
          <?php if ($hasDraft): ?>
            <form method="post" action="/pages/home/discard" data-confirm="Discard the draft and keep the published version?">
              <?= Csrf::field() ?>
              <input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>">
              <button type="submit" class="btn btn-ghost btn-sm">Discard draft</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card" style="margin-top:18px;">
      <div class="card-head"><h3>Revision history</h3></div>
      <?php if ($revisions): ?>
        <div class="table-wrap">
          <table class="data">
            <thead><tr><th>When</th><th>By</th><th>Note</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($revisions as $revision): ?>
              <tr>
                <td><?= e(human_date((string)$revision['created_at'])) ?></td>
                <td><?= e((string)($revision['user_name'] ?? 'System')) ?></td>
                <td style="color:var(--a-ink-faint);"><?= e((string)($revision['note'] ?? '—')) ?></td>
                <td style="text-align:right;">
                  <form method="post" action="/pages/home/revert" data-confirm="Restore this version and publish it?">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>">
                    <input type="hidden" name="revision_id" value="<?= (int)$revision['id'] ?>">
                    <button type="submit" class="btn btn-ghost btn-sm">Restore</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="empty"><p>No revisions yet — the first publish creates one.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>
