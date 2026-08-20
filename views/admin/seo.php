<?php
/** @var string $slug */
/** @var array $meta */
use Core\{Config, Csrf, Seo, View};
?>
<div class="page-head">
  <div>
    <h2>SEO</h2>
    <p>Titles, descriptions and social share previews. The sitemap and structured data update themselves.</p>
  </div>
</div>

<div class="card">
  <div class="card-body tight">
    <div class="actions-row">
      <?php foreach (Seo::PAGES as $pageSlug => $label): ?>
        <a class="btn <?= $slug === $pageSlug ? 'btn-primary' : 'btn-ghost' ?> btn-sm" href="/seo?page=<?= e($pageSlug) ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<form method="post" action="/seo" data-guard>
  <?= Csrf::field() ?>
  <input type="hidden" name="page_slug" value="<?= e($slug) ?>">
  <div class="card">
    <div class="card-head"><h3><?= e(Seo::PAGES[$slug] ?? $slug) ?></h3></div>
    <div class="card-body">
      <div class="field">
        <label class="field-label" for="title">Page title</label>
        <p class="hint">Google shows roughly the first 60 characters.</p>
        <input type="text" id="title" name="title" value="<?= e((string)$meta['title']) ?>" data-max="60">
      </div>
      <div class="field">
        <label class="field-label" for="description">Meta description</label>
        <p class="hint">Aim for 150–160 characters.</p>
        <textarea id="description" name="description" rows="3" data-max="160"><?= e((string)$meta['description']) ?></textarea>
      </div>
      <div class="field">
        <label class="field-label" for="canonical">Canonical URL</label>
        <p class="hint">Leave blank to use <?= e(Seo::siteUrl($slug === 'home' ? '/' : '/' . $slug)) ?>.</p>
        <input type="text" id="canonical" name="canonical" value="<?= e((string)$meta['canonical']) ?>">
      </div>
      <div class="grid cols-2">
        <div class="field">
          <label class="field-label" for="og_title">Social share title</label>
          <input type="text" id="og_title" name="og_title" value="<?= e((string)$meta['og_title']) ?>">
        </div>
        <div class="field">
          <label class="field-label" for="twitter_card">Twitter card</label>
          <select id="twitter_card" name="twitter_card">
            <option value="summary_large_image" <?= $meta['twitter_card'] === 'summary_large_image' ? 'selected' : '' ?>>Large image</option>
            <option value="summary" <?= $meta['twitter_card'] === 'summary' ? 'selected' : '' ?>>Summary</option>
          </select>
        </div>
      </div>
      <div class="field">
        <label class="field-label" for="og_description">Social share description</label>
        <textarea id="og_description" name="og_description" rows="2"><?= e((string)$meta['og_description']) ?></textarea>
      </div>
      <?= View::render('admin/partials/field', [
          'field' => ['key' => 'og_image', 'type' => 'image', 'label' => 'Social share image', 'hint' => 'Shown when the link is pasted into WhatsApp, Facebook or LinkedIn. 1200×630 or larger.'],
          'value' => $meta['og_image_media_id'],
          'name'  => 'og_image_media_id',
          'id'    => 'og_image',
          'depth' => 0,
      ]) ?>
      <div class="field">
        <input type="hidden" name="noindex" value="0">
        <label class="switch">
          <input type="checkbox" name="noindex" value="1" <?= (int)$meta['noindex'] === 1 ? 'checked' : '' ?>>
          <span class="track"></span><span>Hide this page from search engines</span>
        </label>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h3>Generated files</h3></div>
    <div class="card-body">
      <div class="actions-row">
        <a class="btn btn-ghost btn-sm" href="<?= e(Seo::siteUrl('/sitemap.xml')) ?>" target="_blank" rel="noopener">View sitemap.xml</a>
        <a class="btn btn-ghost btn-sm" href="<?= e(Seo::siteUrl('/robots.txt')) ?>" target="_blank" rel="noopener">View robots.txt</a>
      </div>
      <p class="hint" style="margin-top:12px;">Structured data (Organization, Residence and the FAQ) is generated from the live content on every page load — no action needed here. The dashboard itself sends <code>noindex</code> on every response.</p>
    </div>
  </div>

  <div class="savebar">
    <span class="state" data-dirty-state>All changes published</span>
    <div class="spacer"></div>
    <button type="submit" class="btn btn-accent">Save SEO</button>
  </div>
</form>
